<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/omise.php';

$paymentId = (int) ($_GET['payment_id'] ?? $_SESSION['omise_pending_payment_id'] ?? 0);
unset($_SESSION['omise_pending_payment_id']);

if ($paymentId <= 0) {
    flash('payment_error', 'ไม่พบรายการชำระเงิน');
    redirect('/public/checkout.php');
}

if (completeOmisePayment($paymentId)) {
    flash('payment_success', 'ชำระเงินสำเร็จ! เปิดสิทธิ์เรียนแล้ว — เข้าเรียนได้ทันที');
    redirect('/public/my-courses.php');
}

$pageTitle = 'กำลังตรวจสอบการชำระเงิน';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<section class="auth-page">
    <div class="auth-card" style="max-width:480px">
        <h1>กำลังตรวจสอบการชำระเงิน</h1>
        <p style="text-align:center;color:var(--gray-600);line-height:1.7">
            หากชำระเงินแล้ว ระบบจะเปิดสิทธิ์เรียนอัตโนมัติภายในไม่กี่นาที<br>
            หน้านี้จะรีเฟรชอัตโนมัติ
        </p>
        <div style="text-align:center;margin-top:1.5rem">
            <a href="<?= APP_URL ?>/public/omise_return.php?payment_id=<?= $paymentId ?>" class="btn btn-primary">ตรวจสอบอีกครั้ง</a>
            <a href="<?= APP_URL ?>/public/my-courses.php" class="btn btn-outline" style="margin-left:.5rem">ไปหน้าเริ่มเรียน</a>
        </div>
    </div>
</section>
<script>
setTimeout(function () { window.location.reload(); }, 8000);
</script>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
