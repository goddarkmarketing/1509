<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
requireAdmin();

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(404);
    exit('Not found');
}

$stmt = db()->prepare('SELECT slip_image FROM payments WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$filename = $stmt->fetchColumn();

if (!$filename || !is_string($filename)) {
    http_response_code(404);
    exit('Not found');
}

$basename = basename($filename);
$path = UPLOAD_PATH . '/' . $basename;
if (!is_file($path)) {
    http_response_code(404);
    exit('Not found');
}

$ext = strtolower(pathinfo($basename, PATHINFO_EXTENSION));
$types = [
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
    'webp' => 'image/webp',
    'pdf' => 'application/pdf',
];
$mime = $types[$ext] ?? 'application/octet-stream';

header('Content-Type: ' . $mime);
header('Content-Disposition: inline; filename="' . $basename . '"');
header('Content-Length: ' . (string) filesize($path));
readfile($path);
exit;
