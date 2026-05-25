<?php
require_once 'db.php';
$db = getDB();

$sid = (int)($_GET['student'] ?? 0);
$cid = (int)($_GET['class']   ?? 0);

// Validate student exists in class
$st = $db->prepare('SELECT s.*, c.name AS class_name, c.access_type FROM students s JOIN classes c ON c.id=s.class_id WHERE s.id=? AND s.class_id=?');
$st->bind_param('ii',$sid,$cid); $st->execute();
$student = $st->get_result()->fetch_assoc();
if (!$student) { header('Location: student-login.php'); exit; }

// Set cookie so games know who's logged in
setStudentCookie($sid, $cid, $student['name']);

// Load accessible games
if ($student['access_type']==='full') {
    $games = GAMES;
} else {
    $st2 = $db->prepare('SELECT game_slug FROM assigned_games WHERE class_id=?');
    $st2->bind_param('i',$cid); $st2->execute();
    $slugs = array_column($st2->get_result()->fetch_all(MYSQLI_ASSOC),'game_slug');
    $games = array_filter(GAMES, fn($s)=>in_array($s,$slugs), ARRAY_FILTER_USE_KEY);
}

// Load progress
$st3 = $db->prepare('SELECT game_slug,plays FROM progress WHERE student_id=?');
$st3->bind_param('i',$sid); $st3->execute();
$prog = [];
foreach ($st3->get_result()->fetch_all(MYSQLI_ASSOC) as $r) $prog[$r['game_slug']]=$r['plays'];

$gameFiles = [
    'bubble-pop'    => '../bubble-pop.html',
    'phonics-bingo' => '../phonics-bingo.html',
    'pinball'       => '../pinball.html',
    'digraphs'      => '../digraphs.html',
    'word-family'   => '../word-family.html',
    'r-controlled'  => '../r-controlled.html',
    'spell-it'      => '../spell-it.html',
];
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars($student['name']) ?>'s Games — First Step Reading</title>
<style>
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:'Segoe UI',sans-serif;background:linear-gradient(135deg,#f5f7fa 0%,#c3cfe2 100%);min-height:100vh}
  header{background:linear-gradient(135deg,#667eea,#764ba2);color:white;padding:1.2rem 2rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.5rem}
  .hello{display:flex;align-items:center;gap:.8rem}
  .hello .animal{font-size:2.2rem}
  .hello h1{font-size:1.2rem;font-weight:800}
  .hello p{font-size:.85rem;opacity:.85}
  .logout{color:rgba(255,255,255,.7);text-decoration:none;font-size:.85rem;border:1px solid rgba(255,255,255,.4);border-radius:20px;padding:.3rem .8rem}
  .container{max-width:800px;margin:2rem auto;padding:0 1rem}
  h2{color:#4a3a7a;font-size:1.1rem;margin-bottom:1.2rem;text-align:center}
  .game-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:1.2rem}
  .game-card{background:white;border-radius:20px;padding:1.5rem 1rem;text-align:center;text-decoration:none;box-shadow:0 4px 20px rgba(0,0,0,.08);transition:all .2s;border:3px solid transparent;display:block}
  .game-card:hover{transform:translateY(-4px);box-shadow:0 8px 30px rgba(0,0,0,.15);border-color:#764ba2}
  .game-card .emoji{font-size:3rem;display:block;margin-bottom:.6rem}
  .game-card h3{color:#2c3e50;font-size:1rem;font-weight:700}
  .game-card .plays{font-size:.78rem;color:#27ae60;font-weight:600;margin-top:.35rem}
  .game-card .plays.none{color:#bdc3c7}
  .no-games{text-align:center;color:#7f8c8d;padding:3rem;font-size:1rem}
</style>
</head>
<body>
<header>
  <div class="hello">
    <span class="animal"><?= $student['icon'] ?></span>
    <div>
      <h1>Hi, <?= htmlspecialchars($student['name']) ?>! 👋</h1>
      <p><?= htmlspecialchars($student['class_name']) ?></p>
    </div>
  </div>
  <a class="logout" href="student-login.php">Switch student</a>
</header>
<div class="container">
  <h2>🎮 Pick a game to play!</h2>
  <?php if (!$games): ?>
    <div class="no-games">No games have been assigned yet. Check back soon! 😊</div>
  <?php else: ?>
  <div class="game-grid">
    <?php foreach ($games as $slug=>$g): ?>
    <a class="game-card" href="<?= $gameFiles[$slug] ?>">
      <span class="emoji"><?= $g['emoji'] ?></span>
      <h3><?= $g['name'] ?></h3>
      <div class="plays <?= isset($prog[$slug])?'':'none' ?>">
        <?= isset($prog[$slug]) ? '⭐ Played '.$prog[$slug].' time'.($prog[$slug]!=1?'s':'') : 'Not played yet' ?>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
</body>
</html>
