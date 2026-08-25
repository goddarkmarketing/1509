<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
requireAdmin();

require_once __DIR__ . '/admin/nav_helpers.php';

$admin = currentAdmin();
$pageTitle = $pageTitle ?? 'แดชบอร์ด';
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

require __DIR__ . '/admin/components/layout_start.php';
require __DIR__ . '/admin/components/sidebar.php';
?>
    <div class="admin-main">
        <?php require __DIR__ . '/admin/components/topbar.php'; ?>
        <div class="admin-content">
            <div class="admin-content-inner">
