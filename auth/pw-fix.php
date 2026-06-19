<?php
require_once 'db.php';
$db = getDB();
$hash = password_hash('read123', PASSWORD_DEFAULT);
$st = $db->prepare('UPDATE teachers SET password=? WHERE email=?');
$st->bind_param('ss', $hash, 'skposluns@firststepreading.com');
$st->execute();
echo $st->affected_rows === 1 ? '✅ Password updated.' : '❌ No rows updated — email not found.';
