<?php
require_once 'db.php';
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

$teacher = requireTeacher();
$db  = getDB();
$tid = $teacher['teacher_id'];

// Admin only
$st = $db->prepare('SELECT email FROM teachers WHERE id=?');
$st->bind_param('i', $tid); $st->execute();
$trow = $st->get_result()->fetch_assoc();
if (strtolower($trow['email'] ?? '') !== 'heather.admin@firststepreading.com') {
    echo '<script>window.location.href="teacher-dashboard.php";</script>'; exit;
}

parse_str(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_QUERY) ?? '', $_QS);

if (!empty($_QS['fsr_ajax'])) {
    header('Content-Type: application/json');
    $action = $_QS['action'] ?? '';

    if ($action === 'generate') {
        $qty = max(1, min(50, (int)($_QS['qty'] ?? 1)));
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $generated = [];
        for ($i = 0; $i < $qty; $i++) {
            // Format: FSR-XXXX-XXXX-XXXX
            do {
                $parts = [];
                for ($p = 0; $p < 3; $p++) {
                    $seg = '';
                    for ($c = 0; $c < 4; $c++) $seg .= $chars[random_int(0, strlen($chars)-1)];
                    $parts[] = $seg;
                }
                $code = 'FSR-' . implode('-', $parts);
                $st = $db->prepare('INSERT IGNORE INTO premium_codes (code) VALUES (?)');
                $st->bind_param('s', $code); $st->execute();
            } while ($st->affected_rows === 0);
            $generated[] = $code;
        }
        echo json_encode(['ok'=>true,'codes'=>$generated]);
        exit;
    }

    if ($action === 'list') {
        $res = $db->query('SELECT pc.id, pc.code, pc.created_at, pc.activated_at, t.name AS teacher_name, t.email AS teacher_email, t.premium_until FROM premium_codes pc LEFT JOIN teachers t ON t.id=pc.used_by ORDER BY pc.created_at DESC LIMIT 200');
        echo json_encode(['ok'=>true,'codes'=>$res->fetch_all(MYSQLI_ASSOC)]);
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_QS['id'] ?? 0);
        $st = $db->prepare('DELETE FROM premium_codes WHERE id=? AND used_by IS NULL');
        $st->bind_param('i', $id); $st->execute();
        echo json_encode(['ok'=>true,'deleted'=>$st->affected_rows]);
        exit;
    }

    echo json_encode(['ok'=>false,'error'=>'Unknown action']);
    exit;
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin — Premium Codes</title>
<style>
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:'Comic Sans MS','Chalkboard SE','Comic Neue';background:#FFF8F0;min-height:100vh}
  header{background:linear-gradient(135deg,#F59E0B,#EF4444);color:white;padding:1rem 2rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.5rem;box-shadow:0 4px 20px rgba(239,68,68,.3)}
  header h1{font-size:1.1rem}
  .back{color:white;text-decoration:none;font-size:.85rem;font-weight:700;background:rgba(255,255,255,.22);border-radius:20px;padding:.3rem .9rem;border:2px solid rgba(255,255,255,.4)}
  .container{max-width:900px;margin:2rem auto;padding:0 1.2rem}
  .card{background:white;border-radius:22px;padding:1.6rem;box-shadow:0 6px 24px rgba(0,0,0,.09);margin-bottom:1.6rem;border:2px solid #fde8d0}
  h2{color:#EF4444;margin-bottom:1rem;font-size:1.1rem}
  .gen-row{display:flex;gap:.8rem;align-items:center;flex-wrap:wrap}
  input[type=number]{border:2.5px solid #e0e0e0;border-radius:12px;padding:.55rem .9rem;font-size:1rem;font-family:'Comic Sans MS','Chalkboard SE','Comic Neue';width:90px}
  .btn{background:linear-gradient(135deg,#F59E0B,#EF4444);color:white;border:none;border-radius:12px;padding:.6rem 1.3rem;font-size:.92rem;font-weight:700;cursor:pointer;font-family:'Comic Sans MS','Chalkboard SE','Comic Neue';box-shadow:0 3px 12px rgba(239,68,68,.25);transition:transform .15s}
  .btn:hover{transform:translateY(-2px)}
  .btn:disabled{opacity:.55;cursor:not-allowed;transform:none}
  .btn-sm{padding:.3rem .7rem;font-size:.78rem}
  .btn-copy{background:linear-gradient(135deg,#6B48FF,#3A8EF6)!important}
  .btn-del{background:linear-gradient(135deg,#FF6B6B,#e74c3c)!important}
  .code-list{margin-top:1rem}
  .new-codes{background:#f0fff4;border:2px solid #9de0a8;border-radius:14px;padding:1rem;margin-top:.8rem}
  .new-codes h3{color:#27ae60;margin-bottom:.6rem;font-size:.95rem}
  .code-item{font-family:monospace;font-size:1.1rem;font-weight:700;letter-spacing:.08em;color:#2C3E50;background:#f8f8ff;border-radius:8px;padding:.3rem .7rem;display:inline-block;margin:.2rem}
  table{width:100%;border-collapse:collapse;font-size:.88rem}
  th{background:linear-gradient(135deg,#fff3e0,#ffe8e8);color:#EF4444;padding:.7rem .8rem;text-align:left;border-bottom:2px solid #fde8d0;font-weight:700}
  td{padding:.6rem .8rem;border-bottom:1px solid #fdf0e8;vertical-align:middle}
  tr:hover td{background:#fff8f4}
  .badge-used{background:#e8f8ff;color:#2980b9;border-radius:20px;padding:.18rem .65rem;font-size:.76rem;font-weight:700;border:1.5px solid #b3deff}
  .badge-free{background:#f0fff4;color:#27ae60;border-radius:20px;padding:.18rem .65rem;font-size:.76rem;font-weight:700;border:1.5px solid #9de0a8}
  .empty{color:#aaa;text-align:center;padding:1.5rem}
  @media(max-width:600px){.container{padding:0 .8rem}header{padding:.8rem 1rem}}
</style>
</head>
<body>
<header>
  <h1>⭐ Premium Code Manager</h1>
  <div style="display:flex;gap:.5rem">
    <a class="back" href="teacher-dashboard.php">← Dashboard</a>
  </div>
</header>
<div class="container">

  <!-- Generate -->
  <div class="card">
    <h2>🔑 Generate Activation Codes</h2>
    <p style="font-size:.88rem;color:#888;margin-bottom:.9rem">Generate codes to send to teachers after they purchase. Each code activates 365 days of premium access.</p>
    <div class="gen-row">
      <label style="font-weight:700;color:#EF4444;font-size:.9rem">How many?</label>
      <input type="number" id="qty-input" value="1" min="1" max="50">
      <button class="btn" id="gen-btn" onclick="generateCodes()">Generate Codes</button>
    </div>
    <div id="new-codes-box" style="display:none" class="new-codes">
      <h3>✅ New codes — copy and send to the buyer:</h3>
      <div id="new-codes-list"></div>
      <button class="btn btn-copy" style="margin-top:.8rem;font-size:.82rem;padding:.4rem 1rem" onclick="copyAll()">📋 Copy All Codes</button>
    </div>
  </div>

  <!-- All codes -->
  <div class="card">
    <h2>📋 All Codes</h2>
    <div id="codes-table"><div class="empty">Loading…</div></div>
  </div>
</div>

<script>
function generateCodes() {
  var qty = parseInt(document.getElementById('qty-input').value) || 1;
  var btn = document.getElementById('gen-btn');
  btn.disabled = true; btn.textContent = 'Generating…';
  fetch('admin-codes.php?fsr_ajax=1&action=generate&qty=' + qty)
    .then(function(r){ return r.json(); })
    .then(function(d) {
      btn.disabled = false; btn.textContent = 'Generate Codes';
      if (!d.ok) { alert('Error generating codes'); return; }
      var box = document.getElementById('new-codes-box');
      var list = document.getElementById('new-codes-list');
      list.innerHTML = d.codes.map(function(c){ return '<span class="code-item">'+c+'</span>'; }).join('');
      box.style.display = '';
      loadCodes();
    })
    .catch(function(){ btn.disabled=false; btn.textContent='Generate Codes'; alert('Error'); });
}

function copyAll() {
  var codes = Array.from(document.querySelectorAll('#new-codes-list .code-item')).map(function(el){ return el.textContent; });
  navigator.clipboard.writeText(codes.join('\n')).then(function(){
    alert('Codes copied to clipboard!');
  }).catch(function(){
    prompt('Copy these codes:', codes.join('\n'));
  });
}

function loadCodes() {
  fetch('admin-codes.php?fsr_ajax=1&action=list')
    .then(function(r){ return r.json(); })
    .then(function(d) {
      var box = document.getElementById('codes-table');
      if (!d.codes || !d.codes.length) { box.innerHTML = '<div class="empty">No codes yet.</div>'; return; }
      var rows = d.codes.map(function(c) {
        var status = c.used_by
          ? '<span class="badge-used">Used by ' + (c.teacher_name||'unknown') + '</span>'
          : '<span class="badge-free">Available</span>';
        var del = !c.used_by
          ? '<button class="btn btn-del btn-sm" onclick="deleteCode('+c.id+')">Delete</button>'
          : '';
        var activated = c.activated_at ? c.activated_at.substring(0,10) : '—';
        var expires = c.premium_until ? c.premium_until.substring(0,10) : '—';
        return '<tr><td><span style="font-family:monospace;font-weight:700;font-size:.95rem">'+c.code+'</span></td>'
             + '<td>'+status+'</td>'
             + '<td>'+(c.teacher_email||'—')+'</td>'
             + '<td>'+activated+'</td>'
             + '<td>'+expires+'</td>'
             + '<td>'+del+'</td></tr>';
      });
      box.innerHTML = '<table><thead><tr><th>Code</th><th>Status</th><th>Teacher Email</th><th>Activated</th><th>Expires</th><th></th></tr></thead><tbody>'
        + rows.join('') + '</tbody></table>';
    });
}

function deleteCode(id) {
  if (!confirm('Delete this unused code?')) return;
  fetch('admin-codes.php?fsr_ajax=1&action=delete&id=' + id)
    .then(function(r){ return r.json(); })
    .then(function(d){ if (d.ok) loadCodes(); });
}

loadCodes();
</script>
</body>
</html>
