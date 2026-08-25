<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/cart.php';
require_once dirname(__DIR__) . '/includes/booking.php';
require_once dirname(__DIR__) . '/includes/student_auth.php';

$slug = trim($_GET['course'] ?? '');
if ($slug === '') {
    redirect('/public/courses.php');
}

$course = getActiveCourseBySlug($slug);
if (!$course) {
    flash('payment_error', 'ไม่พบคอร์สที่ต้องการ');
    redirect('/public/courses.php');
}

if (!isLiveCourse($course)) {
    addToCartCourse((int) $course['id']);
    redirect('/public/cart.php');
}

$sessions = getAvailableSessions((int) $course['id']);
$error = '';
$courseUrl = APP_URL . '/public/course.php?slug=' . urlencode($course['slug']);
$coverUrl = courseCoverUrl($course);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $sessionId = (int) ($_POST['session_id'] ?? 0);
    if ($sessionId <= 0 || !sessionHasCapacity($sessionId)) {
        $error = 'กรุณาเลือกวันเวลาที่ว่าง';
    } else {
        $session = getSessionById($sessionId);
        if (!$session || (int) $session['course_id'] !== (int) $course['id']) {
            $error = 'รอบเรียนไม่ถูกต้อง';
        } else {
            addToCartCourse((int) $course['id']);
            setCartSessionForCourse((int) $course['id'], $sessionId);
            flash('cart_success', 'เลือกรอบเรียนแล้ว — ดำเนินการชำระเงินในขั้นตอนถัดไป');
            redirect('/public/cart.php');
        }
    }
}

$pageTitle = 'จองคลาส — ' . ($course['title'] ?? '');
require_once dirname(__DIR__) . '/includes/header.php';
?>

