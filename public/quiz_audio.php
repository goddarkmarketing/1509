<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/student_auth.php';
require_once dirname(__DIR__) . '/includes/quiz.php';
require_once dirname(__DIR__) . '/includes/media_upload.php';

$questionId = (int) ($_GET['qid'] ?? 0);
$question = $questionId > 0 ? getQuizQuestionById($questionId) : null;
if (!$question || !quizQuestionHasAudio($question)) {
    http_response_code(404);
    exit('Not found');
}

$quiz = getQuizById((int) $question['quiz_id']);
if (!$quiz) {
    http_response_code(404);
    exit('Not found');
}

$allowed = isAdminLoggedIn();
if (!$allowed) {
    $student = currentStudent();
    if ($student && studentCanTakeQuiz((int) $student['id'], $quiz)) {
        $allowed = true;
    }
}

if (!$allowed) {
    http_response_code(403);
    exit('Forbidden');
}

$storedPath = trim((string) $question['audio_url']);
if (!str_starts_with($storedPath, 'uploads/quizzes/')) {
    http_response_code(404);
    exit('Not found');
}

$basename = basename($storedPath);
$path = UPLOAD_QUIZZES_PATH . '/' . $basename;
if ($basename === '' || !is_file($path)) {
    http_response_code(404);
    exit('Not found');
}

$ext = strtolower(pathinfo($basename, PATHINFO_EXTENSION));
$types = [
    'mp3' => 'audio/mpeg',
    'wav' => 'audio/wav',
    'ogg' => 'audio/ogg',
    'm4a' => 'audio/mp4',
    'aac' => 'audio/aac',
];
$mime = $types[$ext] ?? 'application/octet-stream';

header('Content-Type: ' . $mime);
header('Content-Disposition: inline; filename="' . $basename . '"');
header('Content-Length: ' . (string) filesize($path));
header('Accept-Ranges: bytes');
header('Cache-Control: private, max-age=3600');
readfile($path);
exit;
