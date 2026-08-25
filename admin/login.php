<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/rate_limit.php';

if (isAdminLoggedIn()) {
    redirect('/admin/dashboard.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if (isLoginRateLimited('admin', $username)) {
        $error = loginRateLimitMessage();
    } elseif ($username && $password && attemptLogin($username, $password)) {
        clearLoginAttempts('admin', $username);
        redirect('/admin/dashboard.php');
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
