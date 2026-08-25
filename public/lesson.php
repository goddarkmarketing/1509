<?php

declare(strict_types=1);



require_once dirname(__DIR__) . '/includes/access.php';

require_once dirname(__DIR__) . '/includes/progress.php';
require_once dirname(__DIR__) . '/includes/instructor.php';
require_once dirname(__DIR__) . '/includes/game.php';

$lessonId = isset($_GET['lesson_id']) ? (int) $_GET['lesson_id'] : 0;

if ($lessonId <= 0) {

    redirect('/public/courses.php');

}



$lesson = getLessonWithCourse($lessonId);



if (!$lesson) {

    $pageTitle = 'ไม่พบบทเรียน';

    require_once dirname(__DIR__) . '/includes/header.php';

    echo '<section class="section"><div class="container"><p class="lesson-empty">ไม่พบบทเรียนที่ต้องการ</p><a href="' . APP_URL . '/public/courses.php" class="btn btn-primary">กลับหน้าคอร์ส</a></div></section>';

    require_once dirname(__DIR__) . '/includes/footer.php';

    exit;

}



$courseId = (int) $lesson['course_id'];

$course = [

    'id' => $courseId,

    'slug' => $lesson['course_slug'],

    'title' => $lesson['course_title'],

    'category' => $lesson['course_category'],

];

$hasAccess = canAccessLesson($lesson);

$pageTitle = $lesson['title'] ?? 'บทเรียน';

$student = currentStudent();

$lessonCompleted = $student ? isLessonCompleted((int) $student['id'], $lessonId) : false;

$courseProgress = $student ? getCourseProgress((int) $student['id'], $courseId) : null;

$completedLessonIds = $student ? getCompletedLessonIds((int) $student['id'], $courseId) : [];

$allLessons = getLessonsByCourse($courseId);



$accessibleLessons = [];

foreach ($allLessons as $courseLesson) {

    $lessonRow = array_merge($courseLesson, ['course_id' => $courseId]);

    if (canAccessLesson($lessonRow)) {

        $accessibleLessons[] = $lessonRow;

    }

}



$prevLesson = null;

$nextLesson = null;

foreach ($accessibleLessons as $index => $courseLesson) {

    if ((int) $courseLesson['id'] === $lessonId) {

        if ($index > 0) {

            $prevLesson = $accessibleLessons[$index - 1];

        }

        if ($index < count($accessibleLessons) - 1) {

            $nextLesson = $accessibleLessons[$index + 1];

        }

        break;

    }

}



function youtubeEmbedUrl(string $url): ?string

{

    if (preg_match('~youtube\.com/watch\?v=([A-Za-z0-9_-]+)~', $url, $m)) {

        return 'https://www.youtube.com/embed/' . $m[1];

    }

    if (preg_match('~youtu\.be/([A-Za-z0-9_-]+)~', $url, $m)) {

        return 'https://www.youtube.com/embed/' . $m[1];

    }

    return null;

}



$videoEmbed = null;

$videoUrl = trim((string) ($lesson['video_url'] ?? ''));

if ($hasAccess && $videoUrl !== '') {

    $videoEmbed = youtubeEmbedUrl($videoUrl);

}

$instructor = getInstructorProfile();
$instructorPhotoUrl = instructorPhotoUrl($instructor);
$courseGames = ($hasAccess && $student) ? getGamesByCourse($courseId) : [];

require_once dirname(__DIR__) . '/includes/header.php';

?>



<section class="course-hero course-hero--compact">
    <div class="container">
        <div class="breadcrumb" style="opacity:.95">
            <a href="<?= APP_URL ?>/public/courses.php">คอร์ส</a> /
            <a href="<?= APP_URL ?>/public/course.php?slug=<?= e(urlencode($course['slug'])) ?>">
                <?= e($course['title']) ?>
            </a> /
            <?= e($lesson['title'] ?? '') ?>
        </div>
        <h1 style="margin-top:.5rem;"><?= e($lesson['title'] ?? '') ?></h1>
        <?php if (!empty($lesson['description'])): ?>
            <p class="course-hero-sub"><?= e($lesson['description']) ?></p>
        <?php endif; ?>
        <?php if (!empty($lesson['is_free_preview'])): ?>
            <span class="badge lesson-preview-badge">ทดลองเรียนฟรี</span>
        <?php endif; ?>
    </div>
</section>

