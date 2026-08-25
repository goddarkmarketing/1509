<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/functions.php';

$pageTitle = 'นโยบายความเป็นส่วนตัว';
require_once dirname(__DIR__) . '/includes/header.php';

$custom = trim(getSetting('privacy_policy_html', ''));
$siteTitle = getSetting('site_title', 'กวดวิชาเดอะลีโอน่า');
$phone = getSetting('phone', '');
$lineId = getSetting('line_id', '');

$legalBreadcrumbLabel = 'นโยบายความเป็นส่วนตัว';
$legalTitle = 'นโยบายความเป็นส่วนตัว (PDPA)';
$legalLead = 'เราให้ความสำคัญกับการคุ้มครองข้อมูลส่วนบุคคลของท่านตาม พ.ร.บ. คุ้มครองข้อมูลส่วนบุคคล';
$legalActiveTab = 'privacy';
$legalUpdated = date('d/m/Y');

if ($custom !== '') {
    $legalContent = $custom;
} else {
    ob_start();
    ?>
    <p class="legal-intro"><?= e($siteTitle) ?> ("เรา") ให้ความสำคัญกับการคุ้มครองข้อมูลส่วนบุคคลของท่าน ตามพระราชบัญญัติคุ้มครองข้อมูลส่วนบุคคล พ.ศ. 2562 (PDPA)</p>

    <h2>ข้อมูลที่เราเก็บรวบรวม</h2>
    <ul>
        <li>ชื่อ-นามสกุล เบอร์โทร อีเมล Line ID</li>
        <li>ข้อมูลการชำระเงินและหลักฐานการโอนเงิน</li>
        <li>ความคืบหน้าการเรียน ผลแบบทดสอบ และใบประกาศนียบัตร</li>
    </ul>

    <h2>วัตถุประสงค์ในการใช้ข้อมูล</h2>
    <ul>
        <li>เปิดสิทธิ์เรียนและให้บริการคอร์สออนไลน์</li>
        <li>ติดต่อสื่อสารเกี่ยวกับการสมัครเรียนและการชำระเงิน</li>
        <li>ปรับปรุงคุณภาพการสอนและบริการ</li>
    </ul>

    <h2>การเปิดเผยข้อมูล</h2>
    <p>เราจะไม่ขายหรือเผยแพร่ข้อมูลส่วนบุคคลของท่านแก่บุคคลภายนอก เว้นแต่ได้รับความยินยอมหรือกฎหมายกำหนด</p>

    <h2>สิทธิของเจ้าของข้อมูล</h2>
    <p>ท่านมีสิทธิขอเข้าถึง แก้ไข ลบ หรือถอนความยินยอม โดยติดต่อ<?php if ($phone !== ''): ?> <?= e($phone) ?><?php endif; ?><?php if ($phone !== '' && $lineId !== ''): ?> หรือ<?php endif; ?><?php if ($lineId !== ''): ?> Line: <?= e($lineId) ?><?php endif; ?></p>

    <h2>การรักษาความปลอดภัย</h2>
    <p>เราใช้มาตรการทางเทคนิคที่เหมาะสม เช่น การเข้ารหัสรหัสผ่าน และการจำกัดสิทธิ์เข้าถึงข้อมูล</p>
    <?php
    $legalContent = ob_get_clean();
}

require dirname(__DIR__) . '/includes/views/legal_page.php';
require_once dirname(__DIR__) . '/includes/footer.php';
