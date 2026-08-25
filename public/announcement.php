<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/announcement.php';

$slug = trim($_GET['slug'] ?? '');
if ($slug === '') {
    redirect('/public/announcements.php');
}

$announcement = getAnnouncementBySlug($slug);
if (!$announcement) {
    $pageTitle = 'ไม่พบประกาศ';
    require_once dirname(__DIR__) . '/includes/header.php';
    echo '<section class="section"><div class="container announcements-empty"><p>ไม่พบประกาศที่ต้องการ</p><a href="' . APP_URL . '/public/announcements.php" class="btn btn-primary">กลับบอร์ดประชาสัมพันธ์</a></div></section>';
    require_once dirname(__DIR__) . '/includes/footer.php';
    exit;
}

$pageTitle = $announcement['title'];
$imageUrl = announcementImageUrl($announcement['image_url'] ?? null);
$dateLabel = formatAnnouncementDate($announcement['published_at'] ?? $announcement['created_at'] ?? null);

require_once dirname(__DIR__) . '/includes/header.php';
?>

<header class="page-header announcement-detail-header">
    <div class="container">
        <nav class="breadcrumb" aria-label="breadcrumb">
            <a href="<?= APP_URL ?>/public/index.php">หน้าแรก</a>
            <span aria-hidden="true">/</span>
            <a href="<?= APP_URL ?>/public/announcements.php">บอร์ดประชาสัมพันธ์</a>
            <span aria-hidden="true">/</span>
            <span><?= e(mb_strimwidth($announcement['title'], 0, 40, '...')) ?></span>
        </nav>
        <div class="announcement-detail-meta">
            <span class="badge badge-red"><?= e(announcementCategoryLabel($announcement['category'] ?? 'general')) ?></span>
            <?php if (!empty($announcement['is_pinned'])): ?>
                <span class="badge badge-gold">ปักหมุด</span>
            <?php endif; ?>
            <?php if ($dateLabel !== ''): ?>
                <time datetime="<?= e($announcement['published_at'] ?? $announcement['created_at'] ?? '') ?>"><?= e($dateLabel) ?></time>
            <?php endif; ?>
        </div>
        <h1><?= e($announcement['title']) ?></h1>
        <?php if (!empty($announcement['excerpt'])): ?>
            <p><?= e($announcement['excerpt']) ?></p>
        <?php endif; ?>
    </div>
</header>

<section class="announcement-detail-section">
    <div class="container">
        <article class="announcement-detail-card">
            <?php if ($imageUrl): ?>
            <div class="announcement-detail-image">
                <img src="<?= e($imageUrl) ?>" alt="<?= e($announcement['title']) ?>">
            </div>
            <?php endif; ?>
            <div class="announcement-detail-body">
                <?= nl2br(e($announcement['body'])) ?>
            </div>
            <?php if (announcementHasAttachment($announcement)): ?>
            <div class="announcement-detail-attachment">
                <div class="announcement-attachment-icon" aria-hidden="true">
                    <?= lucide_icon('file-down', ['size' => 28, 'stroke' => '1.5']) ?>
                </div>
                <div class="announcement-attachment-info">
                    <strong>ไฟล์แนบ</strong>
                    <span><?= e(announcementAttachmentLabel($announcement)) ?></span>
                </div>
                <a href="<?= e(announcementAttachmentDownloadUrl($announcement)) ?>" class="btn btn-primary btn-sm" target="_blank" rel="noopener">เปิด PDF</a>
            </div>
            <?php endif; ?>
        </article>
        <div class="announcement-detail-actions">
            <a href="<?= APP_URL ?>/public/announcements.php" class="btn btn-secondary">← กลับบอร์ดประชาสัมพันธ์</a>
            <a href="<?= APP_URL ?>/public/contact.php" class="btn btn-primary">ติดต่อเรา</a>
        </div>
    </div>
</section>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
