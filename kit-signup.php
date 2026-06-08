<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $to      = 'sales@firststepreading.com';
    $subject = 'New Free Kit Signup — First Step Reading';
    $name    = htmlspecialchars($_POST['child_name'] ?? '');
    $email   = htmlspecialchars($_POST['email'] ?? '');

    $body  = "New Free Kit Signup!\n\n";
    $body .= "Email: $email\n";
    if ($name) $body .= "Child's name: $name\n";
    $body .= "\nSent from games.firststepreading.com";

    $headers  = "From: noreply@firststepreading.com\r\n";
    $headers .= "Reply-To: $email\r\n";

    mail($to, $subject, $body, $headers);

    header('Location: https://games.firststepreading.com/free-kit.html');
    exit;
}
// If accessed directly, redirect home
header('Location: https://games.firststepreading.com/');
exit;
