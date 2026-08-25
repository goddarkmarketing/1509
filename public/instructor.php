<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/instructor.php';

$homeStats = getHomepageStats();
$instructor = getInstructorProfile($homeStats);
$photoUrl = instructorPhotoUrl($instructor);

$courses = [];
try {
    $courses = getCourses();
} catch (Throwable $e) {
    $courses = [];
}
$featuredCourses = array_values(array_filter($courses, static fn($c) => !empty($c['is_featured'])));
if (!$featuredCourses) {
    $featuredCourses = array_slice($courses, 0, 3);
} else {
    $featuredCourses = array_slice($featuredCourses, 0, 3);
}

$pageTitle = 'โปรไฟล์ผู้สอน';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<section class="instructor-page-hero">
    <div class="container instructor-page-hero-inner">
        <div class="breadcrumb instructor-page-breadcrumb">
            <a href="<?= APP_URL ?>/public/index.php">หน้าแรก</a> /
            <?= e($instructor['name']) ?>
        </div>
        <span class="instructor-role-badge instructor-role-badge--hero"><?= e($instructor['role']) ?></span>
        <h1><?= e($instructor['name']) ?></h1>
        <p class="instructor-page-hero-tagline"><?= e($instructor['tagline']) ?></p>
    </div>
</section>

<section class="section instructor-page-section">
    <div class="container instructor-page-layout">
        <aside class="instructor-page-aside">
            <div class="instructor-page-photo-card">
                <div class="instructor-photo-frame">
                    <img src="<?= e($photoUrl) ?>" alt="<?= e($instructor['name']) ?>" loading="eager">
                </div>
            </div>
            <div class="instructor-page-stats-grid">
                <?php foreach ($instructor['stats'] as $stat): ?>
                <article class="instructor-stat-card instructor-stat-card--compact">
                    <span class="instructor-stat-icon" aria-hidden="true"><?= instructorStatIconSvg($stat['icon']) ?></span>
                    <p class="instructor-stat-value"><?= e($stat['value']) ?></p>
                    <p class="instructor-stat-label"><?= e($stat['label']) ?></p>
                </article>
                <?php endforeach; ?>
            </div>
        </aside>

        <div class="instructor-page-main">
            <div class="instructor-page-block">
                <h2>เกี่ยวกับอาจารย์</h2>
                <?php foreach (preg_split('/\R+/u', trim($instructor['bio'])) as $paragraph): ?>
                <?php if (trim($paragraph) !== ''): ?>
                <p><?= e(trim($paragraph)) ?></p>
                <?php endif; ?>
                <?php endforeach; ?>
                <?php if (!empty($instructor['quote'])): ?>
                <blockquote class="instructor-page-quote">&ldquo;<?= e($instructor['quote']) ?>&rdquo;</blockquote>
                <?php endif; ?>
            </div>

            <div class="instructor-page-block">
                <h2>ประวัติและความเชี่ยวชาญ</h2>
                <ul class="instructor-credentials instructor-credentials--page">
                    <?php foreach ($instructor['credentials'] as $index => $credential): ?>
                    <?php $icons = ['degree', 'time', 'users', 'award']; ?>
                    <?php $iconKey = $icons[$index % count($icons)]; ?>
                    <li>
                        <span class="instructor-credential-icon" aria-hidden="true">
                            <?= instructorCredentialIconSvg()[$iconKey] ?? instructorCredentialIconSvg()['degree'] ?>
                        </span>
                        <span class="instructor-credential-text"><?= e($credential) ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="instructor-page-block">
                <h2>แนวทางการสอน</h2>
                <div class="instructor-highlights-grid">
                    <?php foreach ($instructor['highlights'] as $index => $highlight): ?>
                    <article class="instructor-highlight-card">
                        <span class="instructor-highlight-num"><?= $index + 1 ?></span>
                        <p><?= e($highlight) ?></p>
                    </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php if ($featuredCourses): ?>
<section class="section section-alt instructor-page-courses">
    <div class="container">
        <div class="section-header">
            <h2>คอร์สจากครูบอล</h2>
            <p>เริ่มเรียนภาษาจีนกับคอร์สที่ออกแบบโดยอาจารย์โดยตรง</p>
        </div>
        <div class="course-grid">
            <?php foreach ($featuredCourses as $course): ?>
                <?php include dirname(__DIR__) . '/includes/course_card.php'; ?>
            <?php endforeach; ?>
        </div>
        <div class="instructor-page-cta">
            <a href="<?= APP_URL ?>/public/courses.php" class="btn btn-primary">ดูคอร์สทั้งหมด</a>
            <a href="<?= lineContactUrl() ?>" class="btn btn-outline" target="_blank" rel="noopener">สอบถามอาจารย์</a>
        </div>
    </div>
</section>
<?php endif; ?>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
