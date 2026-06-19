<?php
require_once 'db.php';
$redirect = isset($_GET['teacher']) ? 'teacher-login.php' : 'student-login.php';
?><!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>
<script>
document.cookie = 'fsr_teacher=; max-age=0; path=/; SameSite=Lax';
document.cookie = 'fsr_student=; max-age=0; path=/; SameSite=Lax';
window.location.href = '<?= $redirect ?>';
</script>
</body></html>
