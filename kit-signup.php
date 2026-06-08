<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = htmlspecialchars(trim($_POST['child_name'] ?? ''));
    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header('Location: https://games.firststepreading.com/');
        exit;
    }

    // ── Generate trial code ──────────────────────────────────────
    $code    = 'FSR-' . strtoupper(substr(md5(uniqid($email, true)), 0, 8));
    $expires = date('Y-m-d', strtotime('+14 days'));

    // ── Save to tokens file (outside public_html) ────────────────
    $tokens_file = '/home/firststep/trial-tokens.json';
    $tokens = [];
    if (file_exists($tokens_file)) {
        $tokens = json_decode(file_get_contents($tokens_file), true) ?? [];
    }
    // Remove any previous trial for this email
    $tokens = array_filter($tokens, fn($t) => $t['email'] !== $email);
    $tokens[] = [
        'code'    => $code,
        'email'   => $email,
        'name'    => $name,
        'expires' => $expires,
        'created' => date('Y-m-d H:i:s'),
    ];
    file_put_contents($tokens_file, json_encode(array_values($tokens), JSON_PRETTY_PRINT));

    // ── Notify sales ─────────────────────────────────────────────
    $sales_body  = "New Free Trial Signup!\n\n";
    $sales_body .= "Email:       $email\n";
    if ($name) $sales_body .= "Child name:  $name\n";
    $sales_body .= "Trial code:  $code\n";
    $sales_body .= "Expires:     $expires\n";
    mail(
        'sales@firststepreading.com',
        'New Free Trial Signup — First Step Reading',
        $sales_body,
        "From: noreply@firststepreading.com\r\nReply-To: $email\r\n"
    );

    // ── Welcome email to parent ──────────────────────────────────
    $greeting = $name ? "your little one" : "your child";

    $msg  = "Hi there!\n\n";
    $msg .= "Welcome to First Step Reading — we're so thrilled to have you! 🎉\n\n";
    $msg .= "Your FREE Starter Kit is ready and waiting. Here's everything included:\n\n";
    $msg .= "✏️  3 Printable Worksheets\n";
    $msg .= "     • Worksheet 1: Alphabet Match\n";
    $msg .= "     • Worksheet 2: Beginning Sounds\n";
    $msg .= "     • Worksheet 3: Sight Words\n\n";
    $msg .= "💡  3 Bonus Reading Tips\n";
    $msg .= "     Practical, easy-to-use tips to help $greeting build real reading confidence.\n\n";
    $msg .= "🎮  2 Weeks of FREE Hub Access!\n";
    $msg .= "─────────────────────────────────────────\n";
    $msg .= "That's right — 100% FREE. No credit card. No commitment. No catch.\n\n";
    $msg .= "$greeting gets full access to ALL of our interactive reading games\n";
    $msg .= "for 14 days, completely free. Letters, sight words, phonics and more!\n\n";
    $msg .= "Want more practice? Try our free online reading games — they make\n";
    $msg .= "learning to read feel like play, and kids genuinely love them! 🕹️\n\n";
    $msg .= "YOUR FREE TRIAL LOGIN:\n";
    $msg .= "─────────────────────────────────────────\n";
    $msg .= "  Website:  https://games.firststepreading.com/hub.html\n";
    $msg .= "  Email:    $email\n";
    $msg .= "  Code:     $code\n";
    $msg .= "  Expires:  $expires\n";
    $msg .= "─────────────────────────────────────────\n\n";
    $msg .= "Just visit the Hub, click \"Free Trial Login\", enter your email\n";
    $msg .= "and code above, and you're in! It only takes a few seconds.\n\n";
    $msg .= "👉 Get your worksheets + start your free trial now:\n";
    $msg .= "   https://games.firststepreading.com/free-kit.html\n\n";
    $msg .= "We built First Step Reading for parents just like you — busy, caring,\n";
    $msg .= "and wanting the very best start for their child. We hope you and\n";
    $msg .= "$greeting love it as much as our families do!\n\n";
    $msg .= "If you ever have questions, just reply to this email — we read every one.\n\n";
    $msg .= "Happy reading! 📚\n\n";
    $msg .= "The First Step Reading Team\n";
    $msg .= "https://www.firststepreading.com\n\n";
    $msg .= "──────────────────────────────────────────────────────\n";
    $msg .= "You're receiving this because you signed up at games.firststepreading.com.\n";
    $msg .= "No spam, ever. Questions? Reply to this email anytime.\n";

    $headers  = "From: First Step Reading <noreply@firststepreading.com>\r\n";
    $headers .= "Reply-To: support@firststepreading.com\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    mail($email, '🎁 Your Free Reading Starter Kit + 2-Week Trial Login!', $msg, $headers);

    header('Location: https://games.firststepreading.com/free-kit.html');
    exit;
}

header('Location: https://games.firststepreading.com/');
exit;
