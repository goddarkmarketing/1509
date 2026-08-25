<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
requireAdmin();
require_once dirname(__DIR__) . '/includes/checkout_flow.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payment_id'], $_POST['status'])) {
    verifyCsrf();
    $paymentId = (int) $_POST['payment_id'];
    $status = $_POST['status'];
    if (in_array($status, ['pending', 'verified', 'rejected'], true)) {
        if ($status === 'verified') {
            $fetch = db()->prepare('SELECT * FROM payments WHERE id = ? LIMIT 1');
            $fetch->execute([$paymentId]);
            $payment = $fetch->fetch();
            if ($payment && ($payment['status'] ?? '') !== 'verified') {
                enrollFromPayment($payment);
                rejectDuplicatePendingPayments($payment, $paymentId);
            }
        }
        $stmt = db()->prepare('UPDATE payments SET status = ? WHERE id = ?');
        $stmt->execute([$status, $paymentId]);
        flash('admin_success', $status === 'verified' ? 'ยืนยันและเปิดสิทธิ์เรียนเรียบร้อย' : 'อัปเดตสถานะเรียบร้อย');
    }
    $listPage = max(1, (int) ($_POST['list_page'] ?? 1));
    $anchor = $paymentId > 0 ? '#payment-' . $paymentId : '';
    redirect('/admin/payments.php?page=' . $listPage . $anchor);
}

$pageTitle = 'การชำระเงิน';
require_once dirname(__DIR__) . '/includes/admin_header.php';
require_once dirname(__DIR__) . '/includes/booking.php';

$message = flash('admin_success');

$paymentsPerPage = 20;
$paymentsPage = max(1, (int) ($_GET['page'] ?? 1));
$paymentsTotal = 0;
$paymentsPager = adminPagination(0, $paymentsPerPage, $paymentsPage);

