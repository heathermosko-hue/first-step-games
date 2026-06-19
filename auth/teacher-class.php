<?php
require_once 'db.php';
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

// WordPress clears $_GET — parse everything from REQUEST_URI
parse_str(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_QUERY) ?? '', $_QS);

$teacher = requireTeacher();
$db  = getDB();
$tid = $teacher['teacher_id'];
$cid = (int)($_QS['id'] ?? 0);

// ── AJAX handler ──────────────────────────────────────────
if (!empty($_QS['fsr_ajax'])) {
    header('Content-Type: application/json');
    $action = base64_decode($_QS['action'] ?? '');
    $data   = json_decode(base64_decode($_QS['data'] ?? ''), true) ?? [];

    $st = $db->prepare('SELECT id FROM classes WHERE id=? AND teacher_id=?');
    $st->bind_param('ii', $cid, $tid); $st->execute();
    if (!$st->get_result()->fetch_assoc()) {
        echo json_encode(['ok'=>false,'error'=>'Not found.']); exit;
    }

    if ($action === 'add_student') {
        $sname = trim($data['student_name'] ?? '');
        if (!$sname) { echo json_encode(['ok'=>false,'error'=>'Name required.']); exit; }
        $icon = getNextIcon($cid, $db);
        $st   = $db->prepare('INSERT INTO students (class_id,name,icon) VALUES (?,?,?)');
        $st->bind_param('iss', $cid, $sname, $icon);
        $st->execute();
        echo json_encode(['ok'=>true,'icon'=>$icon]);
    }
    elseif ($action === 'remove_student') {
        $sid = (int)($data['student_id'] ?? 0);
        $st  = $db->prepare('DELETE FROM students WHERE id=? AND class_id=?');
        $st->bind_param('ii', $sid, $cid); $st->execute();
        echo json_encode(['ok'=>true]);
    }
    elseif ($action === 'change_icon') {
        $sid  = (int)($data['student_id'] ?? 0);
        $icon = $data['icon'] ?? '';
        if (!in_array($icon, ICON_CHOICES)) { echo json_encode(['ok'=>false,'error'=>'Invalid icon.']); exit; }
        $st = $db->prepare('UPDATE students SET icon=? WHERE id=? AND class_id=?');
        $st->bind_param('sii', $icon, $sid, $cid); $st->execute();
        echo json_encode(['ok'=>true]);
    }
    elseif ($action === 'save_games') {
        $selected = $data['games'] ?? [];
        $st2 = $db->prepare('DELETE FROM assigned_games WHERE class_id=?');
        $st2->bind_param('i', $cid); $st2->execute();
        if ($selected) {
            $st3 = $db->prepare('INSERT IGNORE INTO assigned_games (class_id,game_slug) VALUES (?,?)');
            foreach ($selected as $slug) {
                if (array_key_exists($slug, GAMES)) {
                    $st3->bind_param('is', $cid, $slug); $st3->execute();
                }
            }
        }
        echo json_encode(['ok'=>true]);
    }
    elseif ($action === 'set_access') {
        $atype = ($data['access_type'] ?? '') === 'assigned' ? 'assigned' : 'full';
        $st = $db->prepare('UPDATE classes SET access_type=? WHERE id=? AND teacher_id=?');
        $st->bind_param('sii', $atype, $cid, $tid); $st->execute();
        echo json_encode(['ok'=>true]);
    }
    else {
        echo json_encode(['ok'=>false,'error'=>'Unknown action.']);
    }
    exit;
}

// ── Verify class belongs to teacher ──────────────────────
$st = $db->prepare('SELECT * FROM classes WHERE id=? AND teacher_id=?');
$st->bind_param('ii', $cid, $tid); $st->execute();
$class = $st->get_result()->fetch_assoc();
if (!$class) {
    echo '<script>window.location.href="teacher-dashboard.php";</script>'; exit;
}

// ── Load data ─────────────────────────────────────────────
$st = $db->prepare('SELECT * FROM students WHERE class_id=? ORDER BY name');
$st->bind_param('i', $cid); $st->execute();
$students = $st->get_result()->fetch_all(MYSQLI_ASSOC);

$st = $db->prepare('SELECT game_slug FROM assigned_games WHERE class_id=?');
$st->bind_param('i', $cid); $st->execute();
$assignedRaw = $st->get_result()->fetch_all(MYSQLI_ASSOC);
$assigned = array_column($assignedRaw, 'game_slug');

