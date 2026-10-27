<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/feedback.php';

$config = feedbackLoadConfig();
$token = trim((string) ($_GET['review'] ?? $_GET['feedback_token'] ?? ''));
$error = '';
$adminStart = feedbackAdminStartUrl($token);

if (empty($config['enabled'])) {
    $pageTitle = 'ปิดการตรวจงาน';
    require_once dirname(__DIR__) . '/includes/header.php';
    echo '<section class="section"><div class="container"><div class="admin-card"><div class="admin-card-body"><p>ระบบแจ้งแก้ไขหลังบ้านปิดอยู่ชั่วคราว</p></div></div></div></section>';
    require_once dirname(__DIR__) . '/includes/footer.php';
    exit;
}

if ($token === '' || $config['token'] === '' || !hash_equals($config['token'], $token)) {
    $pageTitle = 'ลิงก์ตรวจงานไม่ถูกต้อง';
    require_once dirname(__DIR__) . '/includes/header.php';
    echo '<section class="section"><div class="container"><div class="admin-card"><div class="admin-card-body"><p>ลิงก์ตรวจงานไม่ถูกต้องหรือหมดอายุ กรุณาขอลิงก์ใหม่จากทีมงาน</p></div></div></div></section>';
    require_once dirname(__DIR__) . '/includes/footer.php';
    exit;
}

$needsPassword = trim((string) ($config['password_hash'] ?? '')) !== '';

$activateAndGo = static function () use ($config, $token, $adminStart): void {
    $_SESSION['feedback_preview_unlocked'] = true;
    $_SESSION['feedback_preview_token'] = $config['token'];
    $_SESSION['feedback_admin_preview'] = true;
    if (isAdminLoggedIn()) {
        redirect('/admin/dashboard.php?feedback=1&review=' . rawurlencode($token));
    }
    $next = '/admin/dashboard.php?feedback=1&review=' . rawurlencode($token);
    redirect('/admin/login.php?redirect=' . rawurlencode($next));
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    if (!$needsPassword) {
        $activateAndGo();
    }
    $pass = (string) ($_POST['password'] ?? '');
    if ($pass !== '' && password_verify($pass, $config['password_hash'])) {
        $activateAndGo();
    }
    $error = 'รหัสผ่านไม่ถูกต้อง';
}

if (!$needsPassword || !empty($_SESSION['feedback_preview_unlocked'])) {
    $_SESSION['feedback_preview_token'] = $config['token'];
    $_SESSION['feedback_admin_preview'] = true;
    if (!$needsPassword) {
        $activateAndGo();
    }
}

$pageTitle = 'เข้าสู่โหมดตรวจหลังบ้าน';
require_once dirname(__DIR__) . '/includes/header.php';
?>
<section class="section">
    <div class="container" style="max-width:480px">
        <div class="admin-card">
            <div class="admin-card-header">
                <h2>ตรวจระบบหลังบ้านและแจ้งแก้ไข</h2>
            </div>
            <div class="admin-card-body">
                <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
                <p class="form-hint" style="margin-top:0">โหมดนี้ใช้ตรวจหน้า Admin เท่านั้น ไม่แสดงบนเว็บหน้าบ้านสำหรับผู้ใช้ทั่วไป</p>
                <?php if ($needsPassword && empty($_SESSION['feedback_preview_unlocked'])): ?>
                <p>กรอกรหัสผ่านที่ได้รับจากทีมงานเพื่อเปิดโหมดแจ้งแก้ไขหลังบ้าน</p>
                <form method="post">
                    <?= csrfField() ?>
                    <div class="form-group">
                        <label for="fbPass">รหัสผ่านตรวจงาน</label>
                        <input type="password" id="fbPass" name="password" class="form-control" required autocomplete="current-password">
                    </div>
                    <button type="submit" class="btn btn-primary">เข้าสู่โหมดตรวจหลังบ้าน</button>
                </form>
                <?php else: ?>
                <p>พร้อมแล้ว — เข้าสู่ระบบแอดมินแล้วจะเห็นปุ่ม “แจ้งแก้ไข” บนหน้าหลังบ้าน</p>
                <?php if (isAdminLoggedIn()): ?>
                <a class="btn btn-primary" href="<?= e($adminStart) ?>">เปิดแดชบอร์ดแอดมิน</a>
                <?php else: ?>
                <a class="btn btn-primary" href="<?= e(APP_URL . '/admin/login.php?redirect=' . rawurlencode('/admin/dashboard.php?feedback=1&review=' . $token)) ?>">เข้าสู่ระบบแอดมินเพื่อเริ่มตรวจ</a>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
