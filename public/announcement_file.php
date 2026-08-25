<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/database.php';
require_once dirname(__DIR__) . '/includes/announcement.php';

$slug = trim($_GET['slug'] ?? '');
if ($slug === '') {
    http_response_code(404);
    exit('Not found');
}

$announcement = getAnnouncementBySlug($slug);
if (!$announcement || !announcementHasAttachment($announcement)) {
    http_response_code(404);
    exit('Not found');
}

$stored = trim($announcement['attachment_url'] ?? '');
if (!str_starts_with($stored, 'uploads/announcements/')) {
    http_response_code(404);
    exit('Not found');
}

$file = basename($stored);
if (!preg_match('/^[a-zA-Z0-9._-]+$/', $file)) {
    http_response_code(404);
    exit('Not found');
}

$path = UPLOAD_ANNOUNCEMENTS_PATH . '/' . $file;
if (!is_file($path)) {
    http_response_code(404);
    exit('Not found');
}

$downloadName = announcementAttachmentLabel($announcement);
$downloadName = preg_replace('/[^\p{L}\p{N}\s._-]/u', '_', $downloadName) ?? 'attachment.pdf';
if (!str_ends_with(strtolower($downloadName), '.pdf')) {
    $downloadName .= '.pdf';
}

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $downloadName . '"');
header('Content-Length: ' . (string) filesize($path));
readfile($path);
exit;
