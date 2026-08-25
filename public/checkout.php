<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/cart.php';
require_once dirname(__DIR__) . '/includes/checkout_flow.php';
require_once dirname(__DIR__) . '/includes/booking.php';
require_once dirname(__DIR__) . '/includes/student_auth.php';
require_once dirname(__DIR__) . '/includes/coupon.php';
require_once dirname(__DIR__) . '/includes/promptpay.php';
require_once dirname(__DIR__) . '/includes/omise.php';

$paymentSuccess = flash('payment_success');
$paymentError = flash('payment_error');
$checkoutStudent = currentStudent();

if (!$paymentSuccess) {
    $courseSlug = trim($_GET['course'] ?? '');
    if ($courseSlug !== '') {
        if (!addCourseSlugToCart($courseSlug)) {
            flash('payment_error', 'ไม่พบคอร์สที่เลือก');
            redirect('/public/courses.php');
        }
        redirect('/public/cart.php');
    }
    requireCartForCheckout();
}

$pageTitle = 'ชำระเงิน';
require_once dirname(__DIR__) . '/includes/header.php';

$orderItems = cartItems();
$cartSessionDetails = getCartSessionDetails();
$preselectAmount = (string) (cartTotal() ?: '');
$cartNotePrefill = cartCount() > 0 ? 'คอร์สในตะกร้า: ' . cartTitlesSummary() : '';
$preselectCourseId = count($orderItems) === 1 ? (int) ($orderItems[0]['id'] ?? 0) : 0;
$orderTotal = cartTotal();
$orderSubtotal = cartSubtotal();
$orderDiscount = cartDiscount();
$bankNumber = getSetting('bank_account_number');
$bankName = getSetting('bank_name');
$bankAccountName = getSetting('bank_account_name');
$bankDigits = preg_replace('/\D/', '', $bankNumber);
$bankNumberDisplay = strlen($bankDigits) === 10
    ? substr($bankDigits, 0, 3) . '-' . substr($bankDigits, 3, 1) . '-' . substr($bankDigits, 4, 5) . '-' . substr($bankDigits, 9, 1)
    : $bankNumber;
$checkoutStep = $paymentSuccess ? 4 : 3;
$promptPay = getCheckoutPromptPayData($orderTotal);
$omiseEnabled = isOmiseCheckoutVisible();
$omisePublicKey = omisePublicKey();
$omiseReady = isOmiseEnabled();
?>

