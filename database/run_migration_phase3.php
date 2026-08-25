<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/database.php';

header('Content-Type: text/plain; charset=utf-8');
$steps = [];

try {
    db()->exec('
        CREATE TABLE IF NOT EXISTS quizzes (
          id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          course_id INT UNSIGNED NOT NULL,
          title VARCHAR(200) NOT NULL,
          description TEXT,
          pass_score INT UNSIGNED DEFAULT 70,
          time_limit_minutes INT UNSIGNED DEFAULT 0,
          is_published TINYINT(1) DEFAULT 1,
          sort_order INT DEFAULT 0,
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ');
    $steps[] = 'quizzes table ready';
} catch (Throwable $e) {
    $steps[] = 'quizzes: ' . $e->getMessage();
}

try {
    db()->exec('
        CREATE TABLE IF NOT EXISTS quiz_questions (
          id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          quiz_id INT UNSIGNED NOT NULL,
          question_text TEXT NOT NULL,
          audio_url VARCHAR(500) DEFAULT NULL,
          options_json TEXT NOT NULL,
          correct_key VARCHAR(10) NOT NULL,
          sort_order INT DEFAULT 0,
          FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ');
    $steps[] = 'quiz_questions table ready';
} catch (Throwable $e) {
    $steps[] = 'quiz_questions: ' . $e->getMessage();
}

try {
    db()->exec('
        CREATE TABLE IF NOT EXISTS quiz_attempts (
          id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          student_id INT UNSIGNED NOT NULL,
          quiz_id INT UNSIGNED NOT NULL,
          score INT UNSIGNED DEFAULT 0,
          total_questions INT UNSIGNED DEFAULT 0,
          passed TINYINT(1) DEFAULT 0,
          answers_json TEXT,
          completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
          FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ');
    $steps[] = 'quiz_attempts table ready';
} catch (Throwable $e) {
    $steps[] = 'quiz_attempts: ' . $e->getMessage();
}

try {
    db()->exec('
        CREATE TABLE IF NOT EXISTS certificates (
          id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          student_id INT UNSIGNED NOT NULL,
          course_id INT UNSIGNED NOT NULL,
          certificate_code VARCHAR(32) NOT NULL UNIQUE,
          issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          UNIQUE KEY uk_cert_student_course (student_id, course_id),
          FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
          FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ');
    $steps[] = 'certificates table ready';
} catch (Throwable $e) {
    $steps[] = 'certificates: ' . $e->getMessage();
}

try {
    db()->exec('
        CREATE TABLE IF NOT EXISTS coupons (
          id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          code VARCHAR(50) NOT NULL UNIQUE,
          discount_type ENUM("percent","fixed") NOT NULL DEFAULT "percent",
          discount_value DECIMAL(10,2) NOT NULL DEFAULT 0,
          min_amount DECIMAL(10,2) DEFAULT 0,
          max_uses INT UNSIGNED DEFAULT 0,
          used_count INT UNSIGNED DEFAULT 0,
          expires_at DATE DEFAULT NULL,
          is_active TINYINT(1) DEFAULT 1,
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ');
    $steps[] = 'coupons table ready';
} catch (Throwable $e) {
    $steps[] = 'coupons: ' . $e->getMessage();
}

try {
    db()->exec('ALTER TABLE payments ADD COLUMN coupon_code VARCHAR(50) DEFAULT NULL AFTER note');
    $steps[] = 'payments.coupon_code added';
} catch (Throwable $e) {
    $steps[] = 'payments.coupon_code: ' . $e->getMessage();
}

$defaults = [
    'line_notify_enabled' => '0',
    'line_notify_token' => '',
    'promptpay_enabled' => '1',
    'promptpay_id' => '',
    'promptpay_id_type' => 'phone',
];
$ins = db()->prepare('INSERT IGNORE INTO site_settings (setting_key, setting_value) VALUES (?, ?)');
foreach ($defaults as $k => $v) {
    $ins->execute([$k, $v]);
}
$steps[] = 'phase3 settings seeded';

try {
    db()->prepare('INSERT IGNORE INTO coupons (code, discount_type, discount_value, min_amount, max_uses, is_active) VALUES (?,?,?,?,?,1)')
        ->execute(['WENXIN10', 'percent', 10, 0, 0]);
    $steps[] = 'sample coupon WENXIN10 (10%)';
} catch (Throwable $e) {
    $steps[] = 'sample coupon: ' . $e->getMessage();
}

echo "Wenxin LMS Phase 3 migration complete\n\n";
foreach ($steps as $step) {
    echo "- {$step}\n";
}
