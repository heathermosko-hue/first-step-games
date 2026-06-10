/**
 * tts.js — Google Cloud Text-to-Speech interceptor v10
 * Patches window.speechSynthesis.speak() globally → Google Journey-F voice.
 *
 * v10 fixes two issues that caused games to appear silent:
 *
 * Fix 1 — Desktop Chrome (autoplay blocked):
 *   Chrome blocks speechSynthesis.speak() before first user gesture. Games that
 *   auto-speak on load left _speaking=true for 3-4 s while the safety timer
 *   waited for an onend that would never come. Added a 600 ms onstart watchdog:
 *   if browser TTS doesn't actually start within 600 ms we fire the end callback
 *   immediately so the queue unblocks on the first click.
 *
 * Fix 2 — iOS Safari (cancel doesn't fire onend):
 *   iOS allows pre-gesture TTS (onstart fires, quickTimer clears), but when
 *   site-nav.js calls speechSynthesis.cancel() on the first touchend the cancel
 *   does NOT reliably fire onend on iOS. This left _speaking=true for the entire
 *   welcome-message duration (~4 s). Fix: store a reference to the current
 *   safeOrigSpeak fireEnd() and call it immediately inside onGesture on the very
 *   first touch, so the queue is unblocked instantly — before the button handler
 *   even fires.
 *
 * v9: Switched from <audio>.play() to AudioContext + decodeAudioData.
 */
