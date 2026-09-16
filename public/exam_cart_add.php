<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/cart.php';
require_once dirname(__DIR__) . '/includes/exam.php';

$examPackId = (int) ($_REQUEST['exam_pack_id'] ?? 0);
$isAjax = !empty($_GET['ajax'])
    || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

if ($examPackId <= 0 || !getActiveExamPackById($examPackId)) {
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'message' => 'ไม่พบชุดข้อสอบ'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    redirect('/public/exams.php');
}

$added = addToCartExamPack($examPackId);
$message = $added ? 'เพิ่มชุดข้อสอบลงตะกร้าแล้ว' : 'ชุดข้อสอบนี้อยู่ในตะกร้าแล้ว';

if ($isAjax) {
    header('Content-Type: application/json; charset=utf-8');
    $returnPath = trim($_GET['return'] ?? '');
    if (!isSafeLocalReturn($returnPath)) {
        $returnPath = '/public/exams.php';
    }
    $items = cartItems();
    echo json_encode([
        'ok' => true,
        'added' => $added,
        'message' => $message,
        'count' => cartCount(),
        'total' => formatPrice(cartTotal()),
        'items' => array_map(static function (array $c) use ($returnPath): array {
            return [
                'id' => (int) ($c['id'] ?? 0),
                'title' => $c['title'] ?? '',
                'price' => formatPrice((float) ($c['price'] ?? 0)),
                'removeUrl' => cartItemRemoveUrl($c, $returnPath),
            ];
        }, $items),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

flash('cart_success', $message);
redirectBack('/public/exams.php');
