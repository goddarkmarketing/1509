<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/cart.php';
require_once dirname(__DIR__) . '/includes/booking.php';
require_once dirname(__DIR__) . '/includes/student_auth.php';
require_once dirname(__DIR__) . '/includes/access.php';

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

$student = currentStudent();
$studentId = $student ? (int) $student['id'] : 0;
$hasCourseAccess = $studentId > 0 && studentHasCourseAccess($studentId, (int) $course['id']);
$existingBooking = $hasCourseAccess ? getStudentBookingForCourse($studentId, (int) $course['id']) : null;

$sessions = getAvailableSessions((int) $course['id']);
$error = '';
$courseUrl = APP_URL . '/public/course.php?slug=' . urlencode($course['slug']);
$coverUrl = courseCoverUrl($course);
$loginUrl = APP_URL . '/public/login.php?redirect=' . rawurlencode('/public/book.php?course=' . $course['slug']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $sessionId = (int) ($_POST['session_id'] ?? 0);
    if ($sessionId <= 0) {
        $error = 'กรุณาเลือกวันเวลาที่ว่าง';
    } elseif (!sessionHasCapacity($sessionId)) {
        // Allow keeping the same session the student already holds even if listed full.
        $sameHeld = $existingBooking && (int) ($existingBooking['session_id'] ?? 0) === $sessionId;
        if (!$sameHeld) {
            $error = 'กรุณาเลือกวันเวลาที่ว่าง';
        }
    }

    if ($error === '') {
        $session = getSessionById($sessionId);
        if (!$session || (int) $session['course_id'] !== (int) $course['id']) {
            $error = 'รอบเรียนไม่ถูกต้อง';
        } elseif ($hasCourseAccess) {
            $result = bookSessionForEnrolledStudent($studentId, (int) $course['id'], $sessionId);
            if (!$result['ok']) {
                $error = $result['message'];
            } else {
                flash('account_success', $result['message'] . ' — ไม่ต้องชำระเงินซ้ำ');
                redirect('/public/profile.php?tab=bookings');
            }
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
            <?php if ($hasCourseAccess): ?>
            <span class="book-step">
                <span class="book-step-num">2</span>
                <span class="book-step-label">ยืนยันจอง</span>
            </span>
            <?php else: ?>
            <span class="book-step">
                <span class="book-step-num">2</span>
                <span class="book-step-label">ใส่ตะกร้า</span>
            </span>
            <span class="book-step-line" aria-hidden="true"></span>
            <span class="book-step">
                <span class="book-step-num">3</span>
                <span class="book-step-label">ชำระเงิน</span>
            </span>
            <?php endif; ?>
        </nav>

        <header class="checkout-main-header book-page-header">
            <span class="checkout-main-icon" aria-hidden="true">
                <?= lucide_icon('calendar', ['size' => 28, 'stroke' => '1.75']) ?>
            </span>
            <div>
                <h1>จองคลาสออนไลน์</h1>
                <p class="book-page-header-sub">
                    <?php if ($hasCourseAccess): ?>
                    คุณมีสิทธิ์เรียนคอร์สนี้อยู่แล้ว — เลือกรอบได้เลย ไม่ต้องชำระเงินซ้ำ
                    <?php else: ?>
                    เลือกวันและเวลาที่สะดวก แล้วดำเนินการชำระเงินในขั้นตอนถัดไป
                    <?php endif; ?>
                </p>
            </div>
        </header>

        <?php if ($error): ?><div class="alert alert-error checkout-alert"><?= e($error) ?></div><?php endif; ?>

        <?php if ($hasCourseAccess): ?>
        <div class="alert alert-success checkout-alert">ซื้อคอร์สแล้ว — จองรอบเรียนฟรี</div>
        <?php elseif (!$student): ?>
        <div class="alert checkout-alert" style="background:#f8fafc;border:1px solid #e2e8f0;color:#334155">
            หากซื้อคอร์สนี้แล้ว กรุณา <a href="<?= e($loginUrl) ?>">เข้าสู่ระบบ</a> ก่อนจองรอบ เพื่อไม่ต้องจ่ายซ้ำ
        </div>
        <?php endif; ?>

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
                            $isCurrent = $existingBooking && (int) ($existingBooking['session_id'] ?? 0) === $sid;
                        ?>
                        <li>
                            <label class="book-session-card">
                                <input
                                    type="radio"
                                    name="session_id"
                                    value="<?= $sid ?>"
                                    class="visually-hidden"
                                    <?= ($isCurrent || ($index === 0 && !$existingBooking)) ? 'checked' : '' ?>
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
                                        <strong class="book-session-title"><?= e($sessionTitle) ?><?php if ($isCurrent): ?> · จองไว้แล้ว<?php endif; ?></strong>
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
                    <?php if ($hasCourseAccess): ?>
                    <button type="submit" class="btn btn-primary btn-lg">
                        <?= lucide_icon('calendar-check', ['size' => 18, 'stroke' => '2']) ?>
                        <?= $existingBooking ? 'เปลี่ยนรอบเรียน' : 'ยืนยันจองรอบเรียน' ?>
                    </button>
                    <?php else: ?>
                    <button type="submit" class="btn btn-primary btn-lg">
                        <?= lucide_icon('shopping-cart', ['size' => 18, 'stroke' => '2']) ?>
                        ใส่ตะกร้าและชำระเงิน
                    </button>
                    <?php endif; ?>
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
                            เรียนสดผ่าน Google Meet หรือ Zoom
                        </li>
                    </ul>
                    <div class="book-course-price">
                        <?php if ($hasCourseAccess): ?>
                        <span class="book-course-price-label">สถานะ</span>
                        <strong>มีสิทธิ์เรียนแล้ว</strong>
                        <?php else: ?>
                        <span class="book-course-price-label">ราคา</span>
                        <strong><?= e(formatPrice((float) ($course['price'] ?? 0))) ?></strong>
                        <?php endif; ?>
                    </div>
                </div>
            </aside>
        </div>
        <?php endif; ?>
    </div>
</main>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
