<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/database.php';
require_once dirname(__DIR__) . '/includes/line_messaging.php';

header('Content-Type: text/plain; charset=utf-8');

if (!isLineOaEnabled()) {
    echo "LINE OA is disabled or token missing.\n";
    exit(1);
}

$sent = sendDueClassReminders(60, 10);
echo "Wenxin LINE reminder cron OK\n";
echo "- reminders sent: {$sent}\n";
echo '- time: ' . date('Y-m-d H:i:s') . "\n";
