<?php
// ═══════════════════════════════════════════════════════════
//  MEMBER LOGIN VALIDATOR
//  Validates email/password against WordPress membership on
//  firststepreading.com — returns JSON {success, error}
// ═══════════════════════════════════════════════════════════
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST')    { echo json_encode(['success'=>false,'error'=>'Bad request']); exit; }

$email    = trim($_POST['email']    ?? '');
$password = trim($_POST['password'] ?? '');

if (!$email || !$password) {
    echo json_encode(['success'=>false,'error'=>'Please enter your email and password.']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success'=>false,'error'=>'Please enter a valid email address.']);
    exit;
}

// ── Try to load WordPress ───────────────────────────────────
// Games live in public_html/reading-games/auth/
// WordPress lives in public_html/
$wp_paths = [
    dirname(dirname(dirname(__FILE__))) . '/wp-load.php',   // ../../wp-load.php
    '/home/firststep65/public_html/wp-load.php',
    '/home/firststep/public_html/wp-load.php',
    $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php',
    dirname($_SERVER['DOCUMENT_ROOT']) . '/wp-load.php',
];

$wp_loaded = false;
foreach ($wp_paths as $path) {
    if (file_exists($path)) {
        // Load WordPress in a way that doesn't output anything
        define('SHORTINIT', false);
        require_once $path;
        $wp_loaded = true;
        break;
    }
}

if (!$wp_loaded) {
    // Fallback: validate via WordPress REST API
    validateViaRestApi($email, $password);
    exit;
}

// ── WordPress loaded — authenticate ────────────────────────
$user = wp_authenticate($email, $password);

if (is_wp_error($user)) {
    // Don't leak whether email exists or not
    echo json_encode(['success'=>false,'error'=>'Incorrect email or password. Please try again.']);
    exit;
}

// ── Check for active membership / purchase ─────────────────
$has_access = false;
$user_id    = $user->ID;

// 1. WooCommerce Memberships plugin
if (function_exists('wc_memberships_is_user_active_member')) {
    $has_access = wc_memberships_is_user_active_member($user_id);
}

// 2. WooCommerce Subscriptions plugin
if (!$has_access && function_exists('wcs_user_has_subscription')) {
    $has_access = wcs_user_has_subscription($user_id, '', 'active');
}

// 3. Check user roles — subscriber / customer with completed order
if (!$has_access) {
    $user_roles = (array)$user->roles;
    $member_roles = ['subscriber','customer','member','woocommerce_member'];
    if (array_intersect($member_roles, $user_roles)) {
        // Confirm they have at least one completed WooCommerce order
        if (function_exists('wc_get_orders')) {
            $orders = wc_get_orders([
                'customer' => $user_id,
                'status'   => 'completed',
                'limit'    => 1,
            ]);
            $has_access = !empty($orders);
        } else {
            // No WooCommerce — trust the role
            $has_access = true;
        }
    }
}

// 4. Ultimate Member / Paid Memberships Pro fallback
if (!$has_access) {
    $pmpro_level = get_user_meta($user_id, 'pmpro_membership_level', true);
    if ($pmpro_level) $has_access = true;
}

// 5. Last resort: any logged-in user with subscriber+ role gets access
//    (remove this block if you want stricter access control)
if (!$has_access) {
    $all_roles = (array)$user->roles;
    if (!empty($all_roles) && !in_array('pending', $all_roles)) {
        $has_access = true;
    }
}

if ($has_access) {
    $display_name = $user->display_name ?: $user->user_login;
    echo json_encode([
        'success' => true,
        'name'    => $display_name,
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error'   => 'No active membership found for this account.<br>Please <a href="https://www.firststepreading.com/shop/" target="_blank" style="color:#3498db;">purchase a membership</a> to access the games.',
    ]);
}

// ── REST API fallback (if WordPress files not found) ────────
function validateViaRestApi($email, $password) {
    $url = 'https://www.firststepreading.com/wp-json/wp/v2/users/me';
    $ch  = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Basic ' . base64_encode($email . ':' . $password),
        ],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT        => 8,
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code === 200 && $resp) {
        $data = json_decode($resp, true);
        $name = $data['name'] ?? 'Member';
        echo json_encode(['success'=>true,'name'=>$name]);
    } else {
        echo json_encode(['success'=>false,'error'=>'Incorrect email or password. Please try again.']);
    }
}
