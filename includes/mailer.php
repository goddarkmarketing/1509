<?php
declare(strict_types=1);

require_once __DIR__ . '/smtp_client.php';

function isEmailEnabled(): bool
{
    return getSetting('email_enabled', '0') === '1';
}

function emailTransport(): string
{
    $t = getSetting('email_transport', 'mail');
    return in_array($t, ['mail', 'smtp'], true) ? $t : 'mail';
}

function smtpPassword(): string
{
    $fromEnv = env('SMTP_PASS', '');
    if ($fromEnv !== null && $fromEnv !== '') {
        return $fromEnv;
    }
    return getSetting('smtp_pass', '');
}

function siteBaseUrl(): string
{
    $url = trim(getSetting('site_url', ''));
    if ($url !== '') {
        return rtrim($url, '/');
    }
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host . APP_URL;
}

function logMailFailure(string $to, string $subject, string $reason = ''): void
{
    $logDir = BASE_PATH . '/storage/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    $extra = $reason !== '' ? " reason={$reason}" : '';
    file_put_contents(
        $logDir . '/email.log',
        date('Y-m-d H:i:s') . " FAILED to={$to} subject={$subject}{$extra}\n",
        FILE_APPEND
    );
}

function sendMail(string $to, string $subject, string $htmlBody): bool
{
    if (!isEmailEnabled() || $to === '') {
        return false;
    }

    $from = getSetting('email_from', 'noreply@wenxin.local');
    $fromName = getSetting('email_from_name', 'กวดวิชาเดอะลีโอน่า');

    if (emailTransport() === 'smtp') {
        $host = getSetting('smtp_host', '');
        $port = (int) getSetting('smtp_port', '587');
        $encryption = getSetting('smtp_encryption', 'tls');
        $user = getSetting('smtp_user', '');
        $pass = smtpPassword();

        $ok = smtpSendMail($host, $port, $encryption, $user, $pass, $from, $fromName, $to, $subject, $htmlBody);
        if (!$ok) {
            logMailFailure($to, $subject, 'smtp');
        }
        return $ok;
    }

    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: ' . mb_encode_mimeheader($fromName, 'UTF-8') . " <{$from}>",
        'Reply-To: ' . $from,
        'X-Mailer: PHP/' . PHP_VERSION,
    ];

    $ok = @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $htmlBody, implode("\r\n", $headers));

    if (!$ok) {
        logMailFailure($to, $subject, 'mail()');
    }

    return $ok;
}

function mailTemplate(string $title, string $body): string
{
    $site = e(getSetting('site_title', 'กวดวิชาเดอะลีโอน่า'));
    return '<!DOCTYPE html><html lang="th"><body style="font-family:sans-serif;line-height:1.6;color:#333;max-width:560px;margin:0 auto;padding:24px">'
        . '<h2 style="color:#c41e24">' . e($title) . '</h2>'
        . $body
        . '<hr style="border:none;border-top:1px solid #eee;margin:24px 0">'
        . '<p style="font-size:12px;color:#888">' . $site . '</p></body></html>';
}

function notifyPaymentReceived(array $payment): void
{
    $admin = getSetting('email_admin', '');
    if ($admin === '') {
        return;
    }

    $courses = getPaymentCourseTitles((int) $payment['id']);
    $courseList = $courses ? implode(', ', $courses) : ($payment['course_title'] ?? '-');

    $body = '<p>มีการแจ้งชำระเงินใหม่</p>'
        . '<ul>'
        . '<li><strong>ชื่อ:</strong> ' . e($payment['student_name'] ?? '') . '</li>'
        . '<li><strong>เบอร์:</strong> ' . e($payment['student_phone'] ?? '') . '</li>'
        . '<li><strong>อีเมล:</strong> ' . e($payment['student_email'] ?? '-') . '</li>'
        . '<li><strong>คอร์ส:</strong> ' . e($courseList) . '</li>'
        . '<li><strong>ยอด:</strong> ' . e(formatPrice((float) ($payment['amount'] ?? 0))) . '</li>'
        . '</ul>'
        . '<p><a href="' . e(siteBaseUrl() . '/admin/payments.php') . '">เปิดหลังบ้านเพื่อตรวจสอบ</a></p>';

    sendMail($admin, '[Wenxin] แจ้งชำระเงินใหม่', mailTemplate('แจ้งชำระเงินใหม่', $body));
}

