<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/homepage.php';
require_once dirname(__DIR__) . '/includes/site_content.php';

$pageTitle = 'คำถามที่พบบ่อย';
require_once dirname(__DIR__) . '/includes/header.php';

$faqItems = getFaqPageItems();
$faqPageContent = getFaqPageContent();
$lineUrl = lineContactUrl();
?>

<header class="page-header">
    <div class="container">
        <nav class="breadcrumb" aria-label="breadcrumb">
            <a href="<?= APP_URL ?>/public/index.php">หน้าแรก</a>
            <span aria-hidden="true">/</span>
            <span>คำถามที่พบบ่อย</span>
        </nav>
        <h1><?= e($faqPageContent['page_title'] ?? '') ?></h1>
        <p><?= e($faqPageContent['page_subtitle'] ?? '') ?></p>
    </div>
</header>

<section class="section faq-page-section">
    <div class="container">
        <?php include dirname(__DIR__) . '/includes/faq_panel.php'; ?>
    </div>
</section>

<section class="faq-page-cta">
    <div class="container faq-page-cta-inner">
        <div>
            <h2><?= e($faqPageContent['cta_title'] ?? '') ?></h2>
            <p><?= e($faqPageContent['cta_text'] ?? '') ?></p>
        </div>
        <div class="faq-page-cta-actions">
            <a href="<?= e($lineUrl) ?>" class="btn btn-gold btn-lg" target="_blank" rel="noopener">แอด Line</a>
            <?php if (trim(getSetting('phone', '')) !== ''): ?>
            <a href="tel:<?= e(preg_replace('/\s+/', '', getSetting('phone', ''))) ?>" class="btn btn-hero-outline btn-lg">โทร <?= e(getSetting('phone')) ?></a>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
