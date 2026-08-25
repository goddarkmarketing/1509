<?php
declare(strict_types=1);

/** @var array $games */
if (empty($games)) {
    return;
}
$variant = $variant ?? 'sidebar';
?>
<div class="course-games-block course-games-block--<?= e($variant) ?>">
    <h4 class="course-games-title">เกมฝึกฝน</h4>
    <ul class="course-games-list">
        <?php foreach ($games as $game): ?>
        <li>
            <a href="<?= e(gamePlayUrl((int) $game['id'])) ?>" class="course-games-link">
                <span class="course-games-link-title"><?= e($game['title']) ?></span>
                <?php if (!empty($game['description']) && $variant === 'course'): ?>
                <span class="course-games-link-desc"><?= e(mb_strimwidth($game['description'], 0, 80, '...')) ?></span>
                <?php endif; ?>
                <span class="course-games-link-cta"><?= $variant === 'sidebar' ? 'เล่นเกม' : ('เล่น' . lucide_text_link_suffix(14)) ?></span>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>
</div>
