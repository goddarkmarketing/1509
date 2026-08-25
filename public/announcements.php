<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/announcement.php';

$pageTitle = 'บอร์ดประชาสัมพันธ์';
require_once dirname(__DIR__) . '/includes/header.php';

$category = trim($_GET['category'] ?? 'all');
$announcements = getAnnouncements(true, $category !== 'all' ? $category : null);

$categories = [
    'all' => 'ทั้งหมด',
    'general' => 'ทั่วไป',
    'promo' => 'โปรโมชัน',
    'course' => 'คอร์ส',
    'event' => 'กิจกรรม',
];
?>

<header class="page-header">
    <div class="container">
        <nav class="breadcrumb" aria-label="breadcrumb">
            <a href="<?= APP_URL ?>/public/index.php">หน้าแรก</a>
            <span aria-hidden="true">/</span>
            <span>บอร์ดประชาสัมพันธ์</span>
        </nav>
        <h1>บอร์ดประชาสัมพันธ์</h1>
        <p>ข่าวสาร โปรโมชัน และประกาศจากกวดวิชาเดอะลีโอน่า</p>
    </div>
</header>

<section class="section announcements-section">
    <div class="container">
        <div class="announcements-toolbar">
            <nav class="announcements-filter" aria-label="กรองหมวดหมู่">
                <?php foreach ($categories as $key => $label): ?>
                <a
                    href="<?= APP_URL ?>/public/announcements.php<?= $key !== 'all' ? '?category=' . urlencode($key) : '' ?>"
                    class="announcements-filter-link<?= $category === $key ? ' is-active' : '' ?>"
                ><?= e($label) ?></a>
                <?php endforeach; ?>
            </nav>
        </div>

        <?php if ($announcements): ?>
        <div class="announcements-grid">
            <?php foreach ($announcements as $announcement): ?>
                <?php include dirname(__DIR__) . '/includes/announcement_card.php'; ?>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="announcements-empty">
            <p>ยังไม่มีประกาศในหมวดนี้</p>
            <a href="<?= APP_URL ?>/public/courses.php" class="btn btn-primary">ดูคอร์สเรียน</a>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
