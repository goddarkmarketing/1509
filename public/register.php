<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/student_auth.php';

if (isStudentLoggedIn()) {
    redirect('/public/my-courses.php');
}

$success = flash('register_success');
$error = flash('register_error');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['password_confirm'] ?? '';
    if ($password !== $confirm) {
        flash('register_error', 'รหัสผ่านไม่ตรงกัน');
        redirect('/public/register.php');
    }
    if (empty($_POST['accept_terms'])) {
        flash('register_error', 'กรุณายอมรับข้อกำหนดและนโยบายความเป็นส่วนตัว');
        redirect('/public/register.php');
    }
    $result = registerStudent(
        trim($_POST['full_name'] ?? ''),
        trim($_POST['student_phone'] ?? ''),
        $_POST['password'] ?? '',
        trim($_POST['email'] ?? '') ?: null,
        trim($_POST['line_id'] ?? '') ?: null
    );
    if ($result['ok']) {
        flash('register_success', $result['message']);
        redirect('/public/my-courses.php');
    }
    flash('register_error', $result['message']);
    redirect('/public/register.php');
}

$pageTitle = 'สมัครสมาชิก';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<header class="page-header auth-register-page-header">
    <div class="container">
        <nav class="breadcrumb" aria-label="breadcrumb">
            <a href="<?= APP_URL ?>/public/index.php">หน้าแรก</a>
            <span aria-hidden="true">/</span>
            <span>สมัครสมาชิก</span>
        </nav>
        <h1>สมัครสมาชิก</h1>
        <p>สร้างบัญชีเพื่อเรียนและติดตามคอร์สของคุณ</p>
    </div>
</header>

<section class="auth-register-section">
    <div class="container">
        <div class="auth-register-shell">
            <aside class="auth-register-side" aria-label="สิทธิประโยชน์สมาชิก">
                <div class="auth-register-side-inner">
                    <p class="auth-register-eyebrow">THE LEONA TUTORS</p>
                    <h2 class="auth-register-side-title">เริ่มติวกับเรา<br>ได้วันนี้</h2>
                    <ul class="auth-register-perks">
                        <li>
                            <span class="auth-register-perk-icon" aria-hidden="true">
                                <?= lucide_icon('circle-check', ['size' => 16, 'stroke' => '2.5']) ?>
                            </span>
                            <span>เข้าเรียนคอร์ส HSK ได้ทันทีหลังเปิดสิทธิ์</span>
                        </li>
                        <li>
                            <span class="auth-register-perk-icon" aria-hidden="true">
                                <?= lucide_icon('circle-check', ['size' => 16, 'stroke' => '2.5']) ?>
                            </span>
                            <span>ติดตามความคืบหน้าและประวัติการเรียน</span>
                        </li>
                        <li>
                            <span class="auth-register-perk-icon" aria-hidden="true">
                                <?= lucide_icon('circle-check', ['size' => 16, 'stroke' => '2.5']) ?>
                            </span>
                            <span>ใช้เบอร์โทรหรืออีเมลเข้าสู่ระบบได้สะดวก</span>
                        </li>
                    </ul>
                </div>
            </aside>

            <div class="auth-register-main">
                <?php if ($success): ?>
                    <div class="alert alert-success"><?= e($success) ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-error"><?= e($error) ?></div>
                <?php endif; ?>

                <form method="post" class="auth-register-form">
                    <?= csrfField() ?>

                    <div class="auth-register-form-block">
                        <h3 class="auth-register-form-title">ข้อมูลส่วนตัว</h3>
                        <div class="auth-form-grid">
                            <div class="form-group">
                                <label for="register-full-name">ชื่อ-นามสกุล *</label>
                                <input type="text" name="full_name" id="register-full-name" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="register-phone">เบอร์โทร *</label>
                                <input type="tel" name="student_phone" id="register-phone" class="form-control" required placeholder="ใช้สำหรับเข้าสู่ระบบ">
                            </div>
                            <div class="form-group">
                                <label for="register-email">อีเมล</label>
                                <input type="email" name="email" id="register-email" class="form-control" placeholder="ไม่บังคับ">
                            </div>
                            <div class="form-group">
                                <label for="register-line">Line ID</label>
                                <input type="text" name="line_id" id="register-line" class="form-control" placeholder="@username">
                            </div>
                        </div>
                    </div>

                    <div class="auth-register-form-block">
                        <h3 class="auth-register-form-title">ตั้งรหัสผ่าน</h3>
                        <div class="auth-form-grid">
                            <div class="form-group">
                                <label for="register-password">รหัสผ่าน *</label>
                                <?php
                                $passwordName = 'password';
                                $passwordId = 'register-password';
                                $passwordAttrs = ['required' => true, 'minlength' => '6', 'autocomplete' => 'new-password'];
                                require dirname(__DIR__) . '/includes/views/password_field.php';
                                ?>
                                <small class="form-hint">อย่างน้อย 6 ตัวอักษร</small>
                            </div>
                            <div class="form-group">
                                <label for="register-password-confirm">ยืนยันรหัสผ่าน *</label>
                                <?php
                                $passwordName = 'password_confirm';
                                $passwordId = 'register-password-confirm';
                                $passwordAttrs = ['required' => true, 'minlength' => '6', 'autocomplete' => 'new-password'];
                                require dirname(__DIR__) . '/includes/views/password_field.php';
                                ?>
                            </div>
                        </div>
                    </div>

                    <div class="form-group auth-register-terms">
                        <label class="auth-terms-label">
                            <input type="checkbox" name="accept_terms" value="1" required>
                            <span>ฉันยอมรับ <a href="<?= APP_URL ?>/public/terms.php" target="_blank" rel="noopener">ข้อกำหนดการใช้งาน</a> และ <a href="<?= APP_URL ?>/public/privacy.php" target="_blank" rel="noopener">นโยบายความเป็นส่วนตัว</a></span>
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block auth-register-submit">สมัครสมาชิก</button>
                </form>

                <div class="auth-register-foot">
                    <span>มีบัญชีแล้ว? <a href="<?= APP_URL ?>/public/login.php">เข้าสู่ระบบ</a></span>
                    <span class="auth-register-foot-sep" aria-hidden="true">·</span>
                    <a href="<?= APP_URL ?>/public/index.php">กลับหน้าแรก</a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
