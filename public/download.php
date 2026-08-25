<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/access.php';
require_once dirname(__DIR__) . '/includes/media_upload.php';

$file = basename($_GET['file'] ?? '');
$lessonId = (int) ($_GET['lesson_id'] ?? 0);

if ($file === '' || !preg_match('/^[a-zA-Z0-9._-]+$/', $file)) {
    http_response_code(404);
    exit('Not found');
}

$path = UPLOAD_COURSES_PATH . '/' . $file;
if (!is_file($path)) {
    $path = UPLOAD_SESSIONS_PATH . '/' . $file;
}
if (!is_file($path)) {
    http_response_code(404);
    exit('Not found');
}

if ($lessonId > 0) {
    $lesson = getLessonWithCourse($lessonId);
    if (!$lesson || !canAccessLesson($lesson)) {
        http_response_code(403);
        exit('Forbidden');
    }
    $docPath = $lesson['document_url'] ?? '';
    if ($docPath === '' || !str_contains($docPath, $file)) {
        http_response_code(403);
        exit('Forbidden');
    }
} elseif (!str_starts_with($file, 'cover_') && !str_starts_with($file, 'session_')) {
    http_response_code(403);
    exit('Forbidden');
}

$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
$types = [
    'pdf' => 'application/pdf',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'ppt' => 'application/vnd.ms-powerpoint',
    'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'webp' => 'image/webp',
    'gif' => 'image/gif',
];
$mime = $types[$ext] ?? 'application/octet-stream';

header('Content-Type: ' . $mime);
header('Content-Disposition: inline; filename="' . $file . '"');
header('Content-Length: ' . (string) filesize($path));
readfile($path);
exit;
