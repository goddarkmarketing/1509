<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/student_auth.php';
require_once dirname(__DIR__) . '/includes/game.php';
require_once dirname(__DIR__) . '/includes/checkout_flow.php';

requireStudentLogin();

$gameId = (int) ($_GET['game_id'] ?? 0);
$game = $gameId > 0 ? getGameById($gameId) : null;
$student = currentStudent();

if (!$game || empty($game['is_published'])) {
    redirect('/public/profile.php?tab=courses');
}
if (!studentCanPlayGame((int) $student['id'], $game)) {
    flash('payment_error', 'คุณยังไม่มีสิทธิ์เล่นเกมนี้');
    redirect('/public/profile.php?tab=courses');
}

$course = getCourseById((int) $game['course_id']);
$gameUrl = normalizeGameUrl($game['game_url'] ?? '');
if (!$gameUrl) {
    flash('payment_error', 'ลิงก์เกมไม่ถูกต้อง กรุณาติดต่อทีมงาน');
    redirect('/public/profile.php?tab=courses');
}

$pageTitle = $game['title'];
$lessonId = getFirstLessonIdForCourse((int) $game['course_id']);
$backLessonUrl = $lessonId
    ? APP_URL . '/public/lesson.php?lesson_id=' . $lessonId
    : APP_URL . '/public/course.php?slug=' . urlencode($course['slug'] ?? '');

require_once dirname(__DIR__) . '/includes/header.php';
?>

<main class="game-external-page">
    <div class="container game-external-wrap">
        <div class="breadcrumb game-external-breadcrumb">
            <a href="<?= APP_URL ?>/public/courses.php">คอร์ส</a> /
            <?php if ($course): ?>
            <a href="<?= APP_URL ?>/public/course.php?slug=<?= e(urlencode($course['slug'])) ?>"><?= e($course['title']) ?></a> /
            <?php endif; ?>
            <span><?= e($game['title']) ?></span>
        </div>

        <div class="game-external-card">
            <div class="game-external-icon" aria-hidden="true">
                <?= lucide_icon('gamepad-2', ['size' => 48, 'stroke' => '1.5']) ?>
            </div>
            <?php if ($course): ?>
            <p class="game-external-course"><?= e($course['title']) ?></p>
            <?php endif; ?>
            <h1><?= e($game['title']) ?></h1>
            <?php if (!empty($game['description'])): ?>
            <p class="game-external-desc"><?= e($game['description']) ?></p>
            <?php endif; ?>
            <p class="game-external-note">เกมนี้อยู่บนแพลตฟอร์มภายนอก กดปุ่มด้านล่างเพื่อเปิดในแท็บใหม่</p>
            <div class="game-external-actions">
                <a href="<?= e($gameUrl) ?>" class="btn btn-primary btn-lg game-external-open" target="_blank" rel="noopener noreferrer">เล่นเกม →</a>
                <a href="<?= e($backLessonUrl) ?>" class="btn btn-outline">กลับไปเรียนต่อ</a>
            </div>
        </div>
    </div>
</main>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
