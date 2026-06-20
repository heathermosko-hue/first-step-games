<?php
require_once 'db.php';
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
$teacher = requireTeacher();
$db  = getDB();
$tid = $teacher['teacher_id'];

// ── AJAX actions — WordPress clears $_GET so parse REQUEST_URI ──
parse_str(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_QUERY) ?? '', $_QS);
if (!empty($_QS['fsr_ajax'])) {
    header('Content-Type: application/json');
    $action = base64_decode($_QS['action'] ?? '');
    $data   = json_decode(base64_decode($_QS['data'] ?? ''), true) ?? [];
    if ($action === 'create_class') {
        $cname = trim($data['class_name'] ?? '');
        $atype = ($data['access_type'] ?? '') === 'assigned' ? 'assigned' : 'full';
        if (!$cname) { echo json_encode(['ok'=>false,'error'=>'Class name required.']); exit; }
        $code     = generateClassCode($db);
        $iconCode = generateIconCode($db);
        $st = $db->prepare('INSERT INTO classes (teacher_id,name,class_code,icon_code,access_type) VALUES (?,?,?,?,?)');
        $st->bind_param('issss', $tid, $cname, $code, $iconCode, $atype);
        $st->execute();
        echo json_encode(['ok'=>true]);
    } elseif ($action === 'delete_class') {
        $cid = (int)($data['class_id'] ?? 0);
        $st  = $db->prepare('DELETE FROM classes WHERE id=? AND teacher_id=?');
        $st->bind_param('ii', $cid, $tid); $st->execute();
        echo json_encode(['ok'=>true]);
    } else {
        echo json_encode(['ok'=>false,'error'=>'Unknown action.']);
    }
    exit;
}

$msg = '';

// ── Load premium status ───────────────────────────────────
$st = $db->prepare('SELECT email, premium_until FROM teachers WHERE id=?');
$st->bind_param('i', $tid); $st->execute();
$trow = $st->get_result()->fetch_assoc();
$isPremium = !empty($trow['premium_until']) && strtotime($trow['premium_until']) > time();
$isAdmin   = strtolower($trow['email'] ?? '') === 'heather.admin@firststepreading.com';

