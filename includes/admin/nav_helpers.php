<?php

declare(strict_types=1);

function adminNavIcon(string $name): string
{
    $map = [
        'dashboard' => 'layout-dashboard',
        'courses' => 'book-open',
        'lessons' => 'circle-play',
        'students' => 'users',
        'payments' => 'credit-card',
        'quizzes' => 'clipboard-check',
        'games' => 'gamepad-2',
        'announcements' => 'megaphone',
        'content' => 'file-text',
        'coupons' => 'ticket',
        'users' => 'shield-check',
        'backup' => 'database',
        'settings' => 'settings',
        'external' => 'external-link',
        'logout' => 'log-out',
    ];

    return lucide_icon($map[$name] ?? 'circle-help', ['class' => 'nav-icon', 'stroke' => '1.75']);
}

function adminNavLink(string $page, string $href, string $label, string $icon, string $currentPage, ?string $extraClass = null): void
{
    $classes = trim(($currentPage === $page ? 'active' : '') . ' ' . ($extraClass ?? ''));
    echo '<a href="' . e($href) . '"' . ($classes !== '' ? ' class="' . e($classes) . '"' : '') . '>'
        . adminNavIcon($icon)
        . '<span>' . e($label) . '</span></a>';
}
