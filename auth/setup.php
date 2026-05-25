<?php
// Run this ONCE to create the database tables.
// Visit: yoursite.com/reading-games/auth/setup.php
// Then DELETE this file from the server for security.
require_once 'db.php';
$db = getDB();

$tables = [
"CREATE TABLE IF NOT EXISTS teachers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

"CREATE TABLE IF NOT EXISTS classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    class_code VARCHAR(20) NOT NULL UNIQUE,
    access_type ENUM('full','assigned') NOT NULL DEFAULT 'full',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

"CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    icon VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

"CREATE TABLE IF NOT EXISTS assigned_games (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT NOT NULL,
    game_slug VARCHAR(50) NOT NULL,
    UNIQUE KEY uq_class_game (class_id, game_slug),
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

"CREATE TABLE IF NOT EXISTS progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    game_slug VARCHAR(50) NOT NULL,
    score INT DEFAULT 0,
    plays INT DEFAULT 1,
    last_played TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_student_game (student_id, game_slug),
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
];

echo '<h2>First Step Reading — Database Setup</h2>';
$ok = true;
foreach ($tables as $sql) {
    preg_match('/CREATE TABLE IF NOT EXISTS (\w+)/', $sql, $m);
    $table = $m[1] ?? '?';
    if ($db->query($sql)) {
        echo "✅ Table <b>$table</b> ready<br>";
    } else {
        echo "❌ Error on <b>$table</b>: " . $db->error . '<br>';
        $ok = false;
    }
}
echo $ok
    ? '<br><b style="color:green">Setup complete! Delete this file from your server now.</b>'
    : '<br><b style="color:red">Some errors occurred — check your DB credentials in db.php</b>';
