<?php
declare(strict_types=1);

$pageTitle = 'ตะกร้าของฉัน';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/includes/cart.php';
require_once dirname(__DIR__) . '/includes/coupon.php';
require_once dirname(__DIR__) . '/includes/checkout_flow.php';
require_once dirname(__DIR__) . '/includes/booking.php';

$cartSuccess = flash('cart_success');
$cartError = flash('payment_error');
$items = cartItems();
$cartSessionDetails = getCartSessionDetails();
$subtotal = cartSubtotal();
$discount = cartDiscount();
$total = cartTotal();
$appliedCoupon = getAppliedCoupon();
?>

<main class="checkout-page cart-page">
    <div class="container">
        <?php if ($cartSuccess): ?>
        <div class="alert alert-success checkout-alert"><?= e($cartSuccess) ?></div>
        <?php endif; ?>
        <?php if ($cartError): ?>
        <div class="alert alert-error checkout-alert"><?= e($cartError) ?></div>
        <?php endif; ?>

        <?php renderCheckoutSteps(2); ?>

        <header class="checkout-main-header cart-page-header">
            <span class="checkout-main-icon" aria-hidden="true">
                <?= lucide_icon('shopping-cart', ['size' => 28, 'stroke' => '1.75']) ?>
            </span>
            <div>
                <h1>ตะกร้าของฉัน</h1>
                <p class="cart-page-header-sub">ตรวจสอบคอร์สและยอดชำระก่อนดำเนินการต่อ</p>
            </div>
        </header>

        <div class="cart-page-layout<?= $items ? '' : ' cart-page-layout--empty' ?>">
            <?php if ($items): ?>
            <div class="cart-page-card">
                <section class="cart-page-block cart-page-block--items" aria-labelledby="cart-items-heading">
                    <h2 class="cart-page-block-title" id="cart-items-heading">รายการคอร์ส <span class="cart-page-count"><?= count($items) ?> รายการ</span></h2>
                    <ul class="cart-page-list">
                        <?php foreach ($items as $item): ?>
                        <?php $sess = $cartSessionDetails[(int) ($item['id'] ?? 0)] ?? null; ?>
                        <li class="cart-page-item">
                            <img src="<?= e(courseCoverUrl($item)) ?>" alt="" class="checkout-order-thumb" width="72" height="72" loading="lazy">
                            <div class="cart-page-item-body">
                                <h3 class="cart-page-item-title"><a href="<?= APP_URL ?>/public/course.php?slug=<?= urlencode($item['slug']) ?>"><?= e($item['title']) ?></a></h3>
                                <?php if ($sess): ?>
                                <p class="cart-page-item-session">📅 รอบเรียน: <?= e(formatSessionRange($sess)) ?></p>
                                <?php endif; ?>
                                <p class="cart-page-item-price"><?= e(formatPrice((float) ($item['price'] ?? 0))) ?></p>
                            </div>
                            <a class="cart-page-remove" href="<?= APP_URL ?>/public/cart_remove.php?course_id=<?= (int) $item['id'] ?>&return=<?= urlencode('/public/cart.php') ?>">ลบ</a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            </div>

            <aside class="cart-page-side-card" aria-label="สรุปคำสั่งซื้อ">
                <section class="cart-page-block cart-page-block--coupon" aria-labelledby="cart-coupon-heading">
                    <h2 class="cart-page-block-title" id="cart-coupon-heading">รหัสส่วนลด</h2>
                    <form method="post" action="<?= APP_URL ?>/public/apply_coupon.php" class="cart-coupon-form">
                        <?= csrfField() ?>
                        <label class="visually-hidden" for="coupon_code">รหัสส่วนลด</label>
                        <div class="cart-coupon-row">
                            <input type="text" id="coupon_code" name="coupon_code" class="form-control" placeholder="เช่น FIRST25" value="<?= e($appliedCoupon['code'] ?? '') ?>">
                            <?php if ($appliedCoupon): ?>
                            <button type="submit" name="remove_coupon" value="1" class="btn btn-outline btn-sm">ลบรหัส</button>
                            <?php else: ?>
                            <button type="submit" class="btn btn-outline btn-sm">ใช้รหัส</button>
                            <?php endif; ?>
                        </div>
                    </form>
                </section>

                <section class="cart-page-block cart-page-block--summary" aria-labelledby="cart-summary-heading">
                    <h2 class="cart-page-block-title" id="cart-summary-heading">สรุปยอดชำระ</h2>
                    <dl class="cart-page-summary">
                        <div class="cart-page-summary-row">
                            <dt>ยอดรวม</dt>
                            <dd><?= e(formatPrice($subtotal > 0 ? $subtotal : null)) ?></dd>
                        </div>
                        <?php if ($discount > 0): ?>
                        <div class="cart-page-summary-row cart-page-summary-row--discount">
                            <dt>ส่วนลด</dt>
                            <dd>-<?= e(formatPrice($discount)) ?></dd>
                        </div>
                        <?php endif; ?>
                        <div class="cart-page-summary-row cart-page-summary-row--payable">
                            <dt>ยอดชำระ</dt>
                            <dd><?= e(formatPrice($total > 0 ? $total : null)) ?></dd>
                        </div>
                    </dl>
                    <div class="cart-page-actions">
                        <a href="<?= APP_URL ?>/public/courses.php" class="btn btn-outline">เลือกคอร์สเพิ่ม</a>
                        <a href="<?= APP_URL ?>/public/checkout.php" class="btn btn-primary">ไปชำระเงิน</a>
                    </div>
                </section>
            </aside>
            <?php else: ?>
            <div class="cart-page-card cart-page-card--empty">
                <div class="cart-page-empty">
                    <p>ยังไม่มีคอร์สในตะกร้า</p>
                    <a href="<?= APP_URL ?>/public/courses.php" class="btn btn-primary">เลือกคอร์สเรียน</a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
