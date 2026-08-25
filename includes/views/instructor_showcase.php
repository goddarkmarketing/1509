<?php
declare(strict_types=1);
/** @var array $instructor */
/** @var string $photoUrl */
/** @var bool $showFullLink */
/** @var bool $showStats */
$showFullLink = $showFullLink ?? false;
$showStats = $showStats ?? true;
$credentialIcons = ['degree', 'time', 'users', 'award'];
?>
<article class="instructor-profile-panel">
    <div class="instructor-profile-panel-body">
        <div class="instructor-showcase-photo">
            <div class="instructor-photo-frame">
                <img src="<?= e($photoUrl) ?>" alt="<?= e($instructor['name']) ?>" loading="lazy">
            </div>
        </div>
        <div class="instructor-showcase-content">
            <div class="instructor-showcase-head">
                <span class="instructor-role-badge"><?= e($instructor['role']) ?></span>
                <h3 class="instructor-showcase-name"><?= e($instructor['name']) ?></h3>
                <p class="instructor-showcase-tagline"><?= e($instructor['tagline']) ?></p>
            </div>
            <ul class="instructor-credentials">
                <?php foreach ($instructor['credentials'] as $index => $credential): ?>
                <?php $iconKey = $credentialIcons[$index % count($credentialIcons)]; ?>
                <li>
                    <span class="instructor-credential-icon" aria-hidden="true">
                        <?= instructorCredentialIconSvg()[$iconKey] ?? instructorCredentialIconSvg()['degree'] ?>
                    </span>
                    <span class="instructor-credential-text"><?= e($credential) ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php if ($showFullLink): ?>
            <div class="instructor-showcase-actions">
                <a href="<?= APP_URL ?>/public/instructor.php" class="btn btn-primary btn-sm">ดูโปรไฟล์ผู้สอน</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php if ($showStats): ?>
    <div class="instructor-profile-panel-stats">
        <?php foreach ($instructor['stats'] as $stat): ?>
        <article class="instructor-stat-card" data-stat="<?= e($stat['icon']) ?>">
            <div class="instructor-stat-icon-wrap" aria-hidden="true">
                <span class="instructor-stat-icon"><?= instructorStatIconSvg($stat['icon']) ?></span>
            </div>
            <div class="instructor-stat-body">
                <p class="instructor-stat-value"><?= e($stat['value']) ?></p>
                <p class="instructor-stat-label"><?= e($stat['label']) ?></p>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</article>
