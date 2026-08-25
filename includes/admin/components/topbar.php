<?php

declare(strict_types=1);

/** @var string $pageTitle */
/** @var array<string, mixed> $admin */
?>
<header class="admin-topbar">
    <div class="admin-topbar-inner">
        <button type="button" class="admin-menu-toggle" id="adminMenuToggle" aria-label="เปิดเมนู" aria-controls="adminSidebar" aria-expanded="false">
            <?= lucide_icon('menu', ['size' => 20]) ?>
        </button>
        <h1><?= e($pageTitle) ?></h1>
        <div class="admin-topbar-meta">
            <span class="admin-topbar-pill"><?= e($admin['username'] ?? 'admin') ?></span>
        </div>
    </div>
</header>
