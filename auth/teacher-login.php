<?php
require_once 'db.php';

// WordPress clears $_GET and QUERY_STRING — parse from REQUEST_URI instead
parse_str(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_QUERY) ?? '', $_QS);
if (!empty($_QS['fsr_ajax'])) {
    header('Content-Type: application/json');
    $email = trim(base64_decode($_QS['e'] ?? ''));
    $pass  = base64_decode($_QS['p'] ?? '');
    if (!$email || !$pass) { echo json_encode(['ok'=>false,'error'=>'Missing fields.']); exit; }
    $db = getDB();
    $st = $db->prepare('SELECT id, name, password, premium_until FROM teachers WHERE email=?');
    $st->bind_param('s', $email); $st->execute();
    $row = $st->get_result()->fetch_assoc();
    if ($row && password_verify($pass, $row['password'])) {
        $isAdmin   = (strtolower($email) === 'heather.admin@firststepreading.com');
        $isPremium = !empty($row['premium_until']) && strtotime($row['premium_until']) > time();
        if (!$isAdmin && !$isPremium) {
            // Free accounts: Mon–Fri 9 am–4 pm Eastern only
            date_default_timezone_set('America/Toronto');
            $dow  = (int)date('N'); // 1=Mon … 7=Sun
            $hour = (int)date('G'); // 0–23
            if ($dow > 5 || $hour < 9 || $hour >= 16) {
                echo json_encode(['ok'=>false,'error'=>'Sorry, you have a free account. Access is available Monday–Friday, 9 am–4 pm. Upgrade for unlimited access!']);
                exit;
            }
        }
        echo json_encode(['ok'=>true,'cookie'=>teacherCookieValue($row['id'], $row['name'])]);
    } else {
        echo json_encode(['ok'=>false,'error'=>'Incorrect email or password.']);
    }
    exit;
}

