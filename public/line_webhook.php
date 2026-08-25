<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/line_messaging.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$body = file_get_contents('php://input') ?: '';
$signature = $_SERVER['HTTP_X_LINE_SIGNATURE'] ?? '';

lineOaLog("webhook {$method} body_len=" . strlen($body));

if ($body === '') {
    http_response_code(200);
    echo 'OK';
    exit;
}

if (!verifyLineWebhookSignature($body, $signature)) {
    $secretLen = strlen(lineOaChannelSecret());
    lineOaLog("webhook rejected: invalid signature (secret_len={$secretLen}, sig=" . ($signature !== '' ? 'yes' : 'no') . ')');
    http_response_code(403);
    exit('invalid signature');
}

$payload = json_decode($body, true);
if (!is_array($payload)) {
    http_response_code(400);
    exit('bad request');
}

foreach ($payload['events'] ?? [] as $event) {
    $type = $event['type'] ?? '';
    $replyToken = $event['replyToken'] ?? '';
    $source = $event['source'] ?? [];
    $lineUserId = (string) ($source['userId'] ?? '');

    lineOaLog('event ' . $type . ' user=' . $lineUserId);

    if ($type === 'follow' && $lineUserId !== '') {
        lineReplyMessage(
            $replyToken,
            "สวัสดีค่ะ ยินดีต้อนรับสู่กวดวิชาเดอะลีโอน่า\n\n"
            . "เชื่อมบัญชีนักเรียน: ส่งเบอร์โทรที่ใช้สมัครเรียนในแชทนี้\n"
            . "(เช่น 0812345678)\n\n"
            . "เมื่อเชื่อมสำเร็จ ระบบจะแจ้งการจองคลาสและลิงก์ Zoom ให้ทาง LINE นี้ค่ะ"
        );
        continue;
    }

    if ($type === 'unfollow' && $lineUserId !== '') {
        unlinkLineUser($lineUserId);
        continue;
    }

    if ($type === 'message' && ($event['message']['type'] ?? '') === 'text' && $lineUserId !== '') {
        $text = trim((string) ($event['message']['text'] ?? ''));
        $digits = preg_replace('/\D/', '', $text);

        if ($digits !== '' && strlen($digits) >= 9) {
            if (linkLineUserByPhone($lineUserId, $text)) {
                lineReplyMessage(
                    $replyToken,
                    "เชื่อมบัญชีเรียบร้อยแล้วค่ะ\n\n"
                    . "ระบบจะแจ้งการจองคลาสและลิงก์ Zoom ทาง LINE นี้\n"
                    . "ดูรายละเอียด: " . lineOaStudentAccountUrl()
                );
            } else {
                lineReplyMessage(
                    $replyToken,
                    "ไม่พบเบอร์นี้ในระบบค่ะ\n\n"
                    . "กรุณาสมัครเรียนที่เว็บก่อน หรือตรวจสอบว่าใช้เบอร์เดียวกับตอนสมัคร\n"
                    . "หากยังไม่ได้ ติดต่อทีมงานได้เลยค่ะ"
                );
            }
            continue;
        }

        if (in_array(mb_strtolower($text), ['help', 'ช่วย', 'วิธี', 'help me'], true)) {
            lineReplyMessage(
                $replyToken,
                "วิธีเชื่อมบัญชีกวดวิชาเดอะลีโอน่า\n"
                . "1. สมัครหรือเข้าสู่ระบบที่เว็บ\n"
                . "2. เพิ่มเพื่อน Official Account นี้\n"
                . "3. ส่งเบอร์โทรที่ใช้สมัครในแชท\n"
                . "4. รอข้อความยืนยัน「เชื่อมบัญชีเรียบร้อยแล้ว」\n\n"
                . lineOaPublicBaseUrl()
            );
            continue;
        }

        lineReplyMessage(
            $replyToken,
            "กรุณาส่งเบอร์โทรที่ใช้สมัครเรียน (เช่น 0812345678)\n"
            . "หรือพิมพ์「ช่วย」เพื่อดูวิธีเชื่อมบัญชี"
        );
    }
}

http_response_code(200);
echo 'OK';