<main class="checkout-page">
    <div class="container">
        <?php if ($paymentSuccess): ?>
        <div class="alert alert-success checkout-alert"><?= e($paymentSuccess) ?></div>
        <?php endif; ?>
        <?php if ($paymentError): ?>
        <div class="alert alert-error checkout-alert"><?= e($paymentError) ?></div>
        <?php endif; ?>

        <?php renderCheckoutSteps($checkoutStep); ?>

        <?php if ($paymentSuccess): ?>
        <div class="checkout-complete-card">
            <h2>แจ้งชำระเงินเรียบร้อยแล้ว</h2>
            <p>ทีมงานจะตรวจสอบและเปิดสิทธิ์เรียนภายใน 24 ชั่วโมง เมื่อเปิดสิทธิ์แล้วสามารถเริ่มเรียนได้ทันที</p>
            <div class="checkout-complete-actions">
                <a href="<?= APP_URL ?>/public/my-courses.php" class="btn btn-primary">ไปหน้าเริ่มเรียน</a>
                <a href="<?= APP_URL ?>/public/courses.php" class="btn btn-outline">เลือกคอร์สเพิ่ม</a>
            </div>
        </div>
        <?php else: ?>
        <div class="checkout-layout">
            <div class="checkout-main">
                <header class="checkout-main-header">
                    <span class="checkout-main-icon" aria-hidden="true">
                        <?= lucide_icon('credit-card', ['size' => 28, 'stroke' => '1.75']) ?>
                    </span>
                    <h1>ชำระเงินซื้อคอร์สเรียน</h1>
                </header>

                <section class="checkout-panel checkout-panel--pay-hub">
                    <div class="checkout-pay-hub">
                        <div class="checkout-pay-picker">
                            <h2 class="checkout-pay-picker-title">ช่องทางการชำระเงิน</h2>
                            <div class="checkout-pay-options" role="radiogroup" aria-label="ช่องทางการชำระเงิน">
                                <button type="button" class="checkout-pay-option" data-checkout-method="transfer" role="radio" aria-checked="false">
                                    <span class="checkout-pay-option-badge">แนะนำ</span>
                                    <span class="checkout-pay-option-radio" aria-hidden="true"></span>
                                    <span class="checkout-pay-option-icon checkout-pay-option-icon--bank" aria-hidden="true">
                                        <?= lucide_icon('landmark', ['size' => 26, 'stroke' => '1.75']) ?>
                                    </span>
                                    <span class="checkout-pay-option-body">
                                        <strong class="checkout-pay-option-title">โอนเงินผ่านธนาคาร</strong>
                                        <span class="checkout-pay-option-desc">ชำระผ่านแอปธนาคาร หรือ Internet Banking</span>
                                    </span>
                                    <span class="checkout-pay-option-check" aria-hidden="true"><?= lucide_icon('circle-check', ['size' => 22]) ?></span>
                                </button>

                                <?php if ($omiseEnabled): ?>
                                <button type="button" class="checkout-pay-option" data-checkout-method="card" role="radio" aria-checked="false">
                                    <span class="checkout-pay-option-radio" aria-hidden="true"></span>
                                    <span class="checkout-pay-option-icon checkout-pay-option-icon--card" aria-hidden="true">
                                        <?= lucide_icon('credit-card', ['size' => 26, 'stroke' => '1.75']) ?>
                                    </span>
                                    <span class="checkout-pay-option-body">
                                        <strong class="checkout-pay-option-title">ชำระด้วยบัตรเครดิต/เดบิต</strong>
                                        <span class="checkout-pay-option-desc">ชำระเงินด้วยบัตรเครดิต หรือบัตรเดบิต</span>
                                    </span>
                                    <span class="checkout-pay-option-brands" aria-hidden="true">
                                        <img src="<?= e(imageAsset('images/checkout/cards/card-brands.png')) ?>" alt="Visa, Mastercard, JCB, UnionPay" class="checkout-card-brands-img" width="220" height="32" loading="lazy" decoding="async">
                                    </span>
                                    <span class="checkout-pay-option-check" aria-hidden="true"><?= lucide_icon('circle-check', ['size' => 22]) ?></span>
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="checkout-pay-detail" id="checkoutDetailTransfer" hidden>
                            <div class="checkout-pay-detail-grid<?= $promptPay ? ' checkout-pay-detail-grid--with-qr' : '' ?>">
                                <section class="checkout-pay-detail-bank" aria-labelledby="checkout-detail-bank-title">
                                    <header class="checkout-pay-detail-bank-head">
                                        <div class="checkout-pay-detail-bank-brand">
                                            <img src="<?= e(imageAsset('images/checkout/kbank-logo.png', 'images/checkout/kbank-logo.svg')) ?>" alt="" class="checkout-pay-detail-bank-logo" width="36" height="36" loading="lazy" decoding="async">
                                            <span>โอนผ่านธนาคาร <?= e($bankName) ?></span>
                                        </div>
                                    </header>
                                    <h3 class="checkout-pay-detail-heading" id="checkout-detail-bank-title">ข้อมูลบัญชีผู้รับโอน</h3>
                                    <dl class="checkout-pay-fields">
                                        <div class="checkout-pay-field">
                                            <dt>ชื่อบัญชี</dt>
                                            <dd><?= e($bankAccountName) ?></dd>
                                        </div>
                                        <div class="checkout-pay-field checkout-pay-field--account">
                                            <dt>เลขที่บัญชี</dt>
                                            <dd>
                                                <span class="checkout-pay-account-no" id="checkoutBankNumber"><?= e($bankNumberDisplay) ?></span>
                                                <button type="button" class="checkout-bank-copy js-copy-bank" data-copy="<?= e($bankDigits ?: $bankNumber) ?>" aria-label="คัดลอกเลขบัญชี">
                                                    <?= lucide_icon('copy', ['size' => 15]) ?>
                                                    <span class="checkout-bank-copy-label">คัดลอก</span>
                                                </button>
                                            </dd>
                                        </div>
                                    </dl>
                                    <p class="checkout-pay-detail-note">โอนจากแอป <?= e($bankName) ?> หรือธนาคารอื่น แล้วแนบสลิปด้านล่าง</p>
                                </section>

                                <?php if ($promptPay): ?>
                                <section class="checkout-pay-detail-qr" aria-label="สแกน PromptPay">
                                    <div class="checkout-pay-detail-qr-head">
                                        <h3 class="checkout-pay-detail-heading">สแกนเพื่อชำระเงิน</h3>
                                        <img src="<?= e(imageAsset('images/checkout/promptpay-logo.png')) ?>" alt="PromptPay" class="checkout-pay-pp-logo" width="120" height="34" loading="lazy">
                                    </div>
                                    <div class="checkout-pay-qr-stage">
                                        <div class="checkout-pay-qr-frame">
                                            <img src="<?= e($promptPay['qr_url']) ?>" alt="PromptPay QR ยอด <?= e(formatPrice($orderTotal)) ?>" width="168" height="168" class="checkout-pay-qr-img" loading="lazy">
                                        </div>
                                    </div>
                                    <dl class="checkout-pay-fields checkout-pay-fields--inline">
                                        <div class="checkout-pay-field">
                                            <dt><?= ($promptPay['target_type'] ?? 'phone') === 'national_id' ? 'เลขบัตร' : 'เบอร์พร้อมเพย์' ?></dt>
                                            <dd><?= e($promptPay['target_display']) ?></dd>
                                        </div>
                                    </dl>
                                </section>
                                <?php endif; ?>
                            </div>

                            <div class="checkout-pay-slip-block">
                                <h3 class="checkout-pay-detail-heading">แจ้งการโอนเงิน</h3>
                                <ol class="checkout-instructions">
                                    <li>โอนเงินตามยอดรวมในสรุปคำสั่งซื้อ</li>
                                    <li>เก็บหลักฐานการโอน (สลิป) ไว้</li>
                                    <li>กรอกข้อมูลและแนบสลิปด้านล่าง</li>
                                    <li>รอทีมงานตรวจสอบและเปิดสิทธิ์เรียน (ภายใน 24 ชม.)</li>
                                </ol>

                                <?php if (getSetting('payment_note')): ?>
                                <p class="checkout-note"><?= e(getSetting('payment_note')) ?></p>
                                <?php endif; ?>

                                <form action="<?= APP_URL ?>/public/payment.php" method="post" enctype="multipart/form-data" class="checkout-form" id="checkoutForm">
                                    <?= csrfField() ?>
                                    <div class="form-row checkout-form-row">
                                        <div class="form-group">
                                            <label for="student_name">ชื่อ-นามสกุล *</label>
                                            <input type="text" id="student_name" name="student_name" class="form-control" required value="<?= e($checkoutStudent['full_name'] ?? '') ?>">
                                        </div>
                                        <div class="form-group">
                                            <label for="student_phone">เบอร์โทร *</label>
                                            <input type="tel" id="student_phone" name="student_phone" class="form-control" required value="<?= e($checkoutStudent['phone'] ?? '') ?>">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="student_email">อีเมล</label>
                                        <input type="email" id="student_email" name="student_email" class="form-control" placeholder="สำหรับรับการยืนยัน" value="<?= e($checkoutStudent['email'] ?? '') ?>">
                                    </div>

                                    <input type="hidden" name="from_cart" value="1">
                                    <?php if ($preselectCourseId > 0): ?>
                                    <input type="hidden" name="course_id" value="<?= $preselectCourseId ?>">
                                    <?php endif; ?>

                                    <div class="form-row checkout-form-row">
                                        <div class="form-group">
                                            <label>จำนวนเงิน (บาท)</label>
                                            <div class="form-control checkout-readonly"><?= e(formatPrice($orderTotal > 0 ? $orderTotal : null)) ?></div>
                                        </div>
                                        <div class="form-group">
                                            <label for="transfer_date">วันที่โอน</label>
                                            <input type="date" id="transfer_date" name="transfer_date" class="form-control">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="transfer_time">เวลาโอน</label>
                                        <input type="text" id="transfer_time" name="transfer_time" class="form-control" placeholder="เช่น 14:30">
                                    </div>

                                    <div class="form-group">
                                        <label for="slip_image">แนบสลิปการโอนเงิน</label>
                                        <div class="checkout-slip-drop" id="slipDropZone">
                                            <input type="file" id="slip_image" name="slip_image" accept="image/*,.pdf" class="checkout-slip-input">
                                            <div class="checkout-slip-placeholder">
                                                <?= lucide_icon('upload', ['size' => 32, 'stroke' => '1.5']) ?>
                                                <p><strong>คลิกหรือลากไฟล์</strong> มาวางที่นี่</p>
                                                <span>รองรับ JPG, PNG, PDF (ไม่เกิน 5MB)</span>
                                            </div>
                                            <p class="checkout-slip-filename" id="slipFileName" hidden></p>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="note">หมายเหตุเพิ่มเติม</label>
                                        <textarea id="note" name="note" class="form-control" rows="3" placeholder="ระบุรายละเอียดเพิ่มเติม (ถ้ามี)"><?= e($cartNotePrefill) ?></textarea>
                                    </div>

                                    <button type="submit" class="btn btn-primary btn-block checkout-submit">
                                        <?= lucide_icon('lock', ['size' => 20]) ?>
                                        ยืนยันการชำระเงิน
                                    </button>
                                </form>
                            </div>
                        </div>

                        <?php if ($omiseEnabled): ?>
                        <div class="checkout-pay-detail" id="checkoutDetailCard" hidden>
                            <?php if (!$omiseReady): ?>
                            <div class="alert alert-error checkout-alert checkout-omise-alert">
                                โหมดตั้งค่า — เปิดใช้ Omise และใส่ Secret Key ในแอดมินก่อนชำระจริง
                            </div>
                            <?php endif; ?>

                            <form action="<?= APP_URL ?>/public/omise_pay.php" method="post" class="checkout-form checkout-omise-form" id="omiseCheckoutForm">
                                <?= csrfField() ?>
                                <div class="form-row checkout-form-row">
                                    <div class="form-group">
                                        <label>ชื่อ-นามสกุล *</label>
                                        <input type="text" name="student_name" class="form-control" required value="<?= e($checkoutStudent['full_name'] ?? '') ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>เบอร์โทร *</label>
                                        <input type="tel" name="student_phone" class="form-control" required value="<?= e($checkoutStudent['phone'] ?? '') ?>">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>อีเมล</label>
                                    <input type="email" name="student_email" class="form-control" value="<?= e($checkoutStudent['email'] ?? '') ?>">
                                </div>
                                <input type="hidden" name="omise_method" value="card">
                                <div class="form-group">
                                    <label for="omiseCardNumber">หมายเลขบัตร</label>
                                    <div class="checkout-card-input-wrap">
                                        <span class="checkout-card-input-brand" id="omiseCardBrand" hidden aria-hidden="true"></span>
                                        <input type="text" id="omiseCardNumber" class="form-control checkout-card-input" inputmode="numeric" autocomplete="cc-number" placeholder="4111 1111 1111 1111" maxlength="19" spellcheck="false">
                                    </div>
                                </div>
                                <div class="form-row checkout-form-row">
                                    <div class="form-group">
                                        <label>เดือน (MM)</label>
                                        <input type="text" id="omiseCardMonth" class="form-control" maxlength="2" placeholder="12">
                                    </div>
                                    <div class="form-group">
                                        <label>ปี (YYYY)</label>
                                        <input type="text" id="omiseCardYear" class="form-control" maxlength="4" placeholder="2028">
                                    </div>
                                    <div class="form-group">
                                        <label>CVV</label>
                                        <input type="text" id="omiseCardCvv" class="form-control" maxlength="4" autocomplete="cc-csc" placeholder="123">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>ชื่อบนบัตร</label>
                                    <input type="text" id="omiseCardName" class="form-control" autocomplete="cc-name" placeholder="NAME ON CARD">
                                </div>
                                <input type="hidden" name="omise_token" id="omiseToken" value="">
                                <button type="submit" class="btn btn-primary btn-block checkout-submit" id="omiseSubmitBtn"<?= $omiseReady ? '' : ' disabled' ?>>
                                    ชำระเงิน <?= e(formatPrice($orderTotal)) ?>
                                </button>
                            </form>
                        </div>
                        <?php endif; ?>

                        <footer class="checkout-pay-hub-foot">
                            <div class="checkout-pay-hub-foot-item">
                                <?= lucide_icon('shield-check', ['size' => 18]) ?>
                                <span>ข้อมูลการชำระเงินของคุณได้รับการเข้ารหัสและปลอดภัยตามมาตรฐานสากล</span>
                            </div>
                            <div class="checkout-pay-hub-foot-item checkout-pay-hub-foot-item--timer">
                                <?= lucide_icon('clock', ['size' => 18]) ?>
                                <span>กรุณาชำระเงินภายใน 24 ชั่วโมงหลังสร้างรายการ</span>
                            </div>
                        </footer>
                    </div>
                </section>
            </div>

            <aside class="checkout-sidebar">
                <div class="checkout-summary-card">
                    <h2>
                        <?= lucide_icon('book-open', ['size' => 20, 'stroke' => '1.75']) ?>
                        สรุปคำสั่งซื้อ
                    </h2>

                    <?php if ($orderItems): ?>
                    <ul class="checkout-order-list">
                        <?php foreach ($orderItems as $item): ?>
                        <?php $sess = $cartSessionDetails[(int) ($item['id'] ?? 0)] ?? null; ?>
                        <li class="checkout-order-item">
                            <img src="<?= e(courseCoverUrl($item)) ?>" alt="" class="checkout-order-thumb" width="56" height="56" loading="lazy">
                            <div class="checkout-order-meta">
                                <strong><?= e($item['title']) ?></strong>
                                <?php if ($sess): ?>
                                <span style="display:block;font-size:.85rem;color:#667085">📅 <?= e(formatSessionRange($sess)) ?></span>
                                <?php endif; ?>
                                <span><?= e(formatPrice((float) ($item['price'] ?? 0))) ?></span>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php else: ?>
                    <p class="checkout-order-empty">ยังไม่ได้เลือกคอร์ส — <a href="<?= APP_URL ?>/public/courses.php">เลือกคอร์สเรียน</a></p>
                    <?php endif; ?>

                    <?php if ($orderDiscount > 0): ?>
                    <dl class="checkout-total-row checkout-discount-row">
                        <dt>ส่วนลด</dt>
                        <dd>-<?= e(formatPrice($orderDiscount)) ?></dd>
                    </dl>
                    <?php endif; ?>
                    <dl class="checkout-total-row">
                        <dt>ยอดชำระทั้งหมด</dt>
                        <dd id="checkoutTotalDisplay"><?= e(formatPrice($orderTotal > 0 ? $orderTotal : null)) ?></dd>
                    </dl>

                    <p class="checkout-summary-note">
                        <?= lucide_icon('circle-check', ['size' => 16]) ?>
                        หลังชำระเงิน ทีมงานจะเปิดสิทธิ์เรียนและติดต่อกลับทางอีเมลหรือ Line
                    </p>
                </div>

                <div class="checkout-trust-card">
                    <h3>ทำไมต้องชำระกับเดอะลีโอน่า</h3>
                    <ul>
                        <li>
                            <span class="checkout-mini-icon" aria-hidden="true">
                                <?= lucide_icon('shield', ['size' => 18, 'stroke' => '1.75']) ?>
                            </span>
                            <div class="checkout-mini-text">
                                <strong>ปลอดภัย มั่นใจ</strong>
                                <span>ตรวจสอบสลิปและเปิดคอร์สอย่างเป็นระบบ</span>
                            </div>
                        </li>
                        <li>
                            <span class="checkout-mini-icon" aria-hidden="true">
                                <?= lucide_icon('clock', ['size' => 18, 'stroke' => '1.75']) ?>
                            </span>
                            <div class="checkout-mini-text">
                                <strong>รวดเร็ว</strong>
                                <span>ยืนยันภายใน 24 ชั่วโมงทำการ</span>
                            </div>
                        </li>
                        <li>
                            <span class="checkout-mini-icon" aria-hidden="true">
                                <?= lucide_icon('book-open', ['size' => 18, 'stroke' => '1.75']) ?>
                            </span>
                            <div class="checkout-mini-text">
                                <strong>เรียนได้ทันที</strong>
                                <span>เข้าถึงวิดีโอและเอกสารหลังเปิดสิทธิ์</span>
                            </div>
                        </li>
                    </ul>
                </div>

                <div class="checkout-help-card">
                    <h3>ต้องการความช่วยเหลือ?</h3>
                    <?php if (getSetting('phone')): ?>
                    <a href="tel:<?= e(preg_replace('/\D+/', '', getSetting('phone'))) ?>" class="checkout-help-item">
                        <span class="checkout-mini-icon" aria-hidden="true">
                            <?= lucide_icon('phone', ['size' => 18, 'stroke' => '1.75']) ?>
                        </span>
                        <div class="checkout-mini-text">
                            <strong><?= e(getSetting('phone')) ?></strong>
                            <span>จันทร์–ศุกร์ 9:00–18:00 น.</span>
                        </div>
                    </a>
                    <?php endif; ?>
                    <?php if (getSetting('line_id')): ?>
                    <div class="checkout-help-item">
                        <span class="checkout-mini-icon" aria-hidden="true">
                            <?= lucide_icon('message-circle', ['size' => 18, 'stroke' => '1.75']) ?>
                        </span>
                        <div class="checkout-mini-text">
                            <strong>Line <?= e(getSetting('line_id')) ?></strong>
                            <span>ตอบกลับภายใน 1 ชั่วโมง</span>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if (getSetting('facebook_url')): ?>
                    <a href="<?= e(getSetting('facebook_url')) ?>" target="_blank" rel="noopener" class="checkout-help-item">
                        <span class="checkout-mini-icon" aria-hidden="true">
                            <?= lucide_icon('facebook', ['size' => 18, 'stroke' => '1.75']) ?>
                        </span>
                        <div class="checkout-mini-text">
                            <strong>Facebook</strong>
                            <span>ติดต่อทีมงานเดอะลีโอน่า</span>
                        </div>
                    </a>
                    <?php endif; ?>
                </div>
            </aside>
        </div>
        <?php endif; ?>
    </div>
