<?php
require_once 'db.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!empty($_SESSION['teacher_id'])) { header('Location: teacher-dashboard.php'); exit; }

$error = $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $pass2 = $_POST['password2'] ?? '';
    if (!$name || !$email || !$pass) {
        $error = 'Please fill in all fields.';
    } elseif ($pass !== $pass2) {
        $error = 'Passwords do not match.';
    } elseif (strlen($pass) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        $db   = getDB();
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $st   = $db->prepare('INSERT INTO teachers (name,email,password) VALUES (?,?,?)');
        $st->bind_param('sss', $name, $email, $hash);
        if ($st->execute()) {
            $success = 'Account created! You can now sign in.';
        } else {
            $error = 'That email is already registered.';
        }
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Teacher Register — First Step Reading</title>
<style>
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:'Comic Sans MS','Chalkboard SE','Comic Neue';background:#f0f4ff;min-height:100vh;display:flex;align-items:center;justify-content:center}
  .card{background:white;border-radius:20px;padding:2.5rem;width:100%;max-width:420px;box-shadow:0 8px 30px rgba(0,0,0,.12)}
  .logo{text-align:center;margin-bottom:1.5rem}
  .logo h1{font-size:1.6rem;color:#2c3e50}
  .logo p{color:#7f8c8d;font-size:.95rem;margin-top:.3rem}
  label{display:block;font-weight:600;color:#2c3e50;margin-bottom:.4rem;font-size:.9rem}
  input{width:100%;border:2px solid #e0e0e0;border-radius:10px;padding:.75rem 1rem;font-size:1rem;transition:border-color .2s;margin-bottom:1.1rem}
  input:focus{outline:none;border-color:#3498db}
  .btn{width:100%;background:#27ae60;color:white;border:none;border-radius:10px;padding:.85rem;font-size:1.05rem;font-weight:700;cursor:pointer;transition:background .2s}
  .btn:hover{background:#219a52}
  .error{background:#fee;border:1px solid #f99;color:#c0392b;border-radius:8px;padding:.7rem 1rem;margin-bottom:1rem;font-size:.9rem}
  .success{background:#efe;border:1px solid #9d9;color:#1a7a3a;border-radius:8px;padding:.7rem 1rem;margin-bottom:1rem;font-size:.9rem}
  .footer{text-align:center;margin-top:1.2rem;font-size:.9rem;color:#7f8c8d}
  .footer a{color:#3498db;text-decoration:none;font-weight:600}
  .icon{font-size:2.5rem;display:block;text-align:center;margin-bottom:.5rem}
</style>
</head>
<body>
<div class="card">
  <div class="logo">
    <span class="icon">🍎</span>
    <h1>Create Teacher Account</h1>
    <p>First Step Reading</p>
  </div>
  <?php if ($error): ?><div class="error">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>
  <?php if ($success): ?><div class="success">✅ <?= htmlspecialchars($success) ?> <a href="teacher-login.php">Sign in →</a></div><?php endif; ?>
  <?php if (!$success): ?>
  <form method="post">
    <label>Your name</label>
    <input type="text" name="name" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
    <label>Email address</label>
    <input type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
    <label>Password</label>
    <input type="password" name="password" required>
    <label>Confirm password</label>
    <input type="password" name="password2" required>
    <button class="btn" type="submit">Create Account →</button>
  </form>
  <?php endif; ?>
  <div class="footer">
    Already have an account? <a href="teacher-login.php">Sign in</a>
  </div>
</div>
</body>
</html>
