<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
requireAdmin();
require_once dirname(__DIR__) . '/includes/booking.php';
require_once dirname(__DIR__) . '/includes/line_messaging.php';
require_once dirname(__DIR__) . '/includes/schema.php';

$filter = $_GET['status'] ?? '';
$schemaMissing = missingDatabaseTables();
$schemaError = $schemaMissing ? migrationHintMessage($schemaMissing) : '';
$syncedBookings = 0;
if (!$schemaMissing) {
    $syncedBookings = syncAllMissingSessionBookings();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $bookingId = (int) ($_POST['booking_id'] ?? 0);
    $postAction = $_POST['action'] ?? '';
    if ($bookingId && $postAction === 'confirm') {
        $bStmt = db()->prepare('
            SELECT sb.*, c.title AS course_title FROM session_bookings sb
            JOIN course_sessions cs ON cs.id = sb.session_id
            JOIN courses c ON c.id = cs.course_id
            WHERE sb.id = ? LIMIT 1
        ');
        $bStmt->execute([$bookingId]);
        $bookingRow = $bStmt->fetch();
        confirmSessionBooking($bookingId);
        if ($bookingRow) {
            linePushBookingConfirmed((int) $bookingRow['student_id'], $bookingRow);
            notifyBookingConfirmed((int) $bookingRow['student_id'], $bookingRow);
        }
        flash('admin_success', 'ยืนยันการจองแล้ว');
    } elseif ($bookingId && $postAction === 'cancel') {
        cancelSessionBooking($bookingId);
        flash('admin_success', 'ยกเลิกการจองแล้ว');
    }
    redirect('/admin/bookings.php' . ($filter ? '?status=' . urlencode($filter) : ''));
}

$pageTitle = 'รายการจองคลาส';
require_once dirname(__DIR__) . '/includes/admin_header.php';

$message = flash('admin_success');
if ($syncedBookings > 0) {
    $message = $message
        ? $message . ' (ซิงก์รายการจองจากการชำระเงิน ' . $syncedBookings . ' รายการ)'
        : 'ซิงก์รายการจองจากการชำระเงิน ' . $syncedBookings . ' รายการ';
}

$bookings = getAdminBookings($filter !== '' ? $filter : null);
?>

<?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
<?php if ($schemaError): ?><div class="alert alert-warning"><?= e($schemaError) ?></div><?php endif; ?>

<div class="admin-card">
    <div class="admin-card-header">
        <h2>รายการจองคลาส</h2>
        <a href="<?= APP_URL ?>/admin/sessions.php" class="btn btn-outline btn-sm">จัดการตารางคลาส</a>
    </div>
    <div class="admin-card-toolbar">
        <div class="admin-filter-bar">
            <a href="?" class="btn btn-sm <?= $filter === '' ? 'btn-primary' : 'btn-secondary' ?>">ทั้งหมด</a>
            <a href="?status=pending" class="btn btn-sm <?= $filter === 'pending' ? 'btn-primary' : 'btn-secondary' ?>">รอยืนยัน</a>
            <a href="?status=confirmed" class="btn btn-sm <?= $filter === 'confirmed' ? 'btn-primary' : 'btn-secondary' ?>">ยืนยันแล้ว</a>
            <a href="?status=cancelled" class="btn btn-sm <?= $filter === 'cancelled' ? 'btn-primary' : 'btn-secondary' ?>">ยกเลิก</a>
        </div>
    </div>
    <div class="admin-card-body is-flush">
        <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>นักเรียน</th>
                    <th>คอร์ส / รอบ</th>
                    <th>วันเวลา</th>
                    <th>Zoom</th>
                    <th>ชำระเงิน</th>
                    <th>สถานะ</th>
                    <th class="actions">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$bookings): ?>
                <tr><td colspan="7" class="table-empty">ยังไม่มีรายการจอง</td></tr>
                <?php endif; ?>
                <?php foreach ($bookings as $b): ?>
                <?php $zoomUrl = getSessionZoomUrl($b, $b); ?>
                <tr>
                    <td>
                        <strong><?= e($b['full_name']) ?></strong><br>
                        <small><?= e($b['phone']) ?></small>
                    </td>
                    <td>
                        <?= e($b['course_title']) ?><br>
                        <small><?= e($b['session_title'] ?: 'รอบเรียน') ?></small>
                    </td>
                    <td><?= e(formatSessionRange($b)) ?></td>
                    <td>
                        <?php if ($zoomUrl): ?>
                        <a href="<?= e($zoomUrl) ?>" target="_blank" rel="noopener" class="btn btn-outline btn-sm">เปิด Zoom</a>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($b['payment_id'])): ?>
                        <a href="<?= APP_URL ?>/admin/payments.php#payment-<?= (int) $b['payment_id'] ?>">#<?= (int) $b['payment_id'] ?></a>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td><span class="badge <?= e(bookingStatusBadgeClass($b['status'] ?? '')) ?>"><?= e(bookingStatusLabel($b['status'] ?? '')) ?></span></td>
                    <td class="actions">
                        <div class="table-actions">
                            <a href="<?= APP_URL ?>/admin/students.php?student_id=<?= (int) $b['student_id'] ?>" class="btn btn-outline btn-sm">ดูนักเรียน</a>
                            <?php if (($b['status'] ?? '') === 'pending'): ?>
                            <form method="post">
                                <?= csrfField() ?>
                                <input type="hidden" name="booking_id" value="<?= (int) $b['id'] ?>">
                                <input type="hidden" name="action" value="confirm">
                                <button type="submit" class="btn btn-primary btn-sm">ยืนยัน</button>
                            </form>
                            <?php endif; ?>
                            <?php if (($b['status'] ?? '') !== 'cancelled'): ?>
                            <form method="post" onsubmit="return confirm('ยกเลิกการจอง?')">
                                <?= csrfField() ?>
                                <input type="hidden" name="booking_id" value="<?= (int) $b['id'] ?>">
                                <input type="hidden" name="action" value="cancel">
                                <button type="submit" class="btn btn-danger btn-sm">ยกเลิก</button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/admin_footer.php'; ?>
