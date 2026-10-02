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
    $docPath = (string) ($lesson['document_url'] ?? '');
    $videoPath = (string) ($lesson['video_url'] ?? '');
    if (!str_contains($docPath, $file) && !str_contains($videoPath, $file)) {
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
    'mp4' => 'video/mp4',
    'webm' => 'video/webm',
    'ogg' => 'video/ogg',
];
$mime = $types[$ext] ?? 'application/octet-stream';
$size = filesize($path);
$isVideo = in_array($ext, ['mp4', 'webm', 'ogg'], true);

header('Content-Type: ' . $mime);
header('Content-Disposition: inline; filename="' . $file . '"');
header('Accept-Ranges: bytes');

if ($isVideo && isset($_SERVER['HTTP_RANGE']) && preg_match('/bytes=(\d+)-(\d*)/', (string) $_SERVER['HTTP_RANGE'], $range)) {
    $start = (int) $range[1];
    $end = $range[2] !== '' ? (int) $range[2] : $size - 1;
    if ($start > $end || $end >= $size) {
        http_response_code(416);
        header('Content-Range: bytes */' . $size);
        exit;
    }
    http_response_code(206);
    header('Content-Range: bytes ' . $start . '-' . $end . '/' . $size);
    header('Content-Length: ' . (string) ($end - $start + 1));
    $handle = fopen($path, 'rb');
    if ($handle === false) {
        http_response_code(500);
        exit;
    }
    fseek($handle, $start);
    echo fread($handle, $end - $start + 1);
    fclose($handle);
    exit;
}

header('Content-Length: ' . (string) $size);
readfile($path);
exit;
