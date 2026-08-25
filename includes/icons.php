<?php

declare(strict_types=1);

/**
 * Lucide icons — https://github.com/lucide-icons/lucide (ISC)
 * SVG set vendored from lucide-static into assets/vendor/lucide/icons/
 */

function lucideIconsDir(): string
{
    return dirname(__DIR__) . '/assets/vendor/lucide/icons';
}

/**
 * @return array<string, string> name => inner SVG markup
 */
function lucideIconAliasMap(): array
{
    return [
        // Lucide removed brand icons — keep aliases for existing call sites
        'facebook' => '__brand:facebook',
        'youtube' => '__brand:youtube',
        // Common renames / shorthand used in this project
        'home' => 'house',
        'edit' => 'square-pen',
        'close' => 'x',
        'trash' => 'trash-2',
        'logout' => 'log-out',
        'help' => 'circle-help',
        'check-circle' => 'circle-check',
        'play-circle' => 'circle-play',
    ];
}

function lucide_icon(string $name, array $options = []): string
{
    static $cache = [];

    $name = strtolower(trim($name));
    $name = str_replace('_', '-', $name);
    if ($name === '') {
        return '';
    }

    $aliases = lucideIconAliasMap();
    if (isset($aliases[$name])) {
        $mapped = $aliases[$name];
        if (str_starts_with($mapped, '__brand:')) {
            return brand_icon(substr($mapped, 8), $options);
        }
        $name = $mapped;
    }

    if (!isset($cache[$name])) {
        $path = lucideIconsDir() . '/' . $name . '.svg';
        if (!is_file($path)) {
            $cache[$name] = false;
        } else {
            $raw = (string) file_get_contents($path);
            if (preg_match('/<svg\b[^>]*>(.*)<\/svg>/is', $raw, $m)) {
                $cache[$name] = trim($m[1]);
            } else {
                $cache[$name] = false;
            }
        }
    }

    if ($cache[$name] === false) {
        // Fallback: circle-help, then empty
        if ($name !== 'circle-help') {
            return lucide_icon('circle-help', $options);
        }
        return '';
    }

    $size = (int) ($options['size'] ?? 24);
    $class = trim((string) ($options['class'] ?? ''));
    $stroke = (string) ($options['stroke'] ?? '2');
    $attrs = (string) ($options['attrs'] ?? '');
    $label = trim((string) ($options['label'] ?? ''));
    $classAttr = $class !== '' ? ' class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '"' : '';

    if ($label !== '') {
        $a11y = ' role="img" aria-label="' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '"';
    } else {
        $a11y = ' aria-hidden="true"';
    }

    return '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="'
        . htmlspecialchars($stroke, ENT_QUOTES, 'UTF-8')
        . '" stroke-linecap="round" stroke-linejoin="round"'
        . $a11y
        . $classAttr
        . ($attrs !== '' ? ' ' . $attrs : '')
        . '>' . $cache[$name] . '</svg>';
}