// Already logged in?
if (getTeacherFromCookie()) {
    echo '<script>window.location.href="teacher-dashboard.php";</script>'; exit;
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Teacher Login — First Step Reading</title>
<style>
  *{box-sizing:border-box;margin:0;padding:0}
  body{
    font-family:'Comic Sans MS','Chalkboard SE','Comic Neue';
    background:linear-gradient(160deg,#5BB8F5 0%,#87CEEB 50%,#B8E4F9 100%);
    min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;
    padding:1.5rem;
  }
  body::before{
    content:'☁  ☁  ☁  ☁  ☁  ☁  ☁  ☁';
    position:fixed;top:12px;left:0;right:0;font-size:2rem;opacity:.25;
    white-space:nowrap;overflow:hidden;letter-spacing:80px;pointer-events:none;
  }
  .card{
    background:white;border-radius:28px;padding:2.4rem 2.2rem 2rem;
    width:100%;max-width:420px;
    box-shadow:0 16px 60px rgba(0,0,0,.18);
    position:relative;z-index:1;
  }
  .logo{text-align:center;margin-bottom:1.6rem}
  .logo-icon{font-size:3rem;display:block;margin-bottom:.3rem;animation:bounce 2s ease-in-out infinite}
  @keyframes bounce{0%,100%{transform:translateY(0)}50%{transform:translateY(-6px)}}
  .logo h1{font-size:1.75rem;color:#2C3E50;font-family:'Comic Sans MS','Chalkboard SE','Comic Neue'}
  .logo p{color:#888;font-size:.92rem;margin-top:.25rem;font-weight:600}
  label{display:block;font-weight:700;color:#6B48FF;margin-bottom:.35rem;font-size:.9rem}
  input{
    width:100%;border:2.5px solid #e0e0e0;border-radius:14px;
    padding:.75rem 1rem;font-size:1rem;font-family:'Comic Sans MS','Chalkboard SE','Comic Neue';
    transition:border-color .2s,box-shadow .2s;margin-bottom:1rem;
  }
  input:focus{outline:none;border-color:#6B48FF;box-shadow:0 0 0 3px rgba(107,72,255,.15)}
  .pw-wrap{position:relative;margin-bottom:1rem}
  .pw-wrap input{margin-bottom:0;padding-right:3rem}
  .pw-eye{position:absolute;right:.85rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;font-size:1.1rem;padding:.2rem;color:#aaa;line-height:1}
  .pw-eye:hover{color:#6B48FF}
  .btn{
    width:100%;background:linear-gradient(135deg,#6BCB77,#27AE60);color:white;
    border:none;border-radius:14px;padding:.9rem;font-size:1.1rem;font-weight:700;
    cursor:pointer;font-family:'Comic Sans MS','Chalkboard SE','Comic Neue';
    box-shadow:0 4px 18px rgba(39,174,96,.35);transition:transform .18s,box-shadow .18s;
  }
  .btn:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(39,174,96,.45)}
  .btn:disabled{opacity:.6;cursor:not-allowed;transform:none}
  .error{
    background:#fff0f0;border:2px solid #ffb3b3;color:#c0392b;
    border-radius:12px;padding:.7rem 1rem;margin-bottom:1rem;font-size:.9rem;font-weight:600;
  }
  .footer{text-align:center;margin-top:1.3rem;font-size:.92rem;color:#888}
  .footer a{color:#6B48FF;text-decoration:none;font-weight:700}
  .footer a:hover{text-decoration:underline}
  .divider{display:flex;align-items:center;gap:.6rem;margin:.3rem 0;color:#ccc;font-size:.8rem}
  .divider::before,.divider::after{content:'';flex:1;height:1px;background:#eee}
  .btn-purchase{
    display:block;width:100%;text-align:center;text-decoration:none;
    background:linear-gradient(135deg,#F59E0B,#EF4444);color:white;
    border-radius:14px;padding:.8rem;font-size:.98rem;font-weight:700;
    font-family:'Comic Sans MS','Chalkboard SE','Comic Neue';
    box-shadow:0 4px 16px rgba(239,68,68,.3);transition:transform .18s,box-shadow .18s;
    margin-top:.5rem;
  }
  .btn-purchase:hover{transform:translateY(-2px);box-shadow:0 8px 22px rgba(239,68,68,.4)}
  .back-link{
    margin-top:1.4rem;color:rgba(255,255,255,.9);text-decoration:none;
    font-size:.92rem;font-weight:700;position:relative;z-index:1;
  }
  .back-link:hover{color:white}
</style>
</head>
<body>
<div class="card">
  <div class="logo">
    <span class="logo-icon">🍎</span>
    <h1>Teacher Login</h1>
    <p>First Step Reading Games</p>
  </div>
  <div id="error-box" style="display:none" class="error"></div>
  <form id="login-form">
    <label>📧 Email address</label>
    <input type="email" id="email" required autofocus>
    <label>🔑 Password</label>
    <div class="pw-wrap">
      <input type="password" id="pw" required>
      <button type="button" class="pw-eye" onclick="var i=document.getElementById('pw');i.type=i.type==='password'?'text':'password';this.textContent=i.type==='password'?'👁️':'🙈'">👁️</button>
    </div>
    <button class="btn" id="submit-btn" type="submit">🍎 Sign In →</button>
  </form>
  <div class="footer">
    <a href="forgot-password.php">Forgot your password?</a>
  </div>
  <div class="footer" style="margin-top:.6rem">
    New teacher? <a href="teacher-register.php">Create a free account! 🎉</a>
  </div>
  <div class="divider">or</div>
  <a class="btn-purchase" href="https://www.firststepreading.com/checkout/?add-to-cart=28201">⭐ Purchase Teacher License — $99 →</a>
</div>
<a href="../hub.html" class="back-link">← Back to Games</a>
<script>
document.getElementById('login-form').addEventListener('submit', function(e) {
  e.preventDefault();
  var btn = document.getElementById('submit-btn');
  var errBox = document.getElementById('error-box');
  btn.disabled = true;
  btn.textContent = 'Signing in...';
  errBox.style.display = 'none';
  var params = 'email=' + encodeURIComponent(document.getElementById('email').value)
             + '&password=' + encodeURIComponent(document.getElementById('pw').value);
  var url = 'teacher-login.php?fsr_ajax=1&e=' + encodeURIComponent(btoa(document.getElementById('email').value))
          + '&p=' + encodeURIComponent(btoa(document.getElementById('pw').value));
  fetch(url)
  .then(function(r){ return r.text().then(function(t){ console.log('LOGIN RESP:',t); try{return JSON.parse(t);}catch(e){throw new Error('Not JSON: '+t.substring(0,300));} }); })
  .then(function(data) {
    if (data.ok) {
      document.cookie = 'fsr_teacher=' + data.cookie + '; max-age=2592000; path=/; SameSite=Lax';
      window.location.href = 'teacher-dashboard.php?t=' + Date.now();
    } else {
      errBox.textContent = '⚠️ ' + data.error;
      errBox.style.display = 'block';
      btn.disabled = false;
      btn.textContent = '🍎 Sign In →';
    }
  })
  .catch(function(e) {
    errBox.textContent = '⚠️ ' + e;
    errBox.style.display = 'block';
    btn.disabled = false;
    btn.textContent = '🍎 Sign In →';
  });
});
</script>
</body>
</html>
