<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/functions.php';

$payload = trim($_GET['data'] ?? '');
$size = min(400, max(120, (int) ($_GET['size'] ?? 280)));

if ($payload === '' || strlen($payload) > 512) {
    http_response_code(400);
    exit;
}

$remoteUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=' . $size . 'x' . $size
    . '&data=' . rawurlencode($payload);

$cacheDir = dirname(__DIR__) . '/storage/cache/promptpay';
if (!is_dir($cacheDir)) {
    @mkdir($cacheDir, 0755, true);
}

$cacheFile = $cacheDir . '/' . hash('sha256', $payload . ':' . $size) . '.png';
if (is_file($cacheFile) && filemtime($cacheFile) > time() - 86400) {
    header('Content-Type: image/png');
    header('Cache-Control: public, max-age=86400');
    readfile($cacheFile);
    exit;
}

$context = stream_context_create([
    'http' => [
        'timeout' => 8,
        'user_agent' => 'WenxinLMS/1.0',
    ],
]);

$image = @file_get_contents($remoteUrl, false, $context);
if ($image === false || strlen($image) < 100) {
    http_response_code(502);
    exit;
}

@file_put_contents($cacheFile, $image);

header('Content-Type: image/png');
header('Cache-Control: public, max-age=86400');
echo $image;
