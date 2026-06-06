/**
 * site-nav.js — Global sticky top bar for every page
 * Adds: [📚 First Step Reading logo] .... [☰ Menu] [🏠 Home]
 * Menu  → hub.html (games menu)
 * Home  → https://www.firststepreading.com (main website / shop)
 */
(function () {
  'use strict';

  var HOME_URL = 'https://www.firststepreading.com';
  var MENU_URL = 'hub.html';
  var NAV_H    = 48; // px

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
      "font-family: 'Comic Sans MS', 'Comic Neue', cursive;font-size:.98rem;",
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
      "font-family: 'Comic Sans MS', 'Comic Neue', cursive;font-size:.85rem;",
      'color:#fff;text-decoration:none;white-space:nowrap;',
      'transition:transform .15s,background .2s;',
    '}',
    '.fsr-nav-btn:hover{transform:translateY(-1px);}',
    '.fsr-nav-menu{background:rgba(255,255,255,.18);}',
    '.fsr-nav-menu:hover{background:rgba(255,255,255,.30);}',
    '.fsr-nav-menu::before{content:"";display:inline-block;width:14px;height:2px;background:currentColor;box-shadow:0 5px 0 currentColor,0 -5px 0 currentColor;margin-right:6px;vertical-align:middle;flex-shrink:0;}',
    '.fsr-nav-home{',
      'background:linear-gradient(135deg,#FF6B6B,#FF3B8A);',
      'box-shadow:0 3px 10px rgba(255,59,138,.4);',
    '}',
    '.fsr-nav-home:hover{background:linear-gradient(135deg,#ff8080,#ff5599);}',
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
      '<a href="' + MENU_URL + '" class="fsr-nav-btn fsr-nav-menu">Menu</a>' +
      '<a href="' + HOME_URL + '" class="fsr-nav-btn fsr-nav-home">🏠 Home</a>' +
    '</div>';

  document.body.insertBefore(nav, document.body.firstChild);

  /* ── Shift hub's access-bar so it sticks BELOW our nav ── */
  var ab = document.getElementById('access-bar');
  if (ab) ab.style.top = NAV_H + 'px';

})();