try {
    $paymentsTotal = (int) db()->query('SELECT COUNT(*) FROM payments')->fetchColumn();
    $paymentsPager = adminPagination($paymentsTotal, $paymentsPerPage, $paymentsPage);
    $stmt = db()->prepare('
        SELECT p.*, c.title AS course_title
        FROM payments p
        LEFT JOIN courses c ON c.id = p.course_id
        ORDER BY p.created_at DESC
        LIMIT ? OFFSET ?
    ');
    $stmt->bindValue(1, $paymentsPerPage, PDO::PARAM_INT);
    $stmt->bindValue(2, $paymentsPager['offset'], PDO::PARAM_INT);
    $stmt->execute();
    $payments = $stmt->fetchAll();
} catch (Throwable $e) {
    $payments = [];
    $paymentsLoadError = 'โหลดรายการชำระเงินไม่สำเร็จ — ตรวจสอบตาราง payments ในฐานข้อมูล';
}

$pendingEnrollments = getPendingEnrollmentsForAdmin();

$paymentItemsMap = [];
foreach ($payments as $p) {
    $paymentItemsMap[(int) $p['id']] = getPaymentItemsWithTitles((int) $p['id']);
}

$statusLabels = [
    'pending' => 'รอตรวจสอบ',
    'verified' => 'ยืนยันแล้ว',
    'rejected' => 'ปฏิเสธ',
];
?>

<?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
<?php if (!empty($paymentsLoadError)): ?><div class="alert alert-error"><?= e($paymentsLoadError) ?></div><?php endif; ?>

<?php if ($pendingEnrollments): ?>
<div class="admin-card admin-card--spaced">
    <div class="admin-card-header">
        <h2>นักเรียนรอเปิดสิทธิ์ (<?= count($pendingEnrollments) ?>)</h2>
    </div>
    <div class="admin-card-body is-flush">
        <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>วันที่</th>
                    <th>นักเรียน</th>
                    <th>โทร</th>
                    <th>คอร์ส</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pendingEnrollments as $row): ?>
                <tr>
                    <td><?= e(date('d/m/Y H:i', strtotime($row['enrolled_at'] ?? 'now'))) ?></td>
                    <td><strong><?= e($row['full_name'] ?? '') ?></strong></td>
                    <td><?= e($row['phone'] ?? '-') ?></td>
                    <td><?= e($row['course_title'] ?? '-') ?></td>
                    <td>
                        <a href="<?= APP_URL ?>/admin/students.php?student_id=<?= (int) ($row['student_id'] ?? 0) ?>&open=1" class="btn btn-primary btn-sm">เปิดสิทธิ์</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php if (!$payments): ?>
        <p class="form-hint" style="padding:0 1rem 1rem;margin:0">ยังไม่พบรายการแจ้งชำระในตาราง payments — หากนักเรียนแจ้งโอนแล้ว ให้กด「เปิดสิทธิ์」จากรายการด้านบน หรือตรวจสอบ <code>storage/logs/payment.log</code></p>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<div class="admin-card">
    <div class="admin-card-header">
        <h2>รายการแจ้งชำระเงิน</h2>
        <?php if ($paymentsTotal > 0): ?>
        <span class="admin-card-meta">
            แสดง <?= (int) ($paymentsPager['offset'] + 1) ?>–<?= (int) min($paymentsPager['offset'] + $paymentsPerPage, $paymentsTotal) ?>
            จาก <?= (int) $paymentsTotal ?> รายการ
        </span>
        <?php endif; ?>
    </div>
    <div class="admin-card-body is-flush">
        <?php if ($payments): ?>
        <div class="table-responsive">
        <table class="admin-table payments-table payments-table--compact">
            <thead>
                <tr>
                    <th>วันที่</th>
                    <th>ลูกค้า</th>
                    <th>คอร์ส</th>
                    <th class="col-amount">จำนวน</th>
                    <th>สถานะ</th>
                    <th class="actions">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payments as $p): ?>
                <?php
                    $items = $paymentItemsMap[(int) $p['id']] ?? [];
                    $paymentStatus = (string) ($p['status'] ?? 'pending');
                    $sessionTime = formatPaymentSessionTimeLabel($p['note'] ?? null, (int) $p['id']);
                    $courseLine = formatPaymentCourseLine($p, $items);
                    if ($sessionTime !== '') {
                        $courseLine .= ' (' . $sessionTime . ')';
                    }
                    $customerUser = formatPaymentCustomerUser($p);
                    $customerTitle = formatPaymentCustomerTitle($p);
                    $slipFilename = (string) ($p['slip_image'] ?? '');
                    $slipUrl = $slipFilename !== ''
                        ? APP_URL . '/public/view_slip.php?id=' . (int) $p['id']
                        : '';
                    $slipExt = strtolower(pathinfo(basename($slipFilename), PATHINFO_EXTENSION));
                    $slipIsImage = in_array($slipExt, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
                ?>
                <tr id="payment-<?= (int) $p['id'] ?>" class="payments-table-row">
                    <td class="payment-date"><?= e(date('d/m/Y H:i', strtotime($p['created_at']))) ?></td>
                    <td class="payment-payer"<?= $customerTitle !== '' ? ' title="' . e($customerTitle) . '"' : '' ?>>
                        <?= e($customerUser) ?>
                    </td>
                    <td class="payment-courses" title="<?= e($courseLine) ?>"><?= e($courseLine) ?></td>
                    <td class="payment-amount"><?= e(formatPrice((float) $p['amount'])) ?></td>
                    <td class="payment-status">
                        <span class="badge badge-<?= e($paymentStatus) ?>"><?= e($statusLabels[$paymentStatus] ?? $paymentStatus) ?></span>
                    </td>
                    <td class="actions payment-actions-cell">
                        <?php if ($slipUrl !== ''): ?>
                        <button
                            type="button"
                            class="btn btn-outline btn-sm payment-slip-btn"
                            data-open-slip
                            data-slip-url="<?= e($slipUrl) ?>"
                            data-slip-image="<?= $slipIsImage ? '1' : '0' ?>"
                            data-slip-payer="<?= e($p['student_name']) ?>"
                        >สลิป</button>
                        <?php endif; ?>
                        <?php if ($paymentStatus === 'pending'): ?>
                        <form method="post" class="payment-action-form table-actions">
                            <?= csrfField() ?>
                            <input type="hidden" name="payment_id" value="<?= (int) $p['id'] ?>">
                            <input type="hidden" name="list_page" value="<?= (int) $paymentsPager['page'] ?>">
                            <button type="submit" name="status" value="verified" class="btn btn-primary btn-sm">ยืนยัน</button>
                            <button type="submit" name="status" value="rejected" class="btn btn-danger btn-sm">ปฏิเสธ</button>
                        </form>
                        <?php elseif ($paymentStatus === 'rejected'): ?>
                        <form method="post" class="payment-action-form table-actions">
                            <?= csrfField() ?>
                            <input type="hidden" name="payment_id" value="<?= (int) $p['id'] ?>">
                            <input type="hidden" name="list_page" value="<?= (int) $paymentsPager['page'] ?>">
                            <button type="submit" name="status" value="verified" class="btn btn-primary btn-sm">ยืนยัน</button>
                        </form>
                        <?php else: ?>
                        <span class="payment-actions-muted">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php renderAdminPagination($paymentsPager, '/admin/payments.php'); ?>
        <?php else: ?>
        <p class="table-empty">ยังไม่มีรายการ</p>
        <?php endif; ?>
    </div>
</div>

<div class="admin-modal" id="paymentSlipModal" hidden>
    <div class="admin-modal-backdrop" data-close-slip-modal></div>
    <div class="admin-modal-dialog payment-slip-dialog" role="dialog" aria-modal="true" aria-labelledby="paymentSlipModalTitle">
        <button type="button" class="admin-modal-close" data-close-slip-modal aria-label="ปิด">
            <?= lucide_icon('x', ['size' => 18]) ?>
        </button>
        <div class="admin-modal-header">
            <h2 id="paymentSlipModalTitle">สลิปการโอนเงิน</h2>
            <p class="payment-slip-modal-meta" id="paymentSlipModalMeta"></p>
        </div>
        <div class="payment-slip-modal-body">
            <div class="payment-slip-frame" id="paymentSlipFrame">
                <img id="paymentSlipImage" class="payment-slip-preview" alt="สลิปการโอนเงิน" hidden>
                <iframe id="paymentSlipPdf" class="payment-slip-preview payment-slip-preview--pdf" title="สลิปการโอนเงิน" hidden></iframe>
            </div>
        </div>
        <div class="payment-slip-modal-footer">
            <a href="#" id="paymentSlipOpenTab" class="btn btn-secondary btn-sm" target="_blank" rel="noopener">เปิดแท็บใหม่</a>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/admin_footer.php'; ?>
