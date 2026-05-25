<?php
require_once 'db.php';

// If code in URL (from QR scan), pre-fill and auto-proceed to icon pick
$preCode = trim($_GET['code'] ?? '');
$error   = '';
$class   = null;
$students = [];

function loadClass($code, $db) {
    $st = $db->prepare('SELECT * FROM classes WHERE class_code=?');
    $st->bind_param('s', $code); $st->execute();
    return $st->get_result()->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD']==='POST' || $preCode) {
    $code = strtoupper(trim($_POST['code'] ?? $preCode));
    $db   = getDB();
    $class = loadClass($code, $db);
    if (!$class) {
        $error = 'That class code was not found. Please check with your teacher.';
        $class = null;
    } else {
        $st = $db->prepare('SELECT * FROM students WHERE class_id=? ORDER BY name');
        $st->bind_param('i',$class['id']); $st->execute();
        $students = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Class Login — First Step Reading</title>
<style>
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:'Segoe UI',sans-serif;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:1rem}
  .card{background:white;border-radius:24px;padding:2.5rem;width:100%;max-width:480px;box-shadow:0 20px 60px rgba(0,0,0,.25)}
  .logo{text-align:center;margin-bottom:2rem}
  .logo h1{font-size:1.8rem;color:#2c3e50;font-weight:800}
  .logo p{color:#7f8c8d;margin-top:.3rem}
  .stars{font-size:2rem;margin-bottom:.5rem}
  label{display:block;font-weight:700;color:#2c3e50;margin-bottom:.5rem;font-size:1rem}
  .code-input{width:100%;border:3px solid #e0e0e0;border-radius:14px;padding:1rem 1.2rem;font-size:1.5rem;font-family:monospace;text-align:center;letter-spacing:.15em;text-transform:uppercase;transition:border-color .2s}
  .code-input:focus{outline:none;border-color:#764ba2}
  .btn{width:100%;background:linear-gradient(135deg,#667eea,#764ba2);color:white;border:none;border-radius:14px;padding:1rem;font-size:1.15rem;font-weight:800;cursor:pointer;margin-top:1rem;transition:opacity .2s}
  .btn:hover{opacity:.9}
  .error{background:#fee;border:2px solid #f99;color:#c0392b;border-radius:10px;padding:.8rem 1rem;margin-bottom:1rem;font-size:.95rem;text-align:center}
  .teacher-link{text-align:center;margin-top:1.5rem;font-size:.85rem;color:#bdc3c7}
  .teacher-link a{color:#764ba2;text-decoration:none;font-weight:600}

  /* Icon picker (shown after code entry) */
  .pick-section h2{font-size:1.3rem;color:#2c3e50;text-align:center;margin-bottom:.3rem;font-weight:800}
  .pick-section p{text-align:center;color:#7f8c8d;margin-bottom:1.2rem;font-size:.95rem}
  .icon-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:.7rem}
  .icon-card{display:flex;flex-direction:column;align-items:center;background:#f8f0ff;border:3px solid transparent;border-radius:16px;padding:.8rem .5rem;cursor:pointer;transition:all .15s;text-decoration:none}
  .icon-card:hover{border-color:#764ba2;background:#f0e6ff;transform:scale(1.06)}
  .icon-card .animal{font-size:2.4rem;line-height:1}
  .icon-card .sname{font-size:.72rem;font-weight:700;color:#5a3e7a;margin-top:.35rem;text-align:center;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:70px}
  .back-link{display:block;text-align:center;margin-top:1rem;color:#764ba2;font-size:.9rem;cursor:pointer;font-weight:600}
</style>
</head>
<body>
<div class="card">

<?php if (!$class): ?>
  <!-- Step 1: Enter code -->
  <div class="logo">
    <div class="stars">⭐📚⭐</div>
    <h1>Class Login</h1>
    <p>First Step Reading</p>
  </div>
  <?php if ($error): ?><div class="error">😕 <?= htmlspecialchars($error) ?></div><?php endif; ?>
  <form method="post">
    <label>Enter your class code:</label>
    <input class="code-input" type="text" name="code" placeholder="ABC-123" maxlength="10" autofocus value="<?= htmlspecialchars($preCode) ?>">
    <button class="btn" type="submit">Let's Go! →</button>
  </form>
  <div class="teacher-link">Are you a teacher? <a href="teacher-login.php">Teacher login →</a></div>

<?php else: ?>
  <!-- Step 2: Pick your icon -->
  <div class="pick-section">
    <h2>Who are you? 👋</h2>
    <p>Tap your animal!</p>
    <?php if (!$students): ?>
      <div class="error">No students in this class yet. Ask your teacher to add you!</div>
    <?php else: ?>
    <div class="icon-grid">
      <?php foreach ($students as $s): ?>
      <a class="icon-card" href="student-games.php?student=<?= $s['id'] ?>&class=<?= $class['id'] ?>">
        <span class="animal"><?= $s['icon'] ?></span>
        <span class="sname"><?= htmlspecialchars($s['name']) ?></span>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <span class="back-link" onclick="history.back()">← Wrong class? Go back</span>
  </div>
<?php endif; ?>

</div>
</body>
</html>
