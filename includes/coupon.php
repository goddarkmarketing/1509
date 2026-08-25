<?php
declare(strict_types=1);

function getAppliedCoupon(): ?array
{
    return $_SESSION['applied_coupon'] ?? null;
}

function clearAppliedCoupon(): void
{
    unset($_SESSION['applied_coupon']);
}

function validateCoupon(string $code): ?array
{
    $code = strtoupper(trim($code));
    if ($code === '') {
        return null;
    }
    $stmt = db()->prepare('SELECT * FROM coupons WHERE code = ? AND is_active = 1 LIMIT 1');
    $stmt->execute([$code]);
    $coupon = $stmt->fetch();
    if (!$coupon) {
        return null;
    }
    if (!empty($coupon['expires_at']) && strtotime($coupon['expires_at']) < strtotime('today')) {
        return null;
    }
    $maxUses = (int) ($coupon['max_uses'] ?? 0);
    if ($maxUses > 0 && (int) ($coupon['used_count'] ?? 0) >= $maxUses) {
        return null;
    }
    return $coupon;
}

function applyCouponCode(string $code): array
{
    $coupon = validateCoupon($code);
    if (!$coupon) {
        return ['ok' => false, 'message' => 'รหัสส่วนลดไม่ถูกต้องหรือหมดอายุ'];
    }
    $subtotal = cartSubtotal();
    $min = (float) ($coupon['min_amount'] ?? 0);
    if ($subtotal < $min) {
        return ['ok' => false, 'message' => 'ยอดสั่งซื้อไม่ถึงขั้นต่ำสำหรับรหัสนี้'];
    }
    $_SESSION['applied_coupon'] = [
        'code' => $coupon['code'],
        'discount_type' => $coupon['discount_type'],
        'discount_value' => (float) $coupon['discount_value'],
    ];
    return ['ok' => true, 'message' => 'ใช้รหัสส่วนลดเรียบร้อย'];
}

function cartDiscount(): float
{
    $coupon = getAppliedCoupon();
    if (!$coupon) {
        return 0.0;
    }
    $subtotal = cartSubtotal();
    if ($coupon['discount_type'] === 'fixed') {
        return min($subtotal, (float) $coupon['discount_value']);
    }
    return round($subtotal * ((float) $coupon['discount_value'] / 100), 2);
}

function incrementCouponUsage(string $code): void
{
    $stmt = db()->prepare('UPDATE coupons SET used_count = used_count + 1 WHERE code = ?');
    $stmt->execute([strtoupper(trim($code))]);
}
