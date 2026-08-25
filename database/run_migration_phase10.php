<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/database.php';

header('Content-Type: text/plain; charset=utf-8');
$steps = [];

try {
    db()->exec('ALTER TABLE course_sessions ADD COLUMN image_url VARCHAR(500) DEFAULT NULL AFTER zoom_url');
    $steps[] = 'course_sessions.image_url added';
} catch (Throwable $e) {
    $steps[] = 'course_sessions.image_url: ' . $e->getMessage();
}

$sessionsDir = dirname(__DIR__) . '/uploads/sessions';
if (!is_dir($sessionsDir)) {
    mkdir($sessionsDir, 0755, true);
    $steps[] = 'uploads/sessions directory created';
} else {
    $steps[] = 'uploads/sessions directory ready';
}

echo "Wenxin LMS Phase 10 migration complete\n\n";
foreach ($steps as $step) {
    echo "- {$step}\n";
}
