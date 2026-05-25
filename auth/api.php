<?php
// API endpoint — called by the HTML games via fetch()
// Saves progress and returns current student info from cookie
require_once 'db.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$action = $_GET['action'] ?? (json_decode(file_get_contents('php://input'),true)['action'] ?? '');

// ── Who am I? ─────────────────────────────────────────────
if ($action === 'whoami') {
    $s = getStudentFromCookie();
    if (!$s) { echo json_encode(['loggedIn'=>false]); exit; }
    $db  = getDB();
    $st  = $db->prepare('SELECT s.name,s.icon,c.name AS class_name FROM students s JOIN classes c ON c.id=s.class_id WHERE s.id=?');
    $st->bind_param('i',$s['student_id']); $st->execute();
    $row = $st->get_result()->fetch_assoc();
    if (!$row) { echo json_encode(['loggedIn'=>false]); exit; }
    echo json_encode(['loggedIn'=>true,'name'=>$row['name'],'icon'=>$row['icon'],'class'=>$row['class_name']]);
    exit;
}

// ── Save progress ─────────────────────────────────────────
if ($action === 'save_progress') {
    $s = getStudentFromCookie();
    if (!$s) { echo json_encode(['ok'=>false,'error'=>'not logged in']); exit; }

    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $slug = preg_replace('/[^a-z0-9\-]/', '', $body['game'] ?? '');
    $score = (int)($body['score'] ?? 0);

    if (!$slug || !array_key_exists($slug, GAMES)) {
        echo json_encode(['ok'=>false,'error'=>'unknown game']); exit;
    }

    $db = getDB();
    $sid = $s['student_id'];

    // Upsert: insert or increment plays
    $st = $db->prepare('INSERT INTO progress (student_id,game_slug,score,plays) VALUES (?,?,?,1)
        ON DUPLICATE KEY UPDATE plays=plays+1, score=GREATEST(score,VALUES(score)), last_played=NOW()');
    $st->bind_param('isi',$sid,$slug,$score);
    $st->execute();

    echo json_encode(['ok'=>true]);
    exit;
}

echo json_encode(['ok'=>false,'error'=>'unknown action']);
