<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

function isLineOaEnabled(): bool
{
    return getSetting('line_oa_enabled', '0') === '1'
        && trim(getSetting('line_oa_channel_token', '')) !== '';
}

function isLineOaWebhookReady(): bool
{
    return isLineOaEnabled() && lineOaChannelSecret() !== '';
}

function lineOaChannelSecret(): string
{
    return trim(getSetting('line_oa_channel_secret', ''));
}

function lineOaChannelToken(): string
{
    return trim(getSetting('line_oa_channel_token', ''));
}

function lineOaHasChannelSecret(): bool
{
    return lineOaChannelSecret() !== '';
}

function lineOaHasChannelToken(): bool
{
    return lineOaChannelToken() !== '';
}

/**
 * Save LINE OA credentials from admin form without clearing when fields are left blank.
 */
function persistLineOaSettingsFromPost(array $post, bool $updateEnabled = true): void
{
    $stmt = db()->prepare('
        INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?)
        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
    ');

    foreach (['line_oa_channel_secret', 'line_oa_channel_token'] as $key) {
        $value = trim((string) ($post[$key] ?? ''));
        if ($value !== '') {
            $stmt->execute([$key, $value]);
        }
    }

    if (array_key_exists('line_oa_basic_id', $post)) {
        $stmt->execute(['line_oa_basic_id', trim((string) $post['line_oa_basic_id'])]);
    }

    if ($updateEnabled) {
        $stmt->execute(['line_oa_enabled', isset($post['line_oa_enabled']) ? '1' : '0']);
    }

    resetSettingsCache();
}

function lineOaPublicBaseUrl(): string
{
    if (!empty($_SERVER['HTTP_HOST'])) {
        require_once __DIR__ . '/app_url.php';
        $detected = detectAppUrlFromRequest();
        if (str_contains($detected, '://') && !lineOaRequestIsLocal()) {
            return rtrim($detected, '/');
        }
    }

    $siteUrl = trim(getSetting('site_url', ''));
    if ($siteUrl !== '' && str_contains($siteUrl, '://')) {
        return rtrim($siteUrl, '/');
    }

    if (!empty($_SERVER['HTTP_HOST'])) {
        require_once __DIR__ . '/app_url.php';
        $detected = detectAppUrlFromRequest();
        if (str_contains($detected, '://')) {
            return rtrim($detected, '/');
        }
    }

    $appUrl = APP_URL;
    if (str_contains($appUrl, '://')) {
        return rtrim($appUrl, '/');
    }

    return 'http://localhost' . ($appUrl === '' || $appUrl === '/' ? '' : $appUrl);
}

function lineOaRequestIsLocal(): bool
{
    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    if ($host === '') {
        return false;
    }

    return str_contains($host, 'localhost')
        || str_contains($host, '127.0.0.1')
        || str_contains($host, '.local')
        || str_contains($host, 'ngrok');
}

function lineOaSiteUrlIsStaleLocalhost(): bool
{
    $siteUrl = strtolower(trim(getSetting('site_url', '')));
    if ($siteUrl === '') {
        return false;
    }

    return str_contains($siteUrl, 'localhost') || str_contains($siteUrl, '127.0.0.1');
}

function lineOaWebhookUrl(): string
{
    return lineOaPublicBaseUrl() . '/public/line_webhook.php';
}

function lineOaIsLocalDev(): bool
{
    if (lineOaRequestIsLocal()) {
        return true;
    }

    $base = strtolower(lineOaPublicBaseUrl());
    return str_contains($base, 'localhost')
        || str_contains($base, '127.0.0.1')
        || str_contains($base, '.local')
        || str_contains($base, 'ngrok');
}

function lineOaStudentAccountUrl(): string
{
    return lineOaPublicBaseUrl() . '/public/profile.php?tab=bookings';
}

function lineOaBasicId(): string
{
    $id = trim(getSetting('line_oa_basic_id', ''));
    if ($id === '') {
        $id = trim(getSetting('line_id', ''));
    }

    return ltrim($id, '@');
}

function lineOaAddFriendUrl(): ?string
{
    $id = lineOaBasicId();
    if ($id === '') {
        return null;
    }

    return 'https://line.me/R/ti/p/@' . $id;
}

