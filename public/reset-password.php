<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/student_auth.php';

if (isStudentLoggedIn()) {
    redirect('/public/my-courses.php');
}

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$record = $token !== '' ? validatePasswordResetToken($token) : null;
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $record) {
    verifyCsrf();
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['password_confirm'] ?? '';
    if ($password !== $confirm) {
        $error = 'รหัสผ่านไม่ตรงกัน';
    } else {
        $result = resetPasswordWithToken($token, $password);
        if ($result['ok']) {
            flash('login_error', $result['message']);
            redirect('/public/login.php');
        }
        $error = $result['message'];
    }
}

$pageTitle = 'ตั้งรหัสผ่านใหม่';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<section class="auth-page">
    <div class="auth-card">
        <h1>ตั้งรหัสผ่านใหม่</h1>
        <?php if (!$record): ?>
            <div class="alert alert-error">ลิงก์หมดอายุหรือไม่ถูกต้อง กรุณาขอรีเซ็ตใหม่</div>
            <p style="text-align:center;margin-top:1rem">
                <a href="<?= APP_URL ?>/public/forgot-password.php" class="btn btn-primary">ขอลิงก์ใหม่</a>
            </p>
        <?php else: ?>
            <p style="text-align:center;color:var(--gray-600);margin-bottom:1.5rem;font-size:.9rem">
                สำหรับ <?= e($record['full_name'] ?? '') ?>
            </p>
            <?php if ($error): ?>
                <div class="alert alert-error"><?= e($error) ?></div>
            <?php endif; ?>
            <form method="post">
                <?= csrfField() ?>
                <input type="hidden" name="token" value="<?= e($token) ?>">
                <div class="form-group">
                    <label>รหัสผ่านใหม่</label>
                    <input type="password" name="password" class="form-control" required minlength="6" autocomplete="new-password">
                </div>
                <div class="form-group">
                    <label>ยืนยันรหัสผ่านใหม่</label>
                    <input type="password" name="password_confirm" class="form-control" required minlength="6" autocomplete="new-password">
                </div>
                <button type="submit" class="btn btn-primary btn-block">บันทึกรหัสผ่านใหม่</button>
            </form>
        <?php endif; ?>
        <p style="text-align:center;margin-top:1rem;font-size:.9rem">
            <a href="<?= APP_URL ?>/public/login.php">กลับหน้าเข้าสู่ระบบ</a>
        </p>
    </div>
</section>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
