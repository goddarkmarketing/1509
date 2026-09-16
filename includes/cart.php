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

function getCartExamPackIds(): array
{
    if (empty($_SESSION['cart_exam_pack_ids']) || !is_array($_SESSION['cart_exam_pack_ids'])) {
        return [];
    }
    return array_values(array_unique(array_map('intval', $_SESSION['cart_exam_pack_ids'])));
}

function setCartExamPackIds(array $ids): void
{
    $_SESSION['cart_exam_pack_ids'] = array_values(array_unique(array_map('intval', $ids)));
}

function cartCount(): int
{
    return count(getCartCourseIds()) + count(getCartExamPackIds());
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

/** @return bool true = เพิ่มใหม่, false = มีในตะกร้าแล้ว */
function addToCartExamPack(int $examPackId): bool
{
    $ids = getCartExamPackIds();
    if (in_array($examPackId, $ids, true)) {
        return false;
    }
    $ids[] = $examPackId;
    setCartExamPackIds($ids);
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

function removeFromCartExamPack(int $examPackId): void
{
    $ids = array_values(array_filter(getCartExamPackIds(), fn($id) => $id !== $examPackId));
    setCartExamPackIds($ids);
}

function clearCart(): void
{
    setCartCourseIds([]);
    setCartExamPackIds([]);
    clearCartSessions();
    if (function_exists('clearAppliedCoupon')) {
        clearAppliedCoupon();
    }
}

/**
 * @return list<array{item_type:string,id:int,slug:string,title:string,price:float,cover?:string}>
 */
function cartItems(): array
{
    $ordered = [];

    $courseIds = getCartCourseIds();
    if ($courseIds) {
        $placeholders = implode(',', array_fill(0, count($courseIds), '?'));
        $stmt = db()->prepare("SELECT id, slug, title, price FROM courses WHERE id IN ({$placeholders}) ORDER BY sort_order ASC, id ASC");
        $stmt->execute($courseIds);
        $byId = [];
        foreach ($stmt->fetchAll() as $row) {
            $byId[(int) $row['id']] = $row;
        }
        foreach ($courseIds as $id) {
            if (!isset($byId[$id])) {
                continue;
            }
            $row = $byId[$id];
            $ordered[] = [
                'item_type' => 'course',
                'id' => (int) $row['id'],
                'slug' => (string) ($row['slug'] ?? ''),
                'title' => (string) ($row['title'] ?? ''),
                'price' => (float) ($row['price'] ?? 0),
            ];
        }
    }

    $examIds = getCartExamPackIds();
    if ($examIds) {
        require_once __DIR__ . '/exam.php';
        if (examTablesReady()) {
            $placeholders = implode(',', array_fill(0, count($examIds), '?'));
            try {
                $stmt = db()->prepare("SELECT id, slug, title, price, cover_image FROM exam_packs WHERE id IN ({$placeholders}) ORDER BY sort_order ASC, id ASC");
                $stmt->execute($examIds);
                $byId = [];
                foreach ($stmt->fetchAll() as $row) {
                    $byId[(int) $row['id']] = $row;
                }
                foreach ($examIds as $id) {
                    if (!isset($byId[$id])) {
                        continue;
                    }
                    $row = $byId[$id];
                    $ordered[] = [
                        'item_type' => 'exam_pack',
                        'id' => (int) $row['id'],
                        'slug' => (string) ($row['slug'] ?? ''),
                        'title' => (string) ($row['title'] ?? ''),
                        'price' => (float) ($row['price'] ?? 0),
                        'cover_image' => $row['cover_image'] ?? null,
                    ];
                }
            } catch (Throwable $e) {
                // ignore missing table
            }
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

function cartItemRemoveUrl(array $item, string $returnPath = ''): string
{
    $return = $returnPath !== '' ? '&return=' . urlencode($returnPath) : '';
    if (($item['item_type'] ?? 'course') === 'exam_pack') {
        return APP_URL . '/public/cart_remove.php?exam_pack_id=' . (int) ($item['id'] ?? 0) . $return;
    }
    return APP_URL . '/public/cart_remove.php?course_id=' . (int) ($item['id'] ?? 0) . $return;
}

function cartItemDetailUrl(array $item): string
{
    if (($item['item_type'] ?? 'course') === 'exam_pack') {
        return APP_URL . '/public/exam.php?slug=' . urlencode((string) ($item['slug'] ?? ''));
    }
    return APP_URL . '/public/course.php?slug=' . urlencode((string) ($item['slug'] ?? ''));
}

function cartItemThumbUrl(array $item): string
{
    if (($item['item_type'] ?? 'course') === 'exam_pack') {
        require_once __DIR__ . '/exam.php';
        $cover = examCoverUrl($item);
        return $cover !== '' ? $cover : asset('images/courses/cover-exam.svg');
    }
    return courseCoverUrl($item);
}

function cartItemTypeLabel(array $item): string
{
    return ($item['item_type'] ?? 'course') === 'exam_pack' ? 'ชุดข้อสอบ' : 'คอร์ส';
}
