<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/student_auth.php';

if (isStudentLoggedIn()) {
    redirect('/public/my-courses.php');
}

$message = '';
$error = flash('forgot_error') ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $result = createPasswordResetToken(trim($_POST['identifier'] ?? ''));
    if ($result['ok']) {
        $message = $result['message'];
    } else {
        $error = $result['message'];
    }
}

$pageTitle = 'ลืมรหัสผ่าน';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<section class="auth-page">
    <div class="auth-card">
        <h1>ลืมรหัสผ่าน</h1>
        <p style="text-align:center;color:var(--gray-600);margin-bottom:1.5rem;font-size:.9rem">กรอกเบอร์โทรหรืออีเมลที่ลงทะเบียนไว้</p>
        <?php if ($message): ?>
            <div class="alert alert-success"><?= e($message) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>
        <?php if (!$message): ?>
        <form method="post">
            <?= csrfField() ?>
            <div class="form-group">
                <label>เบอร์โทร หรือ อีเมล</label>
                <input type="text" name="identifier" class="form-control" required autofocus placeholder="เช่น 0895567438 หรือ email@example.com">
            </div>
            <button type="submit" class="btn btn-primary btn-block">ส่งลิงก์รีเซ็ตรหัสผ่าน</button>
        </form>
        <?php endif; ?>
        <p style="text-align:center;margin-top:1rem;font-size:.9rem">
            <a href="<?= APP_URL ?>/public/login.php">กลับหน้าเข้าสู่ระบบ</a>
        </p>
    </div>
</section>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
