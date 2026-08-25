<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/database.php';

$steps = [];

try {
    $hasUrl = (bool) db()->query("SHOW COLUMNS FROM announcements LIKE 'attachment_url'")->fetch();
    if (!$hasUrl) {
        db()->exec("
            ALTER TABLE announcements
            ADD COLUMN attachment_url VARCHAR(255) DEFAULT NULL AFTER image_url,
            ADD COLUMN attachment_name VARCHAR(255) DEFAULT NULL AFTER attachment_url
        ");
        $steps[] = 'announcements attachment columns added';
    } else {
        $steps[] = 'announcements attachment columns already exist';
    }
} catch (Throwable $e) {
    $steps[] = 'announcements attachment: ' . $e->getMessage();
}

foreach ($steps as $step) {
    echo $step . PHP_EOL;
}
