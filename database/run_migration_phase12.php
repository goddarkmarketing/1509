<?php

declare(strict_types=1);

/**
 * Phase 12: จำลองสนามสอบ (exam packs) — ซื้อชุดข้อสอบก่อนเข้าทำ
 */

require_once dirname(__DIR__) . '/includes/database.php';

header('Content-Type: text/plain; charset=utf-8');

$steps = [];

try {
    db()->exec("
        CREATE TABLE IF NOT EXISTS exam_packs (
          id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          slug VARCHAR(120) NOT NULL UNIQUE,
          title VARCHAR(200) NOT NULL,
          subtitle VARCHAR(255) DEFAULT NULL,
          description TEXT,
          subject VARCHAR(100) NOT NULL DEFAULT 'ทั่วไป',
          level ENUM('beginner','intermediate','advanced') NOT NULL DEFAULT 'intermediate',
          price DECIMAL(10,2) NOT NULL DEFAULT 0,
          time_limit_minutes INT UNSIGNED NOT NULL DEFAULT 60,
          pass_score INT UNSIGNED NOT NULL DEFAULT 50,
          instructions TEXT,
          cover_image VARCHAR(255) DEFAULT NULL,
          is_featured TINYINT(1) NOT NULL DEFAULT 0,
          is_published TINYINT(1) NOT NULL DEFAULT 1,
          sort_order INT NOT NULL DEFAULT 0,
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          INDEX idx_exam_packs_pub (is_published, sort_order, id),
          INDEX idx_exam_packs_subject (subject)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    $steps[] = 'exam_packs table ready';
} catch (Throwable $e) {
    $steps[] = 'exam_packs: ' . $e->getMessage();
}

try {
    db()->exec("
        CREATE TABLE IF NOT EXISTS exam_questions (
          id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          exam_pack_id INT UNSIGNED NOT NULL,
          question_text TEXT NOT NULL,
          options_json TEXT NOT NULL,
          correct_key VARCHAR(10) NOT NULL,
          explanation TEXT,
          sort_order INT NOT NULL DEFAULT 0,
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          FOREIGN KEY (exam_pack_id) REFERENCES exam_packs(id) ON DELETE CASCADE,
          INDEX idx_exam_questions_pack (exam_pack_id, sort_order, id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    $steps[] = 'exam_questions table ready';
} catch (Throwable $e) {
    $steps[] = 'exam_questions: ' . $e->getMessage();
}

try {
    db()->exec("
        CREATE TABLE IF NOT EXISTS exam_purchases (
          id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          student_id INT UNSIGNED NOT NULL,
          exam_pack_id INT UNSIGNED NOT NULL,
          payment_id INT UNSIGNED DEFAULT NULL,
          status ENUM('pending','active','cancelled') NOT NULL DEFAULT 'pending',
          purchased_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          UNIQUE KEY uk_exam_purchase (student_id, exam_pack_id),
          FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
          FOREIGN KEY (exam_pack_id) REFERENCES exam_packs(id) ON DELETE CASCADE,
          INDEX idx_exam_purchases_status (student_id, status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    $steps[] = 'exam_purchases table ready';
} catch (Throwable $e) {
    $steps[] = 'exam_purchases: ' . $e->getMessage();
}

try {
    db()->exec("
        CREATE TABLE IF NOT EXISTS exam_attempts (
          id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          student_id INT UNSIGNED NOT NULL,
          exam_pack_id INT UNSIGNED NOT NULL,
          score INT UNSIGNED NOT NULL DEFAULT 0,
          correct_count INT UNSIGNED NOT NULL DEFAULT 0,
          total_questions INT UNSIGNED NOT NULL DEFAULT 0,
          passed TINYINT(1) NOT NULL DEFAULT 0,
          answers_json TEXT,
          time_spent_seconds INT UNSIGNED NOT NULL DEFAULT 0,
          started_at DATETIME DEFAULT NULL,
          completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
          FOREIGN KEY (exam_pack_id) REFERENCES exam_packs(id) ON DELETE CASCADE,
          INDEX idx_exam_attempts_student (student_id, exam_pack_id, completed_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    $steps[] = 'exam_attempts table ready';
} catch (Throwable $e) {
    $steps[] = 'exam_attempts: ' . $e->getMessage();
}

try {
    db()->exec('ALTER TABLE payment_items MODIFY course_id INT UNSIGNED NULL');
    $steps[] = 'payment_items.course_id nullable';
} catch (Throwable $e) {
    $steps[] = 'payment_items.course_id: ' . $e->getMessage();
}

try {
    db()->exec('ALTER TABLE payment_items ADD COLUMN exam_pack_id INT UNSIGNED NULL AFTER course_id');
    $steps[] = 'payment_items.exam_pack_id added';
} catch (Throwable $e) {
    $steps[] = 'payment_items.exam_pack_id: ' . $e->getMessage();
}

foreach ($steps as $step) {
    echo $step . "\n";
}

echo "Phase 12 done.\n";
