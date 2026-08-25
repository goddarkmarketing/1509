<?php
declare(strict_types=1);

require dirname(__DIR__) . '/includes/database.php';
require dirname(__DIR__) . '/includes/functions.php';

/** Omise sandbox keys from docs.opn.ooo — for local UI preview only. */
$settings = [
    'omise_enabled' => '1',
    'omise_public_key' => 'pkey_test_bgwtwgdmon2i23pwaxw',
    'omise_secret_key' => 'skey_test_ueq529yrmuzk0gmu730',
];

$stmt = db()->prepare('
    INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?)
    ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
');

foreach ($settings as $key => $value) {
    $stmt->execute([$key, $value]);
    echo $key . ' = ' . $value . PHP_EOL;
}

resetSettingsCache();
echo 'Done — refresh checkout page.' . PHP_EOL;
