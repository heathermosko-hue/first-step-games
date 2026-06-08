<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://games.firststepreading.com');

$email = strtolower(trim($_POST['email'] ?? ''));
$code  = strtoupper(trim($_POST['code']  ?? ''));

if (!$email || !$code) {
    echo json_encode(['valid' => false, 'error' => 'Missing fields']);
    exit;
}

$tokens_file = '/home/firststep/trial-tokens.json';
if (!file_exists($tokens_file)) {
    echo json_encode(['valid' => false, 'error' => 'No trials found']);
    exit;
}

$tokens = json_decode(file_get_contents($tokens_file), true) ?? [];

foreach ($tokens as $token) {
    if (strtolower($token['email']) === $email && $token['code'] === $code) {
        $expires = strtotime($token['expires'] . ' 23:59:59');
        if (time() <= $expires) {
            $days_left = ceil(($expires - time()) / 86400);
            echo json_encode([
                'valid'     => true,
                'days_left' => $days_left,
                'expires'   => $token['expires'],
            ]);
        } else {
            echo json_encode(['valid' => false, 'error' => 'Trial expired']);
        }
        exit;
    }
}

echo json_encode(['valid' => false, 'error' => 'Invalid email or code']);
exit;
