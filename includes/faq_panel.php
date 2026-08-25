<?php
declare(strict_types=1);
/** @var array $faqItems */
/** @var string $lineUrl */
require_once __DIR__ . '/site_content.php';
$faqPageContent = getFaqPageContent();
$faqCol1 = array_slice($faqItems, 0, (int) ceil(count($faqItems) / 2));
$faqCol2 = array_slice($faqItems, count($faqCol1));
$faqMobileLimit = 6;
$faqIndex = 0;
?>
<div class="faq-panel-layout">
    <div class="faq-columns">
        <div class="faq-col">
            <?php foreach ($faqCol1 as $faq): ?>
            <?php $faqIndex++; ?>
            <details class="faq-item<?= $faqIndex > $faqMobileLimit ? ' faq-item--mobile-hidden' : '' ?>">
                <summary><?= e($faq['q']) ?></summary>
                <p><?= e($faq['a']) ?></p>
            </details>
            <?php endforeach; ?>
        </div>
        <div class="faq-col">
            <?php foreach ($faqCol2 as $faq): ?>
            <?php $faqIndex++; ?>
            <details class="faq-item<?= $faqIndex > $faqMobileLimit ? ' faq-item--mobile-hidden' : '' ?>">
                <summary><?= e($faq['q']) ?></summary>
                <p><?= e($faq['a']) ?></p>
            </details>
            <?php endforeach; ?>
        </div>
        <?php if (count($faqItems) > $faqMobileLimit): ?>
        <p class="faq-mobile-more-wrap">
            <a href="<?= APP_URL ?>/public/faq.php" class="btn btn-outline btn-block faq-mobile-more">อ่านเพิ่มเติม</a>
        </p>
        <?php endif; ?>
    </div>
    <aside class="faq-promo-card">
        <p class="faq-promo-eyebrow"><?= e($faqPageContent['promo_eyebrow'] ?? '') ?></p>
        <h3><?= e($faqPageContent['promo_title'] ?? '') ?></h3>
        <p><?= e($faqPageContent['promo_text'] ?? '') ?></p>
        <a href="<?= e($lineUrl) ?>" class="btn btn-primary btn-block" target="_blank" rel="noopener">สอบถาม Line</a>
        <a href="<?= APP_URL ?>/public/register.php" class="btn btn-outline btn-block">สมัครเรียนเลย</a>
        <a href="<?= APP_URL ?>/public/contact.php" class="btn btn-outline btn-block">ดูช่องทางติดต่อ</a>
    </aside>
</div>
