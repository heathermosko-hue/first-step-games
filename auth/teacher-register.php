<?php
require_once 'db.php';
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

if (getTeacherFromCookie()) {
    echo '<script>window.location.href="teacher-dashboard.php";</script>'; exit;
}

parse_str(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_QUERY) ?? '', $_QS);

if (!empty($_QS['fsr_ajax'])) {
    header('Content-Type: application/json');
    $name  = trim(base64_decode($_QS['n'] ?? ''));
    $email = trim(base64_decode($_QS['e'] ?? ''));
    $pass  = base64_decode($_QS['p'] ?? '');
    $pass2 = base64_decode($_QS['p2'] ?? '');

    if (!$name || !$email || !$pass) {
        echo json_encode(['ok'=>false,'error'=>'Please fill in all fields.']); exit;
    }
    if ($pass !== $pass2) {
        echo json_encode(['ok'=>false,'error'=>'Passwords do not match.']); exit;
    }
    if (strlen($pass) < 6) {
        echo json_encode(['ok'=>false,'error'=>'Password must be at least 6 characters.']); exit;
    }
    $db   = getDB();
    $hash = password_hash($pass, PASSWORD_DEFAULT);
    $st   = $db->prepare('INSERT INTO teachers (name,email,password) VALUES (?,?,?)');
    $st->bind_param('sss', $name, $email, $hash);
    if ($st->execute()) {
        echo json_encode(['ok'=>true]);
    } else {
        echo json_encode(['ok'=>false,'error'=>'That email is already registered.']);
    }
    exit;
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Teacher Register — First Step Reading</title>
<style>
  *{box-sizing:border-box;margin:0;padding:0}
  body{
    font-family:'Comic Sans MS','Chalkboard SE','Comic Neue';
    background:linear-gradient(160deg,#5BB8F5 0%,#87CEEB 50%,#B8E4F9 100%);
    min-height:100vh;
    display:flex;flex-direction:column;align-items:center;
    justify-content:flex-start;
    padding:2rem 1rem 3rem;
  }
  body::before{
    content:'☁  ☁  ☁  ☁  ☁  ☁  ☁  ☁';
    position:fixed;top:12px;left:0;right:0;font-size:2rem;opacity:.25;
    white-space:nowrap;overflow:hidden;letter-spacing:80px;pointer-events:none;
  }
  .card{
    background:white;border-radius:28px;padding:2.4rem 2.2rem 2rem;
    width:100%;max-width:440px;box-shadow:0 16px 60px rgba(0,0,0,.18);
    position:relative;z-index:1;margin-top:1rem;
  }
  .logo{text-align:center;margin-bottom:1.4rem}
  .logo-icon{font-size:3rem;display:block;margin-bottom:.3rem;animation:bounce 2s ease-in-out infinite}
  @keyframes bounce{0%,100%{transform:translateY(0)}50%{transform:translateY(-6px)}}
  .logo h1{font-size:1.65rem;color:#2C3E50}
  .logo p{color:#888;font-size:.88rem;margin-top:.25rem;font-weight:600}
  .perks{display:flex;gap:.5rem;justify-content:center;flex-wrap:wrap;margin:.8rem 0 1.2rem}
  .perk{background:linear-gradient(135deg,#FFF0FF,#F0F0FF);border:2px solid #D8B4FE;border-radius:20px;padding:.3rem .8rem;font-size:.78rem;color:#6B48FF;font-weight:700}
  label{display:block;font-weight:700;color:#6B48FF;margin-bottom:.3rem;font-size:.88rem}
  input{
    width:100%;border:2.5px solid #e0e0e0;border-radius:14px;
    padding:.72rem 1rem;font-size:1rem;font-family:'Comic Sans MS','Chalkboard SE','Comic Neue';
    transition:border-color .2s,box-shadow .2s;margin-bottom:.9rem;
  }
  input:focus{outline:none;border-color:#6B48FF;box-shadow:0 0 0 3px rgba(107,72,255,.15)}
  .pw-wrap{position:relative;margin-bottom:.9rem}
  .pw-wrap input{margin-bottom:0}
  .pw-eye{position:absolute;right:.85rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;font-size:1.1rem;padding:.2rem;color:#aaa}
  .pw-eye:hover{color:#6B48FF}
  .btn-free{
    width:100%;background:linear-gradient(135deg,#6B48FF,#3A8EF6);color:white;
    border:none;border-radius:14px;padding:.9rem;font-size:1.05rem;font-weight:700;
    cursor:pointer;font-family:'Comic Sans MS','Chalkboard SE','Comic Neue';
    box-shadow:0 4px 18px rgba(107,72,255,.35);transition:transform .18s,box-shadow .18s;
    display:block;margin-bottom:.75rem;
  }
  .btn-free:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(107,72,255,.45)}
  .btn-free:disabled{opacity:.6;cursor:not-allowed;transform:none}
  .btn-premium{
    width:100%;background:linear-gradient(135deg,#F59E0B,#EF4444);color:white;
    border:none;border-radius:14px;padding:.9rem;font-size:1.05rem;font-weight:700;
    cursor:pointer;font-family:'Comic Sans MS','Chalkboard SE','Comic Neue';
    box-shadow:0 4px 18px rgba(239,68,68,.35);transition:transform .18s,box-shadow .18s;
    text-decoration:none;display:block;text-align:center;
  }
  .btn-premium:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(239,68,68,.45)}
  .divider{display:flex;align-items:center;gap:.6rem;margin:.2rem 0 .75rem;color:#bbb;font-size:.82rem}
  .divider::before,.divider::after{content:'';flex:1;height:1px;background:#e8e8e8}
  .premium-note{font-size:.78rem;color:#888;text-align:center;margin-top:.5rem;line-height:1.4}
  .premium-note strong{color:#EF4444}
  .error{background:#fff0f0;border:2px solid #ffb3b3;color:#c0392b;border-radius:12px;padding:.7rem 1rem;margin-bottom:1rem;font-size:.9rem;font-weight:600}
  .success{background:#f0fff4;border:2px solid #9de0a8;color:#1a7a3a;border-radius:12px;padding:.7rem 1rem;margin-bottom:1rem;font-size:.9rem;font-weight:600}
  .success a{color:#27ae60;font-weight:700}
  .footer{text-align:center;margin-top:1.2rem;font-size:.92rem;color:#888}
  .footer a{color:#6B48FF;text-decoration:none;font-weight:700}
  .back-link{margin-top:1.4rem;color:rgba(255,255,255,.9);text-decoration:none;font-size:.92rem;font-weight:700;position:relative;z-index:1}
  .back-link:hover{color:white}
</style>
</head>
<body>
<div class="card">
  <div class="logo">
    <span class="logo-icon">🎉</span>
    <h1>Create Teacher Account</h1>
    <p>First Step Reading Games</p>
  </div>
  <div class="perks">
    <span class="perk">🟢 Free Mon–Fri 9–4</span>
    <span class="perk">📷 QR Codes</span>
    <span class="perk">🐶 Picture Logins</span>
    <span class="perk">📅 1 Year Data</span>
  </div>

  <div id="error-box" style="display:none" class="error"></div>
  <div id="success-box" style="display:none" class="success"></div>

  <form id="reg-form">
    <label>👤 Your name</label>
    <input type="text" id="fname" required placeholder="First name">
    <label>📧 Email address</label>
    <input type="email" id="email" required>
    <label>🔑 Password</label>
    <div class="pw-wrap">
      <input type="password" id="pw" required placeholder="At least 6 characters">
      <button type="button" class="pw-eye" onclick="var i=document.getElementById('pw');i.type=i.type==='password'?'text':'password';this.textContent=i.type==='password'?'👁️':'🙈'">👁️</button>
    </div>
    <label>🔑 Confirm password</label>
    <input type="password" id="pw2" required placeholder="Same password again">

    <button class="btn-free" id="submit-btn" type="submit">🎉 Create My Free Account →</button>

    <div class="divider">or purchase unlimited access</div>

    <a class="btn-premium" href="https://www.firststepreading.com/checkout/?add-to-cart=28201" target="_blank">
      ⭐ Teacher License — $99
    </a>
    <div class="premium-note" style="margin-bottom:.7rem">1 teacher · 365 days · unlimited access · any time</div>

    <a class="btn-premium" href="https://www.firststepreading.com/checkout/?add-to-cart=28202" target="_blank" style="background:linear-gradient(135deg,#1565C0,#3A8EF6)">
      🏫 School License — $499
    </a>
    <div class="premium-note">Whole school · 365 days · unlimited access · any time</div>
  </form>

  <div class="footer">Already have an account? <a href="teacher-login.php">Sign in →</a></div>
</div>
<a href="../hub.html" class="back-link">← Back to Games</a>

<script>
document.getElementById('reg-form').addEventListener('submit', function(e) {
  e.preventDefault();
  var btn = document.getElementById('submit-btn');
  var errBox = document.getElementById('error-box');
  var okBox  = document.getElementById('success-box');
  btn.disabled = true; btn.textContent = 'Creating account...';
  errBox.style.display = 'none'; okBox.style.display = 'none';

  var url = 'teacher-register.php?fsr_ajax=1'
    + '&n='  + encodeURIComponent(btoa(document.getElementById('fname').value))
    + '&e='  + encodeURIComponent(btoa(document.getElementById('email').value))
    + '&p='  + encodeURIComponent(btoa(document.getElementById('pw').value))
    + '&p2=' + encodeURIComponent(btoa(document.getElementById('pw2').value));

  fetch(url)
    .then(function(r){ return r.text().then(function(t){ try{return JSON.parse(t);}catch(e){throw new Error('Not JSON: '+t.substring(0,200));} }); })
    .then(function(d) {
      if (d.ok) {
        document.getElementById('reg-form').style.display = 'none';
        okBox.innerHTML = '🎉 Account created! <a href="teacher-login.php">Sign in now →</a>';
        okBox.style.display = '';
      } else {
        errBox.textContent = '⚠️ ' + d.error;
        errBox.style.display = '';
        btn.disabled = false; btn.textContent = '🎉 Create My Free Account →';
      }
    })
    .catch(function(err) {
      errBox.textContent = '⚠️ ' + err;
      errBox.style.display = '';
      btn.disabled = false; btn.textContent = '🎉 Create My Free Account →';
    });
});
</script>
</body>
</html>
