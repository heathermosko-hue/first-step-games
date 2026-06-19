<?php
// ═══════════════════════════════════════════════════════════
//  CONFIGURATION  ← husband fills these in before uploading
// ═══════════════════════════════════════════════════════════
// Use WordPress constants if available (WordPress defines DB_HOST, DB_USER, DB_PASSWORD, DB_NAME)
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_USER')) define('DB_USER', 'YOUR_DB_USERNAME');
// WordPress uses DB_PASSWORD; map it to DB_PASS if we don't have DB_PASS yet
if (!defined('DB_PASS')) define('DB_PASS', defined('DB_PASSWORD') ? DB_PASSWORD : 'YOUR_DB_PASSWORD');
if (!defined('DB_NAME')) define('DB_NAME', 'YOUR_DB_NAME');
if (!defined('COOKIE_SECRET')) define('COOKIE_SECRET', 'fsr-cookie-secret-2024-xK9mP3');

// ─── ANIMAL ICONS (40 choices — each student's unique login icon) ─────────────
define('ANIMALS', [
    '🐶','🐱','🐭','🐹','🐰','🦊','🐻','🐼','🐨','🐯',
    '🦁','🐮','🐷','🐸','🐵','🐔','🐧','🦆','🦉','🦋',
    '🐢','🐙','🦈','🐬','🦓','🦒','🐘','🦏','🐊','🦘',
    '🦔','🐿️','🦜','🐝','🦩','🐞','🦀','🐠','🦙','🐺'
]);

// ─── ICON COMBO CHOICES (24 icons — used for 4-icon class code AND 1-icon student code) ──
define('ICON_CHOICES', [
    '🐶','🐱','🐸','🐧','🦁','🦊',
    '🍎','🍋','🍇','🍓','🍕','🍦',
    '⭐','🌈','🌞','🌸','🌙','🌊',
    '🎈','🚀','🎵','🎀','🏆','💎'
]);

// ─── GAMES ────────────────────────────────────────────────
define('GAMES', [
    'bubble-pop'    => ['name'=>'Bubble Pop',    'emoji'=>'🫧'],
    'phonics-bingo' => ['name'=>'Phonics Bingo', 'emoji'=>'🎱'],
    'pinball'       => ['name'=>'Pinball',        'emoji'=>'🎯'],
    'digraphs'      => ['name'=>'Digraphs',       'emoji'=>'📖'],
    'word-family'   => ['name'=>'Word Family',    'emoji'=>'👨‍👩‍👧'],
    'r-controlled'  => ['name'=>'R-Controlled',   'emoji'=>'🔤'],
    'spell-it'      => ['name'=>'Spell It',       'emoji'=>'✏️'],
]);

// ─── DB CONNECTION ────────────────────────────────────────
function getDB() {
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) die('DB error: ' . $conn->connect_error);
        $conn->set_charset('utf8mb4');
    }
    return $conn;
}

// ─── BASE URL (auto-detected) ─────────────────────────────
function baseUrl() {
    $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host  = $_SERVER['HTTP_HOST'];
    $dir   = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
    return $proto . '://' . $host . $dir;
}

// ─── CLASS CODE ───────────────────────────────────────────
function generateClassCode($db) {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    do {
        $code = '';
        for ($i = 0; $i < 3; $i++) $code .= $chars[random_int(0, strlen($chars)-1)];
        $code .= '-';
        for ($i = 0; $i < 3; $i++) $code .= $chars[random_int(0, strlen($chars)-1)];
        $st = $db->prepare('SELECT id FROM classes WHERE class_code=?');
        $st->bind_param('s', $code); $st->execute();
        $exists = $st->get_result()->num_rows > 0;
    } while ($exists);
    return $code;
}

// ─── ICON COMBO CODE (4 icons from ICON_CHOICES, stored as comma-separated) ──
function generateIconCode($db) {
    $icons = ICON_CHOICES;
    do {
        $picks = [];
        for ($i = 0; $i < 4; $i++) {
            $picks[] = $icons[random_int(0, count($icons)-1)];
        }
        $code = implode(',', $picks);
        $st = $db->prepare('SELECT id FROM classes WHERE icon_code=?');
        $st->bind_param('s', $code); $st->execute();
        $exists = $st->get_result()->num_rows > 0;
    } while ($exists);
    return $code;
}

// ─── AUTO-ASSIGN ANIMAL ───────────────────────────────────
function getNextAnimal($classId, $db) {
    $st = $db->prepare('SELECT icon FROM students WHERE class_id=?');
    $st->bind_param('i', $classId); $st->execute();
    $res = $st->get_result();
    $used = [];
    while ($row = $res->fetch_assoc()) $used[] = $row['icon'];
    foreach (ANIMALS as $a) { if (!in_array($a, $used)) return $a; }
    return ANIMALS[0];
}

// ─── STUDENT COOKIE ───────────────────────────────────────
function setStudentCookie($studentId, $classId, $name) {
    $data = $studentId.'|'.$classId.'|'.$name;
    $sig  = hash_hmac('sha256', $data, COOKIE_SECRET);
    $val  = base64_encode($data).'.'.$sig;
    setcookie('fsr_student', $val, time()+86400*7, '/', '', false, false);
}

function getStudentFromCookie() {
    if (empty($_COOKIE['fsr_student'])) return null;
    $parts = explode('.', $_COOKIE['fsr_student'], 2);
    if (count($parts) !== 2) return null;
    $data = base64_decode($parts[0]);
    if (!hash_equals(hash_hmac('sha256', $data, COOKIE_SECRET), $parts[1])) return null;
    $f = explode('|', $data, 3);
    if (count($f) !== 3) return null;
    return ['student_id'=>(int)$f[0], 'class_id'=>(int)$f[1], 'name'=>$f[2]];
}

function clearStudentCookie() {
    setcookie('fsr_student', '', time()-3600, '/', '', false, false);
}

// ─── TEACHER COOKIE AUTH ──────────────────────────────────
function teacherCookieValue($id, $name) {
    $data = $id . '|' . $name;
    $sig  = hash_hmac('sha256', $data, COOKIE_SECRET);
    return base64_encode($data) . '.' . $sig;
}
function setTeacherCookie($id, $name) {
    // kept for compatibility — JS approach used in login page directly
    $val = teacherCookieValue($id, $name);
    echo '<script>document.cookie="fsr_teacher='.addslashes($val).'; max-age=2592000; path=/; SameSite=Lax";</script>';
}
function getTeacherFromCookie() {
    if (empty($_COOKIE['fsr_teacher'])) return null;
    $parts = explode('.', $_COOKIE['fsr_teacher'], 2);
    if (count($parts) !== 2) return null;
    $data = base64_decode($parts[0]);
    if (!hash_equals(hash_hmac('sha256', $data, COOKIE_SECRET), $parts[1])) return null;
    $f = explode('|', $data, 2);
    if (count($f) !== 2) return null;
    return ['teacher_id' => (int)$f[0], 'teacher_name' => $f[1]];
}
function clearTeacherCookie() {
    echo '<script>document.cookie="fsr_teacher=; max-age=0; path=/; SameSite=Lax";</script>';
}
function requireTeacher() {
    $t = getTeacherFromCookie();
    if (!$t) { header('Location: teacher-login.php'); exit; }
    return $t;
}
