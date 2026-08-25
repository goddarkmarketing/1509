<?php
declare(strict_types=1);
$pageTitle = 'คอร์สเรียนทั้งหมด';
require_once dirname(__DIR__) . '/includes/header.php';

$courses = [];
$cartSuccess = flash('cart_success');
$searchQuery = trim($_GET['q'] ?? '');
$typeFilter = trim($_GET['type'] ?? '');
try {
    $courseType = in_array($typeFilter, ['live', 'hybrid', 'recorded'], true) ? $typeFilter : null;
    $courses = getCourses(null, true, $searchQuery !== '' ? $searchQuery : null, $courseType);
} catch (Throwable $e) {
    $courses = [];
}
?>

<section class="page-header">
    <div class="container">
        <h1>คอร์สเรียนทั้งหมด</h1>
        <p>เลือกคอร์สภาษาจีนที่เหมาะกับคุณ</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <form class="filter-tools" method="get" action="" role="search" aria-label="ค้นหาและกรองคอร์ส">
            <div class="filter-tools-left">
                    <div class="filter-field">
                        <label for="courseSearch" class="filter-label">ค้นหา</label>
                        <div class="filter-input-wrap">
                            <span class="filter-input-icon" aria-hidden="true">
                                <?= lucide_icon('search', ['size' => 18]) ?>
                            </span>
                            <input
                                id="courseSearch"
                                name="q"
                                class="form-control"
                                type="search"
                                placeholder="พิมพ์ชื่อคอร์ส เช่น HSK 3 / พินอิน"
                                value="<?= e($searchQuery) ?>"
                                autocomplete="off"
                            >
                        </div>
                    </div>
            </div>
            <div class="filter-tools-right">
                <div class="filter-field">
                    <label for="courseTypeSelect" class="filter-label">ประเภท</label>
                        <div class="filter-select-wrap">
                            <span class="filter-input-icon" aria-hidden="true">
                                <?= lucide_icon('video', ['size' => 18]) ?>
                            </span>
                            <select id="courseTypeSelect" name="type" class="form-control">
                                <option value="" <?= $typeFilter === '' ? 'selected' : '' ?>>ทั้งหมด</option>
                                <option value="live" <?= $typeFilter === 'live' ? 'selected' : '' ?>>คลาส Live</option>
                                <option value="hybrid" <?= $typeFilter === 'hybrid' ? 'selected' : '' ?>>Hybrid</option>
                                <option value="recorded" <?= $typeFilter === 'recorded' ? 'selected' : '' ?>>เรียนวิดีโอ</option>
                            </select>
                        </div>
                </div>
                <div class="filter-field">
                    <label for="courseCategorySelect" class="filter-label">หมวดหมู่</label>
                        <div class="filter-select-wrap">
                            <span class="filter-input-icon" aria-hidden="true">
                                <?= lucide_icon('sliders-horizontal', ['size' => 18]) ?>
                            </span>
                            <select id="courseCategorySelect" class="form-control">
                                <option value="all">ทั้งหมด</option>
                                <option value="foundation">พื้นฐาน</option>
                                <option value="hsk">HSK</option>
                                <option value="exam_prep">ติวสอบ</option>
                            </select>
                        </div>
                </div>
                    <div class="filter-field filter-field-button">
                        <label class="filter-label filter-label-spacer" aria-hidden="true">&nbsp;</label>
                        <div class="filter-button-group">
                            <button type="submit" class="btn btn-primary btn-sm">ค้นหา</button>
                            <button id="courseClearBtn" type="button" class="btn btn-outline btn-sm">
                                ล้างตัวกรอง
                            </button>
                        </div>
                    </div>
            </div>
        </form>
        <div class="courses-grid">
            <?php if ($courses): ?>
                <?php foreach ($courses as $course): ?>
                    <?php include dirname(__DIR__) . '/includes/course_card.php'; ?>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="lesson-empty" style="grid-column:1/-1"><?= $searchQuery !== '' ? 'ไม่พบคอร์สที่ค้นหา "' . e($searchQuery) . '"' : 'ยังไม่มีคอร์ส กรุณา import ฐานข้อมูล database/schema.sql' ?></p>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
