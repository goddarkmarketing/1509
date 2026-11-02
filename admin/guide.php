<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
requireAdmin();
require_once dirname(__DIR__) . '/includes/admin_guide_tours.php';

$pageTitle = 'คู่มือการใช้งาน';
$tours = adminGuideTours();
require_once dirname(__DIR__) . '/includes/admin_header.php';
?>

<div class="guide-hub" data-guide="guide-hub">
    <header class="guide-hub-hero">
        <div class="guide-hub-hero-copy">
            <p class="guide-hub-kicker">คู่มือหลังบ้าน THE LEONA</p>
            <h2>ทัวร์การใช้งานทีละจุดจนครบกระบวนการ</h2>
            <p>
                กดเริ่มทัวร์ในหัวข้อที่ต้องการ ระบบจะพาไปยังหน้าจริงในหลังบ้าน
                ไฮไลต์ปุ่ม/ตารางที่เกี่ยวข้อง และอธิบายขั้นตอนอย่างละเอียดทีละขั้น
            </p>
        </div>
        <div class="guide-hub-hero-aside" data-guide="guide-student-journey">
            <strong>ลำดับงานที่แนะนำ</strong>
            <ol>
                <li>เริ่มต้นใช้หลังบ้าน</li>
                <li>สร้างคอร์สและบทเรียน</li>
                <li>ตรวจชำระเงิน / เปิดสิทธิ์</li>
                <li>คลาส Live และการจอง</li>
            </ol>
        </div>
    </header>

    <section class="guide-hub-note" data-guide="guide-student-coupon">
        <h3>นักเรียนใช้คูปองอย่างไร</h3>
        <p>
            เพิ่มคอร์สลงตะกร้า → กรอกรหัสส่วนลด → ถ้ารหัส 100% ยอดเป็นฟรีและเปิดสิทธิ์ทันที
            → ถ้ามีส่วนที่ต้องจ่าย ให้โอนตามยอดสุทธิแล้วแนบสลิป → แอดมินยืนยันในเมนูการชำระเงิน
        </p>
    </section>

    <section class="guide-catalog" data-guide="guide-catalog" aria-label="รายการทัวร์คู่มือ">
        <?php foreach ($tours as $index => $tour): ?>
            <?php
            $tourId = (string) ($tour['id'] ?? '');
            $steps = $tour['steps'] ?? [];
            $firstHref = $steps[0]['href'] ?? (APP_URL . '/admin/guide.php');
            $startUrl = $firstHref . (str_contains($firstHref, '?') ? '&' : '?') . 'tour=' . rawurlencode($tourId) . '&step=0';
            ?>
            <article class="guide-card" id="tour-<?= e($tourId) ?>">
                <div class="guide-card-index"><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></div>
                <div class="guide-card-body">
                    <h3><?= e((string) ($tour['title'] ?? '')) ?></h3>
                    <p><?= e((string) ($tour['summary'] ?? '')) ?></p>
                    <div class="guide-card-meta">
                        <span><?= e((string) ($tour['duration'] ?? '')) ?></span>
                        <span><?= count($steps) ?> ขั้นตอน</span>
                        <span><?= e((string) ($tour['audience'] ?? '')) ?></span>
                    </div>
                </div>
                <div class="guide-card-actions">
                    <button
                        type="button"
                        class="btn btn-primary"
                        data-guide-start="<?= e($tourId) ?>"
                        data-guide-start-url="<?= e($startUrl) ?>"
                    >เริ่มทัวร์</button>
                    <details class="guide-card-steps">
                        <summary>ดูหัวข้อย่อย</summary>
                        <ol>
                            <?php foreach ($steps as $step): ?>
                                <li><?= e((string) ($step['title'] ?? '')) ?></li>
                            <?php endforeach; ?>
                        </ol>
                    </details>
                </div>
            </article>
        <?php endforeach; ?>
    </section>
</div>

<script>
window.__ADMIN_GUIDE_TOURS__ = <?= json_encode($tours, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
</script>

<?php require_once dirname(__DIR__) . '/includes/admin_footer.php'; ?>
