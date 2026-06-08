<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $to      = 'sales@firststepreading.com';
    $name    = htmlspecialchars($_POST['child_name'] ?? '');
    $email   = htmlspecialchars($_POST['email'] ?? '');

    // ── 1. Notify sales ──────────────────────────────────────────
    $subject = 'New Free Kit Signup — First Step Reading';
    $body    = "New Free Kit Signup!\n\n";
    $body   .= "Email: $email\n";
    if ($name) $body .= "Child's name: $name\n";
    $body   .= "\nSent from games.firststepreading.com";

    $headers  = "From: noreply@firststepreading.com\r\n";
    $headers .= "Reply-To: $email\r\n";

    mail($to, $subject, $body, $headers);

    // ── 2. Welcome email to the parent ───────────────────────────
    if ($email) {
        $greeting = $name ? "your little one" : "your child";
        $welcome_subject = "🎁 Your Free Reading Starter Kit is Ready!";

        $welcome_body = "Hi there!\n\n";
        $welcome_body .= "Welcome to First Step Reading — we're so excited to have you!\n\n";
        $welcome_body .= "Your FREE Starter Kit is waiting for you right now. Here's everything included:\n\n";
        $welcome_body .= "✏️  3 Printable Worksheets\n";
        $welcome_body .= "     • Worksheet 1: Alphabet Match\n";
        $welcome_body .= "     • Worksheet 2: Beginning Sounds\n";
        $welcome_body .= "     • Worksheet 3: Sight Words\n\n";
        $welcome_body .= "💡  3 Bonus Reading Tips\n";
        $welcome_body .= "     Practical tips to help $greeting build reading confidence fast.\n\n";
        $welcome_body .= "🎮  2 Weeks of FREE Hub Access — Completely Free, No Credit Card Needed!\n";
        $welcome_body .= "     That's right — FREE. No credit card, no commitment, no catch.\n";
        $welcome_body .= "     $greeting gets full access to ALL of our interactive reading games\n";
        $welcome_body .= "     for 14 days, absolutely free. Just click the link below and\n";
        $welcome_body .= "     your trial starts automatically the moment you arrive!\n\n";
        $welcome_body .= "🕹️  Want more practice? Try our free online reading games!\n";
        $welcome_body .= "     Fun, engaging games that make learning to read feel like play.\n";
        $welcome_body .= "     Letters, sight words, phonics and more — all free to play!\n";
        $welcome_body .= "     👉 https://games.firststepreading.com\n\n";
        $welcome_body .= "─────────────────────────────────────\n";
        $welcome_body .= "👉  Get your kit + start your free trial:\n";
        $welcome_body .= "    https://games.firststepreading.com/free-kit.html\n";
        $welcome_body .= "─────────────────────────────────────\n\n";
        $welcome_body .= "We built First Step Reading for parents just like you — busy, caring, and\n";
        $welcome_body .= "wanting the best start for their child. Our games make learning to read\n";
        $welcome_body .= "feel like play, and kids genuinely love them.\n\n";
        $welcome_body .= "If you ever have questions, just reply to this email — we read every one.\n\n";
        $welcome_body .= "Happy reading! 📚\n\n";
        $welcome_body .= "The First Step Reading Team\n";
        $welcome_body .= "https://www.firststepreading.com\n\n";
        $welcome_body .= "──────────────────────────────────────────\n";
        $welcome_body .= "You're receiving this because you signed up at games.firststepreading.com.\n";
        $welcome_body .= "No spam, ever. Questions? Reply to this email anytime.\n";

        $welcome_headers  = "From: First Step Reading <noreply@firststepreading.com>\r\n";
        $welcome_headers .= "Reply-To: support@firststepreading.com\r\n";
        $welcome_headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

        mail($email, $welcome_subject, $welcome_body, $welcome_headers);
    }

    header('Location: https://games.firststepreading.com/free-kit.html');
    exit;
}

header('Location: https://games.firststepreading.com/');
exit;