function normalizePhoneDigits(string $phone): string
{
    $digits = preg_replace('/\D/', '', $phone);
    if ($digits === '') {
        return '';
    }

    if (str_starts_with($digits, '66') && strlen($digits) >= 11) {
        return '0' . substr($digits, 2);
    }

    if (strlen($digits) === 9 && $digits[0] !== '0') {
        return '0' . $digits;
    }

    return $digits;
}

function phoneLookupVariants(string $phone): array
{
    $digits = preg_replace('/\D/', '', $phone);
    if ($digits === '') {
        return [];
    }

    $variants = [$digits, normalizePhoneDigits($phone)];

    if (str_starts_with($digits, '0') && strlen($digits) >= 10) {
        $variants[] = '66' . substr($digits, 1);
        $variants[] = substr($digits, 1);
    } elseif (str_starts_with($digits, '66') && strlen($digits) >= 11) {
        $local = '0' . substr($digits, 2);
        $variants[] = $local;
        $variants[] = substr($digits, 2);
    } elseif (strlen($digits) === 9) {
        $variants[] = '0' . $digits;
        $variants[] = '66' . $digits;
    }

    return array_values(array_unique(array_filter($variants)));
}

function findStudentIdByPhone(string $phone): ?int
{
    foreach (phoneLookupVariants($phone) as $candidate) {
        $stmt = db()->prepare('SELECT id FROM students WHERE phone = ? LIMIT 1');
        $stmt->execute([$candidate]);
        $studentId = $stmt->fetchColumn();
        if ($studentId) {
            return (int) $studentId;
        }
    }

    return null;
}

function studentHasLineLinked(int $studentId): bool
{
    try {
        $stmt = db()->prepare('SELECT line_user_id FROM students WHERE id = ? LIMIT 1');
        $stmt->execute([$studentId]);
        $lineUserId = trim((string) ($stmt->fetchColumn() ?: ''));

        return $lineUserId !== '';
    } catch (Throwable $e) {
        return false;
    }
}

function lineOaGetBotProfile(): ?array
{
    if (!isLineOaEnabled()) {
        return null;
    }

    $ch = curl_init('https://api.line.me/v2/bot/info');
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . lineOaChannelToken(),
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
    ]);
    $response = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200) {
        lineOaLog("bot/info HTTP {$code} {$response}");
        return null;
    }

    $json = json_decode((string) $response, true);
    return is_array($json) ? $json : null;
}

function linePushMessage(string $lineUserId, string $text): bool
{
    if (!isLineOaEnabled() || $lineUserId === '') {
        return false;
    }

    $payload = json_encode([
        'to' => $lineUserId,
        'messages' => [['type' => 'text', 'text' => $text]],
    ], JSON_UNESCAPED_UNICODE);

    $ch = curl_init('https://api.line.me/v2/bot/message/push');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . lineOaChannelToken(),
        ],
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
    ]);
    $response = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200) {
        lineOaLog("push HTTP {$code} {$response}");
        return false;
    }
    return true;
}

function lineReplyMessage(string $replyToken, string $text): bool
{
    if (!isLineOaEnabled() || $replyToken === '') {
        return false;
    }

    $payload = json_encode([
        'replyToken' => $replyToken,
        'messages' => [['type' => 'text', 'text' => $text]],
    ], JSON_UNESCAPED_UNICODE);

    $ch = curl_init('https://api.line.me/v2/bot/message/reply');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . lineOaChannelToken(),
        ],
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
    ]);
    $response = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200) {
        lineOaLog("reply HTTP {$code} {$response}");
        return false;
    }
    return true;
}

function lineOaLog(string $message): void
{
    $logDir = BASE_PATH . '/storage/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    file_put_contents($logDir . '/line_oa.log', date('Y-m-d H:i:s') . ' ' . $message . "\n", FILE_APPEND);
}

function linkLineUserByPhone(string $lineUserId, string $phone): bool
{
    if ($lineUserId === '') {
        return false;
    }

    $studentId = findStudentIdByPhone($phone);
    if (!$studentId) {
        return false;
    }

    db()->prepare('UPDATE students SET line_user_id = ? WHERE id = ?')->execute([$lineUserId, $studentId]);
    lineOaLog("linked student #{$studentId} to LINE user {$lineUserId}");
    return true;
}

function unlinkLineUser(string $lineUserId): void
{
    if ($lineUserId === '') {
        return;
    }

    db()->prepare('UPDATE students SET line_user_id = NULL WHERE line_user_id = ?')->execute([$lineUserId]);
    lineOaLog("unlinked LINE user {$lineUserId}");
}

