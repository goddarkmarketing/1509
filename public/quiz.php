<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/student_auth.php';
require_once dirname(__DIR__) . '/includes/quiz.php';

requireStudentLogin();

$quizId = (int) ($_GET['quiz_id'] ?? 0);
$quiz = $quizId > 0 ? getQuizById($quizId) : null;
$student = currentStudent();

if (!$quiz || empty($quiz['is_published'])) {
    redirect('/public/my-courses.php');
}
if (!studentCanTakeQuiz((int) $student['id'], $quiz)) {
    flash('payment_error', 'คุณยังไม่มีสิทธิ์ทำแบบทดสอบนี้');
    redirect('/public/my-courses.php');
}

$questions = getQuizQuestions($quizId);
if (!$questions) {
    flash('payment_error', 'แบบทดสอบนี้ยังไม่มีคำถาม');
    redirect('/public/my-courses.php');
}

$course = getCourseById((int) $quiz['course_id']);
$best = getBestQuizAttempt((int) $student['id'], $quizId);
$pageTitle = $quiz['title'];
require_once dirname(__DIR__) . '/includes/header.php';
?>

<main class="checkout-page quiz-page">
    <div class="container" style="max-width:720px">
        <header class="checkout-main-header">
            <div>
                <p class="quiz-course-label"><?= e($course['title'] ?? '') ?></p>
                <h1><?= e($quiz['title']) ?></h1>
                <?php if (!empty($quiz['description'])): ?>
                <p class="my-courses-greeting"><?= e($quiz['description']) ?></p>
                <?php endif; ?>
            </div>
        </header>

        <div class="quiz-meta-bar">
            <span>ผ่านเกณฑ์ <?= (int) $quiz['pass_score'] ?>%</span>
            <span><?= count($questions) ?> ข้อ</span>
            <?php if ((int) $quiz['time_limit_minutes'] > 0): ?>
            <span>เวลา <?= (int) $quiz['time_limit_minutes'] ?> นาที</span>
            <?php endif; ?>
            <?php if ($best): ?>
            <span>คะแนนสูงสุด: <?= (int) $best['score'] ?>% <?= $best['passed'] ? '✓ ผ่าน' : '' ?></span>
            <?php endif; ?>
        </div>

        <form method="post" action="<?= APP_URL ?>/public/quiz_submit.php" class="quiz-form" id="quizForm">
            <?= csrfField() ?>
            <input type="hidden" name="quiz_id" value="<?= $quizId ?>">
            <?php foreach ($questions as $i => $q): ?>
            <?php $opts = parseQuestionOptions($q); ?>
            <?php $audioUrl = quizQuestionAudioUrl($q); ?>
            <fieldset class="quiz-question-card">
                <legend><?= ($i + 1) ?>. <?= e($q['question_text']) ?></legend>
                <?php if ($audioUrl): ?>
                <div class="quiz-question-audio">
                    <p class="quiz-question-audio-label">ฟังเสียงแล้วเลือกคำตอบ</p>
                    <audio controls preload="metadata" controlsList="nodownload" src="<?= e($audioUrl) ?>">
                        เบราว์เซอร์ของคุณไม่รองรับการเล่นไฟล์เสียง
                    </audio>
                </div>
                <?php endif; ?>
                <?php foreach ($opts as $key => $label): ?>
                <label class="quiz-option">
                    <input type="radio" name="answer_<?= (int) $q['id'] ?>" value="<?= e($key) ?>" required>
                    <span><strong><?= e($key) ?>.</strong> <?= e($label) ?></span>
                </label>
                <?php endforeach; ?>
            </fieldset>
            <?php endforeach; ?>
            <button type="submit" class="btn btn-primary btn-block">ส่งคำตอบ</button>
        </form>

        <p style="text-align:center;margin-top:1rem">
            <a href="<?= APP_URL ?>/public/my-courses.php">กลับคอร์สของฉัน</a>
        </p>
    </div>
</main>

<?php if ((int) $quiz['time_limit_minutes'] > 0): ?>
<script>
(function(){
  var mins = <?= (int) $quiz['time_limit_minutes'] ?>;
  var left = mins * 60;
  var form = document.getElementById('quizForm');
  var bar = document.createElement('p');
  bar.className = 'quiz-timer';
  bar.style.cssText = 'text-align:center;color:var(--red);font-weight:600;margin-bottom:1rem';
  form.parentNode.insertBefore(bar, form);
  function tick(){
    var m = Math.floor(left/60), s = left%60;
    bar.textContent = 'เหลือเวลา ' + m + ':' + String(s).padStart(2,'0');
    if (left <= 0) { form.submit(); return; }
    left--; setTimeout(tick, 1000);
  }
  tick();
})();
</script>
<?php endif; ?>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
