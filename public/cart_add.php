<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/cart.php';

$courseId = (int) ($_REQUEST['course_id'] ?? 0);
$isAjax = !empty($_GET['ajax'])
    || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

if ($courseId <= 0) {
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'message' => 'ไม่พบคอร์ส'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    redirect('/public/courses.php');
}

$course = getActiveCourseById($courseId);
if (!$course) {
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'message' => 'ไม่พบคอร์ส'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    redirect('/public/courses.php');
}

$added = addToCartCourse($courseId);
$message = $added ? 'เพิ่มคอร์สลงตะกร้าแล้ว' : 'คอร์สนี้อยู่ในตะกร้าแล้ว';

if ($isAjax) {
    header('Content-Type: application/json; charset=utf-8');
    $returnPath = trim($_GET['return'] ?? '');
    if (!isSafeLocalReturn($returnPath)) {
        $returnPath = '/public/index.php';
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
                'removeUrl' => APP_URL . '/public/cart_remove.php?course_id=' . (int) ($c['id'] ?? 0)
                    . '&return=' . urlencode($returnPath),
            ];
        }, $items),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

flash('cart_success', $message);
redirectBack('/public/courses.php');
