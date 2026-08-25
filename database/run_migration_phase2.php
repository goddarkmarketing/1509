<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/database.php';

header('Content-Type: text/plain; charset=utf-8');

$steps = [];

try {
    db()->exec('
        CREATE TABLE IF NOT EXISTS lesson_progress (
          id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          student_id INT UNSIGNED NOT NULL,
          lesson_id INT UNSIGNED NOT NULL,
          course_id INT UNSIGNED NOT NULL,
          completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          UNIQUE KEY uk_lesson_progress (student_id, lesson_id),
          FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
          FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE,
          FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ');
    $steps[] = 'lesson_progress table ready';
} catch (Throwable $e) {
    $steps[] = 'lesson_progress: ' . $e->getMessage();
}

try {
    db()->exec('
        CREATE TABLE IF NOT EXISTS payment_items (
          id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          payment_id INT UNSIGNED NOT NULL,
          course_id INT UNSIGNED NOT NULL,
          amount DECIMAL(10,2) NOT NULL DEFAULT 0,
          FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE,
          FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ');
    $steps[] = 'payment_items table ready';
} catch (Throwable $e) {
    $steps[] = 'payment_items: ' . $e->getMessage();
}

$defaults = [
    'email_enabled' => '0',
    'email_from' => 'noreply@wenxin.local',
    'email_from_name' => 'Wenxin Chinese',
    'email_admin' => 'admin@wenxin.local',
    'site_url' => 'http://localhost/LMS',
];
$ins = db()->prepare('INSERT IGNORE INTO site_settings (setting_key, setting_value) VALUES (?, ?)');
foreach ($defaults as $k => $v) {
    $ins->execute([$k, $v]);
}
$steps[] = 'email settings seeded';

echo "Wenxin LMS Phase 2 migration complete\n\n";
foreach ($steps as $step) {
    echo "- {$step}\n";
}
