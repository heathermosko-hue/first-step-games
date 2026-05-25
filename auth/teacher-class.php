<?php
require_once 'db.php';
requireTeacher();
$db  = getDB();
$tid = $_SESSION['teacher_id'];
$cid = (int)($_GET['id'] ?? 0);

// Verify class belongs to teacher
$st = $db->prepare('SELECT * FROM classes WHERE id=? AND teacher_id=?');
$st->bind_param('ii', $cid, $tid); $st->execute();
$class = $st->get_result()->fetch_assoc();
if (!$class) { header('Location: teacher-dashboard.php'); exit; }

$msg = '';

// ── Add student ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD']==='POST' && $_POST['action']==='add_student') {
    $sname = trim($_POST['student_name'] ?? '');
    if ($sname) {
        $icon = getNextAnimal($cid, $db);
        $st   = $db->prepare('INSERT INTO students (class_id,name,icon) VALUES (?,?,?)');
        $st->bind_param('iss', $cid, $sname, $icon);
        $st->execute();
        $msg = "✅ $sname added!";
    }
}
// ── Remove student ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD']==='POST' && $_POST['action']==='remove_student') {
    $sid = (int)$_POST['student_id'];
    $st  = $db->prepare('DELETE FROM students WHERE id=? AND class_id=?');
    $st->bind_param('ii', $sid, $cid); $st->execute();
}
// ── Change student icon ───────────────────────────────────
if ($_SERVER['REQUEST_METHOD']==='POST' && $_POST['action']==='change_icon') {
    $sid  = (int)$_POST['student_id'];
    $icon = $_POST['icon'] ?? '';
    if (in_array($icon, ANIMALS)) {
        $st = $db->prepare('UPDATE students SET icon=? WHERE id=? AND class_id=?');
        $st->bind_param('sii', $icon, $sid, $cid); $st->execute();
    }
}
// ── Save game assignments ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD']==='POST' && $_POST['action']==='save_games') {
    $db->prepare('DELETE FROM assigned_games WHERE class_id=?')->bind_param('i',$cid) && true;
    $st2 = $db->prepare('DELETE FROM assigned_games WHERE class_id=?');
    $st2->bind_param('i',$cid); $st2->execute();
    $selected = $_POST['games'] ?? [];
    if ($selected) {
        $st3 = $db->prepare('INSERT IGNORE INTO assigned_games (class_id,game_slug) VALUES (?,?)');
        foreach ($selected as $slug) {
            if (array_key_exists($slug, GAMES)) {
                $st3->bind_param('is',$cid,$slug); $st3->execute();
            }
        }
    }
    $msg = '✅ Game assignments saved!';
}
// ── Update access type ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD']==='POST' && $_POST['action']==='set_access') {
    $atype = $_POST['access_type']==='assigned' ? 'assigned' : 'full';
    $st = $db->prepare('UPDATE classes SET access_type=? WHERE id=? AND teacher_id=?');
    $st->bind_param('sii',$atype,$cid,$tid); $st->execute();
    $class['access_type'] = $atype;
}

// ── Load data ─────────────────────────────────────────────
$st = $db->prepare('SELECT * FROM students WHERE class_id=? ORDER BY name');
$st->bind_param('i',$cid); $st->execute();
$students = $st->get_result()->fetch_all(MYSQLI_ASSOC);

$st = $db->prepare('SELECT game_slug FROM assigned_games WHERE class_id=?');
$st->bind_param('i',$cid); $st->execute();
$assignedRaw = $st->get_result()->fetch_all(MYSQLI_ASSOC);
$assigned = array_column($assignedRaw, 'game_slug');

// Progress per student per game
$progress = [];
if ($students) {
    $ids = implode(',', array_column($students,'id'));
    $res = $db->query("SELECT student_id,game_slug,score,plays FROM progress WHERE student_id IN ($ids)");
    while ($row = $res->fetch_assoc()) {
        $progress[$row['student_id']][$row['game_slug']] = $row;
    }
}

