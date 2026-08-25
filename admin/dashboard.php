<?php
declare(strict_types=1);
$pageTitle = 'แดชบอร์ด';
require_once dirname(__DIR__) . '/includes/admin_header.php';
require_once dirname(__DIR__) . '/includes/booking.php';

$stats = [
    'courses' => 0,
    'lessons' => 0,
    'students' => 0,
    'enrollments' => 0,
    'payments' => 0,
    'pending' => 0,
    'bookings_pending' => 0,
    'revenue' => 0.0,
    'revenue_month' => 0.0,
    'quiz_attempts' => 0,
    'certificates' => 0,
];

try {
    $stats['courses'] = (int) db()->query('SELECT COUNT(*) FROM courses')->fetchColumn();
    $stats['lessons'] = (int) db()->query('SELECT COUNT(*) FROM lessons')->fetchColumn();
    $stats['students'] = (int) db()->query('SELECT COUNT(*) FROM students')->fetchColumn();
    $stats['enrollments'] = (int) db()->query('SELECT COUNT(*) FROM enrollments WHERE status IN ("active","completed")')->fetchColumn();
    $stats['payments'] = (int) db()->query('SELECT COUNT(*) FROM payments')->fetchColumn();
    $stats['pending'] = (int) db()->query('SELECT COUNT(*) FROM payments WHERE status = "pending"')->fetchColumn();
    $stats['bookings_pending'] = (int) db()->query('SELECT COUNT(*) FROM session_bookings WHERE status = "pending"')->fetchColumn();
    $stats['revenue'] = (float) db()->query('SELECT COALESCE(SUM(amount),0) FROM payments WHERE status = "verified"')->fetchColumn();
    $stats['revenue_month'] = (float) db()->query('SELECT COALESCE(SUM(amount),0) FROM payments WHERE status = "verified" AND created_at >= DATE_FORMAT(NOW(), "%Y-%m-01")')->fetchColumn();
    $stats['quiz_attempts'] = (int) db()->query('SELECT COUNT(*) FROM quiz_attempts')->fetchColumn();
    $stats['certificates'] = (int) db()->query('SELECT COUNT(*) FROM certificates')->fetchColumn();
} catch (Throwable $e) {
}

