<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/database.php';

header('Content-Type: text/plain; charset=utf-8');
$steps = [];

try {
    db()->exec('
        CREATE TABLE IF NOT EXISTS password_reset_tokens (
          id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          student_id INT UNSIGNED NOT NULL,
          token_hash VARCHAR(64) NOT NULL,
          expires_at DATETIME NOT NULL,
          used_at DATETIME DEFAULT NULL,
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          INDEX idx_token_hash (token_hash),
          INDEX idx_student_id (student_id),
          FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ');
    $steps[] = 'password_reset_tokens table ready';
} catch (Throwable $e) {
    $steps[] = 'password_reset_tokens: ' . $e->getMessage();
}

$defaults = [
    'email_transport' => 'mail',
    'smtp_host' => '',
    'smtp_port' => '587',
    'smtp_user' => '',
    'smtp_pass' => '',
    'smtp_encryption' => 'tls',
    'certificate_require_quiz' => '0',
    'privacy_policy_html' => '',
    'terms_html' => '',
];
$ins = db()->prepare('INSERT IGNORE INTO site_settings (setting_key, setting_value) VALUES (?, ?)');
foreach ($defaults as $k => $v) {
    $ins->execute([$k, $v]);
}
$steps[] = 'phase4 settings seeded';

echo "Wenxin LMS Phase 4 migration complete\n\n";
foreach ($steps as $step) {
    echo "- {$step}\n";
}
