<?php
declare(strict_types=1);
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/cart.php';
require_once __DIR__ . '/student_auth.php';
$settings = getSettings();
$pageTitle = $pageTitle ?? getSetting('site_title', 'กวดวิชาเดอะลีโอน่า');
$cartCount = cartCount();
$cartItems = cartItems();
$currentStudent = currentStudent();
$checkoutNavUrl = $cartCount > 0
    ? APP_URL . '/public/checkout.php'
    : APP_URL . '/public/cart.php';
$isHome = str_ends_with(str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? ''), '/index.php');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | <?= e(getSetting('site_title')) ?></title>
  <meta name="description" content="<?= e(getSetting('site_tagline')) ?>">
    <?php require __DIR__ . '/views/fonts_head.php'; ?>
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>?v=161">
    <?php if (!empty($pageStylesheets) && is_array($pageStylesheets)): ?>
        <?php foreach ($pageStylesheets as $sheet): ?>
        <link rel="stylesheet" href="<?= asset($sheet) ?>?v=4">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body class="<?= $isHome ? 'page-home' : '' ?>">
<header class="site-header leona-header" id="top">
    <div class="container header-inner">
        <a href="<?= APP_URL ?>/public/index.php" class="brand">
            <span class="brand-text">
                <strong>THE LEONA</strong>
                <span>TUTORS</span>
            </span>
        </a>
        <button class="nav-toggle" id="navToggle" aria-label="เปิดเมนู">
            <span></span><span></span><span></span>
        </button>
        <nav class="site-nav" id="siteNav">
            <div class="site-nav-links">
                <a href="<?= APP_URL ?>/public/index.php">หน้าแรก</a>
                <a href="<?= APP_URL ?>/public/index.php#about">เกี่ยวกับเรา</a>
                <a href="<?= APP_URL ?>/public/courses.php">คอร์สเรียน</a>
                <a href="<?= APP_URL ?>/public/exams.php">จำลองสนามสอบ</a>
                <a href="<?= APP_URL ?>/public/index.php#subjects">วิชาที่เปิดสอน</a>
                <a href="<?= APP_URL ?>/public/index.php#reviews">รีวิว</a>
                <a href="<?= APP_URL ?>/public/announcements.php">ข่าวสาร</a>
                <a href="<?= APP_URL ?>/public/contact.php">ติดต่อเรา</a>
            </div>
            <div class="header-actions">
                <button id="cartToggle" type="button" class="cart-toggle header-action-btn header-btn-cart" aria-controls="cartDrawer" aria-expanded="false" title="ตะกร้า">
                    <span class="cart-toggle-icon" aria-hidden="true">
                        <?= lucide_icon('shopping-cart', ['size' => 18]) ?>
                    </span>
                    <span class="cart-count" id="cartCount"><?= (int) $cartCount ?></span>
                </button>
                <?php if ($currentStudent): ?>
                <a href="<?= APP_URL ?>/public/profile.php" class="header-user-btn" aria-label="โปรไฟล์">
                    <?= lucide_icon('user', ['size' => 20]) ?>
                </a>
                <a href="<?= APP_URL ?>/public/my-courses.php" class="btn btn-primary btn-sm header-action-btn">เริ่มเรียน</a>
                <?php else: ?>
                <a href="<?= APP_URL ?>/public/register.php" class="btn btn-primary btn-sm header-action-btn header-btn-enroll">สมัครเรียน</a>
                <a href="<?= APP_URL ?>/public/login.php" class="header-user-btn" aria-label="เข้าสู่ระบบ">
                    <?= lucide_icon('user', ['size' => 20]) ?>
                </a>
                <?php endif; ?>
            </div>
        </nav>
    </div>
</header>
<div class="cart-overlay" id="cartOverlay" aria-hidden="true"></div>
<aside class="cart-drawer" id="cartDrawer" aria-hidden="true" aria-label="ตะกร้าสินค้า">
    <div class="cart-drawer-header">
        <h3>ตะกร้าของคุณ</h3>
        <button type="button" class="cart-close" id="cartCloseBtn" aria-label="ปิดตะกร้า"><?= lucide_icon('x', ['size' => 18]) ?></button>
    </div>
    <div class="cart-drawer-body">
        <?php if (!$cartItems): ?>
            <p class="cart-empty">ยังไม่มีรายการในตะกร้า</p>
        <?php else: ?>
            <ul class="cart-list">
                <?php foreach ($cartItems as $item): ?>
                    <li class="cart-item">
                        <div class="cart-item-main">
                            <div class="cart-item-title"><?= e($item['title'] ?? '') ?></div>
                            <div class="cart-item-price"><?= e(cartItemTypeLabel($item)) ?> · <?= e(formatPrice((float) ($item['price'] ?? 0))) ?></div>
                        </div>
                        <a class="cart-remove" href="<?= e(cartItemRemoveUrl($item, currentReturnPath())) ?>" title="นำออกจากตะกร้า">ลบ</a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
    <div class="cart-drawer-footer">
        <div class="cart-total-row">
            <span>รวม</span>
            <strong><?= e(formatPrice(cartTotal())) ?></strong>
        </div>
        <a class="btn btn-primary btn-block cart-checkout"
           href="<?= APP_URL ?>/public/cart.php"
           aria-disabled="<?= $cartCount > 0 ? 'false' : 'true' ?>"
           style="<?= $cartCount > 0 ? '' : 'pointer-events:none;opacity:.6;' ?>">
            ดูตะกร้า
        </a>
    </div>
</aside>
<main>
