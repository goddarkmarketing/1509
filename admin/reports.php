<?php

declare(strict_types=1);

$pageTitle = 'รายงานคอร์สและรายได้';
require_once dirname(__DIR__) . '/includes/admin_header.php';
require_once dirname(__DIR__) . '/includes/booking.php';

$stats = getBookingReportStats();
$courseReport = getCourseRevenueReport();
$upcoming = getUpcomingSessions(10);
?>

<div class="stats-grid">
    <div class="stat-card">
        <h3>รายได้รวม</h3>
        <div class="stat-value is-money"><?= e(formatPrice($stats['revenue_total'])) ?></div>
    </div>
    <div class="stat-card gold">
        <h3>รายได้เดือนนี้</h3>
        <div class="stat-value is-money"><?= e(formatPrice($stats['revenue_month'])) ?></div>
    </div>
    <div class="stat-card">
        <h3>การจองยืนยันแล้ว</h3>
        <div class="stat-value"><?= (int) $stats['bookings_confirmed'] ?></div>
    </div>
    <div class="stat-card">
        <h3>รอบเรียนที่จะมาถึง</h3>
        <div class="stat-value"><?= (int) $stats['sessions_upcoming'] ?></div>
    </div>
</div>

<div class="admin-grid-2">
    <div class="admin-card">
        <div class="admin-card-header"><h2>รายได้ตามคอร์ส</h2></div>
        <div class="admin-card-body is-flush">
            <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>คอร์ส</th>
                        <th>นักเรียน</th>
                        <th>รายได้</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$courseReport): ?>
                    <tr><td colspan="3" class="table-empty">ยังไม่มีข้อมูล</td></tr>
                    <?php endif; ?>
                    <?php foreach ($courseReport as $row): ?>
                    <tr>
                        <td><?= e($row['title']) ?></td>
                        <td><?= (int) $row['enrollments'] ?></td>
                        <td><?= e(formatPrice((float) $row['revenue'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>

    <div class="admin-card">
        <div class="admin-card-header">
            <h2>คลาส Live ที่จะมาถึง</h2>
            <a href="<?= APP_URL ?>/admin/sessions.php" class="btn btn-outline btn-sm">จัดการตาราง</a>
        </div>
        <div class="admin-card-body is-flush">
            <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>คอร์ส</th>
                        <th>เวลา</th>
                        <th>ที่นั่ง</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$upcoming): ?>
                    <tr><td colspan="3" class="table-empty">ไม่มีรอบเรียนที่จะมาถึง</td></tr>
                    <?php endif; ?>
                    <?php foreach ($upcoming as $s): ?>
                    <tr>
                        <td><?= e($s['course_title']) ?></td>
                        <td><?= e(formatSessionRange($s)) ?></td>
                        <td><?= (int) $s['booked_count'] ?> / <?= (int) $s['capacity'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <h2>ทางลัด</h2>
    </div>
    <div class="admin-card-body admin-shortcuts">
        <a href="<?= APP_URL ?>/admin/bookings.php" class="btn btn-primary btn-sm">รายการจอง</a>
        <a href="<?= APP_URL ?>/admin/students.php" class="btn btn-secondary btn-sm">นักเรียน</a>
        <a href="<?= APP_URL ?>/admin/payments.php" class="btn btn-secondary btn-sm">การชำระเงิน</a>
        <a href="<?= APP_URL ?>/admin/settings.php" class="btn btn-secondary btn-sm">ตั้งค่า Payment / LINE</a>
    </div>
    <p class="form-hint" style="margin-top:1rem;padding:0 1rem 1rem">แจ้งเตือนก่อนเริ่มคลาส 1 ชม.: รัน <code>php database/line_reminder_cron.php</code> ทุก 10–15 นาที (ตั้ง Windows Task Scheduler / cron บน server)</p>
</div>

<?php require_once dirname(__DIR__) . '/includes/admin_footer.php'; ?>
