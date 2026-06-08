// ── Student Session Bar ───────────────────────────────────
// Included in every game page. Shows who's logged in and
// saves progress when a game round ends.
(function () {
  const API = 'auth/api.php';

  // ── Inject CSS ──────────────────────────────────────────
  const style = document.createElement('style');
  style.textContent = `
    #fsr-bar{position:fixed;top:0;left:0;right:0;z-index:9999;background:linear-gradient(90deg,#667eea,#764ba2);color:white;display:flex;align-items:center;justify-content:space-between;padding:.35rem 1rem;font-family:'Segoe UI',sans-serif;font-size:.82rem;box-shadow:0 2px 8px rgba(0,0,0,.25)}
    #fsr-bar .fsr-left{display:flex;align-items:center;gap:.5rem}
    #fsr-bar .fsr-animal{font-size:1.3rem;line-height:1}
    #fsr-bar .fsr-name{font-weight:700}
    #fsr-bar .fsr-class{opacity:.75;margin-left:.2rem}
    #fsr-bar a{color:rgba(255,255,255,.8);text-decoration:none;border:1px solid rgba(255,255,255,.4);border-radius:20px;padding:.2rem .65rem;font-size:.78rem}
    body.fsr-bar-visible{padding-top:2.2rem!important}
  `;
  document.head.appendChild(style);

  // ── Fetch who's logged in ───────────────────────────────
  fetch(API + '?action=whoami')
    .then(r => r.json())
    .then(data => {
      if (!data.loggedIn) return; // not logged in — no bar shown

      const bar = document.createElement('div');
      bar.id = 'fsr-bar';
      bar.innerHTML = `
        <div class="fsr-left">
          <span class="fsr-animal">${data.icon}</span>
          <span class="fsr-name">${data.name}</span>
          <span class="fsr-class">— ${data.class}</span>
        </div>
        <a href="auth/student-games.php">← My Games</a>
      `;
      document.body.insertAdjacentElement('afterbegin', bar);
      document.body.classList.add('fsr-bar-visible');
    })
    .catch(() => {}); // fail silently if not on server

  // ── Public API for games to call ────────────────────────
  window.fsrSaveProgress = function (gameSlug, score) {
    fetch(API, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'save_progress', game: gameSlug, score: score || 0 })
    }).catch(() => {});
  };
})();
