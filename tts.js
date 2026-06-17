/**
 * tts.js v14 — Google Cloud TTS via server proxy
 *
 * Key changes from v13:
 * - Fix pre-gesture race: if onGesture() fires while the fetch is still
 *   in-flight, _preGesture is nulled before the callback runs, causing
 *   utterance.onend to never fire and the game speech engine to hang.
 *   Now: play the audio if playWhenReady, else fire onend immediately.
 */
(function () {
  var PROXY = 'https://www.firststepreading.com/reading-games/tts.php';

  var memCache          = {};
  var _gestureOccurred  = false;
  var _currentUtterance = null;
  var _watchdog         = null;
  var _activeSource     = null;
  var _ctx              = null;
  var _preGesture       = null;

  /* ── AudioContext ──────────────────────────────────────── */
  function getCtx() {
    if (!_ctx) {
      try { _ctx = new (window.AudioContext || window.webkitAudioContext)(); } catch(e) {}
    }
    return _ctx;
  }
  function resumeCtx() {
    var ctx = getCtx();
    if (!ctx) return Promise.resolve();
    if (ctx.state === 'suspended') return ctx.resume().catch(function () {});
    return Promise.resolve();
  }

  /* ── Watchdog ──────────────────────────────────────────── */
  function _clearWatchdog() {
    if (_watchdog) { clearTimeout(_watchdog); _watchdog = null; }
  }
  function _startWatchdog(utt, ms) {
    _clearWatchdog();
    _watchdog = setTimeout(function () {
      if (_currentUtterance === utt) {
        _stopSource();
        _currentUtterance = null;
        if (utt.onend) { try { utt.onend({ type: 'end' }); } catch(e) {} }
      }
    }, ms);
  }

  /* ── Stop current AudioBufferSource ───────────────────── */
  function _stopSource() {
    if (_activeSource) {
      try { _activeSource.onended = null; _activeSource.stop(); } catch(e) {}
      _activeSource = null;
    }
  }

  /* ── Base64 → ArrayBuffer ──────────────────────────────── */
  function b64ToBuffer(b64) {
    var bin = atob(b64), buf = new ArrayBuffer(bin.length), v = new Uint8Array(buf);
    for (var i = 0; i < bin.length; i++) v[i] = bin.charCodeAt(i);
    return buf;
  }

  /* ── Decode and play an ArrayBuffer ───────────────────── */
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
          if (_activeSource === src) _activeSource = null;
          _clearWatchdog();
          var u = _currentUtterance; _currentUtterance = null;
          if (u && u.onend) { try { u.onend({ type: 'end' }); } catch(e) {} }
        };
        src.start(0);
      },
      function () {
        _clearWatchdog(); _currentUtterance = null;
        safeOrigSpeak(utt);
      }
    );
  }

  /* ── Play Journey audio — always resumes context first ── */
  function _playJourney(utt, b64) {
    _clearWatchdog();
    _stopSource();
    origCancel();
    _currentUtterance = utt;
    _startWatchdog(utt, Math.max(3000, utt.text.length * 120));
    if (utt.onstart) { try { utt.onstart({ type: 'start' }); } catch(e) {} }
    /* Wait for AudioContext to be running before we decode/play */
    resumeCtx().then(function () {
      try { playBuffer(b64ToBuffer(b64), utt); }
      catch(e) {
        _clearWatchdog(); _currentUtterance = null;
        safeOrigSpeak(utt);
      }
    });
  }

  /* ── Gesture listener — unlocks AudioContext ───────────── */
  function onGesture() {
    if (_gestureOccurred) return;
    _gestureOccurred = true;
    var pg = _preGesture;
    _preGesture = null;
    /* Resume context, then play any pre-fetched audio */
    resumeCtx().then(function () {
      if (!pg) return;
      if (pg.b64) {
        _playJourney(pg.utt, pg.b64);
      } else if (pg.pending) {
        pg.playWhenReady = true;
      }
    });
  }
  document.addEventListener('touchstart', onGesture, { capture: true, passive: true });
  document.addEventListener('click',      onGesture, { capture: true, passive: true });

  /* ── Fetch audio from server proxy ─────────────────────── */
  function fetchAudio(text, cb) {
    var key = text.trim().toLowerCase();
    if (memCache[key]) { cb(null, memCache[key]); return; }
    try {
      var stored = sessionStorage.getItem('gtts:' + key);
      if (stored) { memCache[key] = stored; cb(null, stored); return; }
    } catch(e) {}

    var fd = new FormData();
    fd.append('text', text.trim());
    fetch(PROXY, { method: 'POST', body: fd })
      .then(function (r) { return r.json(); })
      .then(function (d) {
        if (!d.audioContent) { cb('no audio'); return; }
        var ck = text.trim().toLowerCase();
        memCache[ck] = d.audioContent;
        try { sessionStorage.setItem('gtts:' + ck, d.audioContent); } catch(e) {}
        cb(null, d.audioContent);
      })
      .catch(function (e) { cb(e); });
  }

  /* ── Fallback: browser TTS with guaranteed onend ────────── */
  var origSpeak  = window.speechSynthesis.speak.bind(window.speechSynthesis);
  var origCancel = window.speechSynthesis.cancel.bind(window.speechSynthesis);

  function safeOrigSpeak(utterance) {
    var origEnd = utterance.onend, fired = false, started = false;
    function fireEnd() {
      if (fired) return; fired = true;
      clearTimeout(quickTimer); clearTimeout(safeTimer);
      if (origEnd) { try { origEnd({ type: 'end' }); } catch(e) {} }
    }
    var quickTimer = setTimeout(function () { if (!started) fireEnd(); }, 600);
    var safeTimer  = setTimeout(fireEnd, Math.max(4000, (utterance.text || '').length * 80));
    utterance.onstart = function () { started = true; clearTimeout(quickTimer); };
    utterance.onend   = function () { fireEnd(); };
    try { origSpeak(utterance); } catch(e) { fireEnd(); }
  }

  /* .speaking property */
  try {
    Object.defineProperty(window.speechSynthesis, 'speaking', {
      get: function () { return _currentUtterance !== null || _activeSource !== null; },
      configurable: true
    });
  } catch(e) {}

  /* ── Patched speak() ────────────────────────────────────── */
  window.speechSynthesis.speak = function (utterance) {
    var text = (utterance && utterance.text) ? utterance.text : '';
    if (!text.trim()) return;
    try { utterance.voice = null; } catch(e) {}

    if (!_gestureOccurred) {
      /* Pre-gesture: pre-fetch and hold for first tap */
      var pg = { utt: utterance, b64: null, pending: true, playWhenReady: false };
      _preGesture = pg;
      fetchAudio(text, function (err, b64) {
        if (_preGesture !== pg) {
          /* onGesture() fired while fetch was in-flight and nulled _preGesture.
             Play the audio if the gesture already queued it, otherwise fire
             onend immediately so the calling speech engine doesn't hang. */
          if (!err && b64 && pg.playWhenReady) {
            _playJourney(utterance, b64);
          } else if (utterance.onend) {
            try { utterance.onend({ type: 'end' }); } catch(e) {}
          }
          return;
        }
        if (err || !b64) {
          pg.pending = false;
          if (utterance.onend) { try { utterance.onend({ type: 'end' }); } catch(e) {} }
          return;
        }
        pg.b64 = b64; pg.pending = false;
        if (pg.playWhenReady) {
          _preGesture = null;
          _playJourney(utterance, b64);
        }
      });
      return;
    }

    /* Post-gesture: fetch then play */
    _clearWatchdog(); _stopSource(); _currentUtterance = null; origCancel();
    fetchAudio(text, function (err, b64) {
      if (err || !b64) { safeOrigSpeak(utterance); return; }
      _playJourney(utterance, b64);
    });
  };

  window.speechSynthesis.cancel = function () {
    _clearWatchdog(); _stopSource(); _currentUtterance = null; _preGesture = null;
    origCancel();
  };

})();
