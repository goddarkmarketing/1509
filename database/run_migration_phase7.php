<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/database.php';

$steps = [];

try {
    db()->exec("
        CREATE TABLE IF NOT EXISTS course_games (
          id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          course_id INT UNSIGNED NOT NULL,
          title VARCHAR(200) NOT NULL,
          description TEXT,
          game_url VARCHAR(500) NOT NULL,
          is_published TINYINT(1) DEFAULT 1,
          sort_order INT DEFAULT 0,
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    $steps[] = 'course_games table ready';
} catch (Throwable $e) {
    $steps[] = 'course_games: ' . $e->getMessage();
}

foreach ($steps as $step) {
    echo $step . PHP_EOL;
}
