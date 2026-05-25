<?php
require_once 'db.php';
requireTeacher();
$db  = getDB();
$tid = $_SESSION['teacher_id'];

// ── Handle create class ──────────────────────────────────
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_class') {
    $cname = trim($_POST['class_name'] ?? '');
    $atype = $_POST['access_type'] === 'assigned' ? 'assigned' : 'full';
    if ($cname) {
        $code = generateClassCode($db);
        $st   = $db->prepare('INSERT INTO classes (teacher_id,name,class_code,access_type) VALUES (?,?,?,?)');
        $st->bind_param('isss', $tid, $cname, $code, $atype);
        $st->execute();
        $msg = '✅ Class created!';
    }
}
// ── Delete class ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_class') {
    $cid = (int)$_POST['class_id'];
    $st  = $db->prepare('DELETE FROM classes WHERE id=? AND teacher_id=?');
    $st->bind_param('ii', $cid, $tid); $st->execute();
}

// ── Load classes ─────────────────────────────────────────
$st = $db->prepare('SELECT c.*, (SELECT COUNT(*) FROM students WHERE class_id=c.id) AS student_count FROM classes c WHERE c.teacher_id=? ORDER BY c.created_at DESC');
$st->bind_param('i', $tid); $st->execute();
$classes = $st->get_result()->fetch_all(MYSQLI_ASSOC);
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Teacher Dashboard — First Step Reading</title>
<style>
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:'Segoe UI',sans-serif;background:#f0f4ff;min-height:100vh}
  header{background:#2c3e50;color:white;padding:1rem 2rem;display:flex;align-items:center;justify-content:space-between}
  header h1{font-size:1.3rem}
  header span{font-size:.9rem;opacity:.8}
  .logout{color:#aee;text-decoration:none;font-size:.9rem;margin-left:1.5rem}
  .container{max-width:900px;margin:2rem auto;padding:0 1rem}
  .msg{background:#efe;border:1px solid #9d9;color:#1a7a3a;border-radius:8px;padding:.7rem 1rem;margin-bottom:1rem}
  .card{background:white;border-radius:16px;padding:1.5rem;box-shadow:0 4px 16px rgba(0,0,0,.08);margin-bottom:1.5rem}
  h2{color:#2c3e50;margin-bottom:1rem;font-size:1.15rem}
  .form-row{display:flex;gap:1rem;flex-wrap:wrap;align-items:flex-end}
  .form-group{flex:1;min-width:180px}
  label{display:block;font-weight:600;color:#2c3e50;margin-bottom:.35rem;font-size:.85rem}
  input,select{width:100%;border:2px solid #e0e0e0;border-radius:8px;padding:.6rem .9rem;font-size:.95rem}
  input:focus,select:focus{outline:none;border-color:#3498db}
  .btn{background:#27ae60;color:white;border:none;border-radius:8px;padding:.65rem 1.4rem;font-size:.95rem;font-weight:700;cursor:pointer;white-space:nowrap}
  .btn:hover{background:#219a52}
  .btn-red{background:#e74c3c}.btn-red:hover{background:#c0392b}
  .btn-blue{background:#3498db}.btn-blue:hover{background:#2980b9}
  .class-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:1rem}
  .class-card{background:#f8f9ff;border:2px solid #e8ecff;border-radius:14px;padding:1.2rem}
  .class-card h3{color:#2c3e50;font-size:1.05rem;margin-bottom:.5rem}
  .code{font-family:monospace;font-size:1.2rem;font-weight:700;color:#8e44ad;background:#f3e8ff;border-radius:6px;padding:.3rem .7rem;display:inline-block;letter-spacing:.1em;margin-bottom:.5rem}
  .meta{font-size:.82rem;color:#7f8c8d;margin-bottom:.8rem}
  .actions{display:flex;gap:.5rem;flex-wrap:wrap}
  .empty{text-align:center;color:#95a5a6;padding:2rem}
  .badge{display:inline-block;background:#eaf6ff;color:#2980b9;border-radius:20px;padding:.2rem .7rem;font-size:.78rem;font-weight:600;margin-left:.4rem}
</style>
</head>
<body>
<header>
  <h1>🍎 First Step Reading — Teacher Dashboard</h1>
  <div>
    <span>👋 <?= htmlspecialchars($_SESSION['teacher_name']) ?></span>
    <a class="logout" href="logout.php?teacher=1">Sign out</a>
  </div>
</header>
<div class="container">
  <?php if ($msg): ?><div class="msg"><?= $msg ?></div><?php endif; ?>

  <!-- Create Class -->
  <div class="card">
    <h2>➕ Create a New Class</h2>
    <form method="post">
      <input type="hidden" name="action" value="create_class">
      <div class="form-row">
        <div class="form-group">
          <label>Class name</label>
          <input type="text" name="class_name" placeholder="e.g. Mrs. Smith — Grade 1" required>
        </div>
        <div class="form-group" style="max-width:200px">
          <label>Game access</label>
          <select name="access_type">
            <option value="full">Full access (all games)</option>
            <option value="assigned">Assigned games only</option>
          </select>
        </div>
        <button class="btn" type="submit">Create Class</button>
      </div>
    </form>
  </div>

  <!-- Classes -->
  <div class="card">
    <h2>📚 My Classes (<?= count($classes) ?>)</h2>
    <?php if (!$classes): ?>
      <div class="empty">No classes yet — create your first class above!</div>
    <?php else: ?>
    <div class="class-grid">
      <?php foreach ($classes as $c): ?>
      <div class="class-card">
        <h3><?= htmlspecialchars($c['name']) ?>
          <span class="badge"><?= $c['access_type'] === 'full' ? '🔓 Full' : '📋 Assigned' ?></span>
        </h3>
        <div class="code"><?= htmlspecialchars($c['class_code']) ?></div>
        <div class="meta">👧 <?= $c['student_count'] ?> student<?= $c['student_count'] != 1 ? 's' : '' ?></div>
        <div class="actions">
          <a class="btn btn-blue" href="teacher-class.php?id=<?= $c['id'] ?>" style="text-decoration:none;font-size:.85rem;padding:.5rem 1rem">Manage →</a>
          <form method="post" onsubmit="return confirm('Delete this class and all student data?')">
            <input type="hidden" name="action" value="delete_class">
            <input type="hidden" name="class_id" value="<?= $c['id'] ?>">
            <button class="btn btn-red" style="font-size:.85rem;padding:.5rem 1rem">Delete</button>
          </form>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