function notifyEnrollmentOpened(string $studentEmail, string $studentName, array $courseTitles): void
{
    if ($studentEmail === '') {
        return;
    }

    $list = '<ul>';
    foreach ($courseTitles as $title) {
        $list .= '<li>' . e($title) . '</li>';
    }
    $list .= '</ul>';

    $body = '<p>สวัสดีคุณ ' . e($studentName) . '</p>'
        . '<p>ทีมงานเปิดสิทธิ์เรียนคอร์สของคุณเรียบร้อยแล้ว:</p>'
        . $list
        . '<p><a href="' . e(siteBaseUrl() . '/public/my-courses.php') . '" style="display:inline-block;background:#c41e24;color:#fff;padding:10px 20px;text-decoration:none;border-radius:6px">เริ่มเรียนเลย</a></p>';

    sendMail($studentEmail, '[Wenxin] เปิดสิทธิ์เรียนแล้ว', mailTemplate('เปิดสิทธิ์เรียนแล้ว', $body));
}

function notifyBookingConfirmedEmail(string $studentEmail, string $studentName, string $courseTitle, string $sessionWhen, ?string $zoomUrl): void
{
    if ($studentEmail === '') {
        return;
    }

    $body = '<p>สวัสดีคุณ ' . e($studentName) . '</p>'
        . '<p>ยืนยันการจองคลาสเรียบร้อยแล้ว:</p>'
        . '<ul>'
        . '<li><strong>คอร์ส:</strong> ' . e($courseTitle) . '</li>'
        . '<li><strong>วันเวลา:</strong> ' . e($sessionWhen) . '</li>';
    if ($zoomUrl) {
        $body .= '<li><strong>Zoom:</strong> <a href="' . e($zoomUrl) . '">' . e($zoomUrl) . '</a></li>';
    }
    $body .= '</ul>'
        . '<p><a href="' . e(siteBaseUrl() . '/public/profile.php?tab=bookings') . '" style="display:inline-block;background:#c41e24;color:#fff;padding:10px 20px;text-decoration:none;border-radius:6px">ดูการจองของฉัน</a></p>';

    sendMail($studentEmail, '[Wenxin] ยืนยันการจองคลาส', mailTemplate('ยืนยันการจองคลาส', $body));
}

function sendPasswordResetEmail(string $email, string $studentName, string $resetUrl): bool
{
    $body = '<p>สวัสดีคุณ ' . e($studentName) . '</p>'
        . '<p>เราได้รับคำขอรีเซ็ตรหัสผ่านสำหรับบัญชีกวดวิชาเดอะลีโอน่าของคุณ</p>'
        . '<p><a href="' . e($resetUrl) . '" style="display:inline-block;background:#c41e24;color:#fff;padding:10px 20px;text-decoration:none;border-radius:6px">ตั้งรหัสผ่านใหม่</a></p>'
        . '<p>ลิงก์นี้ใช้ได้ 1 ชั่วโมง หากคุณไม่ได้ขอรีเซ็ตรหัสผ่าน สามารถละเว้นอีเมลนี้ได้</p>'
        . '<p style="font-size:12px;color:#888">หรือคัดลอกลิงก์: ' . e($resetUrl) . '</p>';

    return sendMail($email, '[Wenxin] รีเซ็ตรหัสผ่าน', mailTemplate('รีเซ็ตรหัสผ่าน', $body));
}

function getPaymentCourseTitles(int $paymentId): array
{
    try {
        $stmt = db()->prepare('
            SELECT c.title FROM payment_items pi
            JOIN courses c ON c.id = pi.course_id
            WHERE pi.payment_id = ?
            ORDER BY c.title
        ');
        $stmt->execute([$paymentId]);
        $titles = $stmt->fetchAll(PDO::FETCH_COLUMN);
        if ($titles) {
            return $titles;
        }
    } catch (Throwable $e) {
        // payment_items may be missing on older databases
    }

    return [];
}
