<?php
/**
 * Progress API — called by games when a student completes a round.
 *
 * Usage (from any game JS):
 *   fsrSaveScore('bubble-pop', 850);
 *
 * The student cookie identifies who played.
 * Upserts into progress table: increments plays, keeps best_score.
 */
require_once 'db.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

parse_str(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_QUERY) ?? '', $_QS);

// Must be a logged-in student
$student = getStudentFromCookie();
if (!$student) {
    echo json_encode(['ok'=>false,'error'=>'Not logged in']);
    exit;
}

$sid   = $student['student_id'];
$slug  = trim(base64_decode($_QS['game'] ?? ''));
$score = max(0, (int)base64_decode($_QS['score'] ?? ''));

// Validate game slug
if (!array_key_exists($slug, GAMES)) {
    echo json_encode(['ok'=>false,'error'=>'Unknown game']);
    exit;
}

$db = getDB();

// Upsert: increment plays, keep best score
$st = $db->prepare('
    INSERT INTO progress (student_id, game_slug, plays, best_score, last_played)
    VALUES (?, ?, 1, ?, NOW())
    ON DUPLICATE KEY UPDATE
        plays      = plays + 1,
        best_score = GREATEST(best_score, VALUES(best_score)),
        last_played = NOW()
');
$st->bind_param('isi', $sid, $slug, $score);
$st->execute();

echo json_encode(['ok'=>true,'plays'=>$score]);