$qrUrl = urlencode(baseUrl().'/auth/student-login.php?code='.$class['class_code']);
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars($class['name']) ?> — First Step Reading</title>
<style>
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:'Segoe UI',sans-serif;background:#f0f4ff;min-height:100vh}
  header{background:#2c3e50;color:white;padding:1rem 2rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.5rem}
  header h1{font-size:1.15rem}
  .back{color:#aee;text-decoration:none;font-size:.9rem}
  .container{max-width:1000px;margin:2rem auto;padding:0 1rem}
  .msg{background:#efe;border:1px solid #9d9;color:#1a7a3a;border-radius:8px;padding:.7rem 1rem;margin-bottom:1rem}
  .card{background:white;border-radius:16px;padding:1.5rem;box-shadow:0 4px 16px rgba(0,0,0,.08);margin-bottom:1.5rem}
  h2{color:#2c3e50;margin-bottom:1rem;font-size:1.1rem}
  .code-box{display:flex;align-items:center;gap:1.5rem;flex-wrap:wrap}
  .code{font-family:monospace;font-size:2rem;font-weight:700;color:#8e44ad;background:#f3e8ff;border-radius:10px;padding:.5rem 1.2rem;letter-spacing:.15em}
  .qr img{border:3px solid #e0e0e0;border-radius:10px}
  label{display:block;font-weight:600;color:#2c3e50;margin-bottom:.35rem;font-size:.85rem}
  input,select{border:2px solid #e0e0e0;border-radius:8px;padding:.6rem .9rem;font-size:.95rem}
  input:focus,select:focus{outline:none;border-color:#3498db}
  .btn{background:#27ae60;color:white;border:none;border-radius:8px;padding:.6rem 1.2rem;font-size:.9rem;font-weight:700;cursor:pointer}
  .btn:hover{background:#219a52}
  .btn-sm{padding:.35rem .8rem;font-size:.8rem}
  .btn-red{background:#e74c3c}.btn-red:hover{background:#c0392b}
  .btn-purple{background:#8e44ad}.btn-purple:hover{background:#7d3c98}
  .btn-blue{background:#3498db}.btn-blue:hover{background:#2980b9}
  table{width:100%;border-collapse:collapse;font-size:.9rem}
  th{background:#f0f4ff;color:#2c3e50;padding:.7rem .8rem;text-align:left;border-bottom:2px solid #e0e0e0}
  td{padding:.6rem .8rem;border-bottom:1px solid #f0f0f0;vertical-align:middle}
  tr:hover td{background:#fafbff}
  .animal{font-size:1.6rem}
  .icon-picker{display:flex;flex-wrap:wrap;gap:.3rem;margin-top:.5rem}
  .icon-btn{font-size:1.4rem;background:none;border:2px solid transparent;border-radius:8px;cursor:pointer;padding:.2rem;transition:all .15s}
  .icon-btn:hover{border-color:#8e44ad;background:#f3e8ff}
  .icon-btn.current{border-color:#8e44ad;background:#f3e8ff}
  .game-check{display:flex;align-items:center;gap:.5rem;margin:.4rem 0;font-size:.95rem}
  .game-check input[type=checkbox]{width:18px;height:18px}
  .score-cell{text-align:center;color:#7f8c8d;font-size:.85rem}
  .score-cell.played{color:#27ae60;font-weight:600}
  .add-row{display:flex;gap:.7rem;align-items:flex-end}
  .access-row{display:flex;gap:1rem;align-items:center}
  .badge{display:inline-block;padding:.25rem .7rem;border-radius:20px;font-size:.78rem;font-weight:700}
  .badge-full{background:#eaf6ff;color:#2980b9}
  .badge-assigned{background:#fef9e7;color:#d68910}
  @media(max-width:600px){.code{font-size:1.4rem}}
</style>
</head>
<body>
<header>
  <h1>📚 <?= htmlspecialchars($class['name']) ?></h1>
  <a class="back" href="teacher-dashboard.php">← Back to Dashboard</a>
</header>
<div class="container">
  <?php if ($msg): ?><div class="msg"><?= $msg ?></div><?php endif; ?>

  <!-- Class Code & QR -->
  <div class="card">
    <h2>🔑 Class Login</h2>
    <div class="code-box">
      <div>
        <div style="font-size:.85rem;color:#7f8c8d;margin-bottom:.4rem">Share this code with students:</div>
        <div class="code"><?= htmlspecialchars($class['class_code']) ?></div>
        <div style="margin-top:.8rem;font-size:.85rem;color:#7f8c8d">Or scan the QR code →</div>
      </div>
      <div class="qr">
        <img src="https://api.qrserver.com/v1/create-qr-code/?size=140x140&data=<?= $qrUrl ?>" alt="QR Code" width="140" height="140">
        <div style="text-align:center;margin-top:.4rem">
          <a href="https://api.qrserver.com/v1/create-qr-code/?size=400x400&data=<?= $qrUrl ?>" target="_blank" style="font-size:.8rem;color:#3498db">Print large QR</a>
        </div>
      </div>
    </div>
  </div>

  <!-- Access Type -->
  <div class="card">
    <h2>🎮 Game Access</h2>
    <form method="post" class="access-row">
      <input type="hidden" name="action" value="set_access">
      <select name="access_type" style="width:auto">
        <option value="full" <?= $class['access_type']==='full'?'selected':'' ?>>🔓 Full access — all games</option>
        <option value="assigned" <?= $class['access_type']==='assigned'?'selected':'' ?>>📋 Assigned games only</option>
      </select>
      <button class="btn" type="submit">Save</button>
    </form>

    <?php if ($class['access_type']==='assigned'): ?>
    <div style="margin-top:1.2rem">
      <form method="post">
        <input type="hidden" name="action" value="save_games">
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:.4rem;margin-bottom:1rem">
          <?php foreach (GAMES as $slug=>$g): ?>
          <label class="game-check">
            <input type="checkbox" name="games[]" value="<?= $slug ?>" <?= in_array($slug,$assigned)?'checked':'' ?>>
            <?= $g['emoji'].' '.$g['name'] ?>
          </label>
          <?php endforeach; ?>
        </div>
        <button class="btn" type="submit">Save Game Assignments</button>
      </form>
    </div>
    <?php endif; ?>
  </div>

  <!-- Students -->
  <div class="card">
    <h2>👧 Students (<?= count($students) ?>)</h2>
    <div class="add-row" style="margin-bottom:1.2rem">
      <form method="post" style="display:flex;gap:.7rem;align-items:flex-end;flex-wrap:wrap">
        <input type="hidden" name="action" value="add_student">
        <div>
          <label>Student name</label>
          <input type="text" name="student_name" placeholder="First name" required>
        </div>
        <button class="btn" type="submit">+ Add Student</button>
      </form>
    </div>

    <?php if (!$students): ?>
      <div style="color:#95a5a6;text-align:center;padding:1.5rem">No students yet — add your first student above!</div>
    <?php else: ?>
    <div style="overflow-x:auto">
    <table>
      <thead>
        <tr>
          <th>Icon</th>
          <th>Name</th>
          <?php foreach(GAMES as $slug=>$g): if($class['access_type']==='assigned' && !in_array($slug,$assigned)) continue; ?>
            <th style="text-align:center"><?= $g['emoji'] ?><br><span style="font-size:.75rem"><?= $g['name'] ?></span></th>
          <?php endforeach; ?>
          <th>Change Icon</th>
          <th>Remove</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($students as $s): ?>
        <tr>
          <td><span class="animal"><?= $s['icon'] ?></span></td>
          <td><strong><?= htmlspecialchars($s['name']) ?></strong></td>
          <?php foreach(GAMES as $slug=>$g): if($class['access_type']==='assigned' && !in_array($slug,$assigned)) continue; ?>
          <?php $p=$progress[$s['id']][$slug]??null; ?>
          <td class="score-cell <?= $p?'played':'' ?>">
            <?= $p ? ('⭐ '.$p['plays'].' play'.($p['plays']!=1?'s':'')) : '—' ?>
          </td>
          <?php endforeach; ?>
          <td>
            <!-- Icon picker -->
            <details>
              <summary style="cursor:pointer;font-size:.85rem;color:#8e44ad">Change <?= $s['icon'] ?></summary>
              <form method="post">
                <input type="hidden" name="action" value="change_icon">
                <input type="hidden" name="student_id" value="<?= $s['id'] ?>">
                <div class="icon-picker">
                  <?php foreach(ANIMALS as $a): ?>
                  <button type="submit" name="icon" value="<?= $a ?>" class="icon-btn <?= $a===$s['icon']?'current':'' ?>" title="<?= $a ?>"><?= $a ?></button>
                  <?php endforeach; ?>
                </div>
              </form>
            </details>
          </td>
          <td>
            <form method="post" onsubmit="return confirm('Remove <?= htmlspecialchars($s['name']) ?>?')">
              <input type="hidden" name="action" value="remove_student">
              <input type="hidden" name="student_id" value="<?= $s['id'] ?>">
              <button class="btn btn-red btn-sm" type="submit">✕</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
