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
        $code     = generateClassCode($db);
        $iconCode = generateIconCode($db);
        $st   = $db->prepare('INSERT INTO classes (teacher_id,name,class_code,icon_code,access_type) VALUES (?,?,?,?,?)');
        $st->bind_param('issss', $tid, $cname, $code, $iconCode, $atype);
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
  body{font-family:'Comic Sans MS','Chalkboard SE','Comic Neue';background:#FFF8F0;min-height:100vh}
  header{
    background:linear-gradient(135deg,#6B48FF,#3A8EF6);color:white;
    padding:1rem 2rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.5rem;
    box-shadow:0 4px 20px rgba(107,72,255,.35);
  }
  header h1{font-size:1.22rem;font-family:'Comic Sans MS','Chalkboard SE','Comic Neue'}
  .hdr-right{display:flex;align-items:center;gap:.8rem;flex-wrap:wrap}
  .hdr-right span{font-size:.9rem;opacity:.92;font-weight:600}
  .logout{
    color:white;text-decoration:none;font-size:.85rem;font-weight:700;
    background:rgba(255,255,255,.22);border-radius:20px;padding:.3rem .9rem;
    border:2px solid rgba(255,255,255,.4);transition:background .18s;
  }
  .logout:hover{background:rgba(255,255,255,.38)}
  .container{max-width:920px;margin:2rem auto;padding:0 1.2rem}
  .msg{background:#f0fff4;border:2px solid #9de0a8;color:#1a7a3a;border-radius:14px;padding:.8rem 1rem;margin-bottom:1.2rem;font-weight:700}
  .card{background:white;border-radius:22px;padding:1.6rem;box-shadow:0 6px 24px rgba(0,0,0,.09);margin-bottom:1.6rem;border:2px solid #f0eeff}
  h2{color:#6B48FF;margin-bottom:1rem;font-size:1.15rem}
  .form-row{display:flex;gap:1rem;flex-wrap:wrap;align-items:flex-end}
  .form-group{flex:1;min-width:180px}
  label{display:block;font-weight:700;color:#6B48FF;margin-bottom:.32rem;font-size:.86rem}
  input,select{
    width:100%;border:2.5px solid #e0e0e0;border-radius:12px;
    padding:.6rem .9rem;font-size:.95rem;font-family:'Comic Sans MS','Chalkboard SE','Comic Neue';
    transition:border-color .2s;
  }
  input:focus,select:focus{outline:none;border-color:#6B48FF;box-shadow:0 0 0 3px rgba(107,72,255,.12)}
  .btn{
    background:linear-gradient(135deg,#6BCB77,#27AE60);color:white;border:none;
    border-radius:12px;padding:.65rem 1.4rem;font-size:.95rem;font-weight:700;cursor:pointer;
    font-family:'Comic Sans MS','Chalkboard SE','Comic Neue';white-space:nowrap;
    box-shadow:0 3px 12px rgba(39,174,96,.3);transition:transform .15s,box-shadow .15s;
  }
  .btn:hover{transform:translateY(-2px);box-shadow:0 6px 18px rgba(39,174,96,.4)}
  .btn-red{background:linear-gradient(135deg,#FF6B6B,#e74c3c)!important;box-shadow:0 3px 12px rgba(231,76,60,.3)!important}
  .btn-red:hover{box-shadow:0 6px 18px rgba(231,76,60,.4)!important}
  .btn-blue{background:linear-gradient(135deg,#3A8EF6,#6B48FF)!important;box-shadow:0 3px 12px rgba(58,142,246,.3)!important}
  .btn-blue:hover{box-shadow:0 6px 18px rgba(58,142,246,.4)!important}
  .class-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(270px,1fr));gap:1.2rem}
  .class-card{
    background:linear-gradient(145deg,#f8f4ff,#eef6ff);
    border:2.5px solid #ddd6ff;border-radius:20px;padding:1.35rem;
    transition:transform .18s,box-shadow .18s;
  }
  .class-card:hover{transform:translateY(-3px);box-shadow:0 8px 24px rgba(107,72,255,.15)}
  .class-card h3{color:#2C3E50;font-size:1.08rem;margin-bottom:.5rem}
  .code{font-family:monospace;font-size:1.25rem;font-weight:700;color:#8e44ad;background:#f3e8ff;border-radius:10px;padding:.35rem .85rem;display:inline-block;letter-spacing:.12em;margin-bottom:.55rem;border:2px solid #dbb8ff}
  .meta{font-size:.83rem;color:#888;margin-bottom:.85rem;font-weight:600}
  .actions{display:flex;gap:.5rem;flex-wrap:wrap}
  .empty{text-align:center;color:#bbb;padding:2rem;font-size:1rem}
  .badge{display:inline-block;border-radius:20px;padding:.22rem .75rem;font-size:.76rem;font-weight:700;margin-left:.4rem}
  .badge-full{background:#e8f8ff;color:#2980b9;border:1.5px solid #b3deff}
  .badge-assigned{background:#fffbe8;color:#d68910;border:1.5px solid #ffe599}
  @media(max-width:600px){header{padding:.8rem 1rem}header h1{font-size:1rem}.container{padding:0 .8rem}}
</style>
</head>
<body>
<header>
  <h1>🍎 First Step Reading — Teacher Dashboard</h1>
  <div class="hdr-right">
    <span>👋 <?= htmlspecialchars($_SESSION['teacher_name']) ?></span>
    <a class="logout" href="../hub.html">🎮 Games</a>
    <a class="logout" href="logout.php?teacher=1">🚪 Sign out</a>
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
          <span class="badge <?= $c['access_type']==='full'?'badge-full':'badge-assigned' ?>"><?= $c['access_type'] === 'full' ? '🔓 Full' : '📋 Assigned' ?></span>
        </h3>
        <?php if ($c['icon_code']): ?>
        <div style="font-size:1.95rem;letter-spacing:.12em;margin-bottom:.45rem;line-height:1"><?= implode(' ', explode(',', $c['icon_code'])) ?></div>
        <?php else: ?>
        <div class="code"><?= htmlspecialchars($c['class_code']) ?></div>
        <?php endif; ?>
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
