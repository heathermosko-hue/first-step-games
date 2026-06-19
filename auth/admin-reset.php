<?php
require_once 'db.php';
$msg = $error = '';
$SECRET = 'fsr-reset-2024';
$token = $_GET['k'] ?? '';
if ($token !== $SECRET) { http_response_code(404); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['pass'] ?? '';
    if ($email && strlen($pass) >= 4) {
        $db   = getDB();
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $st   = $db->prepare('UPDATE teachers SET password=? WHERE email=?');
        $st->bind_param('ss', $hash, $email);
        $st->execute();
        $msg = $st->affected_rows === 1 ? '✅ Password updated!' : '❌ Email not found.';
    } else {
        $error = 'Fill in both fields.';
    }
}
?><!DOCTYPE html>
<html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Reset</title>
<style>
body{font-family:sans-serif;max-width:400px;margin:3rem auto;padding:1rem}
input{display:block;width:100%;margin:.4rem 0 1rem;padding:.6rem;border:2px solid #ccc;border-radius:8px;font-size:1rem}
button{background:#6B48FF;color:white;border:none;padding:.7rem 2rem;border-radius:8px;font-size:1rem;cursor:pointer;width:100%}
.msg{padding:.8rem;border-radius:8px;margin-bottom:1rem;font-weight:bold}
.ok{background:#f0fff4;border:2px solid #9de0a8}
.err{background:#fff0f0;border:2px solid #ffb3b3;color:#c00}
</style></head><body>
<h2>Reset Teacher Password</h2>
<?php if ($msg): ?><div class="msg ok"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($error): ?><div class="msg err"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<form method="post" action="?k=<?= htmlspecialchars($SECRET) ?>">
  <label>Email</label><input name="email" value="skposluns@firststepreading.com">
  <label>New Password</label><input name="pass" type="text" value="read123">
  <button type="submit">Update Password</button>
</form>
</body></html>
