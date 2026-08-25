<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
requireAdmin();

$keys = [
    'site_title', 'site_tagline', 'hero_title', 'hero_subtitle',
    'bank_account_name', 'bank_name', 'bank_account_number', 'payment_note',
    'facebook_url', 'line_id', 'phone', 'youtube_url', 'tiktok_url',
    'email_enabled', 'email_transport', 'email_from', 'email_from_name', 'email_admin', 'site_url',
    'smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 'smtp_encryption',
    'line_notify_enabled', 'line_notify_token',
    'promptpay_enabled', 'promptpay_id', 'promptpay_id_type',
    'certificate_require_quiz',
    'privacy_policy_html', 'terms_html',
    'instructor_name', 'instructor_role', 'instructor_tagline', 'instructor_bio', 'instructor_quote',
    'instructor_photo', 'instructor_credentials', 'instructor_highlights',
    'instructor_stat_students', 'instructor_stat_satisfaction',
    'omise_enabled', 'omise_public_key', 'omise_secret_key',
    'payment_gateway_note',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    require_once dirname(__DIR__) . '/includes/line_messaging.php';
    try {
        $stmt = db()->prepare('
            INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?)
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
        ');
        foreach ($keys as $key) {
            if (in_array($key, ['email_enabled', 'line_notify_enabled', 'promptpay_enabled', 'certificate_require_quiz', 'omise_enabled'], true)) {
                $value = isset($_POST[$key]) ? '1' : '0';
            } elseif (in_array($key, ['smtp_pass', 'omise_secret_key'], true) && trim($_POST[$key] ?? '') === '') {
                continue;
            } else {
                $value = trim($_POST[$key] ?? '');
            }
            $stmt->execute([$key, $value]);
        }
        persistLineOaSettingsFromPost($_POST);
        unset($_SESSION['flash']['admin_error']);
        flash('admin_success', 'บันทึกการตั้งค่าเรียบร้อย');
    } catch (Throwable $e) {
        unset($_SESSION['flash']['admin_success']);
        flash('admin_error', 'เกิดข้อผิดพลาด');
    }
    redirect('/admin/settings.php#line-oa');
}

$pageTitle = 'ตั้งค่าเว็บไซต์';
require_once dirname(__DIR__) . '/includes/admin_header.php';

$message = flash('admin_success');
$error = flash('admin_error');

$settings = getSettings();
require_once dirname(__DIR__) . '/includes/line_messaging.php';
require_once dirname(__DIR__) . '/includes/promptpay.php';
$promptPayTarget = resolvePromptPayTargetId();
$promptPayReady = isPromptPayEnabled() && $promptPayTarget !== '';
?>

<?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

