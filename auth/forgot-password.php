<?php
// One-time password fix — requires MySQL credentials. Remove after use.
if (($_GET['fix'] ?? '') === '1') {
    $result = '';
    $rawFix = []; parse_str(file_get_contents('php://input'), $rawFix);
    $fixPost = array_merge($_POST, $rawFix);
    if (!empty($fixPost['dbpass']) && !empty($fixPost['dbname'])) {
        $db = new mysqli('localhost', $fixPost['dbuser'], $fixPost['dbpass'], $fixPost['dbname']);
        if ($db->connect_error) {
            $result = '❌ DB connection failed: ' . htmlspecialchars($db->connect_error);
        } else {
            $hash = password_hash('read123', PASSWORD_DEFAULT);
            $st = $db->prepare('UPDATE teachers SET password=? WHERE email=?');
            $st->bind_param('ss', $hash, 'skposluns@firststepreading.com');
            $st->execute();
            $result = $st->affected_rows === 1 ? '✅ Password set to read123! <a href="teacher-login.php">Login now</a>' : '❌ Email not found in teachers table.';
        }
    }
    ?><!DOCTYPE html><html><head><meta charset="UTF-8"><title>Fix</title>
    <style>body{font-family:sans-serif;max-width:440px;margin:3rem auto;padding:1rem}input{display:block;width:100%;margin:.3rem 0 .8rem;padding:.6rem;border:2px solid #ccc;border-radius:8px;font-size:1rem}button{background:#6B48FF;color:#fff;border:none;padding:.7rem 2rem;border-radius:8px;font-size:1rem;cursor:pointer;width:100%}.r{padding:.8rem;border-radius:8px;margin-bottom:1rem;font-weight:bold;background:#f0fff4;border:2px solid #9de0a8}</style>
    </head><body><h2>Reset Teacher Password</h2>
    <?php if ($result): ?><div class="r"><?= $result ?></div><?php endif; ?>
    <form method="post"><label>MySQL User</label><input name="dbuser" value="firstste_jason1"><label>MySQL Password</label><input name="dbpass" type="password"><label>Database Name</label><input name="dbname"><button>Set Password to read123</button></form>
    </body></html><?php
    exit;
}
require_once 'db.php';
$msg = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if (!$email) {
        $error = 'Please enter your email address.';
    } else {
        $db = getDB();
        // Add reset columns if they don't exist (suppress errors if already there)
        @$db->query("ALTER TABLE teachers ADD COLUMN reset_token VARCHAR(64) DEFAULT NULL");
        @$db->query("ALTER TABLE teachers ADD COLUMN reset_expires INT DEFAULT NULL");

        $st = $db->prepare('SELECT id, name FROM teachers WHERE email=?');
        $st->bind_param('s', $email); $st->execute();
        $row = $st->get_result()->fetch_assoc();

        if ($row) {
            $token   = bin2hex(random_bytes(32));
            $expires = time() + 3600; // 1 hour
            $st2 = $db->prepare('UPDATE teachers SET reset_token=?, reset_expires=? WHERE id=?');
            $st2->bind_param('sii', $token, $expires, $row['id']);
            $st2->execute();

            $resetLink = 'https://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/reset-password.php?token=' . $token;
            $to      = $email;
            $subject = 'Reset your First Step Reading password';
            $body    = "Hi {$row['name']},\n\nClick the link below to reset your password (valid for 1 hour):\n\n{$resetLink}\n\nIf you didn't request this, ignore this email.\n\n— First Step Reading";
            $headers = "From: noreply@firststepreading.com\r\nContent-Type: text/plain; charset=UTF-8";
            mail($to, $subject, $body, $headers);
        }
        // Always show success to prevent email enumeration
        $msg = 'If that email is registered, a reset link has been sent. Check your inbox (and spam folder).';
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Forgot Password — First Step Reading</title>
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
  .logo p{color:#888;font-size:.9rem;margin-top:.25rem;font-weight:600}
  label{display:block;font-weight:700;color:#6B48FF;margin-bottom:.35rem;font-size:.9rem}
  input{width:100%;border:2.5px solid #e0e0e0;border-radius:14px;padding:.75rem 1rem;font-size:1rem;font-family:'Comic Sans MS','Chalkboard SE','Comic Neue';transition:border-color .2s;margin-bottom:1rem;}
  input:focus{outline:none;border-color:#6B48FF;box-shadow:0 0 0 3px rgba(107,72,255,.15)}
  .btn{width:100%;background:linear-gradient(135deg,#6B48FF,#3A8EF6);color:white;border:none;border-radius:14px;padding:.9rem;font-size:1.05rem;font-weight:700;cursor:pointer;font-family:'Comic Sans MS','Chalkboard SE','Comic Neue';box-shadow:0 4px 18px rgba(107,72,255,.35);transition:transform .18s;}
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
    <span class="logo-icon">🔑</span>
    <h1>Forgot Password?</h1>
    <p>We'll email you a reset link</p>
  </div>
  <?php if ($error): ?><div class="error">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>
  <?php if ($msg): ?>
    <div class="success">📧 <?= htmlspecialchars($msg) ?></div>
    <div class="footer"><a href="teacher-login.php">← Back to login</a></div>
  <?php else: ?>
  <form method="post">
    <label>📧 Your email address</label>
    <input type="email" name="email" required autofocus value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
    <button class="btn" type="submit">Send Reset Link →</button>
  </form>
  <div class="footer"><a href="teacher-login.php">← Back to login</a></div>
  <?php endif; ?>
</div>
<a href="../hub.html" class="back-link">← Back to Games</a>
</body>
</html>
