<?php
require_once 'db.php';
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

$teacher = requireTeacher();
$db  = getDB();
$tid = $teacher['teacher_id'];

parse_str(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_QUERY) ?? '', $_QS);

if (!empty($_QS['fsr_ajax'])) {
    header('Content-Type: application/json');
    $code = strtoupper(trim(base64_decode($_QS['code'] ?? '')));
    if (!$code) { echo json_encode(['ok'=>false,'error'=>'Please enter a code.']); exit; }

    // Check if this teacher already has an active premium
    $st = $db->prepare('SELECT premium_until FROM teachers WHERE id=?');
    $st->bind_param('i', $tid); $st->execute();
    $row = $st->get_result()->fetch_assoc();
    if (!empty($row['premium_until']) && strtotime($row['premium_until']) > time()) {
        echo json_encode(['ok'=>false,'error'=>'Your account is already premium until '.date('F j, Y', strtotime($row['premium_until'])).'.']);
        exit;
    }

    // Validate code
    $st = $db->prepare('SELECT id, used_by FROM premium_codes WHERE code=?');
    $st->bind_param('s', $code); $st->execute();
    $codeRow = $st->get_result()->fetch_assoc();

    if (!$codeRow) {
        echo json_encode(['ok'=>false,'error'=>'That code is not valid. Please check and try again.']); exit;
    }
    if ($codeRow['used_by'] !== null) {
        echo json_encode(['ok'=>false,'error'=>'That code has already been used.']); exit;
    }

    // Activate: mark code used, set premium_until = 365 days from now
    $expiry = date('Y-m-d H:i:s', strtotime('+365 days'));
    $st = $db->prepare('UPDATE premium_codes SET used_by=?, activated_at=NOW() WHERE id=?');
    $st->bind_param('ii', $tid, $codeRow['id']); $st->execute();
    $st = $db->prepare('UPDATE teachers SET premium_until=? WHERE id=?');
    $st->bind_param('si', $expiry, $tid); $st->execute();

    echo json_encode(['ok'=>true,'expiry'=>date('F j, Y', strtotime($expiry))]);
    exit;
}

