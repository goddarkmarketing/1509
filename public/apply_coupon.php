<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/cart.php';
require_once dirname(__DIR__) . '/includes/coupon.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/public/cart.php');
}

verifyCsrf();
$code = trim($_POST['coupon_code'] ?? '');
$remove = !empty($_POST['remove_coupon']);

if ($remove) {
    clearAppliedCoupon();
    flash('cart_success', 'ยกเลิกรหัสส่วนลดแล้ว');
    redirect('/public/cart.php');
}

$result = applyCouponCode($code);
if ($result['ok']) {
    flash('cart_success', $result['message']);
} else {
    flash('payment_error', $result['message']);
}
redirect('/public/cart.php');