function linePushBookingConfirmed(int $studentId, array $booking): void
{
    require_once __DIR__ . '/booking.php';
    $stmt = db()->prepare('SELECT line_user_id, full_name FROM students WHERE id = ? LIMIT 1');
    $stmt->execute([$studentId]);
    $student = $stmt->fetch();
    if (!$student || empty($student['line_user_id'])) {
        return;
    }

    $session = getSessionById((int) ($booking['session_id'] ?? 0));
    $zoom = $session ? getSessionZoomUrl($session) : null;
    $when = $session ? formatSessionRange($session) : '';

    $msg = "[THE LEONA TUTORS]\nยืนยันการจองคลาสแล้ว\n"
        . 'คอร์ส: ' . ($booking['course_title'] ?? '') . "\n"
        . 'วันเวลา: ' . $when;
    if ($zoom) {
        $msg .= "\nลิงก์ Zoom: " . $zoom;
    }
    $msg .= "\n\nดูรายละเอียด: " . lineOaStudentAccountUrl();

    linePushMessage((string) $student['line_user_id'], $msg);
}

function linePushClassReminder(int $studentId, array $booking): void
{
    $stmt = db()->prepare('SELECT line_user_id FROM students WHERE id = ? LIMIT 1');
    $stmt->execute([$studentId]);
    $lineUserId = (string) ($stmt->fetchColumn() ?: '');
    if ($lineUserId === '') {
        return;
    }

    $session = getSessionById((int) ($booking['session_id'] ?? 0));
    $zoom = $session ? getSessionZoomUrl($session) : null;
    $when = $session ? formatSessionRange($session) : '';

    $msg = "[THE LEONA TUTORS]\nแจ้งเตือนก่อนเริ่มคลาส\n"
        . ($booking['course_title'] ?? '') . "\n"
        . 'เวลา: ' . $when;
    if ($zoom) {
        $msg .= "\nเข้าเรียน: " . $zoom;
    }

    linePushMessage($lineUserId, $msg);
}

function ensureLineReminderColumn(): void
{
    static $ready = false;
    if ($ready) {
        return;
    }

    try {
        db()->exec('ALTER TABLE session_bookings ADD COLUMN line_reminder_sent_at DATETIME DEFAULT NULL AFTER booked_at');
    } catch (Throwable $e) {
        // column may already exist
    }

    $ready = true;
}

function sendDueClassReminders(int $minutesBefore = 60, int $windowMinutes = 10): int
{
    if (!isLineOaEnabled()) {
        return 0;
    }

    require_once __DIR__ . '/booking.php';
    ensureLineReminderColumn();

    $minMinutes = max(0, $minutesBefore - (int) floor($windowMinutes / 2));
    $maxMinutes = $minutesBefore + (int) ceil($windowMinutes / 2);

    $stmt = db()->prepare('
        SELECT sb.*, c.title AS course_title, s.line_user_id
        FROM session_bookings sb
        JOIN course_sessions cs ON cs.id = sb.session_id
        JOIN courses c ON c.id = cs.course_id
        JOIN students s ON s.id = sb.student_id
        WHERE sb.status = "confirmed"
          AND sb.line_reminder_sent_at IS NULL
          AND cs.status = "scheduled"
          AND cs.starts_at BETWEEN DATE_ADD(NOW(), INTERVAL ? MINUTE) AND DATE_ADD(NOW(), INTERVAL ? MINUTE)
          AND s.line_user_id IS NOT NULL
          AND s.line_user_id != ""
    ');
    $stmt->execute([$minMinutes, $maxMinutes]);
    $bookings = $stmt->fetchAll();

    $sent = 0;
    foreach ($bookings as $booking) {
        linePushClassReminder((int) $booking['student_id'], $booking);
        db()->prepare('UPDATE session_bookings SET line_reminder_sent_at = NOW() WHERE id = ?')
            ->execute([(int) $booking['id']]);
        $sent++;
    }

    if ($sent > 0) {
        lineOaLog("sent {$sent} class reminder(s)");
    }

    return $sent;
}

function verifyLineWebhookSignature(string $body, string $signature): bool
{
    $secret = lineOaChannelSecret();
    if ($secret === '' || $signature === '') {
        return false;
    }
    $hash = base64_encode(hash_hmac('sha256', $body, $secret, true));
    return hash_equals($hash, $signature);
}
