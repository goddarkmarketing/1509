<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/functions.php';

$pageTitle = 'ข้อกำหนดการใช้งาน';
require_once dirname(__DIR__) . '/includes/header.php';

$custom = trim(getSetting('terms_html', ''));
$siteTitle = getSetting('site_title', 'กวดวิชาเดอะลีโอน่า');
$phone = getSetting('phone', '');
$lineId = getSetting('line_id', '');

$legalBreadcrumbLabel = 'ข้อกำหนดการใช้งาน';
$legalTitle = 'ข้อกำหนดและเงื่อนไขการใช้งาน';
$legalLead = 'อ่านเงื่อนไขการใช้บริการเว็บไซต์และคอร์สเรียนออนไลน์ของ ' . $siteTitle;
$legalActiveTab = 'terms';
$legalUpdated = date('d/m/Y');

if ($custom !== '') {
    $legalContent = $custom;
} else {
    ob_start();
    ?>
    <p class="legal-intro">การใช้งานเว็บไซต์และบริการของ <?= e($siteTitle) ?> ถือว่าท่านยอมรับข้อกำหนดดังต่อไปนี้</p>

    <h2>การสมัครและบัญชีผู้ใช้</h2>
    <ul>
        <li>ผู้เรียนต้องให้ข้อมูลที่ถูกต้องและรักษาความลับของรหัสผ่าน</li>
        <li>ห้ามแชร์บัญชีหรือสิทธิ์เรียนให้ผู้อื่น</li>
    </ul>

    <h2>การชำระเงินและการคืนเงิน</h2>
    <ul>
        <li>คอร์สจะเปิดสิทธิ์เรียนหลังยืนยันการชำระเงินโดยทีมงาน</li>
        <li>นโยบายการคืนเงินเป็นไปตามที่ทีมงานแจ้งก่อนชำระเงิน</li>
    </ul>

    <h2>ทรัพย์สินทางปัญญา</h2>
    <p>เนื้อหาคอร์ส วิดีโอ เอกสาร และสื่อการเรียนทั้งหมดเป็นทรัพย์สินของผู้ให้บริการ ห้ามคัดลอก แจกจ่าย หรือเผยแพร่โดยไม่ได้รับอนุญาต</p>

    <h2>การระงับบริการ</h2>
    <p>เราขอสงวนสิทธิ์ระงับบัญชีที่ละเมิดข้อกำหนด หรือใช้บริการในทางที่ผิดกฎหมาย</p>

    <h2>ติดต่อเรา</h2>
    <p>หากมีข้อสงสัยเกี่ยวกับข้อกำหนด สามารถติดต่อได้ที่<?php if ($phone !== ''): ?> โทร <?= e($phone) ?><?php endif; ?><?php if ($phone !== '' && $lineId !== ''): ?> หรือ<?php endif; ?><?php if ($lineId !== ''): ?> Line: <?= e($lineId) ?><?php endif; ?></p>
    <?php
    $legalContent = ob_get_clean();
}

require dirname(__DIR__) . '/includes/views/legal_page.php';
require_once dirname(__DIR__) . '/includes/footer.php';