</main>

<?php if (!$paymentSuccess): ?>
<script>
(function () {
    var options = document.querySelectorAll('[data-checkout-method]');
    var detailTransfer = document.getElementById('checkoutDetailTransfer');
    var detailCard = document.getElementById('checkoutDetailCard');
    if (!options.length) return;

    function setMethod(method) {
        options.forEach(function (btn) {
            var active = btn.getAttribute('data-checkout-method') === method;
            btn.classList.toggle('is-selected', active);
            btn.setAttribute('aria-checked', active ? 'true' : 'false');
        });
        if (detailTransfer) detailTransfer.hidden = method !== 'transfer';
        if (detailCard) detailCard.hidden = method !== 'card';
    }

    options.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var method = btn.getAttribute('data-checkout-method');
            if (!method) return;
            if (btn.classList.contains('is-selected')) {
                btn.classList.remove('is-selected');
                btn.setAttribute('aria-checked', 'false');
                if (detailTransfer) detailTransfer.hidden = true;
                if (detailCard) detailCard.hidden = true;
                return;
            }
            setMethod(method);
        });
    });
})();
</script>
<?php endif; ?>
<?php if ($omiseEnabled && !$paymentSuccess): ?>
<script src="https://cdn.omise.co/omise.js"></script>
<script>
(function () {
    var form = document.getElementById('omiseCheckoutForm');
    if (!form || typeof Omise === 'undefined') return;
    Omise.setPublicKey(<?= json_encode($omisePublicKey) ?>);

    var cardBrands = {
        visa: <?= json_encode(imageAsset('images/checkout/cards/visa.svg')) ?>,
        mastercard: <?= json_encode(imageAsset('images/checkout/cards/mastercard.svg')) ?>,
        jcb: <?= json_encode(imageAsset('images/checkout/cards/jcb.svg')) ?>,
        unionpay: <?= json_encode(imageAsset('images/checkout/cards/unionpay.svg')) ?>
    };

    function formatCardNumber(value) {
        var digits = String(value || '').replace(/\D/g, '').slice(0, 16);
        var out = '';
        for (var i = 0; i < digits.length; i += 4) {
            if (out) out += ' ';
            out += digits.slice(i, i + 4);
        }
        return out;
    }

    function detectCardBrand(digits) {
        if (!digits) return '';
        if (/^4/.test(digits)) return 'visa';
        if (/^(5[1-5]|2(2[2-9]|[3-6]\d|7[01]|720))/.test(digits)) return 'mastercard';
        if (/^35/.test(digits)) return 'jcb';
        if (/^62/.test(digits)) return 'unionpay';
        return '';
    }

    function updateCardBrandIcon(digits) {
        var brandEl = document.getElementById('omiseCardBrand');
        var wrap = cardInput ? cardInput.closest('.checkout-card-input-wrap') : null;
        if (!brandEl) return;
        var brand = detectCardBrand(digits);
        if (!brand || !cardBrands[brand]) {
            brandEl.hidden = true;
            brandEl.innerHTML = '';
            if (wrap) wrap.classList.remove('has-brand');
            return;
        }
        brandEl.hidden = false;
        brandEl.innerHTML = '<img src="' + cardBrands[brand] + '" alt="" width="32" height="20">';
        if (wrap) wrap.classList.add('has-brand');
    }

    var cardInput = document.getElementById('omiseCardNumber');
    if (cardInput) {
        cardInput.addEventListener('input', function () {
            var start = cardInput.selectionStart || 0;
            var digitsBefore = cardInput.value.slice(0, start).replace(/\D/g, '').length;
            var digits = cardInput.value.replace(/\D/g, '').slice(0, 16);
            var formatted = formatCardNumber(digits);
            cardInput.value = formatted;
            updateCardBrandIcon(digits);

            var pos = 0;
            var count = 0;
            for (var i = 0; i < formatted.length && count < digitsBefore; i++) {
                if (/\d/.test(formatted.charAt(i))) count++;
                pos = i + 1;
            }
            cardInput.setSelectionRange(pos, pos);
        });
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var btn = document.getElementById('omiseSubmitBtn');
        if (btn) btn.disabled = true;
        Omise.createToken('card', {
            name: document.getElementById('omiseCardName').value,
            number: document.getElementById('omiseCardNumber').value.replace(/\s+/g, ''),
            expiration_month: parseInt(document.getElementById('omiseCardMonth').value, 10),
            expiration_year: parseInt(document.getElementById('omiseCardYear').value, 10),
            security_code: document.getElementById('omiseCardCvv').value
        }, function (status, response) {
            if (response.id) {
                document.getElementById('omiseToken').value = response.id;
                form.submit();
            } else {
                alert((response.message || 'ไม่สามารถชำระด้วยบัตรได้'));
                if (btn) btn.disabled = false;
            }
        });
    });
})();
</script>
<?php endif; ?>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
