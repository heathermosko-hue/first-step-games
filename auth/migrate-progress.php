<?php
// One-time migration: create progress table
parse_str(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_QUERY) ?? '', $_QS);
if (($_QS['run'] ?? '') !== 'migrate2026') { http_response_code(403); exit('Forbidden'); }

require_once 'db.php';
$db = getDB();

$sql = "CREATE TABLE IF NOT EXISTS progress (
  student_id  INT NOT NULL,
  game_slug   VARCHAR(80) NOT NULL,
  plays       INT DEFAULT 1,
  best_score  INT DEFAULT 0,
  last_played DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (student_id, game_slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($db->query($sql)) {
    echo 'OK — progress table ready.';
} else {
    echo 'ERROR: ' . $db->error;
}
