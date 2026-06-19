<?php
require_once 'db.php';

$preCode  = trim($_GET['code'] ?? '');
$preRoom  = trim($_GET['room'] ?? '');
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

$step = 'class-code'; // class-code | student-code
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
            $step = 'student-code';
        }
    } elseif ($action === 'student_icon') {
        $classId = (int)($_POST['class_id'] ?? 0);
        $icon    = trim($_POST['icon'] ?? '');
        if ($classId && $icon) {
            $stC = $db->prepare('SELECT * FROM classes WHERE id=?');
            $stC->bind_param('i', $classId); $stC->execute();
            $class = $stC->get_result()->fetch_assoc();
            if ($class) {
                $stS = $db->prepare('SELECT * FROM students WHERE class_id=? AND icon=? LIMIT 1');
                $stS->bind_param('is', $classId, $icon); $stS->execute();
                $student = $stS->get_result()->fetch_assoc();
                if ($student) {
                    header('Location: student-games.php?student='.$student['id'].'&class='.$classId);
                    exit;
                } else {
                    $error = "That icon wasn't found! Ask your teacher for help.";
                    $step  = 'student-code';
                    $students = loadStudents($classId, $db);
                }
            }
        }
    }
}

if ($class && $step !== 'class-code') $step = 'student-code';

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
    background:white;border-radius:28px;padding:1.6rem 1.5rem 1.4rem;
    width:100%;max-width:460px;
    box-shadow:0 20px 60px rgba(0,0,0,.3);
  }
  .logo{text-align:center;margin-bottom:1.1rem}
  .logo h1{font-size:1.6rem;color:#2c3e50;font-weight:800}
  .logo p{color:#7f8c8d;margin-top:.25rem;font-size:.9rem}
  .stars{font-size:2rem;margin-bottom:.3rem}

  .prompt{
    text-align:center;font-size:1rem;font-weight:700;color:#6B48FF;
    margin-bottom:.8rem;
  }
  .step-badge{
    display:inline-block;background:#6B48FF;color:white;
    border-radius:999px;padding:.15rem .7rem;font-size:.78rem;
    margin-bottom:.3rem;
  }

  /* ── Entered sequence display ── */
  .seq-row{
    display:flex;justify-content:center;gap:.5rem;
    margin-bottom:.9rem;min-height:54px;align-items:center;
  }
  .seq-slot{
    font-size:1.8rem;width:52px;height:52px;border-radius:14px;
    border:3px solid #ddd6ff;background:#faf7ff;
    display:flex;align-items:center;justify-content:center;
    transition:all .15s;flex-shrink:0;
  }
  .seq-slot.single{width:62px;height:62px;font-size:2.2rem;}
  .seq-slot.filled{border-color:#8e44ad;background:#f3e8ff;}
  .seq-slot.shake{animation:shake .4s}
  @keyframes shake{0%,100%{transform:translateX(0)}25%{transform:translateX(-6px)}75%{transform:translateX(6px)}}

  /* ── 24-icon grid (6 cols × 4 rows) ── */
  .icon-grid{
    display:grid;
    grid-template-columns:repeat(6,1fr);
    gap:.38rem;
    margin-bottom:1rem;
  }
  .ic-btn{
    font-size:1.75rem;line-height:1;background:#f3e8ff;border:3px solid transparent;
    border-radius:12px;padding:.42rem .1rem;cursor:pointer;
    transition:all .12s;user-select:none;-webkit-user-select:none;
    touch-action:manipulation;
    aspect-ratio:1;display:flex;align-items:center;justify-content:center;
  }
  .ic-btn:hover{border-color:#8e44ad;background:#ece0ff;transform:scale(1.1)}
  .ic-btn:active{transform:scale(.94)}
  .ic-btn.selected{border-color:#6B48FF;background:#ddd0ff;transform:scale(1.08)}

  .btn-row{display:flex;gap:.6rem;margin-bottom:.6rem}
  .btn{
    flex:1;background:linear-gradient(135deg,#6B48FF,#3A8EF6);color:white;border:none;
    border-radius:14px;padding:.8rem;font-size:1rem;font-weight:800;cursor:pointer;
    font-family:'Comic Sans MS','Chalkboard SE','Comic Neue';
    transition:opacity .15s;touch-action:manipulation;
  }
  .btn:disabled{opacity:.4;cursor:default}
  .btn:not(:disabled):hover{opacity:.9}
  .btn-clear{
    flex:0 0 auto;width:50px;background:#f0f0f0;color:#888;border:none;
    border-radius:14px;padding:.8rem .4rem;font-size:1.1rem;cursor:pointer;
    transition:background .15s;touch-action:manipulation;
  }
  .btn-clear:hover{background:#e0e0e0}
  .error{
    background:#fee;border:2px solid #f99;color:#c0392b;
    border-radius:12px;padding:.7rem 1rem;margin-bottom:.9rem;
    font-size:.9rem;text-align:center;font-weight:700;
  }
  .back-link{
    display:block;text-align:center;margin-top:.9rem;
    color:#764ba2;font-size:.88rem;cursor:pointer;font-weight:600;
  }
  .teacher-link{text-align:center;margin-top:1rem;font-size:.8rem;color:#bdc3c7}
  .teacher-link a{color:#6B48FF;text-decoration:none;font-weight:600}

  @media(max-width:380px){
    .icon-grid{grid-template-columns:repeat(4,1fr);gap:.35rem;}
    .ic-btn{font-size:1.9rem;padding:.5rem .1rem;}
  }
</style>
</head>
<body>
<div class="card">

<?php if ($step === 'student-code' && $class): ?>
  <!-- ══ STEP 2: Tap YOUR icon (1 icon = student code) ══ -->
  <div class="logo">
    <div class="stars">⭐📚⭐</div>
    <h1>Who are you?</h1>
    <p>Tap YOUR icon!</p>
  </div>

  <div class="prompt">
    <span class="step-badge">Step 2 of 2</span><br>
    Tap your icon:
  </div>

  <?php if ($error): ?><div class="error">😕 <?= htmlspecialchars($error) ?></div><?php endif; ?>

  <div class="seq-row">
    <div class="seq-slot single" id="ss0"></div>
  </div>

  <div class="icon-grid" id="studentIconGrid">
    <?php foreach ($icons as $ic): ?>
    <button type="button" class="ic-btn" data-icon="<?= htmlspecialchars($ic, ENT_QUOTES) ?>"
      onclick="pickStudentIcon(this)"><?= $ic ?></button>
    <?php endforeach; ?>
  </div>

  <form method="post" id="studentForm">
    <input type="hidden" name="action"   value="student_icon">
    <input type="hidden" name="class_id" value="<?= (int)$class['id'] ?>">
    <input type="hidden" name="icon"     id="studentIconVal" value="">
    <div class="btn-row">
      <button type="submit" class="btn" id="studentGoBtn" disabled>Go! →</button>
      <button type="button" class="btn-clear" onclick="clearStudentIcon()" title="Delete">⌫</button>
    </div>
  </form>

  <span class="back-link" onclick="location.href='student-login.php'">← Wrong class? Go back</span>

  <script>
  function pickStudentIcon(btn) {
    document.querySelectorAll('.ic-btn').forEach(function(b){ b.classList.remove('selected'); });
    btn.classList.add('selected');
    var ic = btn.dataset.icon;
    var slot = document.getElementById('ss0');
    slot.textContent = ic;
    slot.classList.add('filled');
    document.getElementById('studentIconVal').value = ic;
    document.getElementById('studentGoBtn').disabled = false;
  }
  function clearStudentIcon() {
    document.querySelectorAll('.ic-btn').forEach(function(b){ b.classList.remove('selected'); });
    var slot = document.getElementById('ss0');
    slot.textContent = '';
    slot.classList.remove('filled');
    document.getElementById('studentIconVal').value = '';
    document.getElementById('studentGoBtn').disabled = true;
  }
  </script>

<?php else: ?>
  <!-- ══ STEP 1: Tap class code (4 icons) ══ -->
  <div class="logo">
    <div class="stars">⭐📚⭐</div>
    <h1>Class Login</h1>
    <p>First Step Reading</p>
  </div>

  <div class="prompt">
    <span class="step-badge">Step 1 of 2</span><br>
    Tap your class code (4 icons):
  </div>

  <?php if ($error): ?><div class="error">😕 <?= htmlspecialchars($error) ?></div><?php endif; ?>

  <div class="seq-row" id="seqRow">
    <div class="seq-slot" id="s0"></div>
    <div class="seq-slot" id="s1"></div>
    <div class="seq-slot" id="s2"></div>
    <div class="seq-slot" id="s3"></div>
  </div>

  <div class="icon-grid">
    <?php foreach ($icons as $ic): ?>
    <button type="button" class="ic-btn" data-icon="<?= htmlspecialchars($ic, ENT_QUOTES) ?>"
      onclick="addIcon(this)"><?= $ic ?></button>
    <?php endforeach; ?>
  </div>

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
  function addIcon(btn) {
    if (seq.length >= 4) return;
    seq.push(btn.dataset.icon);
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
    document.getElementById('goBtn').disabled = (seq.length < 4);
  }
  </script>
<?php endif; ?>

</div>
</body>
</html>
