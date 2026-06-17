<?php
/* tts.php — server-side Google Cloud TTS proxy
   Keeps the API key off the client.
   Called by tts.js v13 via POST { text: "..." }
*/
header('Access-Control-Allow-Origin: https://games.firststepreading.com');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: private, max-age=86400');

/* Read POST body — $_POST may be empty if mod_rewrite rewrites the method */
$text = '';
if (isset($_POST['text']) && $_POST['text'] !== '') {
    $text = trim((string)$_POST['text']);
} else {
    $raw = file_get_contents('php://input');
    if ($raw !== false && $raw !== '') {
        parse_str($raw, $parsed);
        if (isset($parsed['text'])) $text = trim((string)$parsed['text']);
    }
}
if ($text === '' || strlen($text) > 600) {
    http_response_code(400);
    echo '{"error":"invalid"}';
    exit;
}

$payload = json_encode([
    'input'       => ['text' => $text],
    'voice'       => ['languageCode' => 'en-US', 'name' => 'en-US-Journey-F'],
    'audioConfig' => ['audioEncoding' => 'MP3', 'speakingRate' => 0.9, 'pitch' => 0]
]);

$url = 'https://texttospeech.googleapis.com/v1/text:synthesize?key=AIzaSyDPCnZfgZmSYPonViX2S4rSfH8-FGFbhTo';
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_POST           => 1,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_RETURNTRANSFER => 1,
    CURLOPT_TIMEOUT        => 8,
]);
$resp = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if (!$resp || $code !== 200) {
    http_response_code(502);
    echo '{"error":"upstream"}';
    exit;
}
echo $resp;