(function () {
  var API_KEY = 'AIzaSyDPCnZfgZmSYPonViX2S4rSfH8-FGFbhTo';
  var VOICE   = 'en-US-Journey-F';
  var LANG    = 'en-US';

  var memCache          = {};
  var _gestureOccurred  = false;
  var _currentUtterance = null;
  var _watchdog         = null;
  var _activeSource     = null;   /* current AudioBufferSourceNode */
  var _ctx              = null;   /* AudioContext (created on first gesture) */

  /* Stored by safeOrigSpeak so onGesture can fire it immediately on first touch */
  var _preGestureEnd    = null;

  /* ── AudioContext helpers ─────────────────────────────── */
  function getCtx() {
    if (!_ctx) {
      try { _ctx = new (window.AudioContext || window.webkitAudioContext)(); } catch(e) {}
    }
    return _ctx;
  }

  /* ── Track gesture & unlock AudioContext ─────────────── */
  function onGesture() {
    if (!_gestureOccurred) {
      /* First gesture: immediately end any pre-gesture safeOrigSpeak so the
         game speech queue unblocks at once (before the button handler fires).
         This fixes iOS where cancel() doesn't reliably trigger onend, and
         also fires before Chrome's 600 ms watchdog can kick in. */
      var fn = _preGestureEnd;
      if (fn) {
        _preGestureEnd = null;
        try { origCancel(); } catch(e) {}  /* stop browser TTS that may still be playing */
        try { fn(); } catch(e) {}          /* fire _speaking=false immediately */
      }
    }
    _gestureOccurred = true;
    var ctx = getCtx();
    if (ctx && ctx.state === 'suspended') {
      ctx.resume().catch(function () {});
    }
  }
  /* capture:true so onGesture fires BEFORE any element click/touch handler —
     guarantees _gestureOccurred is true and queue is unblocked when game code
     calls speak() on the very first interaction */
  document.addEventListener('touchstart', onGesture, { capture: true, passive: true });
  document.addEventListener('click',      onGesture, { capture: true, passive: true });

  /* ── Watchdog ─────────────────────────────────────────── */
  function _clearWatchdog() {
    if (_watchdog) { clearTimeout(_watchdog); _watchdog = null; }
  }
  function _startWatchdog(utt, ms) {
    _clearWatchdog();
    _watchdog = setTimeout(function () {
      if (_currentUtterance === utt) {
        _stopSource();
        _currentUtterance = null;
        safeOrigSpeak(utt);
      }
    }, ms);
  }

  /* ── Stop any currently playing AudioBufferSource ─────── */
  function _stopSource() {
    if (_activeSource) {
      try { _activeSource.onended = null; _activeSource.stop(); } catch(e) {}
      _activeSource = null;
    }
  }

  /* ── Play decoded audio via AudioContext ──────────────── */
  function playBuffer(arrayBuffer, utt) {
    var ctx = getCtx();
    if (!ctx) { safeOrigSpeak(utt); return; }

    ctx.decodeAudioData(arrayBuffer,
      function (audioBuffer) {
        _stopSource();
        var src = ctx.createBufferSource();
        src.buffer = audioBuffer;
        src.connect(ctx.destination);
        _activeSource = src;

        src.onended = function () {
          if (_activeSource === src) { _activeSource = null; }
          _clearWatchdog();
          var u = _currentUtterance;
          _currentUtterance = null;
          if (u && u.onend) { try { u.onend({ type: 'end' }); } catch(e) {} }
        };

        src.start(0);
      },
      function (err) {
        /* decodeAudioData failed — fall back to browser TTS */
        _clearWatchdog();
        _currentUtterance = null;
        safeOrigSpeak(utt);
      }
    );
  }

  /* ── Base64 → ArrayBuffer ─────────────────────────────── */
  function b64ToBuffer(b64) {
    var bin = atob(b64);
    var buf = new ArrayBuffer(bin.length);
    var view = new Uint8Array(buf);
    for (var i = 0; i < bin.length; i++) { view[i] = bin.charCodeAt(i); }
    return buf;
  }

  /* ── Google TTS fetch ─────────────────────────────────── */
  function fetchAudio(text, cb) {
    var key = text.trim().toLowerCase();
    if (memCache[key]) { cb(null, memCache[key]); return; }
    try {
      var stored = sessionStorage.getItem('gtts:' + key);
      if (stored) { memCache[key] = stored; cb(null, stored); return; }
    } catch(e) {}

    fetch('https://texttospeech.googleapis.com/v1/text:synthesize?key=' + API_KEY, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        input: { text: text.trim() },
        voice: { languageCode: LANG, name: VOICE },
        audioConfig: { audioEncoding: 'MP3', speakingRate: 0.9, pitch: 0 }
      })
    })
    .then(function (r) { return r.json(); })
    .then(function (d) {
      if (!d.audioContent) { cb('no audio'); return; }
      memCache[key] = d.audioContent;
      try { sessionStorage.setItem('gtts:' + key, d.audioContent); } catch(e) {}
      cb(null, d.audioContent);
    })
    .catch(function (e) { cb(e); });
  }

  /* ── Patch speechSynthesis ────────────────────────────── */
  var origSpeak  = window.speechSynthesis.speak.bind(window.speechSynthesis);
  var origCancel = window.speechSynthesis.cancel.bind(window.speechSynthesis);

  /**
   * safeOrigSpeak — call browser TTS with a guaranteed onend.
   *
   * Three layers of protection so the game speech queue always unblocks:
   *   1. onGesture fires fireEnd() immediately on first touch (fixes iOS cancel-no-onend)
   *   2. 600 ms onstart watchdog fires if browser TTS never started (fixes Chrome block)
   *   3. Long absolute safety timer fires if nothing else did
   */
  function safeOrigSpeak(utterance) {
    var origEnd   = utterance.onend;
    var origStart = utterance.onstart;
    var fired     = false;
    var started   = false;

    function fireEnd() {
      _preGestureEnd = null;    /* clear the onGesture hook */
      if (fired) return; fired = true;
      clearTimeout(quickTimer);
      clearTimeout(safeTimer);
      if (origEnd) { try { origEnd({ type: 'end' }); } catch(e) {} }
    }

    /* Layer 1: store for immediate call by onGesture on first touch */
    _preGestureEnd = fireEnd;

    /* Layer 2: if browser TTS didn't start in 600 ms → it was blocked → unblock now */
    var quickTimer = setTimeout(function () {
      if (!started) { fireEnd(); }
    }, 600);

    /* Layer 3: absolute safety net for long sentences that ARE playing */
    var safeTimer = setTimeout(fireEnd, Math.max(4000, (utterance.text || '').length * 80));

    utterance.onstart = function (e) {
      started = true;
      clearTimeout(quickTimer);   /* TTS started → disable the quick-fail */
      if (origStart) { try { origStart(e); } catch(e2) {} }
    };
    utterance.onend = function (e) { fireEnd(); };

    try { origSpeak(utterance); } catch(e) { fireEnd(); }
  }

  /* .speaking property */
  try {
    Object.defineProperty(window.speechSynthesis, 'speaking', {
      get: function () {
        return _currentUtterance !== null || _activeSource !== null;
      },
      configurable: true
    });
  } catch(e) {}

  window.speechSynthesis.speak = function (utterance) {
    var text = (utterance && utterance.text) ? utterance.text : '';
    if (!text.trim()) return;

    /* Before first gesture: browser TTS. Three-layer protection in safeOrigSpeak
       ensures the game queue always unblocks promptly. */
    if (!_gestureOccurred) {
      safeOrigSpeak(utterance);
      return;
    }

    /* After first gesture: Google Journey-F via AudioContext */
    _clearWatchdog();
    _stopSource();
    _currentUtterance = null;
    origCancel();
    try { utterance.voice = null; } catch(e) {}

    fetchAudio(text, function (err, b64) {
      if (err || !b64) { safeOrigSpeak(utterance); return; }

      _currentUtterance = utterance;

      /* Watchdog: if decodeAudioData / source.start() hang or onended never fires,
         fall back to browser TTS after a safe timeout */
      _startWatchdog(utterance, Math.max(3000, text.length * 120));

      if (utterance.onstart) { try { utterance.onstart({ type: 'start' }); } catch(e) {} }

      try {
        playBuffer(b64ToBuffer(b64), utterance);
      } catch(e) {
        _clearWatchdog();
        _currentUtterance = null;
        safeOrigSpeak(utterance);
      }
    });
  };

  window.speechSynthesis.cancel = function () {
    _clearWatchdog();
    _stopSource();
    _currentUtterance = null;
    origCancel();
  };

})();
