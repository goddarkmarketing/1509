<?php
declare(strict_types=1);
/** @var array $instructor */
/** @var string $photoUrl */
?>
<a href="<?= APP_URL ?>/public/instructor.php" class="instructor-chip<?= !empty($chipModifier) ? ' ' . e($chipModifier) : '' ?>" title="ดูโปรไฟล์ผู้สอน">
    <img
        class="instructor-chip-avatar"
        src="<?= e($photoUrl) ?>"
        alt="<?= e($instructor['name']) ?>"
        width="36"
        height="36"
        loading="lazy"
    >
    <span class="instructor-chip-text">
        <span class="instructor-chip-label">ผู้สอน</span>
        <span class="instructor-chip-name"><?= e($instructor['name']) ?></span>
    </span>
</a>
