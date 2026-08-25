<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/student_auth.php';
require_once dirname(__DIR__) . '/includes/rate_limit.php';

if (isStudentLoggedIn()) {
    redirect(consumeLoginRedirect());
}

$error = flash('login_error') ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $identifier = trim($_POST['identifier'] ?? $_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    if (isLoginRateLimited('student', $identifier)) {
        $error = loginRateLimitMessage();
    } elseif (attemptStudentLogin($identifier, $password)) {
        clearLoginAttempts('student', $identifier);
        redirect(consumeLoginRedirect());
    } else {
        recordLoginFailure('student', $identifier);
        $error = 'เบอร์โทร/อีเมลหรือรหัสผ่านไม่ถูกต้อง';
    }
}

$pageTitle = 'เข้าสู่ระบบ';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<section class="auth-page">
    <div class="auth-card">
        <h1>เข้าสู่ระบบ</h1>
        <p style="text-align:center;color:var(--gray-600);margin-bottom:1.5rem;font-size:.9rem">สำหรับนักเรียนกวดวิชาเดอะลีโอน่า</p>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>
        <form method="post">
            <?= csrfField() ?>
            <div class="form-group">
                <label>เบอร์โทรหรืออีเมล</label>
                <input type="text" name="identifier" class="form-control" required autofocus autocomplete="username" value="<?= e($_POST['identifier'] ?? $_POST['phone'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>รหัสผ่าน</label>
                <input type="password" name="password" class="form-control" required autocomplete="current-password">
                <p style="text-align:right;margin-top:.35rem;font-size:.85rem">
                    <a href="<?= APP_URL ?>/public/forgot-password.php">ลืมรหัสผ่าน?</a>
                </p>
            </div>
            <button type="submit" class="btn btn-primary btn-block">เข้าสู่ระบบ</button>
        </form>
        <p style="text-align:center;margin-top:1rem;font-size:.9rem">
            ยังไม่มีบัญชี? <a href="<?= APP_URL ?>/public/register.php">สมัครสมาชิก</a>
        </p>
        <p style="text-align:center;margin-top:.5rem">
            <a href="<?= APP_URL ?>/public/index.php">กลับหน้าแรก</a>
        </p>
    </div>
</section>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
