<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/database.php';

$steps = [];

try {
    db()->exec("
        CREATE TABLE IF NOT EXISTS announcements (
          id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          slug VARCHAR(120) NOT NULL UNIQUE,
          title VARCHAR(200) NOT NULL,
          excerpt VARCHAR(500) DEFAULT NULL,
          body TEXT NOT NULL,
          image_url VARCHAR(255) DEFAULT NULL,
          attachment_url VARCHAR(255) DEFAULT NULL,
          attachment_name VARCHAR(255) DEFAULT NULL,
          category ENUM('general','promo','course','event') NOT NULL DEFAULT 'general',
          is_pinned TINYINT(1) DEFAULT 0,
          is_published TINYINT(1) DEFAULT 1,
          published_at DATETIME DEFAULT NULL,
          sort_order INT DEFAULT 0,
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          INDEX idx_announcements_published (is_published, published_at, sort_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    $steps[] = 'announcements table ready';
} catch (Throwable $e) {
    $steps[] = 'announcements: ' . $e->getMessage();
}

foreach ($steps as $step) {
    echo $step . PHP_EOL;
}
