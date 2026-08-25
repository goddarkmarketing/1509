<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

function getCartCourseIds(): array
{
    if (empty($_SESSION['cart_course_ids']) || !is_array($_SESSION['cart_course_ids'])) {
        return [];
    }
    return array_values(array_unique(array_map('intval', $_SESSION['cart_course_ids'])));
}

function setCartCourseIds(array $courseIds): void
{
    $_SESSION['cart_course_ids'] = array_values(array_unique(array_map('intval', $courseIds)));
}

function cartCount(): int
{
    return count(getCartCourseIds());
}

/** @return bool true = เพิ่มใหม่, false = มีในตะกร้าแล้ว */
function addToCartCourse(int $courseId): bool
{
    $ids = getCartCourseIds();
    if (in_array($courseId, $ids, true)) {
        return false;
    }
    $ids[] = $courseId;
    setCartCourseIds($ids);
    return true;
}

function removeFromCartCourse(int $courseId): void
{
    $ids = array_values(array_filter(getCartCourseIds(), fn($id) => $id !== $courseId));
    setCartCourseIds($ids);
    if (function_exists('removeCartSessionForCourse')) {
        require_once __DIR__ . '/booking.php';
        removeCartSessionForCourse($courseId);
    }
}

function clearCart(): void
{
    setCartCourseIds([]);
    clearCartSessions();
    if (function_exists('clearAppliedCoupon')) {
        clearAppliedCoupon();
    }
}

function cartItems(): array
{
    $ids = getCartCourseIds();
    if (!$ids) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = db()->prepare("SELECT id, slug, title, price FROM courses WHERE id IN ({$placeholders}) ORDER BY sort_order ASC, id ASC");
    $stmt->execute($ids);
    $rows = $stmt->fetchAll();

    // เรียงตามลำดับที่ใส่ตะกร้า
    $byId = [];
    foreach ($rows as $row) {
        $byId[(int) $row['id']] = $row;
    }
    $ordered = [];
    foreach ($ids as $id) {
        if (isset($byId[$id])) {
            $ordered[] = $byId[$id];
        }
    }
    return $ordered;
}

function cartSubtotal(): float
{
    $total = 0.0;
    foreach (cartItems() as $c) {
        $total += (float) ($c['price'] ?? 0);
    }
    return $total;
}

function cartTotal(): float
{
    if (!function_exists('cartDiscount')) {
        require_once __DIR__ . '/coupon.php';
    }
    return max(0, cartSubtotal() - cartDiscount());
}

function cartTitlesSummary(): string
{
    $titles = array_map(fn($c) => $c['title'] ?? '', cartItems());
    return implode(', ', array_filter($titles));
}
