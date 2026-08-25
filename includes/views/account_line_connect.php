<?php
declare(strict_types=1);
/** @var array $student */
require_once dirname(__DIR__) . '/line_messaging.php';

$lineOaOn = isLineOaEnabled();
$lineLinked = studentHasLineLinked((int) ($student['id'] ?? 0));
$addFriendUrl = lineOaAddFriendUrl();
$lineBasicId = lineOaBasicId();
$studentPhone = normalizePhoneDigits((string) ($student['phone'] ?? ''));
?>
<div class="account-line-panel">
    <div class="account-line-panel-head">
        <?= brand_icon('line', ['size' => 22, 'class' => 'account-line-icon']) ?>
        <div>
            <strong>รับการแจ้งเตือนผ่าน LINE</strong>
            <p>ยืนยันการจองคลาส ลิงก์ Zoom และแจ้งเตือนก่อนเริ่มเรียน</p>
        </div>
    </div>

    <?php if (!$lineOaOn): ?>
    <p class="account-line-note">ยังไม่เปิดบริการแจ้งเตือนผ่าน LINE — หากต้องการรับการแจ้งเตือน กรุณาติดต่อทีมงาน</p>
    <?php elseif ($lineLinked): ?>
    <div class="account-line-status account-line-status--ok">
        <?= lucide_icon('circle-check', ['size' => 18]) ?>
        <span>เชื่อม LINE สำเร็จแล้ว ระบบจะแจ้งการจองคลาสและลิงก์ Zoom ให้ทางนี้</span>
    </div>
    <?php else: ?>
    <p class="account-line-intro">เชื่อมบัญชีเพื่อรับแจ้งเตือนอัตโนมัติ — ใช้เวลาไม่เกิน 1 นาที</p>
    <ol class="account-line-steps">
        <li>
            <?php if ($addFriendUrl): ?>
            <a href="<?= e($addFriendUrl) ?>" target="_blank" rel="noopener" class="btn btn-line btn-sm">เพิ่มเพื่อนใน LINE</a>
            <?php if ($lineBasicId !== ''): ?>
            <span class="account-line-id">@<?= e($lineBasicId) ?></span>
            <?php endif; ?>
            <?php else: ?>
            ค้นหา Official Account ของกวดวิชาเดอะลีโอน่า ใน LINE แล้วกดเพิ่มเพื่อน
            <?php endif; ?>
        </li>
        <li>เปิดแชท แล้วส่ง<strong>เบอร์โทรที่ใช้สมัครเรียน</strong><?php if ($studentPhone !== ''): ?> (<?= e($studentPhone) ?>)<?php endif; ?></li>
        <li>เมื่อเชื่อมสำเร็จ ระบบจะตอบกลับว่า「เชื่อมบัญชีเรียบร้อยแล้ว」</li>
    </ol>
    <?php endif; ?>
</div>
