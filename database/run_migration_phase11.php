<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/database.php';

header('Content-Type: text/plain; charset=utf-8');
$steps = [];

try {
    db()->exec('ALTER TABLE quiz_questions ADD COLUMN audio_url VARCHAR(500) DEFAULT NULL AFTER question_text');
    $steps[] = 'quiz_questions.audio_url added';
} catch (Throwable $e) {
    $steps[] = 'quiz_questions.audio_url: ' . $e->getMessage();
}

$quizzesDir = dirname(__DIR__) . '/uploads/quizzes';
if (!is_dir($quizzesDir)) {
    mkdir($quizzesDir, 0755, true);
    $steps[] = 'uploads/quizzes directory created';
} else {
    $steps[] = 'uploads/quizzes directory ready';
}

$gitkeep = $quizzesDir . '/.gitkeep';
if (!is_file($gitkeep)) {
    file_put_contents($gitkeep, '');
    $steps[] = 'uploads/quizzes/.gitkeep created';
}

echo "Wenxin LMS Phase 11 migration complete\n\n";
foreach ($steps as $step) {
    echo "- {$step}\n";
}
