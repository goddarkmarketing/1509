<?php

declare(strict_types=1);

/** @var array<string, mixed> $admin */
/** @var string $currentPage */

$navGroups = require __DIR__ . '/../nav_config.php';
?>
<aside class="admin-sidebar" id="adminSidebar">
    <a href="<?= APP_URL ?>/admin/dashboard.php" class="admin-brand">
        <span class="admin-brand-text">
            <strong>THE LEONA</strong>
            <small>Admin Panel</small>
        </span>
    </a>
    <nav class="admin-nav" aria-label="เมนูหลังบ้าน">
        <?php foreach ($navGroups as $group): ?>
            <div class="admin-nav-group">
                <span class="admin-nav-label"><?= e($group['label']) ?></span>
                <?php foreach ($group['items'] as $item): ?>
                    <?php adminNavLink(
                        $item['page'],
                        APP_URL . $item['href'],
                        $item['label'],
                        $item['icon'],
                        $currentPage
                    ); ?>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
        <div class="admin-nav-divider"></div>
        <a href="<?= APP_URL ?>/public/index.php" target="_blank" rel="noopener"><?= adminNavIcon('external') ?><span>ดูเว็บไซต์</span></a>
        <a href="<?= APP_URL ?>/admin/logout.php" class="logout"><?= adminNavIcon('logout') ?><span>ออกจากระบบ</span></a>
    </nav>
    <div class="admin-user">
        <small>เข้าสู่ระบบโดย</small>
        <strong><?= e($admin['full_name'] ?? $admin['username'] ?? 'Admin') ?></strong>
    </div>
</aside>
