<?php

declare(strict_types=1);

$legalBreadcrumbLabel = $legalBreadcrumbLabel ?? '';
$legalTitle = $legalTitle ?? '';
$legalLead = $legalLead ?? '';
$legalActiveTab = $legalActiveTab ?? '';
$legalUpdated = $legalUpdated ?? date('d/m/Y');
$legalContent = $legalContent ?? '';

$termsUrl = APP_URL . '/public/terms.php';
$privacyUrl = APP_URL . '/public/privacy.php';
$contactUrl = APP_URL . '/public/contact.php';
$homeUrl = APP_URL . '/public/index.php';
?>

<header class="page-header legal-page-header">
    <div class="container">
        <nav class="breadcrumb" aria-label="breadcrumb">
            <a href="<?= e($homeUrl) ?>">หน้าแรก</a>
            <span aria-hidden="true">/</span>
            <span><?= e($legalBreadcrumbLabel) ?></span>
        </nav>
        <h1><?= e($legalTitle) ?></h1>
        <?php if ($legalLead !== ''): ?>
            <p><?= e($legalLead) ?></p>
        <?php endif; ?>
    </div>
</header>

<section class="legal-page-section">
    <div class="container">
        <div class="legal-page-toolbar">
            <p class="legal-page-meta">
                <span class="legal-page-meta-icon" aria-hidden="true">
                    <?= lucide_icon('calendar', ['size' => 16]) ?>
                </span>
                อัปเดตล่าสุด: <?= e($legalUpdated) ?>
            </p>
            <nav class="legal-page-tabs" aria-label="เอกสารทางกฎหมาย">
                <a
                    href="<?= e($termsUrl) ?>"
                    class="legal-page-tab<?= $legalActiveTab === 'terms' ? ' is-active' : '' ?>"
                    <?= $legalActiveTab === 'terms' ? 'aria-current="page"' : '' ?>
                >ข้อกำหนดการใช้งาน</a>
                <a
                    href="<?= e($privacyUrl) ?>"
                    class="legal-page-tab<?= $legalActiveTab === 'privacy' ? ' is-active' : '' ?>"
                    <?= $legalActiveTab === 'privacy' ? 'aria-current="page"' : '' ?>
                >นโยบายความเป็นส่วนตัว</a>
            </nav>
        </div>

        <article class="legal-page-card">
            <div class="legal-content prose-legal">
                <?= $legalContent ?>
            </div>
        </article>

        <div class="legal-page-actions">
            <a href="<?= e($homeUrl) ?>" class="btn btn-secondary">กลับหน้าแรก</a>
            <a href="<?= e($contactUrl) ?>" class="btn btn-primary">ติดต่อเรา</a>
        </div>
    </div>
</section>
