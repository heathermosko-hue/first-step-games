<?php
require_once 'db.php';
if (session_status() === PHP_SESSION_NONE) session_start();
session_destroy();
clearStudentCookie();
$redirect = isset($_GET['teacher']) ? 'teacher-login.php' : 'student-login.php';
header('Location: ' . $redirect); exit;
