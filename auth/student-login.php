<?php
require_once "db.php";
parse_str(parse_url($_SERVER["REQUEST_URI"] ?? "", PHP_URL_QUERY) ?? "", $_QS);

if (!empty($_QS["fsr_ajax"])) {
    header("Content-Type: application/json");
    $action = $_QS["action"] ?? "";

    if ($action === "check_class") {
        $icons = base64_decode($_QS["icons"] ?? "");
        $db = getDB();
        $st = $db->prepare("SELECT id FROM classes WHERE icon_code=?");
        $st->bind_param("s", $icons); $st->execute();
        $row = $st->get_result()->fetch_assoc();
        if (!$row) { echo json_encode(["ok"=>false,"error"=>"Wrong code — try again!"]); exit; }
        echo json_encode(["ok"=>true,"class_id"=>$row["id"]]);
        exit;
    }

    if ($action === "get_class_by_id") {
        $class_id = (int)($_QS["class_id"] ?? 0);
        $db = getDB();
        $st = $db->prepare("SELECT id FROM classes WHERE id=?");
        $st->bind_param("i", $class_id); $st->execute();
        $row = $st->get_result()->fetch_assoc();
        if (!$row) { echo json_encode(["ok"=>false,"error"=>"Class not found."]); exit; }
        echo json_encode(["ok"=>true,"class_id"=>$row["id"]]);
        exit;
    }

    if ($action === "check_student") {
        $class_id = (int)($_QS["class_id"] ?? 0);
        $icon = base64_decode($_QS["icon"] ?? "");
        $db = getDB();
        $st = $db->prepare("SELECT id, name FROM students WHERE class_id=? AND icon=?");
        $st->bind_param("is", $class_id, $icon); $st->execute();
        $row = $st->get_result()->fetch_assoc();
        if (!$row) { echo json_encode(["ok"=>false,"error"=>"That icon isn't in this class — ask your teacher!"]); exit; }
        echo json_encode(["ok"=>true,"student_id"=>$row["id"],"name"=>$row["name"]]);
        exit;
    }

    echo json_encode(["ok"=>false,"error"=>"Unknown action"]);
    exit;
}

