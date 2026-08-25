<?php

declare(strict_types=1);

/** @var array $channel */

$classes = 'contact-channel-card contact-channel-card--row contact-channel-card--' . ($channel['tone'] ?? 'default');

?>
<a
    href="<?= e($channel['href']) ?>"
    class="<?= e($classes) ?>"
    <?= !empty($channel['external']) ? ' target="_blank" rel="noopener"' : '' ?>
>
    <span class="contact-channel-icon" aria-hidden="true">
        <?= contactChannelIcon((string) ($channel['tone'] ?? 'email'), 24) ?>
    </span>
    <span class="contact-channel-body">
        <span class="contact-channel-label"><?= e($channel['label']) ?></span>
        <span class="contact-channel-title"><?= e($channel['title']) ?></span>
        <span class="contact-channel-value"><?= e($channel['value']) ?></span>
        <span class="contact-channel-hint"><?= e($channel['hint']) ?></span>
    </span>
    <span class="contact-channel-cta"><?= e($channel['cta']) ?></span>
</a>
