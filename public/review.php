<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/feedback.php';

$config = feedbackLoadConfig();
$token = trim((string) ($_GET['review'] ?? $_GET['feedback_token'] ?? ''));
$error = '';
$unlocked = false;

if (empty($config['enabled'])) {
    $pageTitle = 'ปิดการตรวจงาน';
    require_once dirname(__DIR__) . '/includes/header.php';
    echo '<section class="section"><div class="container"><div class="admin-card"><div class="admin-card-body"><p>ระบบแจ้งแก้ไขปิดอยู่ชั่วคราว</p></div></div></div></section>';
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    if (!$needsPassword) {
        $_SESSION['feedback_preview_unlocked'] = true;
        $_SESSION['feedback_preview_token'] = $config['token'];
        redirect('/public/index.php?review=' . rawurlencode($token));
    }
    $pass = (string) ($_POST['password'] ?? '');
    if ($pass !== '' && password_verify($pass, $config['password_hash'])) {
        $_SESSION['feedback_preview_unlocked'] = true;
        $_SESSION['feedback_preview_token'] = $config['token'];
        redirect('/public/index.php?review=' . rawurlencode($token));
    }
    $error = 'รหัสผ่านไม่ถูกต้อง';
}

if (!$needsPassword || !empty($_SESSION['feedback_preview_unlocked'])) {
    $_SESSION['feedback_preview_token'] = $config['token'];
    if (!$needsPassword) {
        redirect('/public/index.php?review=' . rawurlencode($token));
    }
    $unlocked = !empty($_SESSION['feedback_preview_token']);
}

$pageTitle = 'เข้าสู่โหมดตรวจงาน';
require_once dirname(__DIR__) . '/includes/header.php';
?>
<section class="section">
    <div class="container" style="max-width:480px">
        <div class="admin-card">
            <div class="admin-card-header">
                <h2>ตรวจเว็บและแจ้งแก้ไข</h2>
            </div>
            <div class="admin-card-body">
                <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
                <?php if ($needsPassword && empty($_SESSION['feedback_preview_unlocked'])): ?>
                <p>กรอกรหัสผ่านที่ได้รับจากทีมงานเพื่อเปิดโหมดแจ้งแก้ไข</p>
                <form method="post">
                    <?= csrfField() ?>
                    <div class="form-group">
                        <label for="fbPass">รหัสผ่านตรวจงาน</label>
                        <input type="password" id="fbPass" name="password" class="form-control" required autocomplete="current-password">
                    </div>
                    <button type="submit" class="btn btn-primary">เข้าสู่โหมดตรวจงาน</button>
                </form>
                <?php else: ?>
                <p>พร้อมใช้งานแล้ว กดปุ่มด้านล่างเพื่อเปิดหน้าเว็บพร้อมปุ่ม “แจ้งแก้ไข”</p>
                <a class="btn btn-primary" href="<?= e(APP_URL . '/public/index.php?review=' . rawurlencode($token)) ?>">เริ่มตรวจเว็บไซต์</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