<main class="checkout-page book-page">
    <div class="container">
        <nav class="book-breadcrumb" aria-label="เส้นทาง">
            <a href="<?= APP_URL ?>/public/courses.php">คอร์ส</a>
            <span aria-hidden="true">/</span>
            <a href="<?= e($courseUrl) ?>"><?= e($course['title']) ?></a>
            <span aria-hidden="true">/</span>
            <span aria-current="page">จองคลาส</span>
        </nav>

        <nav class="book-steps" aria-label="ขั้นตอนจองคลาส">
            <span class="book-step is-active" aria-current="step">
                <span class="book-step-num">1</span>
                <span class="book-step-label">เลือกรอบเรียน</span>
            </span>
            <span class="book-step-line" aria-hidden="true"></span>
            <span class="book-step">
                <span class="book-step-num">2</span>
                <span class="book-step-label">ใส่ตะกร้า</span>
            </span>
            <span class="book-step-line" aria-hidden="true"></span>
            <span class="book-step">
                <span class="book-step-num">3</span>
                <span class="book-step-label">ชำระเงิน</span>
            </span>
        </nav>

        <header class="checkout-main-header book-page-header">
            <span class="checkout-main-icon" aria-hidden="true">
                <?= lucide_icon('calendar', ['size' => 28, 'stroke' => '1.75']) ?>
            </span>
            <div>
                <h1>จองคลาสออนไลน์</h1>
                <p class="book-page-header-sub">เลือกวันและเวลาที่สะดวก แล้วดำเนินการชำระเงินในขั้นตอนถัดไป</p>
            </div>
        </header>

        <?php if ($error): ?><div class="alert alert-error checkout-alert"><?= e($error) ?></div><?php endif; ?>

        <?php if (!$sessions): ?>
        <div class="book-page-empty">
            <div class="book-page-empty-icon" aria-hidden="true">
                <?= lucide_icon('calendar', ['size' => 40, 'stroke' => '1.5']) ?>
            </div>
            <h2>ยังไม่มีรอบเรียนที่เปิดจอง</h2>
            <p>ทีมงานกำลังจัดตารางคลาส — กรุณาติดต่อเราหรือกลับไปเลือกคอร์สอื่น</p>
            <a href="<?= e($courseUrl) ?>" class="btn btn-outline">กลับไปหน้าคอร์ส</a>
        </div>
        <?php else: ?>
        <div class="book-page-layout">
            <form method="post" class="book-page-card">
                <?= csrfField() ?>
                <section class="book-page-block">
                    <div class="book-page-block-head">
                        <h2 class="book-page-block-title">เลือกวันและเวลาเรียน</h2>
                        <span class="book-page-count"><?= count($sessions) ?> รอบว่าง</span>
                    </div>
                    <ul class="book-session-list">
                        <?php foreach ($sessions as $index => $session): ?>
                        <?php
                            $sid = (int) $session['id'];
                            $remaining = max(0, (int) $session['capacity'] - (int) $session['booked_count']);
                            $sessionImg = sessionImageUrl($session);
                            $seatsClass = sessionSeatsStatus($remaining, (int) $session['capacity']);
                            $sessionTitle = $session['title'] !== '' ? $session['title'] : 'รอบเรียน';
                        ?>
                        <li>
                            <label class="book-session-card">
                                <input
                                    type="radio"
                                    name="session_id"
                                    value="<?= $sid ?>"
                                    class="visually-hidden"
                                    <?= $index === 0 ? 'required' : '' ?>
                                >
                                <span class="book-session-radio" aria-hidden="true"></span>
                                <?php if ($sessionImg): ?>
                                <img src="<?= e($sessionImg) ?>" alt="" class="book-session-thumb" loading="lazy">
                                <?php else: ?>
                                <span class="book-session-thumb book-session-thumb--placeholder" aria-hidden="true">
                                    <?= lucide_icon('video', ['size' => 22, 'stroke' => '1.75']) ?>
                                </span>
                                <?php endif; ?>
                                <span class="book-session-main">
                                    <span class="book-session-top">
                                        <strong class="book-session-title"><?= e($sessionTitle) ?></strong>
                                        <span class="book-session-seats book-session-seats--<?= e($seatsClass) ?>">
                                            <?= lucide_icon('users', ['size' => 14, 'stroke' => '2']) ?>
                                            ว่าง <?= $remaining ?>/<?= (int) $session['capacity'] ?>
                                        </span>
                                    </span>
                                    <span class="book-session-meta">
                                        <span class="book-session-meta-item">
                                            <?= lucide_icon('calendar', ['size' => 15, 'stroke' => '1.75']) ?>
                                            <?= e(formatSessionDateLabel($session)) ?>
                                        </span>
                                        <span class="book-session-meta-item">
                                            <?= lucide_icon('clock', ['size' => 15, 'stroke' => '1.75']) ?>
                                            <?= e(formatSessionTimeLabel($session)) ?>
                                        </span>
                                    </span>
                                </span>
                            </label>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </section>
                <footer class="book-page-actions">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <?= lucide_icon('shopping-cart', ['size' => 18, 'stroke' => '2']) ?>
                        ใส่ตะกร้าและชำระเงิน
                    </button>
                    <a href="<?= e($courseUrl) ?>" class="btn btn-outline">ยกเลิก</a>
                </footer>
            </form>

            <aside class="book-page-side" aria-label="ข้อมูลคอร์ส">
                <div class="book-course-cover-wrap">
                    <img src="<?= e($coverUrl) ?>" alt="<?= e($course['title']) ?>" class="book-course-cover" loading="lazy">
                    <span class="book-course-live-badge">
                        <?= lucide_icon('video', ['size' => 14, 'stroke' => '2']) ?>
                        <?= e(courseTypeLabel($course['course_type'] ?? 'live')) ?>
                    </span>
                </div>
                <div class="book-page-side-body">
                    <h2 class="book-course-title"><?= e($course['title']) ?></h2>
                    <?php if (!empty($course['subtitle'])): ?>
                    <p class="book-course-sub"><?= e($course['subtitle']) ?></p>
                    <?php endif; ?>
                    <div class="book-course-tags">
                        <span class="badge badge-red"><?= e(categoryLabel($course['category'])) ?></span>
                        <span class="badge badge-gold"><?= e(levelBadge($course['level'])) ?></span>
                    </div>
                    <ul class="book-course-facts">
                        <li>
                            <?= lucide_icon('clock', ['size' => 16, 'stroke' => '1.75']) ?>
                            <?= (int) ($course['duration_hours'] ?? 0) ?> ชั่วโมง
                        </li>
                        <li>
                            <?= lucide_icon('video', ['size' => 16, 'stroke' => '1.75']) ?>
                            เรียนสดผ่าน Zoom
                        </li>
                    </ul>
                    <div class="book-course-price">
                        <span class="book-course-price-label">ราคา</span>
                        <strong><?= e(formatPrice((float) ($course['price'] ?? 0))) ?></strong>
                    </div>
                </div>
            </aside>
        </div>
        <?php endif; ?>
    </div>
</main>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
