<?php
declare(strict_types=1);

function isLineNotifyEnabled(): bool
{
    return getSetting('line_notify_enabled', '0') === '1'
        && trim(getSetting('line_notify_token', '')) !== '';
}

function sendLineNotify(string $message): bool
{
    if (!isLineNotifyEnabled()) {
        return false;
    }
    $token = trim(getSetting('line_notify_token', ''));
    $ch = curl_init('https://notify-api.line.me/api/notify');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token],
        CURLOPT_POSTFIELDS => http_build_query(['message' => $message]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
    ]);
    $response = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200) {
        $logDir = BASE_PATH . '/storage/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        file_put_contents($logDir . '/line.log', date('Y-m-d H:i:s') . " HTTP {$code} {$response}\n", FILE_APPEND);
        return false;
    }
    return true;
}

function lineNotifyPayment(array $payment): void
{
    $courses = function_exists('getPaymentCourseTitles') ? getPaymentCourseTitles((int) ($payment['id'] ?? 0)) : [];
    $courseList = $courses ? implode(', ', $courses) : ($payment['course_title'] ?? '-');
    $msg = "\n[Wenxin] แจ้งชำระเงินใหม่\n"
        . "ชื่อ: " . ($payment['student_name'] ?? '') . "\n"
        . "โทร: " . ($payment['student_phone'] ?? '') . "\n"
        . "คอร์ส: {$courseList}\n"
        . "ยอด: " . formatPrice((float) ($payment['amount'] ?? 0));
    sendLineNotify($msg);
}

function lineNotifyEnrollment(string $studentName, string $phone, array $courseTitles): void
{
    $msg = "\n[Wenxin] เปิดสิทธิ์เรียน\n"
        . "ชื่อ: {$studentName}\n"
        . "โทร: {$phone}\n"
        . "คอร์ส: " . implode(', ', $courseTitles);
    sendLineNotify($msg);
}
