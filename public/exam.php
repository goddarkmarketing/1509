<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/exam.php';
require_once dirname(__DIR__) . '/includes/cart.php';
require_once dirname(__DIR__) . '/includes/student_auth.php';
require_once dirname(__DIR__) . '/includes/ui.php';

$slug = trim($_GET['slug'] ?? '');
$pack = $slug !== '' ? getExamPackBySlug($slug) : null;
if (!$pack || !(int) ($pack['is_published'] ?? 0)) {
    redirect('/public/exams.php');
}

$student = currentStudent();
$purchaseStatus = $student ? studentExamPurchaseStatus((int) $student['id'], (int) $pack['id']) : null;
$hasAccess = $purchaseStatus === 'active';
$isPending = $purchaseStatus === 'pending';
$best = ($student && $hasAccess) ? getBestExamAttempt((int) $student['id'], (int) $pack['id']) : null;
$questions = getExamQuestions((int) $pack['id']);
$qCount = count($questions);
$inCart = in_array((int) $pack['id'], getCartExamPackIds(), true);

$pageTitle = $pack['title'];
$pageStylesheets = ['css/ui-shadcn.css'];
$cartSuccess = flash('cart_success');
require_once dirname(__DIR__) . '/includes/header.php';
?>

<main class="ui-scope ui-exam-detail-page">
    <div class="container">
        <?php if ($cartSuccess): ?>
            <?= uiAlert($cartSuccess, 'success') ?>
            <div style="height:1rem"></div>
        <?php endif; ?>

        <div class="ui-exam-detail-layout">
            <div class="ui-exam-detail-main">
                <div class="ui-exam-stats" style="margin-bottom:.75rem">
                    <?= uiBadge('ชุดข้อสอบ', 'outline') ?>
                    <?= uiBadge((string) $pack['subject'], 'secondary') ?>
                    <?= uiBadge('ระดับ ' . examLevelLabel((string) $pack['level']), 'outline') ?>
                </div>
                <h1><?= e($pack['title']) ?></h1>
                <?php if (!empty($pack['subtitle'])): ?>
                <p class="ui-lead"><?= e($pack['subtitle']) ?></p>
                <?php endif; ?>

                <?php if (!empty($pack['description'])): ?>
                <div class="ui-prose"><?= nl2br(e($pack['description'])) ?></div>
                <?php endif; ?>

                <section class="ui-exam-block">
                    <h2 class="ui-exam-block-title">สิ่งที่ได้รับในชุดนี้</h2>
                    <div class="ui-feature-grid">
                        <div class="ui-feature-item">
                            <strong><?= $qCount ?> ข้อ</strong>
                            <span>ข้อสอบปรนัยพร้อมเฉลยหลังส่ง</span>
                        </div>
                        <div class="ui-feature-item">
                            <strong><?= (int) $pack['time_limit_minutes'] ?> นาที</strong>
                            <span>จับเวลาเหมือนสอบจริง อัตโนมัติส่งเมื่อหมดเวลา</span>
                        </div>
                        <div class="ui-feature-item">
                            <strong>ผ่าน <?= (int) $pack['pass_score'] ?>%</strong>
                            <span>เกณฑ์ผ่านชัดเจน ดูคะแนนทันที</span>
                        </div>
                        <div class="ui-feature-item">
                            <strong>ทำซ้ำได้</strong>
                            <span>เข้าสอบได้หลายครั้งหลังซื้อแล้ว</span>
                        </div>
                    </div>
                </section>

                <section class="ui-exam-block">
                    <h2 class="ui-exam-block-title">ขั้นตอนการเข้าสอบ</h2>
                    <ol class="ui-steps">
                        <li><strong>ซื้อชุดข้อสอบ</strong><span>ใส่ตะกร้าและชำระเงิน</span></li>
                        <li><strong>รอยืนยัน</strong><span>แอดมินตรวจสลิปแล้วปลดล็อกสิทธิ์</span></li>
                        <li><strong>เข้าสนามสอบ</strong><span>จับเวลา ข้ามข้อ และทำเครื่องหมายทบทวนได้</span></li>
                        <li><strong>ดูผล + เฉลย</strong><span>รู้คะแนนทันที พร้อมทบทวนข้อที่ผิด</span></li>
                    </ol>
                </section>

                <?php if (!empty($pack['instructions'])): ?>
                <section class="ui-exam-block">
                    <h2 class="ui-exam-block-title">คำแนะนำก่อนเข้าสอบ</h2>
                    <div class="ui-prose ui-prose--box"><?= nl2br(e($pack['instructions'])) ?></div>
                </section>
                <?php endif; ?>

                <section class="ui-exam-block">
                    <h2 class="ui-exam-block-title">รูปแบบจำลองสนามสอบ</h2>
                    <ul class="ui-checklist">
                        <li>แผงหมายเลขข้อด้านข้าง — กระโดดไปข้อใดก็ได้</li>
                        <li>ทำเครื่องหมายทบทวนข้อที่ยังไม่แน่ใจ</li>
                        <li>หมดเวลาแล้วระบบส่งคำตอบให้อัตโนมัติ</li>
                        <li>หลังส่งมีเฉลยและคำอธิบายประกอบ</li>
                    </ul>
                </section>
            </div>

            <aside>
                <?= uiCardOpen(['class' => 'ui-card--buy']) ?>
                    <?= examPackMediaHtml($pack) ?>
                    <?= uiCardHeaderOpen() ?>
                        <div class="ui-buy-price"><?= e(formatPrice((float) $pack['price'])) ?></div>
                        <?= uiCardDescription('ซื้อแล้วเข้าจำลองสนามสอบได้ทันทีหลังยืนยันชำระเงิน') ?>
                    <?= uiCardCloseSection() ?>
                    <?= uiCardContentOpen() ?>
                        <ul class="ui-buy-stats">
                            <li><strong><?= e(examLevelLabel((string) $pack['level'])) ?></strong><span>ระดับ</span></li>
                            <li><strong><?= $qCount ?></strong><span>ข้อ</span></li>
                            <li><strong><?= (int) $pack['time_limit_minutes'] ?></strong><span>นาที</span></li>
                            <li><strong><?= (int) $pack['pass_score'] ?>%</strong><span>ผ่าน</span></li>
                        </ul>
                    <?= uiCardCloseSection() ?>
                    <?= uiCardFooterOpen() ?>
                        <?php if ($hasAccess): ?>
                            <?= uiButton('เข้าจำลองสนามสอบ', APP_URL . '/public/exam_take.php?slug=' . urlencode($pack['slug']), 'default', 'lg', ['class' => 'ui-btn--block']) ?>
                            <?php if ($best): ?>
                            <p class="ui-buy-hint">คะแนนสูงสุดของคุณ: <?= (int) $best['score'] ?>%<?= (int) $best['passed'] ? ' · ผ่าน' : '' ?></p>
                            <?php endif; ?>
                        <?php elseif ($isPending): ?>
                            <?= uiAlert('รอแอดมินยืนยันการชำระเงิน', 'default', 'รอดำเนินการ') ?>
                            <?= uiButton('ดูสถานะของฉัน', APP_URL . '/public/profile.php?tab=exams', 'outline', 'default', ['class' => 'ui-btn--block']) ?>
                        <?php else: ?>
                            <?php if ($inCart): ?>
                                <?= uiButton('ไปชำระเงินในตะกร้า', APP_URL . '/public/cart.php', 'default', 'lg', ['class' => 'ui-btn--block']) ?>
                            <?php else: ?>
                                <?= uiButton('ซื้อชุดข้อสอบนี้', APP_URL . '/public/exam_cart_add.php?exam_pack_id=' . (int) $pack['id'], 'default', 'lg', ['class' => 'ui-btn--block']) ?>
                            <?php endif; ?>
                            <?php if (!$student): ?>
                            <p class="ui-buy-hint">แนะนำให้ <a href="<?= APP_URL ?>/public/login.php">เข้าสู่ระบบ</a> ก่อนซื้อ เพื่อผูกสิทธิ์เข้าสอบกับบัญชีคุณ</p>
                            <?php endif; ?>
                        <?php endif; ?>
                        <?= uiSeparator(['style' => 'margin:.25rem 0']) ?>
                        <p class="ui-buy-hint">จับเวลาเหมือนสอบจริง · ข้ามข้อได้ · ทำซ้ำได้หลังซื้อ</p>
                        <?= uiButton('← ชุดข้อสอบทั้งหมด', APP_URL . '/public/exams.php', 'ghost', 'sm') ?>
                    <?= uiCardCloseSection() ?>
                <?= uiCardClose() ?>
            </aside>
        </div>
    </div>
</main>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
