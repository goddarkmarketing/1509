<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/database.php';

header('Content-Type: text/plain; charset=utf-8');
$steps = [];

try {
    db()->exec('
        CREATE TABLE IF NOT EXISTS login_attempts (
          id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          attempt_type ENUM("student","admin") NOT NULL,
          identifier VARCHAR(150) NOT NULL,
          ip_address VARCHAR(45) NOT NULL,
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          INDEX idx_attempt_lookup (attempt_type, identifier, created_at),
          INDEX idx_attempt_ip (attempt_type, ip_address, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ');
    $steps[] = 'login_attempts table ready';
} catch (Throwable $e) {
    $steps[] = 'login_attempts: ' . $e->getMessage();
}

try {
    db()->exec("ALTER TABLE payments ADD COLUMN payment_method VARCHAR(20) NOT NULL DEFAULT 'transfer' AFTER status");
    $steps[] = 'payments.payment_method added';
} catch (Throwable $e) {
    $steps[] = 'payments.payment_method: ' . $e->getMessage();
}

try {
    db()->exec('ALTER TABLE payments ADD COLUMN omise_charge_id VARCHAR(50) DEFAULT NULL AFTER payment_method');
    $steps[] = 'payments.omise_charge_id added';
} catch (Throwable $e) {
    $steps[] = 'payments.omise_charge_id: ' . $e->getMessage();
}

$defaults = [
    'omise_enabled' => '0',
    'omise_public_key' => '',
    'omise_secret_key' => '',
];
$ins = db()->prepare('INSERT IGNORE INTO site_settings (setting_key, setting_value) VALUES (?, ?)');
foreach ($defaults as $k => $v) {
    $ins->execute([$k, $v]);
}
$steps[] = 'phase5 settings seeded';

echo "Wenxin LMS Phase 5 migration complete\n\n";
foreach ($steps as $step) {
    echo "- {$step}\n";
}
