<?php
require_once 'db.php';
clearTeacherCookie();
clearStudentCookie();
$redirect = isset($_GET['teacher']) ? 'teacher-login.php' : 'student-login.php';
header('Location: ' . $redirect); exit;
