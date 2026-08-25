<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/database.php';

header('Content-Type: text/plain; charset=utf-8');
$steps = [];

try {
    db()->exec("ALTER TABLE courses ADD COLUMN course_type ENUM('recorded','live','hybrid') NOT NULL DEFAULT 'recorded' AFTER is_active");
    $steps[] = 'courses.course_type added';
} catch (Throwable $e) {
    $steps[] = 'courses.course_type: ' . $e->getMessage();
}

try {
    db()->exec('ALTER TABLE courses ADD COLUMN zoom_url VARCHAR(500) DEFAULT NULL AFTER course_type');
    $steps[] = 'courses.zoom_url added';
} catch (Throwable $e) {
    $steps[] = 'courses.zoom_url: ' . $e->getMessage();
}

try {
    db()->exec('
        CREATE TABLE IF NOT EXISTS course_sessions (
          id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          course_id INT UNSIGNED NOT NULL,
          title VARCHAR(200) NOT NULL DEFAULT "",
          starts_at DATETIME NOT NULL,
          ends_at DATETIME NOT NULL,
          capacity INT UNSIGNED NOT NULL DEFAULT 20,
          booked_count INT UNSIGNED NOT NULL DEFAULT 0,
          zoom_url VARCHAR(500) DEFAULT NULL,
          status ENUM("scheduled","cancelled","completed") NOT NULL DEFAULT "scheduled",
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          INDEX idx_session_course (course_id),
          INDEX idx_session_starts (starts_at),
          INDEX idx_session_status (status),
          CONSTRAINT fk_session_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ');
    $steps[] = 'course_sessions table ready';
} catch (Throwable $e) {
    $steps[] = 'course_sessions: ' . $e->getMessage();
}

try {
    db()->exec('
        CREATE TABLE IF NOT EXISTS session_bookings (
          id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          session_id INT UNSIGNED NOT NULL,
          student_id INT UNSIGNED NOT NULL,
          payment_id INT UNSIGNED DEFAULT NULL,
          status ENUM("pending","confirmed","cancelled") NOT NULL DEFAULT "pending",
          booked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          note VARCHAR(500) DEFAULT NULL,
          INDEX idx_booking_session (session_id),
          INDEX idx_booking_student (student_id),
          INDEX idx_booking_payment (payment_id),
          INDEX idx_booking_status (status),
          CONSTRAINT fk_booking_session FOREIGN KEY (session_id) REFERENCES course_sessions(id) ON DELETE CASCADE,
          CONSTRAINT fk_booking_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ');
    $steps[] = 'session_bookings table ready';
} catch (Throwable $e) {
    $steps[] = 'session_bookings: ' . $e->getMessage();
}

try {
    db()->exec('ALTER TABLE students ADD COLUMN line_user_id VARCHAR(64) DEFAULT NULL AFTER line_id');
    $steps[] = 'students.line_user_id added';
} catch (Throwable $e) {
    $steps[] = 'students.line_user_id: ' . $e->getMessage();
}

try {
    db()->exec('ALTER TABLE payment_items ADD COLUMN session_id INT UNSIGNED DEFAULT NULL AFTER course_id');
    $steps[] = 'payment_items.session_id added';
} catch (Throwable $e) {
    $steps[] = 'payment_items.session_id: ' . $e->getMessage();
}

$defaults = [
    'line_oa_enabled' => '0',
    'line_oa_channel_secret' => '',
    'line_oa_channel_token' => '',
    'payment_gateway_note' => 'รองรับ Omise (PromptPay / บัตรเครดิต-เดบิต) — ทีมงานช่วยเชื่อมตั้งค่าให้หลังสมัครผู้ให้บริการ',
];
$ins = db()->prepare('INSERT IGNORE INTO site_settings (setting_key, setting_value) VALUES (?, ?)');
foreach ($defaults as $k => $v) {
    $ins->execute([$k, $v]);
}
$steps[] = 'phase9 settings seeded';

echo "Wenxin LMS Phase 9 migration complete\n\n";
foreach ($steps as $step) {
    echo "- {$step}\n";
}
