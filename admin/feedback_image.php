<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
requireAdmin();
require_once dirname(__DIR__) . '/includes/feedback.php';

$id = preg_replace('/[^A-Za-z0-9\-]/', '', (string) ($_GET['id'] ?? '')) ?? '';
if ($id === '' || !preg_match('/^FB-\d+$/i', $id)) {
    http_response_code(404);
    exit('Not found');
}

$path = feedbackScreenshotPath($id);
if (!$path) {
    http_response_code(404);
    exit('Not found');
}

header('Content-Type: image/png');
header('Content-Length: ' . (string) filesize($path));
header('Cache-Control: private, max-age=3600');
readfile($path);
exit;
