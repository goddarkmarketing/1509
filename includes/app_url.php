<?php

declare(strict_types=1);

/**
 * Resolve public base URL for assets and links.
 * Fixes common production misconfig: APP_URL=/LMS while site runs at domain root.
 */
function detectAppUrlFromRequest(): string
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
        || ((string) ($_SERVER['SERVER_PORT'] ?? '') === '443');
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
    $basePath = '';

    if (preg_match('#^(.*)/(?:public|admin|database)(?:/|$)#', $script, $matches)) {
        $basePath = rtrim($matches[1], '/');
    } elseif (preg_match('#^(.*)/includes(?:/|$)#', $script, $matches)) {
        $basePath = rtrim($matches[1], '/');
    } else {
        $dir = dirname($script);
        $basePath = ($dir === '/' || $dir === '.') ? '' : rtrim($dir, '/');
    }

    return $scheme . '://' . $host . $basePath;
}

function resolveAppUrl(): string
{
    $configured = env('APP_URL');
    if ($configured !== null && $configured !== '') {
        $configured = rtrim($configured, '/');

        if ($configured === '/LMS' || $configured === 'LMS') {
            $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
            if (preg_match('#^/LMS(?:/|$)#', $script)) {
                return '/LMS';
            }

            return detectAppUrlFromRequest();
        }

        if (!str_contains($configured, '://') && str_starts_with($configured, '/')) {
            return $configured;
        }

        return $configured;
    }

    return detectAppUrlFromRequest();
}
