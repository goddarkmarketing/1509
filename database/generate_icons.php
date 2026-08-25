<?php

declare(strict_types=1);

$names = [
    'layout-dashboard', 'book-open', 'circle-play', 'users', 'credit-card', 'clipboard-check',
    'gamepad-2', 'megaphone', 'file-text', 'ticket', 'shield-check', 'database', 'settings',
    'external-link', 'log-out', 'menu', 'search', 'shopping-cart', 'chevron-left', 'chevron-right',
    'clock', 'chart-column', 'star', 'circle', 'mail', 'graduation-cap', 'award', 'video', 'lock',
    'check', 'circle-check', 'x', 'eye', 'eye-off', 'phone', 'copy', 'upload', 'shield',
    'smartphone', 'message-circle', 'image', 'pencil', 'calendar', 'sliders-horizontal', 'play',
    'book-open-check', 'list-video', 'headphones', 'sparkles', 'target', 'languages', 'circle-help',
    'plus', 'minus', 'landmark', 'receipt', 'file', 'house', 'map-pinned', 'panel-bottom',
    'facebook', 'youtube', 'music-2', 'messages-square', 'circle-dot', 'badge-check', 'user-check',
    'trash-2', 'square-pen', 'image-off', 'file-down', 'banknote', 'headset', 'monitor-smartphone',
    'life-buoy',
];

$icons = [];
foreach ($names as $name) {
    $url = 'https://unpkg.com/lucide-static@0.468.0/icons/' . rawurlencode($name) . '.svg';
    $svg = @file_get_contents($url);
    if ($svg === false || !preg_match('#<svg[^>]*>(.*)</svg>#s', $svg, $m)) {
        fwrite(STDERR, "Missing: $name\n");
        continue;
    }
    $inner = trim(preg_replace('/\s+/', ' ', $m[1]));
    $icons[$name] = $inner;
}

ksort($icons);

$entries = [];
foreach ($icons as $name => $inner) {
    $entries[] = "            " . var_export($name, true) . ' => ' . var_export($inner, true) . ',';
}

$body = <<<'PHP'
<?php

declare(strict_types=1);

/**
 * Lucide icons (https://lucide.dev/) — ISC License, lucide-static v0.468.0
 */

function lucide_icon(string $name, array $options = []): string
{
    static $icons = null;
    if ($icons === null) {
        $icons = [
PHP;

$body .= "\n" . implode("\n", $entries) . "\n";
$body .= <<<'PHP'
        ];
    }

    $inner = $icons[$name] ?? $icons['circle-help'] ?? '';
    if ($inner === '') {
        return '';
    }

    $size = (int) ($options['size'] ?? 24);
    $class = trim((string) ($options['class'] ?? ''));
    $stroke = (string) ($options['stroke'] ?? '2');
    $attrs = (string) ($options['attrs'] ?? '');
    $classAttr = $class !== '' ? ' class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '"' : '';

    return '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="' . htmlspecialchars($stroke, ENT_QUOTES, 'UTF-8') . '" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"' . $classAttr . ($attrs !== '' ? ' ' . $attrs : '') . '>' . $inner . '</svg>';
}

/** Brand icons without Lucide equivalents — filled for recognizability */
function brand_icon(string $name, array $options = []): string
{
    $size = (int) ($options['size'] ?? 24);
    $class = trim((string) ($options['class'] ?? ''));
    $classAttr = $class !== '' ? ' class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '"' : '';

    $paths = [
        'line' => '<path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>',
        'tiktok' => '<path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-2.88 2.5 2.89 2.89 0 0 1-2.89-2.89 2.89 2.89 0 0 1 2.89-2.89c.28 0 .54.04.79.1v-3.5a6.37 6.37 0 0 0-.79-.05A6.34 6.34 0 0 0 3.15 15.2a6.34 6.34 0 0 0 6.34 6.34 6.34 6.34 0 0 0 6.34-6.34V8.75a8.18 8.18 0 0 0 4.76 1.52V6.82a4.85 4.85 0 0 1-1-.13z"/>',
    ];

    if (isset($paths[$name])) {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"' . $classAttr . '>' . $paths[$name] . '</svg>';
    }

    return lucide_icon($name, array_merge($options, ['size' => $size]));
}

function whyCardIcon(int $index): string
{
    $icons = ['graduation-cap', 'book-open-check', 'monitor-smartphone', 'headset'];
    return lucide_icon($icons[$index] ?? 'sparkles', ['size' => 40, 'class' => 'why-card-lucide', 'stroke' => '1.75']);
}

function courseIncludedIcon(string $key): string
{
    return match ($key) {
        'video' => lucide_icon('video', ['size' => 22]),
        'doc' => lucide_icon('file-text', ['size' => 22]),
        'device' => lucide_icon('monitor-smartphone', ['size' => 22]),
        'support' => lucide_icon('life-buoy', ['size' => 22]),
        default => lucide_icon('circle-check', ['size' => 22]),
    };
}

function contactChannelIcon(string $tone, int $size = 24): string
{
    return match ($tone) {
        'line' => brand_icon('line', ['size' => $size]),
        'facebook' => lucide_icon('facebook', ['size' => $size]),
        'youtube' => lucide_icon('youtube', ['size' => $size]),
        'tiktok' => brand_icon('tiktok', ['size' => $size]),
        'phone' => lucide_icon('phone', ['size' => $size]),
        default => lucide_icon('mail', ['size' => $size]),
    };
}

function instructorCredentialIcon(int $index): string
{
    $icons = ['graduation-cap', 'clock', 'users', 'award'];
    return lucide_icon($icons[$index] ?? 'award', ['size' => 18, 'stroke' => '1.75']);
}

function instructorStatIcon(string $icon): string
{
    return match ($icon) {
        'courses' => lucide_icon('book-open', ['size' => 22, 'stroke' => '1.75']),
        'star' => lucide_icon('star', ['size' => 22, 'stroke' => '1.75']),
        'video' => lucide_icon('video', ['size' => 22, 'stroke' => '1.75']),
        default => lucide_icon('users', ['size' => 22, 'stroke' => '1.75']),
    };
}
PHP;

file_put_contents(dirname(__DIR__) . '/includes/icons.php', $body);
echo "Wrote " . count($icons) . " icons\n";
