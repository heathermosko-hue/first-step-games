<?php
require_once 'db.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!empty($_SESSION['teacher_id'])) { header('Location: teacher-dashboard.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $db    = getDB();
    $st    = $db->prepare('SELECT id, name, password FROM teachers WHERE email=?');
    $st->bind_param('s', $email); $st->execute();
    $row = $st->get_result()->fetch_assoc();
    if ($row && password_verify($pass, $row['password'])) {
        $_SESSION['teacher_id']   = $row['id'];
        $_SESSION['teacher_name'] = $row['name'];
        header('Location: teacher-dashboard.php'); exit;
    }
    $error = 'Incorrect email or password.';
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
  .btn{
    width:100%;background:linear-gradient(135deg,#6BCB77,#27AE60);color:white;
    border:none;border-radius:14px;padding:.9rem;font-size:1.1rem;font-weight:700;
    cursor:pointer;font-family:'Comic Sans MS','Chalkboard SE','Comic Neue';
    box-shadow:0 4px 18px rgba(39,174,96,.35);transition:transform .18s,box-shadow .18s;
  }
  .btn:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(39,174,96,.45)}
  .error{
    background:#fff0f0;border:2px solid #ffb3b3;color:#c0392b;
    border-radius:12px;padding:.7rem 1rem;margin-bottom:1rem;font-size:.9rem;font-weight:600;
  }
  .footer{text-align:center;margin-top:1.3rem;font-size:.92rem;color:#888}
  .footer a{color:#6B48FF;text-decoration:none;font-weight:700}
  .footer a:hover{text-decoration:underline}
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
  <?php if ($error): ?>
    <div class="error">⚠️ <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <form method="post">
    <label>📧 Email address</label>
    <input type="email" name="email" required autofocus value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
    <label>🔑 Password</label>
    <input type="password" name="password" required>
    <button class="btn" type="submit">🍎 Sign In →</button>
  </form>
  <div class="footer">
    New teacher? <a href="teacher-register.php">Create a free account! 🎉</a>
  </div>
</div>
<a href="../hub.html" class="back-link">← Back to Games</a>
</body>
</html>
