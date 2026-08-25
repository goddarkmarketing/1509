<?php

declare(strict_types=1);

/** @var list<array{label: string, ok: bool, detail: string}> */
$GLOBALS['php_test_checks'] = [];

function test_check(string $label, bool $ok, string $detail = ''): void
{
    $GLOBALS['php_test_checks'][] = ['label' => $label, 'ok' => $ok, 'detail' => $detail];
}

function test_reset_checks(): void
{
    $GLOBALS['php_test_checks'] = [];
}

/** @return array{pass: int, fail: int} */
function test_print_summary(string $title): array
{
    echo "=== {$title} ===\n\n";
    $pass = 0;
    $fail = 0;
    foreach ($GLOBALS['php_test_checks'] as $c) {
        $icon = $c['ok'] ? 'PASS' : 'FAIL';
        if ($c['ok']) {
            $pass++;
        } else {
            $fail++;
        }
        echo "[{$icon}] {$c['label']}";
        if ($c['detail'] !== '') {
            echo " — {$c['detail']}";
        }
        echo "\n";
    }
    echo "\nResult: {$pass} passed, {$fail} failed\n";

    return ['pass' => $pass, 'fail' => $fail];
}
