<?php
declare(strict_types=1);

/**
 * CLI backup: php database/backup_cron.php
 * Saves to storage/backups/wenxin_lms_YYYY-mm-dd_His.sql
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

require_once dirname(__DIR__) . '/includes/database.php';
require_once dirname(__DIR__) . '/includes/backup.php';

$created = createBackupFile();
pruneOldBackups(14);

echo 'Backup saved: ' . $created['path'] . PHP_EOL;
