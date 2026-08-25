<?php

declare(strict_types=1);

/** @var array $announcement */

$detailUrl = APP_URL . '/public/announcement.php?slug=' . urlencode($announcement['slug']);
$imageUrl = announcementImageUrl($announcement['image_url'] ?? null);
$dateLabel = formatAnnouncementDate($announcement['published_at'] ?? $announcement['created_at'] ?? null);
$excerpt = trim($announcement['excerpt'] ?? '');
if ($excerpt === '') {
    $excerpt = mb_strimwidth(strip_tags($announcement['body'] ?? ''), 0, 160, '...');
}
?>
<article class="announcement-card<?= !empty($announcement['is_pinned']) ? ' is-pinned' : '' ?>">
    <a href="<?= e($detailUrl) ?>" class="announcement-card-image-link" tabindex="-1" aria-hidden="true">
        <div class="announcement-card-image">
            <?php if ($imageUrl): ?>
                <img src="<?= e($imageUrl) ?>" alt="" loading="lazy">
            <?php else: ?>
                <div class="announcement-card-placeholder" aria-hidden="true">
                    <?= lucide_icon('image-off', ['size' => 40, 'stroke' => '1.5']) ?>
                </div>
            <?php endif; ?>
            <div class="announcement-card-badges">
                <?php if (!empty($announcement['is_pinned'])): ?>
                    <span class="badge badge-gold">ปักหมุด</span>
                <?php endif; ?>
                <span class="badge badge-red"><?= e(announcementCategoryLabel($announcement['category'] ?? 'general')) ?></span>
                <?php if (announcementHasAttachment($announcement)): ?>
                    <span class="badge badge-outline">PDF</span>
                <?php endif; ?>
            </div>
        </div>
    </a>
    <div class="announcement-card-body">
        <?php if ($dateLabel !== ''): ?>
            <time class="announcement-card-date" datetime="<?= e($announcement['published_at'] ?? $announcement['created_at'] ?? '') ?>"><?= e($dateLabel) ?></time>
        <?php endif; ?>
        <a href="<?= e($detailUrl) ?>" class="announcement-card-content-link">
            <h3 class="announcement-card-title"><?= e($announcement['title']) ?></h3>
            <p class="announcement-card-excerpt"><?= e($excerpt) ?></p>
            <span class="announcement-card-more">อ่านต่อ<?= lucide_text_link_suffix(14) ?></span>
        </a>
    </div>
</article>
