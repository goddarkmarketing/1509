<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/cart.php';
require_once dirname(__DIR__) . '/includes/coupon.php';
require_once dirname(__DIR__) . '/includes/checkout_flow.php';
require_once dirname(__DIR__) . '/includes/omise.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/public/checkout.php');
}

verifyCsrf();

if (!isOmiseEnabled()) {
    flash('payment_error', 'ระบบชำระเงินออนไลน์ยังไม่เปิดใช้งาน');
    redirect('/public/checkout.php');
}

requireCartForCheckout();

$name = trim($_POST['student_name'] ?? '');
$phone = trim($_POST['student_phone'] ?? '');
$email = trim($_POST['student_email'] ?? '');
$method = $_POST['omise_method'] ?? 'promptpay';
$omiseToken = trim($_POST['omise_token'] ?? '');

if ($name === '' || $phone === '') {
    flash('payment_error', 'กรุณากรอกชื่อและเบอร์โทร');
    redirect('/public/checkout.php');
}

$items = cartItems();
$amount = cartTotal();
if ($amount <= 0) {
    flash('payment_error', 'ยอดชำระไม่ถูกต้อง');
    redirect('/public/checkout.php');
}

$appliedCoupon = getAppliedCoupon();
$couponCode = $appliedCoupon['code'] ?? null;

try {
    $paymentId = createPendingOmisePayment(
        ['name' => $name, 'phone' => $phone, 'email' => $email],
        $items,
        $amount,
        $couponCode
    );

    if ($method === 'card') {
        if ($omiseToken === '') {
            throw new RuntimeException('ไม่พบข้อมูลบัตรเครดิต');
        }
        $charge = createOmiseCardCharge($paymentId, $amount, $omiseToken);
        if (omiseChargeIsPaid($charge)) {
            if (completeOmisePayment($paymentId)) {
                flash('payment_success', 'ชำระเงินสำเร็จ! เปิดสิทธิ์เรียนแล้ว — เข้าเรียนได้ทันที');
                redirect('/public/my-courses.php');
            }
        }
        flash('payment_error', 'การชำระเงินยังไม่สำเร็จ กรุณาลองใหม่');
        redirect('/public/checkout.php');
    }

    $result = createOmisePromptPayCharge($paymentId, $amount);
    $authorizeUri = $result['authorize_uri'] ?? null;
    if (!$authorizeUri) {
        throw new RuntimeException('ไม่สามารถสร้าง QR PromptPay ได้');
    }

    $_SESSION['omise_pending_payment_id'] = $paymentId;
    $_SESSION['checkout_phone'] = $phone;
    header('Location: ' . $authorizeUri);
    exit;
} catch (Throwable $e) {
    omiseLog('pay error: ' . $e->getMessage());
    flash('payment_error', 'ชำระเงินไม่สำเร็จ: ' . $e->getMessage());
    redirect('/public/checkout.php');
}
