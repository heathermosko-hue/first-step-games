/**
 * site-nav.js — Global sticky top bar for every page
 * Adds: [📚 First Step Reading logo] .... [☰ Menu] [🏠 Home]
 * Menu  → hub.html (games menu)
 * Home  → https://www.firststepreading.com (main website / shop)
 */
(function () {
  try {

  var HOME_URL = 'https://www.firststepreading.com';
  var MENU_URL = 'hub.html';
  var NAV_H    = 48; // px (desktop)
  var NAV_H_M  = 28; // px (mobile ≤600px)

  /* ── FONT — injected here so it works even if fonts.css is slow/blocked ── */
  var fontCss = document.createElement('style');
  fontCss.textContent = "* { font-family: 'Comic Sans MS', 'Chalkboard SE', 'Comic Neue' !important; }";
  document.head.appendChild(fontCss);

  /* ── STYLES ─────────────────────────────────────────────── */
  var css = [
    '#fsr-top-nav{',
      'position:sticky;top:0;z-index:10000;',
      'background:linear-gradient(135deg,#1A1A4E,#2C2C7C);',
      'display:flex;align-items:center;justify-content:space-between;',
      'padding:0 1rem;height:' + NAV_H + 'px;',
      'box-shadow:0 2px 12px rgba(0,0,0,.35);',
      'flex-shrink:0;',
    '}',
    '#fsr-nav-logo{',
      'display:flex;align-items:center;gap:.4rem;',
      "font-family: 'Comic Sans MS', 'Comic Neue';font-size:.98rem;",
      'color:#fff;text-decoration:none;white-space:nowrap;',
      'transition:opacity .2s;',
    '}',
    '#fsr-nav-logo:hover{opacity:.82;}',
    '.fsr-logo-text{display:inline;}',
    '@media(max-width:380px){.fsr-logo-text{display:none;}}',
    '#fsr-nav-right{display:flex;gap:.4rem;align-items:center;}',
    '.fsr-nav-btn{',
      'display:inline-flex;align-items:center;gap:.3rem;',
      'padding:5px 13px;border-radius:999px;',
      "font-family: 'Comic Sans MS', 'Comic Neue';font-size:.85rem;",
      'color:#fff;text-decoration:none;white-space:nowrap;',
      'transition:transform .15s,background .2s;',
    '}',
    '.fsr-nav-btn:hover{transform:translateY(-1px);}',
    '.fsr-nav-menu{background:rgba(255,255,255,.18);}',
    '.fsr-nav-menu:hover{background:rgba(255,255,255,.30);}',
    '.fsr-nav-menu-icon{display:inline-block;margin-right:5px;font-size:1.1rem;vertical-align:middle;line-height:1;}',
    '.fsr-nav-home{',
      'background:linear-gradient(135deg,#FF6B6B,#FF3B8A);',
      'box-shadow:0 3px 10px rgba(255,59,138,.4);',
    '}',
    '.fsr-nav-home:hover{background:linear-gradient(135deg,#ff8080,#ff5599);}',
    /* ── MOBILE: shrink the top bar and all game page headers ── */
    '@media(max-width:600px){',
      /* ── global nav bar ── */
      '#fsr-top-nav{height:28px!important;padding:0 .4rem;}',
      '#fsr-nav-logo{font-size:.72rem;gap:.2rem;}',
      '.fsr-nav-btn{padding:1px 7px;font-size:.65rem;gap:.12rem;}',
      '.fsr-nav-menu-icon{font-size:.8rem;margin-right:2px;}',
      /* ── game page <header>: FORCE single row, tightest possible ── */
      'body>header{padding:.1rem .4rem!important;gap:.22rem!important;flex-wrap:nowrap!important;min-height:0!important;}',
      'body>header h1{font-size:clamp(.72rem,3.8vw,.86rem)!important;line-height:1.1!important;flex:1!important;min-width:0!important;overflow:hidden!important;text-overflow:ellipsis!important;white-space:nowrap!important;}',
      'body>header .back-link,body>header a.back-link{padding:.1rem .32rem!important;font-size:.62rem!important;white-space:nowrap!important;flex-shrink:0!important;}',
      'body>header button,body>header a.btn-header,body>header .btn-header{padding:.1rem .32rem!important;font-size:.62rem!important;white-space:nowrap!important;flex-shrink:0!important;}',
      'body>header .btn-voice{padding:.1rem .32rem!important;font-size:.62rem!important;white-space:nowrap!important;flex-shrink:0!important;}',
      'body>header .btn-mute,body>header #muteBtn,body>header #btnMute{padding:.1rem .3rem!important;font-size:.82rem!important;white-space:nowrap!important;flex-shrink:0!important;}',
      'body>header .header-btns,body>header .controls,body>header .header-right{gap:.18rem!important;flex-wrap:nowrap!important;flex-shrink:0!important;}',
      /* back-btn variant used in some games */
      'body>header .back-btn{padding:.1rem .32rem!important;font-size:.62rem!important;white-space:nowrap!important;flex-shrink:0!important;}',
      /* ── settings / category / level bars: single scrollable row, never wrap ── */
      '.settings-bar,.cat-bar,.level-bar,.mode-bar,.difficulty-bar{padding:.06rem .4rem!important;gap:.3rem!important;overflow-x:auto!important;flex-wrap:nowrap!important;-webkit-overflow-scrolling:touch!important;align-items:center!important;}',
      /* inner button groups must also be nowrap so they don't stack vertically */
      '.cat-btns,.settings-group,.mode-btns,.btn-group{display:flex!important;flex-wrap:nowrap!important;gap:.3rem!important;align-items:center!important;flex-shrink:0!important;}',
      '.lvl-btn,.btn-level,.btn-mode,.btn-cat,.cat-btn{font-size:clamp(.62rem,2vw,.76rem)!important;padding:2px 7px!important;white-space:nowrap!important;flex-shrink:0!important;}',
      /* ── settings label: shrink/hide on very narrow screens ── */
      '.settings-label,.settings-title{font-size:.6rem!important;white-space:nowrap!important;flex-shrink:0!important;}',
    '}',
  ].join('');

  var style = document.createElement('style');
  style.textContent = css;
  document.head.appendChild(style);

  /* ── HTML ───────────────────────────────────────────────── */
  var nav = document.createElement('nav');
  nav.id = 'fsr-top-nav';
  nav.setAttribute('aria-label', 'Site navigation');
  nav.innerHTML =
    '<a href="' + HOME_URL + '" id="fsr-nav-logo">' +
      '📚 <span class="fsr-logo-text">First Step Reading</span>' +
    '</a>' +
    '<div id="fsr-nav-right">' +
      '<a href="' + MENU_URL + '" class="fsr-nav-btn fsr-nav-menu"><span class="fsr-nav-menu-icon">&#9776;</span>Menu</a>' +
      '<a href="' + HOME_URL + '" class="fsr-nav-btn fsr-nav-home">🏠 Home</a>' +
    '</div>';

  document.body.insertBefore(nav, document.body.firstChild);

  /* ── Shift hub's access-bar so it sticks BELOW our nav ── */
  var ab = document.getElementById('access-bar');
  if (ab) ab.style.top = (window.innerWidth <= 600 ? NAV_H_M : NAV_H) + 'px';

  } catch(e) { /* fail silently — nav is non-critical */ }
})();
