<?php
declare(strict_types=1);

require_once __DIR__ . '/env.php';
require_once __DIR__ . '/app_url.php';
loadEnvFile(dirname(__DIR__) . '/.env');

define('APP_NAME', 'THE LEONA TUTORS LMS');
define('APP_URL', resolveAppUrl());
define('BASE_PATH', dirname(__DIR__));

define('DB_HOST', env('DB_HOST', 'localhost') ?? 'localhost');
define('DB_NAME', env('DB_NAME', 'wenxin_lms') ?? 'wenxin_lms');
define('DB_USER', env('DB_USER', 'root') ?? 'root');
define('DB_PASS', env('DB_PASS', '') ?? '');
define('DB_CHARSET', 'utf8mb4');

define('UPLOAD_PATH', BASE_PATH . '/uploads/payments');
define('UPLOAD_URL', APP_URL . '/uploads/payments');
define('UPLOAD_COURSES_PATH', BASE_PATH . '/uploads/courses');
define('UPLOAD_COURSES_URL', APP_URL . '/uploads/courses');
define('UPLOAD_ANNOUNCEMENTS_PATH', BASE_PATH . '/uploads/announcements');
define('UPLOAD_ANNOUNCEMENTS_URL', APP_URL . '/uploads/announcements');
define('UPLOAD_SESSIONS_PATH', BASE_PATH . '/uploads/sessions');
define('UPLOAD_SESSIONS_URL', APP_URL . '/uploads/sessions');
define('UPLOAD_QUIZZES_PATH', BASE_PATH . '/uploads/quizzes');
define('UPLOAD_QUIZZES_URL', APP_URL . '/uploads/quizzes');

date_default_timezone_set('Asia/Bangkok');

mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
ini_set('default_charset', 'UTF-8');

if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
