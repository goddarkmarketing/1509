<?php

declare(strict_types=1);

/**
 * Run all DB migrations (phase 3–5). Open once in browser after schema.sql import.
 * DELETE or protect this file on production after use.
 */

require_once dirname(__DIR__) . '/includes/database.php';

header('Content-Type: text/plain; charset=utf-8');

$files = [
    __DIR__ . '/run_migration_phase3.php',
    __DIR__ . '/run_migration_phase4.php',
    __DIR__ . '/run_migration_phase5.php',
    __DIR__ . '/run_migration_phase6.php',
    __DIR__ . '/run_migration_phase7.php',
    __DIR__ . '/run_migration_phase8.php',
    __DIR__ . '/run_migration_phase9.php',
    __DIR__ . '/run_migration_phase10.php',
    __DIR__ . '/run_migration_phase11.php',
];

foreach ($files as $file) {
    echo "=== " . basename($file) . " ===\n";
    if (!is_file($file)) {
        echo "SKIP: file not found\n\n";
        continue;
    }
    ob_start();
    include $file;
    echo ob_get_clean();
    echo "\n";
}

echo "All migrations finished.\n";