$topCourses = [];
try {
    $topCourses = db()->query('
        SELECT c.title, COUNT(e.id) AS enroll_count
        FROM enrollments e
        JOIN courses c ON c.id = e.course_id
        WHERE e.status IN ("active","completed")
        GROUP BY c.id, c.title
        ORDER BY enroll_count DESC
        LIMIT 5
    ')->fetchAll();
} catch (Throwable $e) {
}

$recentPayments = [];
try {
    $stmt = db()->query('
        SELECT p.*, c.title AS course_title
        FROM payments p
        LEFT JOIN courses c ON c.id = p.course_id
        ORDER BY p.created_at DESC LIMIT 5
    ');
    $recentPayments = $stmt->fetchAll();
} catch (Throwable $e) {
}
?>

<div class="stats-grid">
    <div class="stat-card">
        <h3>นักเรียน</h3>
        <div class="stat-value"><?= $stats['students'] ?></div>
    </div>
    <div class="stat-card gold">
        <h3>เปิดสิทธิ์เรียน</h3>
        <div class="stat-value"><?= $stats['enrollments'] ?></div>
    </div>
    <div class="stat-card">
        <h3>รายได้รวม</h3>
        <div class="stat-value is-money"><?= e(formatPrice($stats['revenue'] > 0 ? $stats['revenue'] : null)) ?></div>
    </div>
    <div class="stat-card gold">
        <h3>รายได้เดือนนี้</h3>
        <div class="stat-value is-money"><?= e(formatPrice($stats['revenue_month'] > 0 ? $stats['revenue_month'] : null)) ?></div>
    </div>
    <div class="stat-card">
        <h3>คอร์ส / บทเรียน</h3>
        <div class="stat-value"><?= $stats['courses'] ?> / <?= $stats['lessons'] ?></div>
    </div>
    <div class="stat-card">
        <h3>รอตรวจสอบชำระเงิน</h3>
        <div class="stat-value"><?= $stats['pending'] ?></div>
        <a href="<?= APP_URL ?>/admin/payments.php" class="btn btn-outline btn-sm" style="margin-top:.5rem">ดูรายการ</a>
    </div>
    <div class="stat-card gold">
        <h3>จองรอยืนยัน</h3>
        <div class="stat-value"><?= $stats['bookings_pending'] ?></div>
        <a href="<?= APP_URL ?>/admin/bookings.php?status=pending" class="btn btn-outline btn-sm" style="margin-top:.5rem">ดูการจอง</a>
    </div>
    <div class="stat-card gold">
        <h3>ทำแบบทดสอบ</h3>
        <div class="stat-value"><?= $stats['quiz_attempts'] ?></div>
    </div>
    <div class="stat-card">
        <h3>ใบประกาศ</h3>
        <div class="stat-value"><?= $stats['certificates'] ?></div>
    </div>
</div>

<div class="admin-grid-2">
    <div class="admin-card">
        <div class="admin-card-header">
            <h2>คอร์สยอดนิยม</h2>
        </div>
        <div class="admin-card-body is-flush">
            <?php if ($topCourses): ?>
            <table class="data-table">
                <thead><tr><th>คอร์ส</th><th>ผู้เรียน</th></tr></thead>
                <tbody>
                    <?php foreach ($topCourses as $row): ?>
                    <tr>
                        <td><?= e($row['title']) ?></td>
                        <td><?= (int) $row['enroll_count'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p class="table-empty">ยังไม่มีข้อมูล</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="admin-card">
        <div class="admin-card-header">
            <h2>แจ้งชำระเงินล่าสุด</h2>
            <a href="<?= APP_URL ?>/admin/payments.php" class="btn btn-primary btn-sm">ดูทั้งหมด</a>
        </div>
        <div class="admin-card-body is-flush">
            <?php if ($recentPayments): ?>
            <table class="data-table">
                <thead>
                    <tr><th>วันที่</th><th>ชื่อ</th><th>จำนวน</th><th>สถานะ</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($recentPayments as $p): ?>
                    <tr>
                        <td><?= e(date('d/m/Y', strtotime($p['created_at']))) ?></td>
                        <td><?= e($p['student_name']) ?></td>
                        <td><?= e(formatPrice((float) $p['amount'])) ?></td>
                        <td><span class="badge badge-<?= e($p['status']) ?>"><?= e($p['status']) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p class="table-empty">ยังไม่มีรายการ</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="admin-card">
    <div class="admin-card-header"><h2>ทางลัด</h2></div>
    <div class="admin-card-body admin-shortcuts">
        <a href="<?= APP_URL ?>/admin/courses.php?action=add" class="btn btn-primary btn-sm">เพิ่มคอร์ส</a>
        <a href="<?= APP_URL ?>/admin/quizzes.php?action=add" class="btn btn-secondary btn-sm">เพิ่มแบบทดสอบ</a>
        <a href="<?= APP_URL ?>/admin/coupons.php" class="btn btn-secondary btn-sm">คูปองส่วนลด</a>
        <a href="<?= APP_URL ?>/admin/sessions.php" class="btn btn-secondary btn-sm">ตารางคลาส Live</a>
        <a href="<?= APP_URL ?>/admin/bookings.php" class="btn btn-secondary btn-sm">การจองคลาส</a>
        <a href="<?= APP_URL ?>/admin/students.php" class="btn btn-secondary btn-sm">นักเรียน</a>
        <a href="<?= APP_URL ?>/admin/settings.php" class="btn btn-secondary btn-sm">ตั้งค่า</a>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/admin_footer.php'; ?>
