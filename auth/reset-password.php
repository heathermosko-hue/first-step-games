<?php
require_once 'db.php';
$token = trim($_GET['token'] ?? '');
$msg = $error = '';
$valid = false;

if ($token) {
    $db = getDB();
    $st = $db->prepare('SELECT id, name FROM teachers WHERE reset_token=? AND reset_expires > ?');
    $now = time();
    $st->bind_param('si', $token, $now); $st->execute();
    $row = $st->get_result()->fetch_assoc();
    if ($row) $valid = true;
}

if ($valid && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $pass  = $_POST['password'] ?? '';
    $pass2 = $_POST['password2'] ?? '';
    if (strlen($pass) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($pass !== $pass2) {
        $error = 'Passwords do not match.';
    } else {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $st2  = $db->prepare('UPDATE teachers SET password=?, reset_token=NULL, reset_expires=NULL WHERE id=?');
        $st2->bind_param('si', $hash, $row['id']);
        $st2->execute();
        $msg   = 'Password updated! You can now sign in.';
        $valid = false;
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Reset Password — First Step Reading</title>
<style>
  *{box-sizing:border-box;margin:0;padding:0}
  body{
    font-family:'Comic Sans MS','Chalkboard SE','Comic Neue';
    background:linear-gradient(160deg,#5BB8F5 0%,#87CEEB 50%,#B8E4F9 100%);
    min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:1.5rem;
  }
  body::before{content:'☁  ☁  ☁  ☁  ☁  ☁  ☁  ☁';position:fixed;top:12px;left:0;right:0;font-size:2rem;opacity:.25;white-space:nowrap;overflow:hidden;letter-spacing:80px;pointer-events:none;}
  .card{background:white;border-radius:28px;padding:2.4rem 2.2rem 2rem;width:100%;max-width:420px;box-shadow:0 16px 60px rgba(0,0,0,.18);position:relative;z-index:1;}
  .logo{text-align:center;margin-bottom:1.6rem}
  .logo-icon{font-size:3rem;display:block;margin-bottom:.3rem}
  .logo h1{font-size:1.6rem;color:#2C3E50}
  label{display:block;font-weight:700;color:#6B48FF;margin-bottom:.35rem;font-size:.9rem}
  input{width:100%;border:2.5px solid #e0e0e0;border-radius:14px;padding:.75rem 1rem;font-size:1rem;font-family:'Comic Sans MS','Chalkboard SE','Comic Neue';transition:border-color .2s;margin-bottom:1rem;}
  input:focus{outline:none;border-color:#6B48FF;box-shadow:0 0 0 3px rgba(107,72,255,.15)}
  .btn{width:100%;background:linear-gradient(135deg,#6BCB77,#27AE60);color:white;border:none;border-radius:14px;padding:.9rem;font-size:1.05rem;font-weight:700;cursor:pointer;font-family:'Comic Sans MS','Chalkboard SE','Comic Neue';box-shadow:0 4px 18px rgba(39,174,96,.35);transition:transform .18s;}
  .btn:hover{transform:translateY(-2px)}
  .error{background:#fff0f0;border:2px solid #ffb3b3;color:#c0392b;border-radius:12px;padding:.7rem 1rem;margin-bottom:1rem;font-size:.9rem;font-weight:600;}
  .success{background:#f0fff4;border:2px solid #9de0a8;color:#1a7a3a;border-radius:12px;padding:.7rem 1rem;margin-bottom:1rem;font-size:.9rem;font-weight:600;}
  .footer{text-align:center;margin-top:1.2rem;font-size:.9rem;color:#888}
  .footer a{color:#6B48FF;text-decoration:none;font-weight:700}
  .back-link{margin-top:1.4rem;color:rgba(255,255,255,.9);text-decoration:none;font-size:.92rem;font-weight:700;position:relative;z-index:1;}
</style>
</head>
<body>
<div class="card">
  <div class="logo">
    <span class="logo-icon">🔐</span>
    <h1>Reset Password</h1>
  </div>
  <?php if ($msg): ?>
    <div class="success">✅ <?= htmlspecialchars($msg) ?></div>
    <div class="footer"><a href="teacher-login.php">Sign in →</a></div>
  <?php elseif (!$token || !$valid): ?>
    <div class="error">⚠️ This reset link is invalid or has expired. <a href="forgot-password.php">Request a new one →</a></div>
  <?php else: ?>
    <?php if ($error): ?><div class="error">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="post">
      <label>🔑 New password</label>
      <input type="password" name="password" required minlength="6">
      <label>🔑 Confirm new password</label>
      <input type="password" name="password2" required minlength="6">
      <button class="btn" type="submit">Save New Password →</button>
    </form>
  <?php endif; ?>
</div>
<a href="../hub.html" class="back-link">← Back to Games</a>
</body>
</html>
