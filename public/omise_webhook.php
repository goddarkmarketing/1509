<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/omise.php';

$raw = file_get_contents('php://input') ?: '';
header('Content-Type: application/json; charset=utf-8');

try {
    if ($raw === '') {
        http_response_code(400);
        echo json_encode(['ok' => false]);
        exit;
    }

    $ok = handleOmiseWebhookPayload($raw);
    echo json_encode(['ok' => $ok]);
} catch (Throwable $e) {
    omiseLog('webhook: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false]);
}