// Load current premium status
$st = $db->prepare('SELECT premium_until FROM teachers WHERE id=?');
$st->bind_param('i', $tid); $st->execute();
$trow = $st->get_result()->fetch_assoc();
$isPremium = !empty($trow['premium_until']) && strtotime($trow['premium_until']) > time();
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Activate Premium — First Step Reading</title>
<style>
  *{box-sizing:border-box;margin:0;padding:0}
  body{
    font-family:'Comic Sans MS','Chalkboard SE','Comic Neue';
    background:linear-gradient(160deg,#F59E0B 0%,#EF4444 100%);
    min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:1.5rem;
  }
  .card{background:white;border-radius:28px;padding:2.4rem 2.2rem 2rem;width:100%;max-width:420px;box-shadow:0 16px 60px rgba(0,0,0,.2);position:relative;z-index:1}
  .logo{text-align:center;margin-bottom:1.6rem}
  .logo-icon{font-size:3rem;display:block;margin-bottom:.3rem}
  .logo h1{font-size:1.65rem;color:#2C3E50}
  .logo p{color:#888;font-size:.88rem;margin-top:.25rem;font-weight:600}
  label{display:block;font-weight:700;color:#EF4444;margin-bottom:.4rem;font-size:.92rem}
  input{
    width:100%;border:2.5px solid #e0e0e0;border-radius:14px;
    padding:.8rem 1rem;font-size:1.2rem;letter-spacing:.12em;text-transform:uppercase;
    font-family:monospace;transition:border-color .2s;margin-bottom:1rem;text-align:center;
  }
  input:focus{outline:none;border-color:#EF4444;box-shadow:0 0 0 3px rgba(239,68,68,.15)}
  .btn{
    width:100%;background:linear-gradient(135deg,#F59E0B,#EF4444);color:white;
    border:none;border-radius:14px;padding:.9rem;font-size:1.05rem;font-weight:700;
    cursor:pointer;font-family:'Comic Sans MS','Chalkboard SE','Comic Neue';
    box-shadow:0 4px 18px rgba(239,68,68,.35);transition:transform .18s,box-shadow .18s;
  }
  .btn:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(239,68,68,.45)}
  .btn:disabled{opacity:.6;cursor:not-allowed;transform:none}
  .error{background:#fff0f0;border:2px solid #ffb3b3;color:#c0392b;border-radius:12px;padding:.7rem 1rem;margin-bottom:1rem;font-size:.9rem;font-weight:600}
  .success{background:#f0fff4;border:2px solid #9de0a8;color:#1a7a3a;border-radius:12px;padding:1rem;margin-bottom:1rem;font-size:.95rem;font-weight:600;text-align:center}
  .already{background:linear-gradient(135deg,#f0fff4,#e8f8ff);border:2.5px solid #9de0a8;border-radius:18px;padding:1.4rem;text-align:center}
  .already .star{font-size:2.5rem;display:block;margin-bottom:.5rem}
  .already h2{color:#27ae60;font-size:1.2rem;margin-bottom:.3rem}
  .already p{color:#555;font-size:.9rem}
  .back{display:block;text-align:center;margin-top:1.2rem;color:#EF4444;font-weight:700;text-decoration:none;font-size:.92rem}
  .back:hover{text-decoration:underline}
  .need-code{margin-top:1.2rem;text-align:center;font-size:.85rem;color:#888}
  .need-code a{color:#EF4444;font-weight:700;text-decoration:none}
</style>
</head>
<body>
<div class="card">
  <div class="logo">
    <span class="logo-icon">⭐</span>
    <h1>Activate Premium</h1>
    <p>First Step Reading — Teacher Account</p>
  </div>

  <?php if ($isPremium): ?>
  <div class="already">
    <span class="star">🏆</span>
    <h2>You're already Premium!</h2>
    <p>Your account is active until<br><strong><?= date('F j, Y', strtotime($trow['premium_until'])) ?></strong></p>
  </div>
  <?php else: ?>
  <div id="error-box" style="display:none" class="error"></div>
  <div id="success-box" style="display:none" class="success"></div>
  <div id="form-area">
    <label>🔑 Enter your activation code</label>
    <input type="text" id="code-input" placeholder="FSR-XXXX-XXXX-XXXX" maxlength="20" autocomplete="off" autocapitalize="characters">
    <button class="btn" id="activate-btn" onclick="activate()">⭐ Activate My Account →</button>
  </div>
  <div class="need-code">
    Don't have a code?
    <a href="https://www.firststepreading.com/checkout/?add-to-cart=28201" target="_blank">Teacher — $99</a> ·
    <a href="https://www.firststepreading.com/checkout/?add-to-cart=28202" target="_blank">School — $499</a>
  </div>
  <?php endif; ?>

  <a class="back" href="teacher-dashboard.php">← Back to Dashboard</a>
</div>
<script>
function activate() {
  var code = document.getElementById('code-input').value.trim();
  var btn  = document.getElementById('activate-btn');
  var err  = document.getElementById('error-box');
  var ok   = document.getElementById('success-box');
  if (!code) { err.textContent = '⚠️ Please enter your activation code.'; err.style.display=''; return; }
  btn.disabled = true; btn.textContent = 'Activating...';
  err.style.display = 'none'; ok.style.display = 'none';

  fetch('activate.php?fsr_ajax=1&code=' + encodeURIComponent(btoa(code)))
    .then(function(r){ return r.json(); })
    .then(function(d) {
      if (d.ok) {
        document.getElementById('form-area').style.display = 'none';
        ok.innerHTML = '🎉 Premium activated! Your account is valid until <strong>' + d.expiry + '</strong>.<br><br><a href="teacher-dashboard.php" style="color:#27ae60;font-weight:700">Go to Dashboard →</a>';
        ok.style.display = '';
      } else {
        err.textContent = '⚠️ ' + d.error;
        err.style.display = '';
        btn.disabled = false; btn.textContent = '⭐ Activate My Account →';
      }
    })
    .catch(function(e){ err.textContent = '⚠️ Connection error.'; err.style.display=''; btn.disabled=false; btn.textContent='⭐ Activate My Account →'; });
}
document.getElementById('code-input') && document.getElementById('code-input').addEventListener('keydown', function(e){ if(e.key==='Enter') activate(); });
</script>
</body>
</html>
