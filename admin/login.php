<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/rate_limit.php';

require_once dirname(__DIR__) . '/includes/feedback.php';

$redirectTarget = feedbackSafeAdminRedirect((string) ($_GET['redirect'] ?? $_POST['redirect'] ?? '/admin/dashboard.php'));

if (isAdminLoggedIn()) {
    redirect($redirectTarget);
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $redirectTarget = feedbackSafeAdminRedirect((string) ($_POST['redirect'] ?? '/admin/dashboard.php'));
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if (isLoginRateLimited('admin', $username)) {
        $error = loginRateLimitMessage();
    } elseif ($username && $password && attemptLogin($username, $password)) {
        clearLoginAttempts('admin', $username);
        if (str_contains($redirectTarget, 'feedback=1') || str_contains($redirectTarget, 'review=')) {
            $_SESSION['feedback_admin_preview'] = true;
        }
        redirect($redirectTarget);
    } else {
        recordLoginFailure('admin', $username);
        $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
    }
}

$pageTitle = 'เข้าสู่ระบบผู้ดูแล';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | Leona Admin</title>
    <meta name="robots" content="noindex, nofollow">
    <?php require dirname(__DIR__) . '/includes/views/fonts_head.php'; ?>
    <link rel="stylesheet" href="<?= adminAsset('css/admin.css') ?>">
</head>
<body class="admin-auth-body">
<section class="admin-auth-page">
    <div class="admin-auth-card">
        <h1>เข้าสู่ระบบผู้ดูแล</h1>
        <p class="admin-auth-lead">สำหรับทีมงานกวดวิชาเดอะลีโอน่าเท่านั้น</p>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>
        <form method="post" class="admin-auth-form">
            <?= csrfField() ?>
            <input type="hidden" name="redirect" value="<?= e($redirectTarget) ?>">
            <div class="form-group">
                <label for="admin-username">ชื่อผู้ใช้</label>
                <input type="text" name="username" id="admin-username" class="form-control" required autofocus autocomplete="username">
            </div>
            <div class="form-group">
                <label for="admin-password">รหัสผ่าน</label>
                <input type="password" name="password" id="admin-password" class="form-control" required autocomplete="current-password">
            </div>
            <button type="submit" class="btn btn-primary btn-block">เข้าสู่ระบบ</button>
        </form>
    </div>
</section>
</body>
</html>
