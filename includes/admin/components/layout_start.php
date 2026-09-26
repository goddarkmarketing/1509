<?php

declare(strict_types=1);

/** @var string $pageTitle */
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | Admin</title>
    <link rel="icon" href="<?= asset('images/favicon.png') ?>" type="image/png" sizes="32x32">
    <link rel="apple-touch-icon" href="<?= asset('images/leona/logo.png') ?>">
    <?php require dirname(__DIR__, 2) . '/views/fonts_head.php'; ?>
    <link rel="stylesheet" href="<?= adminAsset('css/admin.css') ?>">
</head>
<body class="admin-body">
<div class="admin-overlay" id="adminOverlay" aria-hidden="true"></div>
<div class="admin-layout">
