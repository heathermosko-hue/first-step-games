<?php
require_once 'db.php';

$preCode  = trim($_GET['code'] ?? '');   // legacy text code from old QR
$preRoom  = trim($_GET['room'] ?? '');   // class_code from new QR
$error    = '';
$class    = null;
$students = [];

function loadClassByCode($code, $db) {
    $st = $db->prepare('SELECT * FROM classes WHERE class_code=?');
    $st->bind_param('s', $code); $st->execute();
    return $st->get_result()->fetch_assoc();
}
function loadClassByIconCode($iconCode, $db) {
    $st = $db->prepare('SELECT * FROM classes WHERE icon_code=?');
    $st->bind_param('s', $iconCode); $st->execute();
    return $st->get_result()->fetch_assoc();
}
function loadStudents($classId, $db) {
    $st = $db->prepare('SELECT * FROM students WHERE class_id=? ORDER BY name');
    $st->bind_param('i', $classId); $st->execute();
    return $st->get_result()->fetch_all(MYSQLI_ASSOC);
}

// QR scan → pre-fill class by legacy code
if ($preCode || $preRoom) {
    $db = getDB();
    $lookup = $preRoom ?: $preCode;
    $class  = loadClassByCode($lookup, $db);
    if ($class) $students = loadStudents($class['id'], $db);
}

// Icon combo submission
$step = 'icon-entry'; // icon-entry | pick-student
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = getDB();
    $action = $_POST['action'] ?? '';
    if ($action === 'icon_code') {
        $submitted = implode(',', array_slice($_POST['icons'] ?? [], 0, 4));
        $class = loadClassByIconCode($submitted, $db);
        if (!$class) {
            $error = 'That code was not right! Try again.';
        } else {
            $students = loadStudents($class['id'], $db);
            $step = 'pick-student';
        }
    }
}
if ($class && !empty($students) && $step !== 'icon-entry') $step = 'pick-student';

