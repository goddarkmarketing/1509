<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/student_auth.php';
requireStudentLogin();

$result = $_SESSION['quiz_result'] ?? null;
unset($_SESSION['quiz_result']);
if (!$result) {
    redirect('/public/my-courses.php');
}

$pageTitle = 'ผลแบบทดสอบ';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<main class="checkout-page quiz-page">
    <div class="container" style="max-width:520px;text-align:center">
        <div class="quiz-result-card <?= $result['passed'] ? 'is-pass' : 'is-fail' ?>">
            <div class="quiz-result-score"><?= (int) $result['score'] ?>%</div>
            <h1><?= $result['passed'] ? 'ยินดีด้วย ผ่านเกณฑ์!' : 'ยังไม่ผ่านเกณฑ์' ?></h1>
            <p><?= e($result['title']) ?></p>
            <p>ตอบถูก <?= (int) $result['correct'] ?> / <?= (int) $result['total'] ?> ข้อ (ผ่าน <?= (int) $result['pass_score'] ?>%)</p>
            <div style="margin-top:1.5rem;display:flex;gap:.75rem;justify-content:center;flex-wrap:wrap">
                <a href="<?= APP_URL ?>/public/quiz.php?quiz_id=<?= (int) $result['quiz_id'] ?>" class="btn btn-outline">ทำอีกครั้ง</a>
                <a href="<?= APP_URL ?>/public/my-courses.php" class="btn btn-primary">กลับคอร์สของฉัน</a>
            </div>
        </div>
    </div>
</main>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
