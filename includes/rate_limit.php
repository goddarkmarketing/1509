<?php
declare(strict_types=1);

const LOGIN_MAX_ATTEMPTS = 5;
const LOGIN_LOCKOUT_MINUTES = 15;

function clientIpAddress(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    return is_string($ip) ? $ip : '0.0.0.0';
}

function isLoginRateLimited(string $type, string $identifier): bool
{
    $identifier = trim($identifier);
    if ($identifier === '') {
        return false;
    }

    $since = date('Y-m-d H:i:s', time() - LOGIN_LOCKOUT_MINUTES * 60);
    $ip = clientIpAddress();

    $stmt = db()->prepare('
        SELECT COUNT(*) FROM login_attempts
        WHERE attempt_type = ?
          AND created_at >= ?
          AND (identifier = ? OR ip_address = ?)
    ');
    $stmt->execute([$type, $since, $identifier, $ip]);
    return (int) $stmt->fetchColumn() >= LOGIN_MAX_ATTEMPTS;
}

function recordLoginFailure(string $type, string $identifier): void
{
    $identifier = trim($identifier);
    if ($identifier === '') {
        return;
    }
    db()->prepare('INSERT INTO login_attempts (attempt_type, identifier, ip_address) VALUES (?, ?, ?)')
        ->execute([$type, $identifier, clientIpAddress()]);
}

function clearLoginAttempts(string $type, string $identifier): void
{
    $identifier = trim($identifier);
    if ($identifier === '') {
        return;
    }
    $ip = clientIpAddress();
    db()->prepare('DELETE FROM login_attempts WHERE attempt_type = ? AND (identifier = ? OR ip_address = ?)')
        ->execute([$type, $identifier, $ip]);
}

function loginRateLimitMessage(): string
{
    return 'พยายามเข้าสู่ระบบมากเกินไป กรุณารอ ' . LOGIN_LOCKOUT_MINUTES . ' นาทีแล้วลองใหม่';
}
