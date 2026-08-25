<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/site_content.php';

$home = getHomepageContent();
$defaults = defaultHomepageContent();
$home['reviews'] = $defaults['reviews'];
saveJsonSetting('content_homepage_json', $home);

echo 'Updated reviews: ' . count($home['reviews']['items']) . PHP_EOL;
foreach ($home['reviews']['items'] as $i => $item) {
    echo ($i + 1) . '. ' . ($item['name'] ?? '') . PHP_EOL;
}