$icons = ICON_CHOICES;
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,user-scalable=no">
<title>Class Login — First Step Reading</title>
<link href="../fonts.css?v=3" rel="stylesheet">
<style>
  *{box-sizing:border-box;margin:0;padding:0}
  body{
    font-family:'Comic Sans MS','Chalkboard SE','Comic Neue';
    background:linear-gradient(135deg,#6B48FF 0%,#3A8EF6 100%);
    min-height:100vh;display:flex;align-items:center;justify-content:center;padding:1rem;
  }
  .card{
    background:white;border-radius:28px;padding:2rem 1.8rem;
    width:100%;max-width:420px;
    box-shadow:0 20px 60px rgba(0,0,0,.3);
  }
  .logo{text-align:center;margin-bottom:1.6rem}
  .logo h1{font-size:1.7rem;color:#2c3e50;font-weight:800}
  .logo p{color:#7f8c8d;margin-top:.3rem;font-size:.95rem}
  .stars{font-size:2.2rem;margin-bottom:.4rem}

  /* ── Icon grid for combo entry ── */
  .prompt{text-align:center;font-size:1.05rem;font-weight:700;color:#6B48FF;margin-bottom:1rem}
  .icon-grid{
    display:grid;grid-template-columns:repeat(4,1fr);gap:.55rem;margin-bottom:1.4rem;
  }
  .ic-btn{
    font-size:2.2rem;line-height:1;background:#f3e8ff;border:3px solid transparent;
    border-radius:16px;padding:.55rem .2rem;cursor:pointer;
    transition:all .12s;user-select:none;-webkit-user-select:none;
    touch-action:manipulation;
  }
  .ic-btn:hover{border-color:#8e44ad;background:#ece0ff;transform:scale(1.08)}
  .ic-btn:active{transform:scale(.96)}

  /* ── Entered sequence display ── */
  .seq-row{display:flex;justify-content:center;gap:.55rem;margin-bottom:1.2rem;min-height:58px;align-items:center}
  .seq-slot{
    font-size:2rem;width:56px;height:56px;border-radius:14px;
    border:3px solid #ddd6ff;background:#faf7ff;
    display:flex;align-items:center;justify-content:center;
    transition:all .15s;
  }
  .seq-slot.filled{border-color:#8e44ad;background:#f3e8ff;}
  .seq-slot.shake{animation:shake .4s}
  @keyframes shake{0%,100%{transform:translateX(0)}25%{transform:translateX(-6px)}75%{transform:translateX(6px)}}

  .btn-row{display:flex;gap:.7rem;margin-bottom:.8rem}
  .btn{
    flex:1;background:linear-gradient(135deg,#6B48FF,#3A8EF6);color:white;border:none;
    border-radius:14px;padding:.85rem;font-size:1rem;font-weight:800;cursor:pointer;
    font-family:'Comic Sans MS','Chalkboard SE','Comic Neue';
    transition:opacity .15s;touch-action:manipulation;
  }
  .btn:hover{opacity:.9}
  .btn-clear{
    flex:0 0 auto;width:52px;background:#f0f0f0;color:#888;border:none;
    border-radius:14px;padding:.85rem .5rem;font-size:1.1rem;cursor:pointer;
    transition:background .15s;touch-action:manipulation;
  }
  .btn-clear:hover{background:#e0e0e0}
  .error{background:#fee;border:2px solid #f99;color:#c0392b;border-radius:12px;padding:.75rem 1rem;margin-bottom:1rem;font-size:.95rem;text-align:center;font-weight:700}

  /* ── Student icon grid ── */
  .pick-section h2{font-size:1.3rem;color:#2c3e50;text-align:center;margin-bottom:.3rem;font-weight:800}
  .pick-section p{text-align:center;color:#7f8c8d;margin-bottom:1.2rem;font-size:.95rem}
  .student-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:.6rem}
  .student-card{
    display:flex;flex-direction:column;align-items:center;
    background:#f8f0ff;border:3px solid transparent;border-radius:16px;
    padding:.75rem .35rem;cursor:pointer;transition:all .15s;
    text-decoration:none;touch-action:manipulation;
  }
  .student-card:hover{border-color:#764ba2;background:#f0e6ff;transform:scale(1.06)}
  .student-card .s-icon{font-size:2.4rem;line-height:1}
  .student-card .s-name{font-size:.7rem;font-weight:700;color:#5a3e7a;margin-top:.3rem;text-align:center;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:70px}

  .back-link{display:block;text-align:center;margin-top:1rem;color:#764ba2;font-size:.9rem;cursor:pointer;font-weight:600}
  .teacher-link{text-align:center;margin-top:1.4rem;font-size:.82rem;color:#bdc3c7}
  .teacher-link a{color:#6B48FF;text-decoration:none;font-weight:600}
</style>
</head>
<body>
<div class="card">

<?php if ($step === 'pick-student' && $class): ?>
  <!-- Step 2: Pick your icon -->
  <div class="pick-section">
    <h2>Who are you? 👋</h2>
    <p>Tap your animal!</p>
    <?php if (!$students): ?>
      <div class="error">No students yet — ask your teacher to add you!</div>
    <?php else: ?>
    <div class="student-grid">
      <?php foreach ($students as $s): ?>
      <a class="student-card" href="student-games.php?student=<?= $s['id'] ?>&class=<?= $class['id'] ?>">
        <span class="s-icon"><?= $s['icon'] ?></span>
        <span class="s-name"><?= htmlspecialchars($s['name']) ?></span>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <span class="back-link" onclick="location.reload()">← Wrong class? Go back</span>
  </div>

<?php else: ?>
  <!-- Step 1: Tap 4-icon class code -->
  <div class="logo">
    <div class="stars">⭐📚⭐</div>
    <h1>Class Login</h1>
    <p>First Step Reading</p>
  </div>
  <?php if ($error): ?><div class="error">😕 <?= htmlspecialchars($error) ?></div><?php endif; ?>

  <div class="prompt">Tap your class code (4 icons):</div>

  <!-- Entered sequence -->
  <div class="seq-row" id="seqRow">
    <div class="seq-slot" id="s0"></div>
    <div class="seq-slot" id="s1"></div>
    <div class="seq-slot" id="s2"></div>
    <div class="seq-slot" id="s3"></div>
  </div>

  <!-- Icon picker grid -->
  <div class="icon-grid">
    <?php foreach ($icons as $ic): ?>
    <button type="button" class="ic-btn" onclick="addIcon('<?= $ic ?>')"><?= $ic ?></button>
    <?php endforeach; ?>
  </div>

  <!-- Submit + Clear -->
  <form method="post" id="iconForm">
    <input type="hidden" name="action" value="icon_code">
    <input type="hidden" name="icons[]" id="i0" value="">
    <input type="hidden" name="icons[]" id="i1" value="">
    <input type="hidden" name="icons[]" id="i2" value="">
    <input type="hidden" name="icons[]" id="i3" value="">
    <div class="btn-row">
      <button type="submit" class="btn" id="goBtn" disabled>Go! →</button>
      <button type="button" class="btn-clear" onclick="clearLast()" title="Delete">⌫</button>
    </div>
  </form>

  <div class="teacher-link">Are you a teacher? <a href="teacher-login.php">Teacher login →</a></div>

  <script>
  var seq = [];
  function addIcon(ic) {
    if (seq.length >= 4) return;
    seq.push(ic);
    render();
  }
  function clearLast() {
    seq.pop();
    render();
  }
  function render() {
    for (var i = 0; i < 4; i++) {
      var slot = document.getElementById('s'+i);
      var inp  = document.getElementById('i'+i);
      if (seq[i]) {
        slot.textContent = seq[i];
        slot.classList.add('filled');
        inp.value = seq[i];
      } else {
        slot.textContent = '';
        slot.classList.remove('filled');
        inp.value = '';
      }
    }
    document.getElementById('goBtn').disabled = seq.length < 4;
  }
  </script>
<?php endif; ?>

</div>
</body>
</html>
