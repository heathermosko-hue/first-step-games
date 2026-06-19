<?php
// One-time migration tool — enter your MySQL credentials from cPanel, click Run.
// DELETE THIS FILE from the server after the migration succeeds.
$result = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = trim($_POST['host'] ?? 'localhost');
    $user = trim($_POST['user'] ?? '');
    $pass = trim($_POST['pass'] ?? '');
    $name = trim($_POST['name'] ?? '');
    if ($user && $pass && $name) {
        $db = new mysqli($host, $user, $pass, $name);
        if ($db->connect_error) {
            $result = '❌ Connection failed: ' . htmlspecialchars($db->connect_error);
        } else {
            $db->set_charset('utf8mb4');
            $ok = $db->query("ALTER TABLE classes ADD COLUMN IF NOT EXISTS icon_code VARCHAR(60) DEFAULT NULL");
            $result = $ok
                ? '✅ Done! icon_code column added (or already existed). You can now delete this file.'
                : '❌ Query error: ' . htmlspecialchars($db->error);
        }
    } else {
        $result = '⚠️ Fill in all fields.';
    }
}
?><!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>DB Migration</title>
<style>body{font-family:sans-serif;max-width:480px;margin:3rem auto;padding:1rem}
input{display:block;width:100%;margin:.4rem 0 1rem;padding:.6rem;border:2px solid #ccc;border-radius:8px;font-size:1rem}
button{background:#6B48FF;color:white;border:none;padding:.7rem 2rem;border-radius:8px;font-size:1rem;cursor:pointer}
.msg{padding:.8rem;border-radius:8px;margin-bottom:1rem;font-weight:bold;background:#f0fff4;border:2px solid #9de0a8}</style>
</head><body>
<h2>Add icon_code column to classes</h2>
<?php if ($result): ?><div class="msg"><?= $result ?></div><?php endif; ?>
<form method="post">
  <label>MySQL Host (usually localhost)</label><input name="host" value="localhost">
  <label>MySQL Username</label><input name="user" placeholder="firstste_jason1">
  <label>MySQL Password</label><input name="pass" type="password">
  <label>Database Name</label><input name="name" placeholder="firstste_yourdb">
  <button type="submit">Run Migration</button>
</form>
</body></html>
