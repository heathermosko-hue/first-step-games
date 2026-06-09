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
  body{font-family:'Comic Sans MS','Chalkboard SE','Comic Neue';background:#FFF8F0;min-height:100vh}
  header{
    background:linear-gradient(135deg,#6B48FF,#3A8EF6);color:white;
    padding:1rem 2rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.5rem;
    box-shadow:0 4px 20px rgba(107,72,255,.35);
  }
  header h1{font-size:1.12rem;font-family:'Comic Sans MS','Chalkboard SE','Comic Neue'}
  .back{
    color:white;text-decoration:none;font-size:.85rem;font-weight:700;
    background:rgba(255,255,255,.22);border-radius:20px;padding:.3rem .9rem;
    border:2px solid rgba(255,255,255,.4);transition:background .18s;
  }
  .back:hover{background:rgba(255,255,255,.38)}
  .container{max-width:1020px;margin:2rem auto;padding:0 1.2rem}
  .msg{background:#f0fff4;border:2px solid #9de0a8;color:#1a7a3a;border-radius:14px;padding:.8rem 1rem;margin-bottom:1.2rem;font-weight:700}
  .card{background:white;border-radius:22px;padding:1.6rem;box-shadow:0 6px 24px rgba(0,0,0,.09);margin-bottom:1.6rem;border:2px solid #f0eeff}
  h2{color:#6B48FF;margin-bottom:1rem;font-size:1.12rem}
  .code-box{display:flex;align-items:center;gap:1.6rem;flex-wrap:wrap}
  .code{font-family:monospace;font-size:2.1rem;font-weight:700;color:#8e44ad;background:#f3e8ff;border-radius:14px;padding:.5rem 1.3rem;letter-spacing:.15em;border:2.5px solid #dbb8ff}
  .qr img{border:3px solid #ddd6ff;border-radius:14px}
  label{display:block;font-weight:700;color:#6B48FF;margin-bottom:.32rem;font-size:.86rem}
  input,select{
    border:2.5px solid #e0e0e0;border-radius:12px;padding:.6rem .9rem;font-size:.95rem;
    font-family:'Comic Sans MS','Chalkboard SE','Comic Neue';transition:border-color .2s;
  }
  input:focus,select:focus{outline:none;border-color:#6B48FF;box-shadow:0 0 0 3px rgba(107,72,255,.12)}
  .btn{
    background:linear-gradient(135deg,#6BCB77,#27AE60);color:white;border:none;
    border-radius:12px;padding:.6rem 1.2rem;font-size:.92rem;font-weight:700;cursor:pointer;
    font-family:'Comic Sans MS','Chalkboard SE','Comic Neue';
    box-shadow:0 3px 12px rgba(39,174,96,.28);transition:transform .15s,box-shadow .15s;
  }
  .btn:hover{transform:translateY(-2px);box-shadow:0 6px 18px rgba(39,174,96,.4)}
  .btn-sm{padding:.35rem .8rem;font-size:.8rem}
  .btn-red{background:linear-gradient(135deg,#FF6B6B,#e74c3c)!important;box-shadow:0 3px 10px rgba(231,76,60,.28)!important}
  .btn-red:hover{box-shadow:0 6px 16px rgba(231,76,60,.4)!important}
  .btn-purple{background:linear-gradient(135deg,#C084FC,#8e44ad)!important;box-shadow:0 3px 10px rgba(142,68,173,.28)!important}
  .btn-blue{background:linear-gradient(135deg,#3A8EF6,#6B48FF)!important;box-shadow:0 3px 10px rgba(58,142,246,.28)!important}
  table{width:100%;border-collapse:collapse;font-size:.9rem}
  th{background:linear-gradient(135deg,#f0eeff,#e8f4ff);color:#6B48FF;padding:.75rem .85rem;text-align:left;border-bottom:2.5px solid #ddd6ff;font-weight:700}
  td{padding:.62rem .85rem;border-bottom:1.5px solid #f5f0ff;vertical-align:middle}
  tr:hover td{background:#faf7ff}
  .animal{font-size:1.8rem}
  .icon-picker{display:flex;flex-wrap:wrap;gap:.3rem;margin-top:.6rem}
  .icon-btn{font-size:1.5rem;background:none;border:2.5px solid transparent;border-radius:10px;cursor:pointer;padding:.25rem;transition:all .15s}
  .icon-btn:hover{border-color:#8e44ad;background:#f3e8ff}
  .icon-btn.current{border-color:#8e44ad;background:#f3e8ff}
  .game-check{display:flex;align-items:center;gap:.5rem;margin:.4rem 0;font-size:.95rem}
  .game-check input[type=checkbox]{width:18px;height:18px;accent-color:#6B48FF}
  .score-cell{text-align:center;color:#bbb;font-size:.85rem}
  .score-cell.played{color:#27ae60;font-weight:700}
  .add-row{display:flex;gap:.7rem;align-items:flex-end}
  .access-row{display:flex;gap:1rem;align-items:center}
  .badge{display:inline-block;border-radius:20px;padding:.22rem .75rem;font-size:.76rem;font-weight:700;margin-left:.4rem}
  .badge-full{background:#e8f8ff;color:#2980b9;border:1.5px solid #b3deff}
  .badge-assigned{background:#fffbe8;color:#d68910;border:1.5px solid #ffe599}
  @media(max-width:600px){.code{font-size:1.4rem}.container{padding:0 .8rem}header{padding:.8rem 1rem}header h1{font-size:.95rem}}
</style>
</head>
<body>
<header>
  <h1>📚 <?= htmlspecialchars($class['name']) ?></h1>
  <div style="display:flex;gap:.6rem;flex-wrap:wrap">
    <a class="back" href="teacher-dashboard.php">← Dashboard</a>
    <a class="back" href="../hub.html">🎮 Games</a>
  </div>
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
