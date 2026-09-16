<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/student_auth.php';
require_once dirname(__DIR__) . '/includes/exam.php';
require_once dirname(__DIR__) . '/includes/ui.php';

requireStudentLogin();

$result = $_SESSION['exam_result'] ?? null;
unset($_SESSION['exam_result']);
if (!$result) {
    redirect('/public/profile.php?tab=exams');
}

$pack = getExamPackById((int) ($result['exam_pack_id'] ?? 0));
$questions = $pack ? getExamQuestions((int) $pack['id']) : [];
$detail = $result['detail'] ?? [];
$mins = (int) floor(((int) ($result['time_spent_seconds'] ?? 0)) / 60);
$secs = ((int) ($result['time_spent_seconds'] ?? 0)) % 60;
$passed = !empty($result['passed']);

$pageTitle = 'ผลสอบ · ' . ($result['title'] ?? '');
$pageStylesheets = ['css/ui-shadcn.css'];
require_once dirname(__DIR__) . '/includes/header.php';
?>

<main class="ui-scope ui-exam-detail-page">
    <div class="container" style="max-width:820px">
        <?= uiCardOpen(['style' => 'text-align:center;padding:1.75rem 1.25rem;--card-spacing:1.25rem']) ?>
            <?= uiCardContentOpen() ?>
                <?= uiBadge($passed ? 'ผ่านเกณฑ์' : 'ยังไม่ผ่าน', $passed ? 'default' : 'destructive') ?>
                <div class="ui-buy-price" style="margin:1rem 0 .5rem;font-size:3rem"><?= (int) $result['score'] ?>%</div>
                <?= uiCardTitle($passed ? 'ผ่านเกณฑ์สนามสอบ' : 'ยังไม่ถึงเกณฑ์ผ่าน') ?>
                <?= uiCardDescription((string) ($result['title'] ?? '')) ?>
                <p class="ui-buy-hint" style="margin-top:.75rem">
                    ตอบถูก <?= (int) $result['correct'] ?> / <?= (int) $result['total'] ?> ข้อ
                    · เกณฑ์ผ่าน <?= (int) $result['pass_score'] ?>%
                    <?php if (($result['time_spent_seconds'] ?? 0) > 0): ?>
                    · ใช้เวลา <?= $mins ?>:<?= str_pad((string) $secs, 2, '0', STR_PAD_LEFT) ?>
                    <?php endif; ?>
                </p>
                <div style="display:flex;gap:.65rem;justify-content:center;flex-wrap:wrap;margin-top:1.25rem">
                    <?= uiButton('สอบอีกครั้ง', APP_URL . '/public/exam_take.php?slug=' . urlencode((string) ($result['slug'] ?? '')), 'outline') ?>
                    <?= uiButton('ชุดข้อสอบของฉัน', APP_URL . '/public/profile.php?tab=exams', 'default') ?>
                </div>
            <?= uiCardCloseSection() ?>
        <?= uiCardClose() ?>

        <?php if ($questions): ?>
        <div style="height:1.5rem"></div>
        <h2 style="margin:0 0 1rem;font-size:1.15rem">เฉลยและทบทวน</h2>
        <?php foreach ($questions as $i => $q): ?>
        <?php
            $qid = (int) $q['id'];
            $info = $detail[$qid] ?? ['chosen' => '', 'correct_key' => $q['correct_key'], 'is_correct' => false];
            $opts = parseExamQuestionOptions($q);
            $isCorrect = !empty($info['is_correct']);
        ?>
        <?= uiCardOpen() ?>
            <?= uiCardHeaderOpen() ?>
                <?= uiCardTitle('ข้อ ' . ($i + 1) . '. ' . $q['question_text']) ?>
                <?= uiCardAction(uiBadge($isCorrect ? 'ถูก' : 'ผิด', $isCorrect ? 'default' : 'destructive')) ?>
            <?= uiCardCloseSection() ?>
            <?= uiCardContentOpen() ?>
                <p class="ui-buy-hint" style="text-align:left;margin-bottom:.65rem">
                    คำตอบของคุณ: <strong><?= e(($info['chosen'] ?? '') !== '' ? (string) $info['chosen'] : '-') ?></strong>
                    · คำตอบที่ถูก: <strong><?= e((string) ($info['correct_key'] ?? $q['correct_key'])) ?></strong>
                </p>
                <ul style="list-style:none;margin:0;padding:0;display:grid;gap:.35rem">
                    <?php foreach ($opts as $key => $label): ?>
                    <li style="padding:.45rem .6rem;border:1px solid var(--ui-border);border-radius:.5rem;<?= $key === ($q['correct_key'] ?? '') ? 'background:#f0fdf4;' : '' ?><?= $key === ($info['chosen'] ?? '') && !$isCorrect ? 'background:#fef2f2;' : '' ?>">
                        <strong><?= e((string) $key) ?>.</strong> <?= e($label) ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php if (!empty($q['explanation'])): ?>
                <p class="ui-buy-hint" style="text-align:left;margin-top:.75rem;background:var(--ui-muted);padding:.65rem .75rem;border-radius:.5rem"><?= e($q['explanation']) ?></p>
                <?php endif; ?>
            <?= uiCardCloseSection() ?>
        <?= uiCardClose() ?>
        <div style="height:.75rem"></div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