// Free accounts are only active Mon–Fri 9 am–4 pm Eastern
if (!$isAdmin && !$isPremium && !withinFreeHours()) {
    ?><!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Access Hours — First Step Reading</title>
    <style>*{box-sizing:border-box;margin:0;padding:0}body{font-family:'Comic Sans MS','Chalkboard SE','Comic Neue',sans-serif;background:#FFF8F0;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:2rem}.card{background:#fff;border-radius:24px;padding:2.5rem 2rem;max-width:480px;width:100%;text-align:center;box-shadow:0 8px 32px rgba(0,0,0,.1)}h1{font-size:1.6rem;color:#1A3A6B;margin-bottom:.6rem}p{color:#555;font-size:.95rem;line-height:1.6;margin:.5rem 0}.hours{background:#FFF7ED;border:2px solid #FCD34D;border-radius:12px;padding:.8rem 1.2rem;margin:1.2rem 0;color:#92400E;font-weight:700}.btn{display:inline-block;margin-top:1.2rem;padding:.8rem 2rem;border-radius:50px;font-size:1rem;text-decoration:none;font-weight:700;background:linear-gradient(135deg,#6B48FF,#3A8EF6);color:#fff;box-shadow:0 4px 14px rgba(107,72,255,.35)}</style>
    </head><body><div class="card">
    <div style="font-size:3rem;margin-bottom:.8rem">🕐</div>
    <h1>Outside School Hours</h1>
    <p>Free teacher accounts are active:</p>
    <div class="hours">Monday – Friday, 9:00 am – 4:00 pm Eastern</div>
    <p>Upgrade to a Teacher License for unlimited 24/7 access.</p>
    <a class="btn" href="https://www.firststepreading.com/checkout/?add-to-cart=28201">🛒 Upgrade — $99/year</a>
    <br><a href="teacher-login.php" style="display:inline-block;margin-top:1rem;color:#6B48FF;font-size:.9rem">← Back to Login</a>
    </div></body></html><?php
    exit;
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
  .badge-premium{background:linear-gradient(135deg,#FEF3C7,#FEE2E2);color:#D97706;border:1.5px solid #FCD34D;font-size:.8rem;padding:.25rem .8rem}
  .banner-free{background:linear-gradient(135deg,#FFF7ED,#FFF0F0);border:2px solid #FCD34D;border-radius:16px;padding:.9rem 1.2rem;margin-bottom:1.4rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.8rem}
  .banner-free p{color:#92400E;font-size:.88rem;font-weight:600}
  .btn-activate{background:linear-gradient(135deg,#F59E0B,#EF4444);color:white;border:none;border-radius:10px;padding:.45rem 1.1rem;font-size:.85rem;font-weight:700;cursor:pointer;font-family:'Comic Sans MS','Chalkboard SE','Comic Neue';text-decoration:none;white-space:nowrap}
  @media(max-width:600px){header{padding:.8rem 1rem}header h1{font-size:1rem}.container{padding:0 .8rem}}
</style>
</head>
<body>
<header>
  <h1>🍎 First Step Reading — Teacher Dashboard</h1>
  <div class="hdr-right">
    <span>👋 <?= htmlspecialchars($teacher['teacher_name']) ?>
      <?php if ($isPremium): ?>
        <span class="badge badge-premium">⭐ Premium — expires <?= date('M j, Y', strtotime($trow['premium_until'])) ?></span>
      <?php elseif ($isAdmin): ?>
        <span class="badge badge-premium">⭐ Admin</span>
      <?php endif; ?>
    </span>
    <?php if ($isAdmin): ?><a class="logout" href="admin-codes.php">🔑 Codes</a><?php endif; ?>
    <a class="logout" href="../hub.html">🎮 Games</a>
    <a class="logout" href="logout.php?teacher=1">🚪 Sign out</a>
  </div>
</header>
<div class="container">
  <?php if ($msg): ?><div class="msg"><?= $msg ?></div><?php endif; ?>
  <?php if (!$isPremium && !$isAdmin): ?>
  <div class="banner-free">
    <p>🟢 Free account — access Mon–Fri, 9 am–4 pm only</p>
    <a class="btn-activate" href="activate.php">⭐ Activate Premium →</a>
  </div>
  <?php endif; ?>

  <!-- Create Class -->
  <div class="card">
    <h2>➕ Create a New Class</h2>
    <div id="create-msg"></div>
    <div class="form-row">
      <div class="form-group">
        <label>Class name</label>
        <input type="text" id="class_name" placeholder="e.g. Mrs. Smith — Grade 1">
      </div>
      <div class="form-group" style="max-width:200px">
        <label>Game access</label>
        <select id="access_type">
          <option value="full">Full access (all games)</option>
          <option value="assigned">Assigned games only</option>
        </select>
      </div>
      <button class="btn" onclick="createClass()">Create Class</button>
    </div>
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
          <a class="btn btn-blue" href="teacher-class.php?id=<?= $c['id'] ?>&t=<?= time() ?>" style="text-decoration:none;font-size:.85rem;padding:.5rem 1rem">Manage →</a>
          <button class="btn btn-red" style="font-size:.85rem;padding:.5rem 1rem" onclick="deleteClass(<?= $c['id'] ?>)">Delete</button>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>
<script>
function fsrAction(action, data) {
  var url = 'teacher-dashboard.php?fsr_ajax=1&action=' + encodeURIComponent(btoa(action))
          + '&data=' + encodeURIComponent(btoa(JSON.stringify(data)));
  return fetch(url).then(function(r) {
    return r.text().then(function(txt) {
      console.log('RAW RESPONSE:', txt);
      try { return JSON.parse(txt); } catch(e) { throw new Error('Not JSON: ' + txt.substring(0,200)); }
    });
  });
}
var _creating = false;
function createClass() {
  if (_creating) return;
  var name = document.getElementById('class_name').value.trim();
  var access = document.getElementById('access_type').value;
  var msg = document.getElementById('create-msg');
  if (!name) { msg.innerHTML = '<p style="color:#c0392b">Please enter a class name.</p>'; return; }
  _creating = true;
  msg.innerHTML = '<p>Creating...</p>';
  fsrAction('create_class', {class_name: name, access_type: access})
    .then(function(d) {
      _creating = false;
      if (d.ok) { location.reload(); } else { msg.innerHTML = '<p style="color:#c0392b">'+d.error+'</p>'; }
    })
    .catch(function(e) { _creating = false; msg.innerHTML = '<p style="color:#c0392b">Error: '+e+'</p>'; });
}
function deleteClass(id) {
  if (!confirm('Delete this class and all student data?')) return;
  fsrAction('delete_class', {class_id: id}).then(function(d) {
    if (d.ok) { location.reload(); }
  });
}
</script>
<?php if (!$isPremium && !$isAdmin): ?>
<div id="fsr-lockout" style="display:none;position:fixed;inset:0;z-index:99999;background:rgba(0,0,0,.7);align-items:center;justify-content:center;padding:1rem">
  <div style="background:#fff;border-radius:24px;padding:2.5rem 2rem;max-width:440px;width:100%;text-align:center;box-shadow:0 8px 40px rgba(0,0,0,.3)">
    <div style="font-size:3rem;margin-bottom:.8rem">🕐</div>
    <h2 style="color:#1A3A6B;margin-bottom:.5rem">School Hours Have Ended</h2>
    <p style="color:#555;font-size:.9rem;line-height:1.6;margin-bottom:1.2rem">Free accounts are active Monday–Friday, 9:00 am–4:00 pm Eastern. Your session has ended.</p>
    <a href="https://www.firststepreading.com/checkout/?add-to-cart=28201" style="display:inline-block;background:linear-gradient(135deg,#6B48FF,#3A8EF6);color:#fff;border-radius:50px;padding:.8rem 2rem;text-decoration:none;font-weight:700;font-size:1rem;box-shadow:0 4px 14px rgba(107,72,255,.35)">🛒 Upgrade — $99/year</a>
    <br><a href="teacher-login.php" style="display:inline-block;margin-top:1rem;color:#6B48FF;font-size:.9rem">← Back to Login</a>
  </div>
</div>
<script>
(function() {
  function checkHours() {
    var now = new Date(new Date().toLocaleString('en-US', {timeZone:'America/Toronto'}));
    var day = now.getDay(); // 0=Sun, 6=Sat
    var h   = now.getHours();
    var active = (day >= 1 && day <= 5 && h >= 9 && h < 16);
    if (!active) {
      var el = document.getElementById('fsr-lockout');
      if (el) { el.style.display = 'flex'; document.body.style.overflow = 'hidden'; }
    }
  }
  checkHours();
  setInterval(checkHours, 60000); // re-check every minute
})();
</script>
<?php endif; ?>
</body>
</html>