/** Brand / payment marks without Lucide equivalents (filled) */
function brand_icon(string $name, array $options = []): string
{
    $size = (int) ($options['size'] ?? 24);
    $class = trim((string) ($options['class'] ?? ''));
    $classAttr = $class !== '' ? ' class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '"' : '';
    $colored = !empty($options['colored']);

    // Official-style brand glyphs (Simple Icons / brand guidelines inspired)
    if ($colored) {
        $coloredSvgs = [
            'line' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" aria-hidden="true"' . $classAttr . '><path fill="#06C755" d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/></svg>',
            'facebook' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" aria-hidden="true"' . $classAttr . '><path fill="#1877F2" d="M24 12.073C24 5.446 18.627.073 12 .073S0 5.446 0 12.073C0 18.063 4.388 23.027 10.125 23.927v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.063 24 12.073"/></svg>',
            'youtube' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" aria-hidden="true"' . $classAttr . '><path fill="#FF0000" d="M23.5 6.2a3.02 3.02 0 0 0-2.12-2.14C19.4 3.5 12 3.5 12 3.5s-7.4 0-9.38.56A3.02 3.02 0 0 0 .5 6.2 31.8 31.8 0 0 0 0 12a31.8 31.8 0 0 0 .5 5.8 3.02 3.02 0 0 0 2.12 2.14C4.6 20.5 12 20.5 12 20.5s7.4 0 9.38-.56a3.02 3.02 0 0 0 2.12-2.14A31.8 31.8 0 0 0 24 12a31.8 31.8 0 0 0-.5-5.8z"/><path fill="#fff" d="M9.75 15.5v-7l6.5 3.5-6.5 3.5z"/></svg>',
            'tiktok' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" aria-hidden="true"' . $classAttr . '><path fill="#25F4EE" d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.02 1.95 2.89 2.89 0 0 1 2.13-4.84c.28 0 .54.04.79.1v-3.5a6.37 6.37 0 0 0-.79-.05A6.34 6.34 0 0 0 3.15 15.2a6.34 6.34 0 0 0 6.34 6.34 6.34 6.34 0 0 0 6.34-6.34V8.75a8.18 8.18 0 0 0 4.76 1.52V6.82a4.85 4.85 0 0 1-1-.13z"/><path fill="#FE2C55" d="M17.89 7.09a8.18 8.18 0 0 0 2.71 1.23V6.82a4.85 4.85 0 0 1-2.71.27z"/><path fill="#111111" d="M16.14 8.22a4.83 4.83 0 0 1-3.77-4.25V2h-1.7v13.67a2.89 2.89 0 1 1-2.89-2.89c.28 0 .54.04.79.1v-1.78a6.34 6.34 0 1 0 5.55 6.29V8.75c.65.42 1.4.72 2.21.87V7.4c-.07-.05-.14-.1-.19-.18z"/></svg>',
            'promptpay' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="#1B3C8C" stroke-width="1.8" aria-hidden="true"' . $classAttr . '><rect x="2" y="2" width="7" height="7" rx="1"/><rect x="15" y="2" width="7" height="7" rx="1"/><rect x="2" y="15" width="7" height="7" rx="1"/><rect x="11" y="11" width="3" height="3" fill="#1B3C8C" stroke="none"/><rect x="16" y="11" width="3" height="3" fill="#1B3C8C" stroke="none"/><rect x="11" y="16" width="3" height="3" fill="#1B3C8C" stroke="none"/><rect x="16" y="16" width="3" height="3" fill="#1B3C8C" stroke="none"/><rect x="19" y="19" width="3" height="3" fill="#1B3C8C" stroke="none"/></svg>',
        ];
        if (isset($coloredSvgs[$name])) {
            return $coloredSvgs[$name];
        }
    }

    $paths = [
        'line' => '<path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>',
        'tiktok' => '<path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-2.88 2.5 2.89 2.89 0 0 1-2.89-2.89 2.89 2.89 0 0 1 2.89-2.89c.28 0 .54.04.79.1v-3.5a6.37 6.37 0 0 0-.79-.05A6.34 6.34 0 0 0 3.15 15.2a6.34 6.34 0 0 0 6.34 6.34 6.34 6.34 0 0 0 6.34-6.34V8.75a8.18 8.18 0 0 0 4.76 1.52V6.82a4.85 4.85 0 0 1-1-.13z"/>',
        'facebook' => '<path d="M24 12.073C24 5.446 18.627.073 12 .073S0 5.446 0 12.073C0 18.063 4.388 23.027 10.125 23.927v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.063 24 12.073"/>',
        'youtube' => '<path d="M23.5 6.2a3 3 0 0 0-2.1-2.1C19.5 3.5 12 3.5 12 3.5s-7.5 0-9.4.6A3 3 0 0 0 .5 6.2 31.5 31.5 0 0 0 0 12a31.5 31.5 0 0 0 .5 5.8 3 3 0 0 0 2.1 2.1c1.9.6 9.4.6 9.4.6s7.5 0 9.4-.6a3 3 0 0 0 2.1-2.1A31.5 31.5 0 0 0 24 12a31.5 31.5 0 0 0-.5-5.8zM9.75 15.5v-7l6.5 3.5-6.5 3.5z"/>',
        'promptpay' => '<rect x="2" y="2" width="7" height="7" rx="1"/><rect x="15" y="2" width="7" height="7" rx="1"/><rect x="2" y="15" width="7" height="7" rx="1"/><rect x="11" y="11" width="3" height="3"/><rect x="16" y="11" width="3" height="3"/><rect x="11" y="16" width="3" height="3"/><rect x="16" y="16" width="3" height="3"/><rect x="19" y="19" width="3" height="3"/>',
    ];

    if (isset($paths[$name])) {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"' . $classAttr . '>' . $paths[$name] . '</svg>';
    }

    return lucide_icon($name, array_merge($options, ['size' => $size]));
}

/** Inline text + Lucide arrow for “ดูเพิ่มเติม” style links */
function lucide_text_link_suffix(int $size = 16): string
{
    return ' ' . lucide_icon('arrow-right', ['size' => $size, 'stroke' => '2', 'class' => 'lucide-inline']);
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

function contactChannelIcon(string $tone, int $size = 24, bool $colored = false): string
{
    $brandOpts = ['size' => $size, 'colored' => $colored];
    return match ($tone) {
        'line' => brand_icon('line', $brandOpts),
        'facebook', 'fb' => brand_icon('facebook', $brandOpts),
        'youtube' => brand_icon('youtube', $brandOpts),
        'tiktok' => brand_icon('tiktok', $brandOpts),
        'phone' => $colored
            ? '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path fill="#25D366" d="M6.62 10.79a15.15 15.15 0 0 0 6.59 6.59l2.2-2.2a1 1 0 0 1 1.01-.24c1.12.37 2.33.57 3.58.57a1 1 0 0 1 1 1V20a1 1 0 0 1-1 1C10.4 21 3 13.6 3 4a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1c0 1.25.2 2.46.57 3.58a1 1 0 0 1-.25 1.02l-2.2 2.19z"/></svg>'
            : lucide_icon('phone', ['size' => $size]),
        'email' => $colored
            ? '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" aria-hidden="true"><path fill="#EA4335" d="M20 4H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2zm0 4-8 5L4 8V6l8 5 8-5v2z"/></svg>'
            : lucide_icon('mail', ['size' => $size]),
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

function trustBarIcon(array $item): string
{
    $icon = match ($item['mode'] ?? 'manual') {
        'students' => 'users',
        'courses' => 'book-open',
        'lessons' => 'list-video',
        default => 'layers',
    };

    return lucide_icon($icon, ['size' => 22, 'stroke' => '1.75']);
}

function homeStepIcon(int $index): string
{
    $icons = ['user-plus', 'banknote', 'circle-play', 'file-badge'];

    return lucide_icon($icons[$index] ?? 'badge-check', ['size' => 28, 'stroke' => '2', 'attrs' => 'stroke="#fff"']);
}

function adminBtnIcon(string $name, int $size = 16): string
{
    return lucide_icon($name, ['size' => $size, 'class' => 'btn-icon', 'stroke' => '1.75']);
}
