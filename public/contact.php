<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/homepage.php';
require_once dirname(__DIR__) . '/includes/site_content.php';

$pageTitle = 'ติดต่อเรา';
require_once dirname(__DIR__) . '/includes/header.php';

$contactContent = getContactContent();
$lineUrl = lineContactUrl();
$lineId = trim(getSetting('line_id', ''));
$phone = trim(getSetting('phone', ''));
$email = publicContactEmail();
$facebook = trim(getSetting('facebook_url', ''));
$youtube = trim(getSetting('youtube_url', ''));
$tiktok = trim(getSetting('tiktok_url', ''));
$tagline = getSetting('site_tagline', 'สถาบันสอนภาษาจีนออนไลน์ คุณภาพ เรียนง่าย ได้ผลจริง');
$checkoutUrl = $checkoutNavUrl ?? APP_URL . '/public/cart.php';

$contactChannels = [];

if ($lineId !== '') {
    $contactChannels[] = [
        'featured' => true,
        'tone' => 'line',
        'label' => 'LINE Official',
        'title' => 'แชทกับทีมงานทันที',
        'value' => $lineId,
        'hint' => 'ตอบเร็วที่สุด — สอบถามคอร์ส ราคา และการสมัครเรียน',
        'href' => $lineUrl,
        'external' => true,
        'cta' => 'แอด Line',
    ];
}

if ($phone !== '') {
    $contactChannels[] = [
        'tone' => 'phone',
        'label' => 'โทรศัพท์',
        'title' => 'โทรหาทีมงาน',
        'value' => $phone,
        'hint' => $contactContent['phone_hours'] ?? 'จันทร์–อาทิตย์ 10:00–20:00 น.',
        'href' => 'tel:' . preg_replace('/\s+/', '', $phone),
        'external' => false,
        'cta' => 'โทรเลย',
    ];
}

if ($email !== '') {
    $contactChannels[] = [
        'tone' => 'email',
        'label' => 'อีเมล',
        'title' => 'ส่งอีเมลถึงเรา',
        'value' => $email,
        'hint' => 'เหมาะสำหรับสอบถามรายละเอียดหรือเอกสารแนบ',
        'href' => 'mailto:' . $email,
        'external' => false,
        'cta' => 'ส่งอีเมล',
    ];
}

if ($facebook !== '') {
    $contactChannels[] = [
        'tone' => 'facebook',
        'label' => 'Facebook',
        'title' => 'ติดตามข่าวสาร',
        'value' => $contactContent['facebook_label'] ?? 'กวดวิชาเดอะลีโอน่า',
        'hint' => 'อัปเดตคอร์ส กิจกรรม และเทคนิคการเรียน',
        'href' => $facebook,
        'external' => true,
        'cta' => 'ไปที่ Facebook',
    ];
}

if ($youtube !== '') {
    $contactChannels[] = [
        'tone' => 'youtube',
        'label' => 'YouTube',
        'title' => 'ชมคลิปสอน',
        'value' => 'YouTube Channel',
        'hint' => 'ตัวอย่างบทเรียนและเทคนิคภาษาจีน',
        'href' => $youtube,
        'external' => true,
        'cta' => 'ดูช่อง YouTube',
    ];
}

if ($tiktok !== '') {
    $contactChannels[] = [
        'tone' => 'tiktok',
        'label' => 'TikTok',
        'title' => 'คลิปสั้นน่ารู้',
        'value' => 'TikTok',
        'hint' => 'เรียนรู้ภาษาจีนแบบสนุกในคลิปสั้น',
        'href' => $tiktok,
        'external' => true,
        'cta' => 'ดู TikTok',
    ];
}

?>

<header class="page-header contact-page-header">
    <div class="container">
        <nav class="breadcrumb" aria-label="breadcrumb">
            <a href="<?= APP_URL ?>/public/index.php">หน้าแรก</a>
            <span aria-hidden="true">/</span>
            <span>ติดต่อเรา</span>
        </nav>
        <h1><?= e($contactContent['header_title'] ?? 'ติดต่อเรา') ?></h1>
        <p><?= e($contactContent['header_subtitle'] ?? '') ?></p>
    </div>
</header>

<section class="section contact-page-section" id="contact">
    <div class="container contact-page-layout">
        <div class="contact-page-intro-card">
            <p class="contact-page-eyebrow"><?= e($contactContent['intro_eyebrow'] ?? '') ?></p>
            <h2><?= e($contactContent['intro_title'] ?? '') ?></h2>
            <p class="contact-page-lead"><?= e($tagline) ?> <?= e($contactContent['intro_lead_suffix'] ?? '') ?></p>

            <ul class="contact-page-perks">
                <?php foreach (($contactContent['perks'] ?? []) as $perk): ?>
                <li>
                    <span class="contact-page-perk-icon" aria-hidden="true">
                        <?= lucide_icon('circle-check', ['size' => 20]) ?>
                    </span>
                    <span><?= e($perk) ?></span>
                </li>
                <?php endforeach; ?>
            </ul>

            <div class="contact-page-quick-links">
                <a href="<?= APP_URL ?>/public/faq.php" class="contact-page-quick-link">
                    <strong>คำถามที่พบบ่อย</strong>
                    <span>ดูคำตอบก่อนติดต่อ</span>
                </a>
                <a href="<?= e($checkoutUrl) ?>" class="contact-page-quick-link">
                    <strong>แจ้งชำระเงิน</strong>
                    <span>ส่งสลิปหลังโอนเงิน</span>
                </a>
            </div>
        </div>

        <?php if ($contactChannels): ?>
        <div class="contact-page-channels-panel">
            <div class="contact-line-qr">
                <img src="<?= e(asset('images/leona/line-qr.png')) ?>" alt="คิวอาร์โค้ด LINE กวดวิชาเดอะลีโอน่า" width="220" height="220">
                <div>
                    <p class="contact-line-qr-label">LINE</p>
                    <h3>เพิ่มเพื่อนผ่านคิวอาร์โค้ด</h3>
                    <p>สแกนด้วยแอป LINE เพื่อสอบถามคอร์ส การสมัครเรียน และการชำระเงิน</p>
                    <?php if ($lineId !== '' && $lineUrl !== ''): ?>
                    <a class="btn btn-primary btn-sm" href="<?= e($lineUrl) ?>" target="_blank" rel="noopener">เปิด LINE</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php foreach ($contactChannels as $channel): ?>
            <?php include dirname(__DIR__) . '/includes/views/contact_channel_card.php'; ?>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="contact-page-empty contact-box">
            <h2>ยังไม่ได้ตั้งค่าช่องทางติดต่อ</h2>
            <p>กรุณาติดต่อผู้ดูแลระบบ หรือดูคำถามที่พบบ่อยก่อน</p>
            <a href="<?= APP_URL ?>/public/faq.php" class="btn btn-primary">ไปหน้า FAQ</a>
        </div>
        <?php endif; ?>
    </div>
</section>

<section class="contact-page-bottom">
    <div class="container contact-page-bottom-inner">
        <div>
            <h2><?= e($contactContent['bottom_title'] ?? '') ?></h2>
            <p><?= e($contactContent['bottom_text'] ?? '') ?></p>
        </div>
        <div class="contact-page-bottom-actions">
            <a href="<?= APP_URL ?>/public/courses.php" class="btn btn-gold btn-lg">ดูคอร์สทั้งหมด</a>
            <a href="<?= APP_URL ?>/public/register.php" class="btn btn-hero-outline btn-lg">วิธีสมัครเรียน</a>
        </div>
    </div>
</section>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
