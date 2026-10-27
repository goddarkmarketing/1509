<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/feedback.php';

header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

if (!feedbackIsPreviewAuthorized() && !isAdminLoggedIn()) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'ไม่มีสิทธิ์ส่ง Feedback']);
    exit;
}

$config = feedbackLoadConfig();
if (empty($config['enabled']) && !isAdminLoggedIn()) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'ระบบ Feedback ปิดอยู่']);
    exit;
}

$raw = file_get_contents('php://input') ?: '';
$payload = json_decode($raw, true);
if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'ข้อมูลไม่ถูกต้อง']);
    exit;
}

$csrf = (string) ($payload['csrf'] ?? '');
if ($csrf === '' || !hash_equals(csrfToken(), $csrf)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'CSRF ไม่ถูกต้อง']);
    exit;
}

try {
    $screenshotBin = null;
    $dataUrl = (string) ($payload['screenshotDataUrl'] ?? '');
    if ($dataUrl !== '') {
        $screenshotBin = feedbackDecodeScreenshotDataUrl($dataUrl);
        if ($screenshotBin === null) {
            throw new InvalidArgumentException('ภาพ Screenshot ไม่ถูกต้อง');
        }
    }
    unset($payload['screenshotDataUrl'], $payload['csrf']);
    $item = feedbackCreateItem($payload, $screenshotBin);
    echo json_encode(['ok' => true, 'item' => $item], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (InvalidArgumentException $e) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'บันทึกไม่สำเร็จ']);
}