$progress = [];
if ($students) {
    $ids = implode(',', array_column($students, 'id'));
    $res = $db->query("SELECT student_id,game_slug,score,plays FROM progress WHERE student_id IN ($ids)");
    while ($row = $res->fetch_assoc()) {
        $progress[$row['student_id']][$row['game_slug']] = $row;
    }
}

$qrUrl = urlencode(baseUrl().'/auth/student-login.php?class_id='.$class['id']);
$iconChoicesJson = json_encode(ICON_CHOICES, JSON_UNESCAPED_UNICODE);
$gameKeys = array_keys(GAMES);
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
  .err-msg{background:#fff0f0;border:2px solid #ffb3b3;color:#c0392b;border-radius:14px;padding:.8rem 1rem;margin-bottom:1.2rem;font-weight:700}
  .card{background:white;border-radius:22px;padding:1.6rem;box-shadow:0 6px 24px rgba(0,0,0,.09);margin-bottom:1.6rem;border:2px solid #f0eeff}
  h2{color:#6B48FF;margin-bottom:1rem;font-size:1.12rem}
  .code-box{display:flex;align-items:center;gap:1.6rem;flex-wrap:wrap}
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
  .btn:disabled{opacity:.55;cursor:not-allowed;transform:none!important}
  .btn-sm{padding:.35rem .8rem;font-size:.8rem}
  .btn-red{background:linear-gradient(135deg,#FF6B6B,#e74c3c)!important;box-shadow:0 3px 10px rgba(231,76,60,.28)!important}
  .btn-purple{background:linear-gradient(135deg,#C084FC,#8e44ad)!important;box-shadow:0 3px 10px rgba(142,68,173,.28)!important}
  .btn-blue{background:linear-gradient(135deg,#3A8EF6,#6B48FF)!important;box-shadow:0 3px 10px rgba(58,142,246,.28)!important}
  table{width:100%;border-collapse:collapse;font-size:.9rem}
  th{background:linear-gradient(135deg,#f0eeff,#e8f4ff);color:#6B48FF;padding:.75rem .85rem;text-align:left;border-bottom:2.5px solid #ddd6ff;font-weight:700}
  td{padding:.62rem .85rem;border-bottom:1.5px solid #f5f0ff;vertical-align:middle}
  tr:hover td{background:#faf7ff}
  .animal{font-size:1.8rem}
  .icon-picker{display:flex;flex-wrap:wrap;gap:.3rem;margin-top:.6rem;max-width:280px}
  .icon-btn{font-size:1.5rem;background:none;border:2.5px solid transparent;border-radius:10px;cursor:pointer;padding:.25rem;transition:all .15s}
  .icon-btn:hover{border-color:#8e44ad;background:#f3e8ff}
  .icon-btn.current{border-color:#8e44ad;background:#f3e8ff}
  .game-check{display:flex;align-items:center;gap:.5rem;margin:.4rem 0;font-size:.95rem}
  .game-check input[type=checkbox]{width:18px;height:18px;accent-color:#6B48FF}
  .score-cell{text-align:center;color:#bbb;font-size:.85rem}
  .score-cell.played{color:#27ae60;font-weight:700}
  .add-row{display:flex;gap:.7rem;align-items:flex-end;flex-wrap:wrap;margin-bottom:1.2rem}
  .access-row{display:flex;gap:1rem;align-items:center;flex-wrap:wrap}
  details summary{cursor:pointer;font-size:.85rem;color:#8e44ad;user-select:none}
  @media(max-width:600px){.container{padding:0 .8rem}header{padding:.8rem 1rem}header h1{font-size:.95rem}}
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
  <div id="page-msg" style="display:none"></div>

  <!-- Class Login Code -->
  <div class="card">
    <h2>🔑 Class Login</h2>
    <div class="code-box">
      <div>
        <div style="font-size:.85rem;color:#7f8c8d;margin-bottom:.4rem">Students tap these 4 icons in order:</div>
        <?php if ($class['icon_code']): ?>
        <div style="font-size:2.6rem;letter-spacing:.2em;background:#f3e8ff;border-radius:14px;padding:.5rem 1rem;border:2.5px solid #dbb8ff;display:inline-block;line-height:1.3"><?= implode(' ', explode(',', $class['icon_code'])) ?></div>
        <?php else: ?>
        <div style="font-family:monospace;font-size:2.1rem;font-weight:700;color:#8e44ad;background:#f3e8ff;border-radius:14px;padding:.5rem 1.3rem;letter-spacing:.15em;border:2.5px solid #dbb8ff;display:inline-block"><?= htmlspecialchars($class['class_code']) ?></div>
        <?php endif; ?>
        <div style="margin-top:.8rem;font-size:.85rem;color:#7f8c8d">Or scan the QR code to go straight to icon login →</div>
      </div>
      <div>
        <img src="https://api.qrserver.com/v1/create-qr-code/?size=140x140&data=<?= $qrUrl ?>" alt="QR Code" width="140" height="140" style="border:3px solid #ddd6ff;border-radius:14px">
        <div style="text-align:center;margin-top:.4rem">
          <a href="https://api.qrserver.com/v1/create-qr-code/?size=400x400&data=<?= $qrUrl ?>" target="_blank" style="font-size:.8rem;color:#3498db">Print large QR</a>
        </div>
      </div>
    </div>
  </div>

  <!-- Game Access -->
  <div class="card">
    <h2>🎮 Game Access</h2>
    <div class="access-row">
      <select id="access_type_sel">
        <option value="full" <?= $class['access_type']==='full'?'selected':'' ?>>🔓 Full access — all games</option>
        <option value="assigned" <?= $class['access_type']==='assigned'?'selected':'' ?>>📋 Assigned games only</option>
      </select>
      <button class="btn" onclick="saveAccessType()">Save</button>
    </div>
    <div id="games-section" style="margin-top:1.2rem;<?= $class['access_type']==='assigned'?'':'display:none' ?>">
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:.4rem;margin-bottom:1rem">
        <?php foreach (GAMES as $slug=>$g): ?>
        <label class="game-check">
          <input type="checkbox" class="game-chk" value="<?= $slug ?>" <?= in_array($slug,$assigned)?'checked':'' ?>>
          <?= $g['emoji'].' '.$g['name'] ?>
        </label>
        <?php endforeach; ?>
      </div>
      <button class="btn" onclick="saveGames()">Save Game Assignments</button>
    </div>
  </div>

  <!-- Students -->
  <div class="card">
    <h2>👧 Students (<span id="student-count"><?= count($students) ?></span>)</h2>
    <div class="add-row">
      <div>
        <label>Student name</label>
        <input type="text" id="new-student-name" placeholder="First name">
      </div>
      <button class="btn" id="add-btn" onclick="addStudent()">+ Add Student</button>
    </div>

    <div id="no-students" style="color:#95a5a6;text-align:center;padding:1.5rem;<?= $students?'display:none':'' ?>">No students yet — add your first student above!</div>

    <div style="overflow-x:auto">
    <table id="student-table" <?= !$students?'style="display:none"':'' ?>>
      <thead>
        <tr>
          <th>Icon</th>
          <th>Name</th>
          <?php foreach(GAMES as $slug=>$g): ?>
            <th class="game-col game-col-<?= $slug ?>" style="text-align:center<?= ($class['access_type']==='assigned' && !in_array($slug,$assigned))?';display:none':'' ?>"><?= $g['emoji'] ?><br><span style="font-size:.75rem"><?= $g['name'] ?></span></th>
          <?php endforeach; ?>
          <th>Change Icon</th>
          <th>Remove</th>
        </tr>
      </thead>
      <tbody id="student-tbody">
        <?php foreach ($students as $s): ?>
        <tr id="student-row-<?= $s['id'] ?>">
          <td><span class="animal" id="icon-display-<?= $s['id'] ?>"><?= $s['icon'] ?></span></td>
          <td><strong><?= htmlspecialchars($s['name']) ?></strong></td>
          <?php foreach(GAMES as $slug=>$g): ?>
          <?php $p=$progress[$s['id']][$slug]??null; ?>
          <td class="score-cell game-col game-col-<?= $slug ?> <?= $p?'played':'' ?>" style="text-align:center<?= ($class['access_type']==='assigned' && !in_array($slug,$assigned))?';display:none':'' ?>">
            <?= $p ? ('⭐ '.$p['plays'].' play'.($p['plays']!=1?'s':'')) : '—' ?>
          </td>
          <?php endforeach; ?>
          <td>
            <details>
              <summary>Change <?= $s['icon'] ?></summary>
              <div class="icon-picker" id="picker-<?= $s['id'] ?>">
                <?php foreach(ICON_CHOICES as $a): ?>
                <button class="icon-btn <?= $a===$s['icon']?'current':'' ?>" onclick="changeIcon(<?= $s['id'] ?>, this)"><?= $a ?></button>
                <?php endforeach; ?>
              </div>
            </details>
          </td>
          <td><button class="btn btn-red btn-sm" onclick="removeStudent(<?= $s['id'] ?>, '<?= htmlspecialchars($s['name'], ENT_QUOTES) ?>')">✕</button></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>
  </div>
</div>

<script>
var CID = <?= $cid ?>;

function fsrAction(action, data) {
  var encoded = btoa(unescape(encodeURIComponent(JSON.stringify(data))));
  var url = 'teacher-class.php?fsr_ajax=1&id=' + CID
          + '&action=' + encodeURIComponent(btoa(action))
          + '&data=' + encodeURIComponent(encoded);
  return fetch(url).then(function(r) {
    return r.text().then(function(txt) {
      try { return JSON.parse(txt); } catch(e) { throw new Error('Not JSON: ' + txt.substring(0,200)); }
    });
  });
}

function showMsg(text, isErr) {
  var el = document.getElementById('page-msg');
  el.className = isErr ? 'err-msg' : 'msg';
  el.textContent = text;
  el.style.display = '';
  setTimeout(function(){ el.style.display = 'none'; }, 4000);
}

function addStudent() {
  var nameEl = document.getElementById('new-student-name');
  var name = nameEl.value.trim();
  if (!name) { showMsg('Please enter a student name.', true); return; }
  var btn = document.getElementById('add-btn');
  btn.disabled = true; btn.textContent = 'Adding...';
  fsrAction('add_student', {student_name: name}).then(function(d) {
    btn.disabled = false; btn.textContent = '+ Add Student';
    if (!d.ok) { showMsg(d.error || 'Error adding student.', true); return; }
    nameEl.value = '';
    showMsg('✅ ' + name + ' added!', false);
    location.reload();
  }).catch(function(e) {
    btn.disabled = false; btn.textContent = '+ Add Student';
    showMsg('Error: ' + e, true);
  });
}

function removeStudent(sid, name) {
  if (!confirm('Remove ' + name + '?')) return;
  fsrAction('remove_student', {student_id: sid}).then(function(d) {
    if (d.ok) {
      var row = document.getElementById('student-row-' + sid);
      if (row) row.remove();
      var cnt = document.getElementById('student-count');
      cnt.textContent = parseInt(cnt.textContent) - 1;
      var tbody = document.getElementById('student-tbody');
      if (!tbody.querySelector('tr')) {
        document.getElementById('student-table').style.display = 'none';
        document.getElementById('no-students').style.display = '';
      }
      showMsg('Removed ' + name, false);
    } else {
      showMsg(d.error || 'Error', true);
    }
  }).catch(function(e){ showMsg('Error: '+e, true); });
}

function changeIcon(sid, btn) {
  var icon = btn.textContent;
  fsrAction('change_icon', {student_id: sid, icon: icon}).then(function(d) {
    if (d.ok) {
      document.getElementById('icon-display-' + sid).textContent = icon;
      var picker = document.getElementById('picker-' + sid);
      picker.querySelectorAll('.icon-btn').forEach(function(b){ b.classList.remove('current'); });
      btn.classList.add('current');
      showMsg('Icon updated!', false);
    } else {
      showMsg(d.error || 'Error', true);
    }
  }).catch(function(e){ showMsg('Error: '+e, true); });
}

function saveAccessType() {
  var atype = document.getElementById('access_type_sel').value;
  fsrAction('set_access', {access_type: atype}).then(function(d) {
    if (d.ok) {
      document.getElementById('games-section').style.display = atype === 'assigned' ? '' : 'none';
      showMsg('✅ Access type saved!', false);
    } else {
      showMsg(d.error || 'Error', true);
    }
  }).catch(function(e){ showMsg('Error: '+e, true); });
}

function saveGames() {
  var selected = [];
  document.querySelectorAll('.game-chk:checked').forEach(function(cb){ selected.push(cb.value); });
  fsrAction('save_games', {games: selected}).then(function(d) {
    if (d.ok) { showMsg('✅ Game assignments saved!', false); }
    else { showMsg(d.error || 'Error', true); }
  }).catch(function(e){ showMsg('Error: '+e, true); });
}

document.getElementById('access_type_sel').addEventListener('change', function() {
  document.getElementById('games-section').style.display = this.value === 'assigned' ? '' : 'none';
});
</script>
</body>
</html>