<div class="admin-card">
    <div class="admin-card-header">
        <h2>ตั้งค่าหน้าเว็บและการชำระเงิน</h2>
        <a href="<?= APP_URL ?>/admin/content.php" class="btn btn-outline btn-sm">จัดการเนื้อหาเว็บ</a>
    </div>
    <div class="admin-card-body">
        <form method="post">
            <?= csrfField() ?>
            <h3>ข้อมูลเว็บไซต์</h3>
            <div class="form-row">
                <div class="form-group">
                    <label>ชื่อเว็บ</label>
                    <input type="text" name="site_title" class="form-control" value="<?= e($settings['site_title'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>สโลแกน</label>
                    <input type="text" name="site_tagline" class="form-control" value="<?= e($settings['site_tagline'] ?? '') ?>">
                </div>
            </div>
            <div class="form-group">
                <label>หัวข้อ Hero</label>
                <input type="text" name="hero_title" class="form-control" value="<?= e($settings['hero_title'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>คำอธิบาย Hero</label>
                <textarea name="hero_subtitle" class="form-control"><?= e($settings['hero_subtitle'] ?? '') ?></textarea>
            </div>

            <h3>ชำระเงิน</h3>
            <div class="form-group">
                <label>ชื่อบัญชี</label>
                <input type="text" name="bank_account_name" class="form-control" value="<?= e($settings['bank_account_name'] ?? '') ?>">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>ธนาคาร</label>
                    <input type="text" name="bank_name" class="form-control" value="<?= e($settings['bank_name'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>เลขบัญชี</label>
                    <input type="text" name="bank_account_number" class="form-control" value="<?= e($settings['bank_account_number'] ?? '') ?>">
                </div>
            </div>
            <div class="form-group">
                <label>ข้อความแจ้งชำระ</label>
                <textarea name="payment_note" class="form-control"><?= e($settings['payment_note'] ?? '') ?></textarea>
            </div>

            <h3>ติดต่อ</h3>
            <div class="form-group">
                <label>Facebook URL</label>
                <input type="url" name="facebook_url" class="form-control" value="<?= e($settings['facebook_url'] ?? '') ?>">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Line ID</label>
                    <input type="text" name="line_id" class="form-control" value="<?= e($settings['line_id'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>เบอร์โทร</label>
                    <input type="text" name="phone" class="form-control" value="<?= e($settings['phone'] ?? '') ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>YouTube URL</label>
                    <input type="url" name="youtube_url" class="form-control" value="<?= e($settings['youtube_url'] ?? '') ?>" placeholder="https://youtube.com/@...">
                </div>
                <div class="form-group">
                    <label>TikTok URL</label>
                    <input type="url" name="tiktok_url" class="form-control" value="<?= e($settings['tiktok_url'] ?? '') ?>" placeholder="https://tiktok.com/@...">
                </div>
            </div>

            <h3>อีเมลแจ้งเตือน</h3>
            <div class="form-group">
                <label><input type="checkbox" name="email_enabled" value="1" <?= ($settings['email_enabled'] ?? '0') === '1' ? 'checked' : '' ?>> เปิดใช้งานส่งอีเมล</label>
                <small style="display:block;color:#6b7280;margin-top:.25rem">ดู storage/logs/email.log หากส่งไม่สำเร็จ</small>
            </div>
            <div class="form-group">
                <label>วิธีส่งอีเมล</label>
                <select name="email_transport" class="form-control">
                    <option value="mail" <?= ($settings['email_transport'] ?? 'mail') === 'mail' ? 'selected' : '' ?>>PHP mail() (ค่าเริ่มต้น)</option>
                    <option value="smtp" <?= ($settings['email_transport'] ?? '') === 'smtp' ? 'selected' : '' ?>>SMTP</option>
                </select>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>อีเมลผู้ส่ง (From)</label>
                    <input type="email" name="email_from" class="form-control" value="<?= e($settings['email_from'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>ชื่อผู้ส่ง</label>
                    <input type="text" name="email_from_name" class="form-control" value="<?= e($settings['email_from_name'] ?? '') ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>อีเมลแจ้ง Admin (รับแจ้งชำระเงิน)</label>
                    <input type="email" name="email_admin" class="form-control" value="<?= e($settings['email_admin'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>URL เว็บไซต์ (โดเมนหลัก)</label>
                    <input type="url" name="site_url" class="form-control" value="<?= e($settings['site_url'] ?? '') ?>" placeholder="https://wenxinchinese.online">
                    <small class="form-hint">ใช้สร้างลิงก์ในอีเมล, LINE Webhook และหน้าแจ้งเตือน — บนเว็บจริงควรเป็น <code>https://</code> โดเมนของคุณ (ไม่ใช้ localhost)</small>
                </div>
            </div>

            <h4 class="admin-subsection-title">ตั้งค่า SMTP</h4>
            <div class="form-row">
                <div class="form-group">
                    <label>SMTP Host</label>
                    <input type="text" name="smtp_host" class="form-control" value="<?= e($settings['smtp_host'] ?? '') ?>" placeholder="smtp.gmail.com">
                </div>
                <div class="form-group">
                    <label>SMTP Port</label>
                    <input type="number" name="smtp_port" class="form-control" value="<?= e($settings['smtp_port'] ?? '587') ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>SMTP User</label>
                    <input type="text" name="smtp_user" class="form-control" value="<?= e($settings['smtp_user'] ?? '') ?>" autocomplete="off">
                </div>
                <div class="form-group">
                    <label>SMTP Password</label>
                    <input type="password" name="smtp_pass" class="form-control" placeholder="<?= ($settings['smtp_pass'] ?? '') !== '' ? '•••••••• (ว่างไว้ = ไม่เปลี่ยน)' : '' ?>" autocomplete="new-password">
                    <small style="color:#6b7280">หรือตั้งในไฟล์ .env เป็น SMTP_PASS</small>
                </div>
            </div>
            <div class="form-group">
                <label>การเข้ารหัส</label>
                <select name="smtp_encryption" class="form-control">
                    <option value="tls" <?= ($settings['smtp_encryption'] ?? 'tls') === 'tls' ? 'selected' : '' ?>>TLS (พอร์ต 587)</option>
                    <option value="ssl" <?= ($settings['smtp_encryption'] ?? '') === 'ssl' ? 'selected' : '' ?>>SSL (พอร์ต 465)</option>
                    <option value="none" <?= ($settings['smtp_encryption'] ?? '') === 'none' ? 'selected' : '' ?>>ไม่เข้ารหัส</option>
                </select>
            </div>

            <div class="admin-settings-integration admin-settings-integration--line">
                <div class="admin-settings-integration-head">
                    <span class="admin-settings-integration-icon admin-settings-integration-icon--line-notify" aria-hidden="true">
                        <?= brand_icon('line', ['size' => 28, 'class' => 'admin-settings-brand-icon']) ?>
                    </span>
                    <div>
                        <h3 class="admin-settings-integration-title">Line Notify</h3>
                        <p class="admin-settings-integration-desc">แจ้งเตือนทีมงานเมื่อมีการชำระเงิน / เปิดสิทธิ์เรียน</p>
                    </div>
                </div>
            <div class="form-group">
                <label><input type="checkbox" name="line_notify_enabled" value="1" <?= ($settings['line_notify_enabled'] ?? '0') === '1' ? 'checked' : '' ?>> เปิดแจ้งเตือน Line Notify</label>
                <small class="form-hint">สร้าง Token ที่ <a href="https://notify-bot.line.me/" target="_blank" rel="noopener">notify-bot.line.me</a></small>
            </div>
            <div class="form-group">
                <label>Line Notify Token</label>
                <input type="text" name="line_notify_token" class="form-control" value="<?= e($settings['line_notify_token'] ?? '') ?>" autocomplete="off">
            </div>
            </div>

            <div class="admin-settings-integration admin-settings-integration--promptpay">
                <div class="admin-settings-integration-head">
                    <span class="admin-settings-integration-icon admin-settings-integration-icon--promptpay" aria-hidden="true">
                        <?= brand_icon('promptpay', ['size' => 28, 'class' => 'admin-settings-brand-icon']) ?>
                    </span>
                    <div>
                        <h3 class="admin-settings-integration-title">PromptPay — โอนผ่าน QR</h3>
                        <p class="admin-settings-integration-desc">แสดง QR ยอดชำระจริงบนหน้า Checkout (โอนธนาคาร)</p>
                    </div>
                    <?php if ($promptPayReady): ?>
                    <span class="admin-settings-integration-badge admin-settings-integration-badge--on">พร้อมใช้งาน</span>
                    <?php elseif (isPromptPayEnabled()): ?>
                    <span class="admin-settings-integration-badge">ยังไม่ได้ตั้งเบอร์</span>
                    <?php else: ?>
                    <span class="admin-settings-integration-badge">ปิดอยู่</span>
                    <?php endif; ?>
                </div>
            <div class="form-group">
                <label><input type="checkbox" name="promptpay_enabled" value="1" <?= ($settings['promptpay_enabled'] ?? '1') === '1' ? 'checked' : '' ?>> แสดง QR PromptPay หน้าชำระเงิน</label>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="admin-settings-label-with-icon"><?= lucide_icon('phone', ['size' => 16, 'class' => 'admin-settings-label-icon']) ?> เบอร์/เลข PromptPay</label>
                    <input type="text" name="promptpay_id" class="form-control" value="<?= e($settings['promptpay_id'] ?? '') ?>" placeholder="เช่น 0895567438">
                    <small class="form-hint">ว่างไว้จะใช้เบอร์โทรจากช่อง «ติดต่อ» ด้านบน</small>
                </div>
                <div class="form-group">
                    <label class="admin-settings-label-with-icon"><?= lucide_icon('landmark', ['size' => 16, 'class' => 'admin-settings-label-icon']) ?> ประเภท</label>
                    <select name="promptpay_id_type" class="form-control">
                        <option value="phone" <?= ($settings['promptpay_id_type'] ?? 'phone') === 'phone' ? 'selected' : '' ?>>เบอร์โทร</option>
                        <option value="national_id" <?= ($settings['promptpay_id_type'] ?? '') === 'national_id' ? 'selected' : '' ?>>เลขบัตรประชาชน</option>
                    </select>
                </div>
            </div>
            <?php if ($promptPayReady): ?>
            <p class="admin-settings-webhook">
                <?= lucide_icon('smartphone', ['size' => 16, 'class' => 'admin-settings-label-icon']) ?>
                ตั้งค่าแล้ว: <strong><?= e(formatPromptPayTargetDisplay($promptPayTarget, $settings['promptpay_id_type'] ?? 'phone')) ?></strong>
                — ลูกค้าจะเห็น QR ที่ <a href="<?= APP_URL ?>/public/checkout.php" target="_blank" rel="noopener">หน้าชำระเงิน</a> เมื่อมีสินค้าในตะกร้า
            </p>
            <?php endif; ?>
            </div>

            <div class="admin-settings-integration admin-settings-integration--omise">
                <div class="admin-settings-integration-head">
                    <span class="admin-settings-integration-icon admin-settings-integration-icon--omise" aria-hidden="true">
                        <?= lucide_icon('credit-card', ['size' => 26, 'stroke' => '1.75']) ?>
                    </span>
                    <div>
                        <h3 class="admin-settings-integration-title">Omise — ชำระเงินออนไลน์</h3>
                        <p class="admin-settings-integration-desc">PromptPay QR · บัตรเครดิต / เดบิต</p>
                    </div>
                    <?php if (($settings['omise_enabled'] ?? '0') === '1'): ?>
                    <span class="admin-settings-integration-badge admin-settings-integration-badge--on">เปิดใช้งาน</span>
                    <?php else: ?>
                    <span class="admin-settings-integration-badge">ปิดอยู่</span>
                    <?php endif; ?>
                </div>
            <div class="form-group">
                <label><input type="checkbox" name="omise_enabled" value="1" <?= ($settings['omise_enabled'] ?? '0') === '1' ? 'checked' : '' ?>> เปิดชำระเงินออนไลน์ (PromptPay / บัตรเครดิต)</label>
                <small class="form-hint">สมัครที่ <a href="https://www.omise.co/" target="_blank" rel="noopener">omise.co</a> — Webhook: <code class="admin-settings-code"><?= e((getSetting('site_url') ?: APP_URL) . '/public/omise_webhook.php') ?></code></small>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="admin-settings-label-with-icon"><?= lucide_icon('lock', ['size' => 16, 'class' => 'admin-settings-label-icon']) ?> Omise Public Key (pkey_...)</label>
                    <input type="text" name="omise_public_key" class="form-control" value="<?= e($settings['omise_public_key'] ?? '') ?>" autocomplete="off">
                </div>
                <div class="form-group">
                    <label class="admin-settings-label-with-icon"><?= lucide_icon('shield-check', ['size' => 16, 'class' => 'admin-settings-label-icon']) ?> Omise Secret Key (skey_...)</label>
                    <input type="password" name="omise_secret_key" class="form-control" placeholder="<?= ($settings['omise_secret_key'] ?? '') !== '' ? '•••••••• (ว่างไว้ = ไม่เปลี่ยน)' : '' ?>" autocomplete="new-password">
                    <small class="form-hint">หรือตั้งใน .env เป็น OMISE_SECRET_KEY</small>
                </div>
            </div>
            </div>

            <div class="admin-settings-integration admin-settings-integration--line-oa" id="line-oa">
                <?php
                    $lineWebhookUrl = lineOaWebhookUrl();
                    $lineSiteBase = lineOaPublicBaseUrl();
                    $lineIsLocalDev = lineOaIsLocalDev();
                ?>
                <div class="admin-settings-integration-head">
                    <span class="admin-settings-integration-icon admin-settings-integration-icon--line-oa" aria-hidden="true">
                        <?= brand_icon('line', ['size' => 28, 'class' => 'admin-settings-brand-icon']) ?>
                    </span>
                    <div>
                        <h3 class="admin-settings-integration-title">LINE Official Account</h3>
                        <p class="admin-settings-integration-desc">แจ้งเตือนนักเรียนเมื่อจองคลาส · ส่งลิงก์ Zoom · เตือนก่อนเริ่มเรียน</p>
                    </div>
                    <?php if (isLineOaEnabled()): ?>
                    <span class="admin-settings-integration-badge admin-settings-integration-badge--on">เปิดใช้งาน</span>
                    <?php else: ?>
                    <span class="admin-settings-integration-badge">ปิดอยู่</span>
                    <?php endif; ?>
                </div>

            <div class="admin-line-setup-box">
                <p class="admin-settings-webhook" style="margin-bottom:.5rem">
                    <?= lucide_icon('link', ['size' => 16, 'class' => 'admin-settings-label-icon']) ?>
                    Webhook URL
                    <span class="admin-line-env-badge<?= $lineIsLocalDev ? ' admin-line-env-badge--dev' : ' admin-line-env-badge--prod' ?>">
                        <?= $lineIsLocalDev ? 'โหมดทดสอบ' : 'โดเมนจริง' ?>
                    </span>
                </p>
                <code class="admin-settings-code admin-line-webhook-url"><?= e($lineWebhookUrl) ?></code>
                <p class="form-hint" style="margin-top:.65rem;margin-bottom:0">
                    คัดลอก URL นี้ไปตั้งใน <a href="https://developers.line.biz/console/" target="_blank" rel="noopener">LINE Developers Console</a>
                    → Messaging API → Webhook settings → เปิด Use webhook แล้วกด Verify
                </p>
                <?php if (!$lineIsLocalDev && lineOaSiteUrlIsStaleLocalhost()): ?>
                <p class="form-hint admin-line-siteurl-hint">
                    แนะนำ: เปลี่ยน「URL เว็บไซต์」ด้านบนเป็น <code><?= e($lineSiteBase) ?></code> แล้วบันทึก เพื่อให้ลิงก์ในอีเมลและ LINE ถูกต้องทุกครั้ง
                </p>
                <?php endif; ?>
            </div>

            <ol class="admin-line-setup-steps">
                <li>ใส่ <strong>Channel Secret</strong> และ <strong>Channel Access Token</strong> จาก LINE Developers</li>
                <li>ตั้ง Webhook เป็น <code><?= e($lineWebhookUrl) ?></code> แล้วกด Verify ให้ผ่าน</li>
                <li>เปิด「ส่งแจ้งเตือนผ่าน LINE OA」ด้านล่าง แล้วกดบันทึก</li>
                <li>ไปที่ <a href="https://manager.line.biz/" target="_blank" rel="noopener">LINE Official Account Manager</a>
                    → การตอบกลับ → เลือก <strong>Webhook</strong> และปิด Auto-reply / Greeting</li>
            </ol>

            <?php if ($lineIsLocalDev): ?>
            <p class="form-hint admin-line-dev-hint">
                ทดสอบบนเครื่องตัวเอง: ใช้ <code>ngrok http 80</code> แล้วตั้ง Webhook เป็น URL ของ ngrok
                หรือเปลี่ยน「URL เว็บไซต์」ด้านบนเป็น URL ชั่วคราวของ ngrok
            </p>
            <?php endif; ?>

            <div class="form-group">
                <label><input type="checkbox" name="line_oa_enabled" value="1" <?= ($settings['line_oa_enabled'] ?? '0') === '1' ? 'checked' : '' ?>> เปิดส่งแจ้งเตือนผ่าน LINE OA</label>
                <small class="form-hint">นักเรียนเพิ่มเพื่อน Official Account แล้วส่งเบอร์โทรที่ใช้สมัครในแชทเพื่อเชื่อมบัญชี — ดูขั้นตอนที่ บัญชีของฉัน → การจองคลาส</small>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="admin-settings-label-with-icon"><?= lucide_icon('shield', ['size' => 16, 'class' => 'admin-settings-label-icon']) ?> Channel Secret</label>
                    <input type="password" name="line_oa_channel_secret" class="form-control" value="<?= e($settings['line_oa_channel_secret'] ?? '') ?>" placeholder="จาก LINE Developers → Basic settings" autocomplete="off">
                </div>
                <div class="form-group">
                    <label class="admin-settings-label-with-icon"><?= lucide_icon('lock', ['size' => 16, 'class' => 'admin-settings-label-icon']) ?> Channel Access Token</label>
                    <input type="password" name="line_oa_channel_token" class="form-control" value="<?= e($settings['line_oa_channel_token'] ?? '') ?>" placeholder="จาก LINE Developers → Messaging API" autocomplete="off">
                </div>
            </div>
            <div class="form-group">
                <label>LINE OA Basic ID (@username)</label>
                <input type="text" name="line_oa_basic_id" class="form-control" value="<?= e($settings['line_oa_basic_id'] ?? '') ?>" placeholder="เช่น wenxin (ไม่ต้องใส่ @)">
                <small class="form-hint">ใช้สร้างปุ่ม「เพิ่มเพื่อนใน LINE」ในหน้าบัญชีนักเรียน — กดทดสอบด้านล่างเพื่อดึงอัตโนมัติ หรือปล่อยว่างเพื่อใช้ Line ID ในหมวดติดต่อ</small>
            </div>
            <div class="admin-form-actions" style="margin-top:.5rem">
                <button type="submit" formaction="<?= APP_URL ?>/admin/line_test.php" formnovalidate name="line_test_action" value="bot_info" class="btn btn-outline btn-sm">ทดสอบเชื่อมต่อ LINE API</button>
                <?php if (lineOaAddFriendUrl()): ?>
                <a href="<?= e(lineOaAddFriendUrl()) ?>" target="_blank" rel="noopener" class="btn btn-secondary btn-sm">เปิดหน้าเพิ่มเพื่อน</a>
                <?php endif; ?>
            </div>
            <?php if (isLineOaWebhookReady()): ?>
            <?php
                $secretLen = strlen(lineOaChannelSecret());
                $tokenLen = strlen(lineOaChannelToken());
            ?>
            <?php if ($secretLen > 0 && $secretLen < 20): ?>
            <p class="form-hint" style="margin-top:.75rem;color:#b42318">Channel Secret สั้นเกินไป (<?= $secretLen ?> ตัว) — คัดลอกใหม่จาก LINE Developers → Basic settings (ปกติประมาณ 32 ตัว)</p>
            <?php elseif ($tokenLen > 0 && $tokenLen < 80): ?>
            <p class="form-hint" style="margin-top:.75rem;color:#b42318">Access Token สั้นเกินไป (<?= $tokenLen ?> ตัว) — กด Issue ใหม่ที่ Messaging API</p>
            <?php else: ?>
            <p class="form-hint" style="margin-top:.75rem;color:#047857">พร้อมใช้งาน — ตั้ง Webhook ใน LINE Developers แล้วกด Verify ให้ผ่าน</p>
            <?php endif; ?>
            <?php endif; ?>
            </div>

            <h3>หมายเหตุ Payment Gateway</h3>
            <div class="form-group">
                <label>ข้อความแสดงหน้าชำระเงิน (ถ้าต้องการ)</label>
                <textarea name="payment_gateway_note" class="form-control" rows="2"><?= e($settings['payment_gateway_note'] ?? '') ?></textarea>
                <small style="color:#667085">รองรับ Omise: PromptPay QR และบัตรเครดิต/เดบิต — ทีมงานช่วยเชื่อมหลังลูกค้าสมัครผู้ให้บริการ</small>
            </div>

            <h3>ใบประกาศนียบัตร</h3>
            <div class="form-group">
                <label><input type="checkbox" name="certificate_require_quiz" value="1" <?= ($settings['certificate_require_quiz'] ?? '0') === '1' ? 'checked' : '' ?>> ต้องผ่านแบบทดสอบทุกชุดในคอร์สก่อนออกใบประกาศ</label>
                <small style="display:block;color:#6b7280;margin-top:.25rem">ถ้าคอร์สไม่มีแบบทดสอบ จะออกใบประกาศเมื่อเรียนครบ 100% ตามเดิม</small>
            </div>

            <h3>โปรไฟล์ผู้สอน</h3>
            <p style="color:#6b7280;font-size:.9rem;margin-bottom:1rem">แสดงที่หน้าแรกและ <a href="<?= APP_URL ?>/public/instructor.php" target="_blank" rel="noopener">หน้าโปรไฟล์ผู้สอน</a> — คredentials/แนวทางสอน คั่นด้วย |</p>
            <div class="form-row">
                <div class="form-group">
                    <label>ชื่อผู้สอน</label>
                    <input type="text" name="instructor_name" class="form-control" value="<?= e($settings['instructor_name'] ?? '') ?>" placeholder="ครูบอล">
                </div>
                <div class="form-group">
                    <label>ตำแหน่ง / บทบาท</label>
                    <input type="text" name="instructor_role" class="form-control" value="<?= e($settings['instructor_role'] ?? '') ?>" placeholder="ผู้สอนหลัก · กวดวิชาเดอะลีโอน่า">
                </div>
            </div>
            <div class="form-group">
                <label>คำโปรยสั้น</label>
                <input type="text" name="instructor_tagline" class="form-control" value="<?= e($settings['instructor_tagline'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>คำคม / ข้อความหน้าแรก</label>
                <input type="text" name="instructor_quote" class="form-control" value="<?= e($settings['instructor_quote'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>ประวัติผู้สอน</label>
                <textarea name="instructor_bio" class="form-control" rows="4" placeholder="แยกย่อหน้าได้ด้วย Enter"><?= e($settings['instructor_bio'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label>รูปโปรไฟล์ (path ใน assets หรือ URL)</label>
                <input type="text" name="instructor_photo" class="form-control" value="<?= e($settings['instructor_photo'] ?? '') ?>" placeholder="images/instructor/wenxin-portrait.png">
            </div>
            <div class="form-group">
                <label>ประวัติและความเชี่ยวชาญ (คั่นด้วย |)</label>
                <textarea name="instructor_credentials" class="form-control" rows="3" placeholder="ปริญญาโท...|ประสบการณ์..."><?= e($settings['instructor_credentials'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label>แนวทางการสอน (คั่นด้วย |)</label>
                <textarea name="instructor_highlights" class="form-control" rows="3"><?= e($settings['instructor_highlights'] ?? '') ?></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>สถิติผู้เรียน (แสดง)</label>
                    <input type="text" name="instructor_stat_students" class="form-control" value="<?= e($settings['instructor_stat_students'] ?? '') ?>" placeholder="5000+">
                </div>
                <div class="form-group">
                    <label>ความพึงพอใจ (แสดง)</label>
                    <input type="text" name="instructor_stat_satisfaction" class="form-control" value="<?= e($settings['instructor_stat_satisfaction'] ?? '') ?>" placeholder="95%">
                </div>
            </div>

            <h3>นโยบายและข้อกำหนด</h3>
            <p style="color:#6b7280;font-size:.9rem;margin-bottom:1rem">ว่างไว้จะใช้ข้อความมาตรฐาน — ใส่ HTML ได้ถ้าต้องการปรับแต่ง</p>
            <div class="form-group">
                <label>นโยบายความเป็นส่วนตัว (HTML)</label>
                <textarea name="privacy_policy_html" class="form-control" rows="4"><?= e($settings['privacy_policy_html'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label>ข้อกำหนดการใช้งาน (HTML)</label>
                <textarea name="terms_html" class="form-control" rows="4"><?= e($settings['terms_html'] ?? '') ?></textarea>
            </div>

            <div class="admin-form-actions">
                <button type="submit" class="btn btn-primary">บันทึกการตั้งค่า</button>
            </div>
        </form>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/admin_footer.php'; ?>