<section class="section lesson-page-section">

    <div class="container lesson-page-grid">

        <div class="lesson-main">

            <div class="lesson-player-card">

                <?php if (!$hasAccess): ?>

                <div class="lesson-locked">

                    <div class="lesson-locked-icon" aria-hidden="true">

                        <?= lucide_icon('lock', ['size' => 40, 'stroke' => '1.75']) ?>

                    </div>

                    <h2>บทเรียนนี้ต้องสมัครคอร์สก่อน</h2>

                    <p>สมัครและชำระเงินเพื่อเปิดสิทธิ์เรียนบทนี้ หรือเข้าสู่ระบบหากสมัครแล้ว</p>

                    <div class="lesson-locked-actions">

                        <a href="<?= APP_URL ?>/public/course.php?slug=<?= e(urlencode($course['slug'])) ?>" class="btn btn-primary">ดูรายละเอียดคอร์ส</a>

                        <?php if (isStudentLoggedIn()): ?>

                        <a href="<?= APP_URL ?>/public/profile.php?tab=courses" class="btn btn-outline">คอร์สของฉัน</a>

                        <?php else: ?>

                        <a href="<?= APP_URL ?>/public/login.php" class="btn btn-outline">เข้าสู่ระบบ</a>

                        <?php endif; ?>

                    </div>

                </div>

                <?php else: ?>

                <div class="lesson-player-head">
                    <h2>เนื้อหาบทเรียน</h2>
                    <?php if ($videoUrl): ?>
                    <span class="lesson-player-tag">วิดีโอ</span>
                    <?php endif; ?>
                </div>



                <?php if ($videoUrl): ?>

                    <?php if ($videoEmbed): ?>

                        <div class="lesson-video-wrap">

                            <iframe

                                src="<?= e($videoEmbed) ?>"

                                title="<?= e($lesson['title'] ?? 'วิดีโอบทเรียน') ?>"

                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"

                                allowfullscreen>

                            </iframe>

                        </div>

                    <?php else: ?>

                        <div class="lesson-video-fallback">

                            <p>ลิงก์วิดีโอนี้อาจไม่รองรับการฝัง (แสดงเป็นลิงก์แทน)</p>

                            <a class="btn btn-primary" href="<?= e($videoUrl) ?>" target="_blank" rel="noopener">เปิดวิดีโอ</a>

                        </div>

                    <?php endif; ?>

                <?php else: ?>

                    <div class="lesson-empty">บทเรียนนี้ยังไม่ได้ใส่วิดีโอ</div>

                <?php endif; ?>



                <?php if (!empty($lesson['document_url'])): ?>

                    <?php

                    $docUrl = $lesson['document_url'];

                    if (str_starts_with($docUrl, 'uploads/courses/')) {

                        $docUrl = APP_URL . '/public/download.php?lesson_id=' . $lessonId . '&file=' . urlencode(basename($docUrl));

                    }

                    ?>

                    <div class="lesson-document-block">

                        <h3>เอกสารประกอบ</h3>

                        <a class="btn btn-outline btn-sm" href="<?= e($docUrl) ?>" target="_blank" rel="noopener">ดาวน์โหลด / เปิดเอกสาร</a>

                    </div>

                <?php endif; ?>



                <?php if ($student): ?>

                <div class="lesson-complete-section">

                    <?php if ($lessonCompleted): ?>

                    <p class="lesson-complete-done">

                        <?= lucide_icon('circle-check', ['size' => 18, 'stroke' => '2.5']) ?>

                        เรียนจบบทนี้แล้ว — สามารถกลับมาทบทวนได้ทุกเมื่อ

                    </p>

                    <?php else: ?>

                    <form method="post" action="<?= APP_URL ?>/public/lesson_complete.php">

                        <?= csrfField() ?>

                        <input type="hidden" name="lesson_id" value="<?= $lessonId ?>">

                        <input type="hidden" name="return" value="/public/lesson.php?lesson_id=<?= $lessonId ?>">

                        <button type="submit" class="btn btn-outline">ทำเครื่องหมายว่าเรียนจบแล้ว</button>

                    </form>

                    <?php endif; ?>

                </div>

                <?php endif; ?>



                <nav class="lesson-pager" aria-label="นำทางบทเรียน">

                    <?php if ($prevLesson): ?>

                    <a href="<?= APP_URL ?>/public/lesson.php?lesson_id=<?= (int) $prevLesson['id'] ?>" class="lesson-pager-link lesson-pager-link--prev">

                        <span class="lesson-pager-label">บทก่อนหน้า</span>

                        <span class="lesson-pager-title"><?= e($prevLesson['title']) ?></span>

                    </a>

                    <?php else: ?>

                    <span class="lesson-pager-link lesson-pager-link--prev is-disabled" aria-hidden="true"></span>

                    <?php endif; ?>



                    <a href="<?= APP_URL ?>/public/course.php?slug=<?= e(urlencode($course['slug'])) ?>" class="lesson-pager-center">หน้าคอร์ส</a>



                    <?php if ($nextLesson): ?>

                    <a href="<?= APP_URL ?>/public/lesson.php?lesson_id=<?= (int) $nextLesson['id'] ?>" class="lesson-pager-link lesson-pager-link--next">

                        <span class="lesson-pager-label">บทถัดไป</span>

                        <span class="lesson-pager-title"><?= e($nextLesson['title']) ?></span>

                    </a>

                    <?php else: ?>

                    <span class="lesson-pager-link lesson-pager-link--next is-disabled" aria-hidden="true"></span>

                    <?php endif; ?>

                </nav>

                <?php endif; ?>

            </div>

        </div>



        <aside class="lesson-sidebar">

            <div class="lesson-sidebar-card">

                <div class="lesson-sidebar-head">

                    <h3><?= e($course['title']) ?></h3>

                    <p><?= e(categoryLabel($course['category'])) ?></p>

                    <?php $photoUrl = $instructorPhotoUrl; require dirname(__DIR__) . '/includes/views/instructor_chip.php'; ?>

                </div>



                <?php if ($courseProgress): ?>

                <div class="course-progress-bar-wrap course-progress-bar-wrap--compact lesson-sidebar-progress">

                    <div class="course-progress-label">

                        <span>ความคืบหน้า</span>

                        <strong><?= (int) $courseProgress['percent'] ?>%</strong>

                    </div>

                    <div class="course-progress-bar" role="progressbar" aria-valuenow="<?= (int) $courseProgress['percent'] ?>" aria-valuemin="0" aria-valuemax="100">

                        <span style="width:<?= (int) $courseProgress['percent'] ?>%"></span>

                    </div>

                    <small><?= (int) $courseProgress['done'] ?> / <?= (int) $courseProgress['total'] ?> บท</small>

                </div>

                <?php endif; ?>



                <div class="lesson-nav-block">

                    <h4 class="lesson-nav-title">บทเรียนในคอร์ส</h4>

                    <?php if ($allLessons): ?>

                    <ol class="lesson-nav-list">

                        <?php foreach ($allLessons as $index => $courseLesson): ?>

                        <?php

                        $navLessonId = (int) $courseLesson['id'];

                        $lessonRow = array_merge($courseLesson, ['course_id' => $courseId]);

                        $canAccessNav = canAccessLesson($lessonRow);

                        $isCurrent = $navLessonId === $lessonId;

                        $isDone = in_array($navLessonId, $completedLessonIds, true);

                        ?>

                        <li class="lesson-nav-item<?= $isCurrent ? ' is-current' : '' ?><?= !$canAccessNav ? ' is-locked' : '' ?><?= $isDone ? ' is-done' : '' ?>">

                            <span class="lesson-nav-index"><?= $index + 1 ?></span>

                            <?php if ($canAccessNav): ?>

                            <a href="<?= APP_URL ?>/public/lesson.php?lesson_id=<?= $navLessonId ?>" class="lesson-nav-link">

                                <span class="lesson-nav-link-title"><?= e($courseLesson['title']) ?></span>

                                <?php if ($isDone): ?>

                                <span class="lesson-nav-status" title="เรียนจบแล้ว">

                                    <?= lucide_icon('circle-check', ['size' => 14, 'stroke' => '2.5']) ?>

                                </span>

                                <?php endif; ?>

                            </a>

                            <?php else: ?>

                            <span class="lesson-nav-link lesson-nav-link--locked">

                                <span class="lesson-nav-link-title"><?= e($courseLesson['title']) ?></span>

                                <span class="lesson-nav-status" title="ต้องซื้อคอร์สก่อน">
                                    <?= lucide_icon('lock', ['size' => 16, 'class' => 'icon-lock']) ?>
                                </span>

                            </span>

                            <?php endif; ?>

                        </li>

                        <?php endforeach; ?>

                    </ol>

                    <?php else: ?>

                    <p class="lesson-nav-empty">ยังไม่มีบทเรียนในคอร์สนี้</p>

                    <?php endif; ?>

                </div>



                <?php if ($courseGames): ?>
                <?php $games = $courseGames; $variant = 'sidebar'; require dirname(__DIR__) . '/includes/views/course_games_block.php'; ?>
                <?php endif; ?>



                <div class="lesson-sidebar-actions">

                    <a href="<?= APP_URL ?>/public/course.php?slug=<?= e(urlencode($course['slug'])) ?>" class="btn btn-primary btn-block btn-sm">กลับไปหน้าคอร์ส</a>

                    <a href="<?= APP_URL ?>/public/profile.php?tab=courses" class="btn btn-outline btn-block btn-sm">คอร์สของฉัน</a>

                </div>

            </div>

        </aside>

    </div>

</section>



<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>

