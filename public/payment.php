<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/cart.php';
require_once dirname(__DIR__) . '/includes/coupon.php';
require_once dirname(__DIR__) . '/includes/checkout_flow.php';
require_once dirname(__DIR__) . '/includes/student_auth.php';
require_once dirname(__DIR__) . '/includes/mailer.php';
require_once dirname(__DIR__) . '/includes/line_notify.php';
require_once dirname(__DIR__) . '/includes/booking.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/public/checkout.php');
}

verifyCsrf();

$name = trim($_POST['student_name'] ?? '');
$phone = trim($_POST['student_phone'] ?? '');
$email = trim($_POST['student_email'] ?? '');
$courseId = !empty($_POST['course_id']) ? (int) $_POST['course_id'] : null;
$transferDate = $_POST['transfer_date'] ?? null;
$transferTime = trim($_POST['transfer_time'] ?? '');
$note = trim($_POST['note'] ?? '');

requireCartForCheckout();

$items = cartItems();
$amount = cartTotal();
if (!$courseId && count($items) === 1) {
    $courseId = (int) ($items[0]['id'] ?? 0);
}

$summary = cartTitlesSummary();
if ($summary !== '' && !str_contains($note, $summary)) {
    $note = ($note !== '' ? $note . "\n" : '') . 'คอร์สในตะกร้า: ' . $summary;
}
$note = appendCartIdsToNote($note);
$note = appendSessionMapToNote($note, getCartSessionMap());

$appliedCoupon = getAppliedCoupon();
$couponCode = $appliedCoupon['code'] ?? null;
if ($couponCode) {
    $note = ($note !== '' ? $note . "\n" : '') . 'coupon:' . $couponCode;
}

if ($name === '' || $phone === '') {
    flash('payment_error', 'กรุณากรอกชื่อและเบอร์โทร');
    redirect('/public/checkout.php');
}

if ($amount <= 0) {
    flash('payment_error', 'ยอดชำระไม่ถูกต้อง');
    redirect('/public/checkout.php');
}

$slipPath = null;
if (!empty($_FILES['slip_image']['name'])) {
    $uploaded = storeSlipUpload($_FILES['slip_image']);
    if ($uploaded === false) {
        redirect('/public/checkout.php');
    }
    $slipPath = $uploaded;
}

try {
    $paymentId = insertBankTransferPayment(
        $courseId ?: null,
        $name,
        $email ?: null,
        $phone,
        $amount,
        $transferDate ?: null,
        $transferTime ?: null,
        $slipPath,
        $note ?: null,
        $couponCode
    );
    savePaymentItems($paymentId, $items);

    if ($couponCode) {
        try {
            incrementCouponUsage($couponCode);
        } catch (Throwable $e) {
            // non-blocking
        }
    }

    $paymentRow = [
        'id' => $paymentId,
        'student_name' => $name,
        'student_email' => $email,
        'student_phone' => $phone,
        'amount' => $amount,
        'course_title' => count($items) === 1 ? ($items[0]['title'] ?? '') : cartTitlesSummary(),
    ];
    try {
        notifyPaymentReceived($paymentRow);
    } catch (Throwable $e) {
        // non-blocking
    }
    try {
        lineNotifyPayment($paymentRow);
    } catch (Throwable $e) {
        // non-blocking
    }

    $courseIds = getCourseIdsFromCartItems($items);
    if ($courseIds) {
        $studentId = resolveCheckoutStudentId($name, $email ?: null, $phone);
        enrollStudentInCourses($studentId, $courseIds, 'pending');
        $sessionMap = getCartSessionMap();
        if ($sessionMap) {
            createBookingsForPayment($paymentId, $studentId, $sessionMap, 'pending');
        }
    }

    $_SESSION['checkout_phone'] = $phone;
    flash('payment_success', 'แจ้งชำระเงินเรียบร้อยแล้ว ทีมงานจะตรวจสอบและติดต่อกลับโดยเร็ว');
    clearCart();
} catch (Throwable $e) {
    $logDir = dirname(__DIR__) . '/storage/logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    file_put_contents($logDir . '/payment.log', date('Y-m-d H:i:s') . ' ' . $e->getMessage() . "\n", FILE_APPEND);
    flash('payment_error', 'เกิดข้อผิดพลาด กรุณาลองใหม่หรือติดต่อทีมงาน');
}

redirect('/public/checkout.php');
