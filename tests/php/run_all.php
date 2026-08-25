<?php

declare(strict_types=1);

/**
 * รัน PHP tests ทั้งหมด (schema → booking → integration)
 *
 * รัน: php tests/php/run_all.php
 */

$tests = [
    'schema_test.php',
    'courses_test.php',
    'booking_test.php',
    'integration_flow_test.php',
];

$failed = 0;
foreach ($tests as $file) {
    $path = __DIR__ . '/' . $file;
    if (!is_file($path)) {
        echo "SKIP: {$file} not found\n\n";
        $failed++;
        continue;
    }

    passthru(($php = (defined('PHP_BINARY') && PHP_BINARY) ? PHP_BINARY : 'php') . ' ' . escapeshellarg($path), $code);
    echo "\n";
    if ($code !== 0) {
        $failed++;
    }
}

echo $failed === 0 ? "All PHP tests passed.\n" : "{$failed} test suite(s) failed.\n";
exit($failed > 0 ? 1 : 0);