// Check if class_id passed via QR scan
$qrClassId = (int)($_QS["class_id"] ?? 0);
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Class Login — First Step Reading</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{
  font-family:'Segoe UI',sans-serif;
  background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);
  min-height:100vh;display:flex;align-items:center;justify-content:center;padding:1rem;
}
.card{
  background:white;border-radius:24px;padding:2rem 1.8rem;
  width:100%;max-width:520px;box-shadow:0 20px 60px rgba(0,0,0,.25);
}
h1{font-size:1.6rem;color:#2c3e50;font-weight:800;text-align:center;margin-bottom:.25rem}
.subtitle{text-align:center;color:#7f8c8d;margin-bottom:1.2rem;font-size:.95rem}
.stars{font-size:1.8rem;text-align:center;margin-bottom:.4rem}

/* Slots */
.slots{display:flex;justify-content:center;gap:.6rem;margin-bottom:1rem;min-height:3.2rem;align-items:center}
.slot{
  width:3rem;height:3rem;border-radius:12px;border:3px dashed #ccc;
  display:flex;align-items:center;justify-content:center;font-size:1.7rem;
  transition:all .15s;background:#fafafa;
}
.slot.filled{border-color:#764ba2;background:#f0e6ff}

/* Grid */
.icon-grid{
  display:grid;
  grid-template-columns:repeat(6,1fr);
  gap:.45rem;
  margin-bottom:1rem;
}
.icon-btn{
  aspect-ratio:1;border-radius:12px;border:3px solid transparent;
  background:#f5f0ff;cursor:pointer;font-size:1.6rem;
  display:flex;align-items:center;justify-content:center;
  transition:all .12s;user-select:none;-webkit-tap-highlight-color:transparent;
}
.icon-btn:hover{border-color:#764ba2;background:#ede0ff;transform:scale(1.08)}
.icon-btn.selected{border-color:#764ba2;background:#d8c4ff}
.icon-btn.disabled{opacity:.35;pointer-events:none}

.error-msg{
  background:#fee;border:2px solid #f99;color:#c0392b;
  border-radius:10px;padding:.7rem 1rem;margin-bottom:.8rem;
  font-size:.9rem;text-align:center;display:none;
}
.step-label{
  font-weight:800;color:#764ba2;font-size:1rem;
  text-align:center;margin-bottom:.7rem;
}
.back-link{
  display:block;text-align:center;margin-top:.6rem;
  color:#764ba2;font-size:.88rem;cursor:pointer;font-weight:600;
}
.teacher-link{text-align:center;margin-top:1rem;font-size:.8rem;color:#bdc3c7}
.teacher-link a{color:#764ba2;text-decoration:none;font-weight:600}

/* QR scanner button */
.qr-btn{
  display:flex;align-items:center;justify-content:center;gap:.5rem;
  width:100%;margin-bottom:.8rem;padding:.65rem;
  background:linear-gradient(135deg,#667eea,#764ba2);color:white;
  border:none;border-radius:14px;font-size:1rem;font-weight:700;cursor:pointer;
  font-family:'Segoe UI',sans-serif;box-shadow:0 3px 12px rgba(118,75,162,.35);
  transition:transform .15s,box-shadow .15s;
}
.qr-btn:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(118,75,162,.45)}

/* Camera modal */
.cam-overlay{
  position:fixed;inset:0;background:rgba(0,0,0,.85);
  display:flex;flex-direction:column;align-items:center;justify-content:center;
  z-index:999;padding:1rem;
}
.cam-box{
  background:white;border-radius:20px;padding:1.2rem;max-width:360px;width:100%;
  box-shadow:0 20px 60px rgba(0,0,0,.5);
}
.cam-box h2{color:#764ba2;font-size:1.1rem;text-align:center;margin-bottom:.8rem}
#cam-video{width:100%;border-radius:12px;display:block;background:#000}
#cam-canvas{display:none}
.cam-status{text-align:center;font-size:.88rem;color:#7f8c8d;margin-top:.6rem;min-height:1.2rem}
.cam-close{
  display:block;width:100%;margin-top:.8rem;padding:.6rem;
  background:#f0f0f0;border:none;border-radius:10px;
  font-size:.95rem;font-weight:700;cursor:pointer;color:#555;
}
.cam-close:hover{background:#e0e0e0}
.cam-success{color:#27ae60!important;font-weight:700}

@media(max-width:379px){
  .icon-grid{grid-template-columns:repeat(4,1fr)}
  .icon-btn{font-size:1.4rem}
  .slot{width:2.6rem;height:2.6rem;font-size:1.5rem}
}
</style>
</head>
<body>
<div class="card">

  <!-- STEP 1: Class code (4 icons) -->
  <div id="step1">
    <div class="stars">⭐📚⭐</div>
    <h1>Class Login</h1>
    <div class="subtitle">First Step Reading</div>
    <div class="step-label">Tap your 4 class icons in order</div>
    <div class="slots" id="slots1">
      <div class="slot" id="s1-0"></div>
      <div class="slot" id="s1-1"></div>
      <div class="slot" id="s1-2"></div>
      <div class="slot" id="s1-3"></div>
    </div>
    <div class="error-msg" id="err1"></div>
    <button class="qr-btn" onclick="openCamera()">📷 Scan QR Code</button>
    <div class="icon-grid" id="grid1"></div>
    <span class="back-link" id="clear1" style="display:none" onclick="resetStep1()">✕ Clear</span>
    <div class="teacher-link">Are you a teacher? <a href="teacher-login.php">Teacher login →</a></div>
  </div>

  <!-- STEP 2: Personal icon (1 icon) -->
  <div id="step2" style="display:none">
    <div class="stars">👋</div>
    <h1>Who are you?</h1>
    <div class="subtitle">Tap your personal icon!</div>
    <div class="error-msg" id="err2"></div>
    <div class="icon-grid" id="grid2"></div>
    <span class="back-link" onclick="goBack()">← Wrong class? Go back</span>
  </div>

</div>

<!-- Camera overlay -->
<div class="cam-overlay" id="cam-overlay" style="display:none">
  <div class="cam-box">
    <h2>📷 Scan Class QR Code</h2>
    <video id="cam-video" autoplay playsinline muted></video>
    <canvas id="cam-canvas"></canvas>
    <div class="cam-status" id="cam-status">Point camera at the class QR code…</div>
    <button class="cam-close" onclick="closeCamera()">✕ Cancel</button>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
<script>
var ICONS = [
  '🐶','🐱','🐸','🐧','🦁','🦊',
  '🍎','🍋','🍇','🍓','🍕','🍦',
  '⭐','🌈','🌞','🌸','🌙','🌊',
  '🎈','🚀','🎵','🎀','🏆','💎'
];

var step1Picks = [];
var currentClassId = null;
var camStream = null;
var camAnimFrame = null;

function buildGrid(containerId, clickFn) {
  var grid = document.getElementById(containerId);
  grid.innerHTML = '';
  ICONS.forEach(function(icon) {
    var btn = document.createElement('button');
    btn.className = 'icon-btn';
    btn.textContent = icon;
    btn.dataset.icon = icon;
    btn.addEventListener('click', function(){ clickFn(icon, btn); });
    grid.appendChild(btn);
  });
}

// ── Step 1 ──────────────────────────────────────────────
buildGrid('grid1', onStep1Pick);

function onStep1Pick(icon, btn) {
  if (step1Picks.length >= 4) return;
  step1Picks.push(icon);
  btn.classList.add('selected');
  var slot = document.getElementById('s1-' + (step1Picks.length - 1));
  slot.textContent = icon;
  slot.classList.add('filled');
  document.getElementById('clear1').style.display = '';
  document.getElementById('err1').style.display = 'none';
  if (step1Picks.length === 4) {
    document.querySelectorAll('#grid1 .icon-btn').forEach(function(b){ b.classList.add('disabled'); });
    checkClass(step1Picks.join(','));
  }
}

function checkClass(iconCode) {
  var encoded = btoa(unescape(encodeURIComponent(iconCode)));
  var url = 'student-login.php?fsr_ajax=1&action=check_class&icons=' + encodeURIComponent(encoded);
  fetch(url).then(function(r){ return r.json(); }).then(function(d) {
    if (d.ok) {
      currentClassId = d.class_id;
      showStep2();
    } else {
      document.getElementById('err1').textContent = d.error;
      document.getElementById('err1').style.display = 'block';
      resetStep1();
    }
  }).catch(function(){
    document.getElementById('err1').textContent = 'Connection error — please try again.';
    document.getElementById('err1').style.display = 'block';
    resetStep1();
  });
}

function checkClassById(classId) {
  var url = 'student-login.php?fsr_ajax=1&action=get_class_by_id&class_id=' + classId;
  fetch(url).then(function(r){ return r.json(); }).then(function(d) {
    if (d.ok) {
      currentClassId = d.class_id;
      showStep2();
    } else {
      document.getElementById('err1').textContent = d.error || 'QR code not recognised.';
      document.getElementById('err1').style.display = 'block';
    }
  }).catch(function(){
    document.getElementById('err1').textContent = 'Connection error — please try again.';
    document.getElementById('err1').style.display = 'block';
  });
}

function resetStep1() {
  step1Picks = [];
  document.querySelectorAll('#grid1 .icon-btn').forEach(function(b){
    b.classList.remove('selected','disabled');
  });
  for (var i = 0; i < 4; i++) {
    var s = document.getElementById('s1-' + i);
    s.textContent = '';
    s.classList.remove('filled');
  }
  document.getElementById('clear1').style.display = 'none';
}

function showStep2() {
  document.getElementById('step1').style.display = 'none';
  document.getElementById('step2').style.display = '';
  buildGrid('grid2', onStep2Pick);
}

// ── Step 2 ──────────────────────────────────────────────
function onStep2Pick(icon, btn) {
  document.querySelectorAll('#grid2 .icon-btn').forEach(function(b){ b.classList.add('disabled'); });
  document.getElementById('err2').style.display = 'none';
  var encoded = btoa(unescape(encodeURIComponent(icon)));
  var url = 'student-login.php?fsr_ajax=1&action=check_student'
          + '&class_id=' + currentClassId
          + '&icon=' + encodeURIComponent(encoded);
  fetch(url).then(function(r){ return r.json(); }).then(function(d) {
    if (d.ok) {
      window.location.href = 'student-games.php?student=' + d.student_id + '&class=' + currentClassId;
    } else {
      document.getElementById('err2').textContent = d.error;
      document.getElementById('err2').style.display = 'block';
      document.querySelectorAll('#grid2 .icon-btn').forEach(function(b){ b.classList.remove('disabled'); });
    }
  }).catch(function(){
    document.getElementById('err2').textContent = 'Connection error — please try again.';
    document.getElementById('err2').style.display = 'block';
    document.querySelectorAll('#grid2 .icon-btn').forEach(function(b){ b.classList.remove('disabled'); });
  });
}

function goBack() {
  currentClassId = null;
  document.getElementById('step2').style.display = 'none';
  document.getElementById('step1').style.display = '';
  document.getElementById('err2').style.display = 'none';
  resetStep1();
}

// ── QR Scanner ──────────────────────────────────────────
function openCamera() {
  document.getElementById('cam-overlay').style.display = 'flex';
  document.getElementById('cam-status').textContent = 'Starting camera…';
  document.getElementById('cam-status').className = 'cam-status';
  navigator.mediaDevices.getUserMedia({video:{facingMode:'environment'}})
    .then(function(stream) {
      camStream = stream;
      var vid = document.getElementById('cam-video');
      vid.srcObject = stream;
      vid.play();
      document.getElementById('cam-status').textContent = 'Point camera at the class QR code…';
      scanFrame();
    })
    .catch(function(err) {
      document.getElementById('cam-status').textContent = 'Camera not available: ' + err.message;
    });
}

function closeCamera() {
  if (camAnimFrame) { cancelAnimationFrame(camAnimFrame); camAnimFrame = null; }
  if (camStream) { camStream.getTracks().forEach(function(t){ t.stop(); }); camStream = null; }
  document.getElementById('cam-overlay').style.display = 'none';
  document.getElementById('cam-video').srcObject = null;
}

function scanFrame() {
  var vid = document.getElementById('cam-video');
  var canvas = document.getElementById('cam-canvas');
  if (vid.readyState === vid.HAVE_ENOUGH_DATA) {
    canvas.width = vid.videoWidth;
    canvas.height = vid.videoHeight;
    var ctx = canvas.getContext('2d');
    ctx.drawImage(vid, 0, 0, canvas.width, canvas.height);
    var imgData = ctx.getImageData(0, 0, canvas.width, canvas.height);
    var code = jsQR(imgData.data, imgData.width, imgData.height, {inversionAttempts:'dontInvert'});
    if (code) {
      handleQRData(code.data);
      return;
    }
  }
  camAnimFrame = requestAnimationFrame(scanFrame);
}

function handleQRData(raw) {
  var statusEl = document.getElementById('cam-status');
  // Parse class_id from URL like: .../student-login.php?class_id=5
  var match = raw.match(/[?&]class_id=(\d+)/);
  if (match) {
    statusEl.textContent = '✅ QR code found!';
    statusEl.className = 'cam-status cam-success';
    setTimeout(function() {
      closeCamera();
      checkClassById(parseInt(match[1]));
    }, 600);
  } else {
    statusEl.textContent = 'Not a class QR code — try again.';
    camAnimFrame = requestAnimationFrame(scanFrame);
  }
}

// ── Auto-login if class_id in URL (QR redirect) ─────────
(function() {
  var qrCid = <?= $qrClassId ?>;
  if (qrCid) { checkClassById(qrCid); }
})();
</script>
</body>
</html>
