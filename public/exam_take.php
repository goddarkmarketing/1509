<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/student_auth.php';
require_once dirname(__DIR__) . '/includes/exam.php';

requireStudentLogin();

$slug = trim($_GET['slug'] ?? '');
$pack = $slug !== '' ? getExamPackBySlug($slug) : null;
$student = currentStudent();

if (!$pack || empty($pack['is_published'])) {
    redirect('/public/exams.php');
}

$packId = (int) $pack['id'];
if (!studentHasExamAccess((int) $student['id'], $packId)) {
    $status = studentExamPurchaseStatus((int) $student['id'], $packId);
    if ($status === 'pending') {
        flash('payment_error', 'ชุดข้อสอบนี้รอการยืนยันชำระเงินอยู่');
    } else {
        flash('payment_error', 'กรุณาซื้อชุดข้อสอบก่อนเข้าจำลองสนามสอบ');
    }
    redirect('/public/exam.php?slug=' . urlencode($pack['slug']));
}

$questions = getExamQuestions($packId);
if (!$questions) {
    flash('payment_error', 'ชุดข้อสอบนี้ยังไม่มีคำถาม');
    redirect('/public/exam.php?slug=' . urlencode($pack['slug']));
}

$timeLimit = (int) ($pack['time_limit_minutes'] ?? 0);
$pageTitle = 'สนามสอบ · ' . $pack['title'];
$startedAt = date('Y-m-d H:i:s');
require_once dirname(__DIR__) . '/includes/header.php';
?>

<main class="exam-hall-page">
    <div class="exam-hall">
        <header class="exam-hall-top">
            <div class="exam-hall-top-main">
                <p class="exam-hall-label">จำลองสนามสอบ</p>
                <h1><?= e($pack['title']) ?></h1>
            </div>
            <div class="exam-hall-top-meta">
                <span><?= count($questions) ?> ข้อ</span>
                <span>ผ่าน <?= (int) $pack['pass_score'] ?>%</span>
                <?php if ($timeLimit > 0): ?>
                <span class="exam-hall-timer" id="examTimer" aria-live="polite">--:--</span>
                <?php endif; ?>
            </div>
        </header>

        <div class="exam-hall-layout">
            <aside class="exam-hall-nav" aria-label="หมายเลขข้อ">
                <p class="exam-hall-nav-title">ข้อสอบ</p>
                <div class="exam-hall-nav-grid" id="examNavGrid">
                    <?php foreach ($questions as $i => $q): ?>
                    <button type="button" class="exam-nav-btn" data-index="<?= $i ?>" aria-label="ข้อ <?= $i + 1 ?>"><?= $i + 1 ?></button>
                    <?php endforeach; ?>
                </div>
                <ul class="exam-hall-legend">
                    <li><span class="dot is-current"></span> ข้อปัจจุบัน</li>
                    <li><span class="dot is-answered"></span> ตอบแล้ว</li>
                    <li><span class="dot is-flagged"></span> ทบทวน</li>
                </ul>
            </aside>

            <form method="post" action="<?= APP_URL ?>/public/exam_submit.php" class="exam-hall-form" id="examForm">
                <?= csrfField() ?>
                <input type="hidden" name="exam_pack_id" value="<?= $packId ?>">
                <input type="hidden" name="started_at" value="<?= e($startedAt) ?>">
                <input type="hidden" name="time_spent_seconds" id="timeSpentInput" value="0">

                <?php foreach ($questions as $i => $q): ?>
                <?php $opts = parseExamQuestionOptions($q); ?>
                <fieldset class="exam-question-panel<?= $i === 0 ? ' is-active' : '' ?>" data-index="<?= $i ?>" data-qid="<?= (int) $q['id'] ?>">
                    <legend>ข้อ <?= $i + 1 ?> / <?= count($questions) ?></legend>
                    <p class="exam-question-text"><?= e($q['question_text']) ?></p>
                    <div class="exam-options">
                        <?php foreach ($opts as $key => $label): ?>
                        <label class="exam-option">
                            <input type="radio" name="answer_<?= (int) $q['id'] ?>" value="<?= e((string) $key) ?>">
                            <span><strong><?= e((string) $key) ?>.</strong> <?= e($label) ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <label class="exam-flag-toggle">
                        <input type="checkbox" class="exam-flag-input" data-index="<?= $i ?>">
                        ทำเครื่องหมายทบทวนข้อนี้
                    </label>
                </fieldset>
                <?php endforeach; ?>

                <div class="exam-hall-actions">
                    <button type="button" class="btn btn-outline" id="examPrevBtn">ข้อก่อนหน้า</button>
                    <button type="button" class="btn btn-outline" id="examNextBtn">ข้อถัดไป</button>
                    <button type="submit" class="btn btn-primary" id="examSubmitBtn">ส่งคำตอบ</button>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
(function () {
  var form = document.getElementById('examForm');
  if (!form) return;
  var panels = Array.prototype.slice.call(form.querySelectorAll('.exam-question-panel'));
  var navBtns = Array.prototype.slice.call(document.querySelectorAll('.exam-nav-btn'));
  var prevBtn = document.getElementById('examPrevBtn');
  var nextBtn = document.getElementById('examNextBtn');
  var timeSpentInput = document.getElementById('timeSpentInput');
  var current = 0;
  var started = Date.now();
  var flagged = {};

  function show(index) {
    current = Math.max(0, Math.min(panels.length - 1, index));
    panels.forEach(function (p, i) {
      p.classList.toggle('is-active', i === current);
    });
    syncNav();
  }

  function answered(index) {
    var panel = panels[index];
    if (!panel) return false;
    return !!panel.querySelector('input[type="radio"]:checked');
  }

  function syncNav() {
    navBtns.forEach(function (btn, i) {
      btn.classList.toggle('is-current', i === current);
      btn.classList.toggle('is-answered', answered(i));
      btn.classList.toggle('is-flagged', !!flagged[i]);
    });
    if (prevBtn) prevBtn.disabled = current <= 0;
    if (nextBtn) nextBtn.disabled = current >= panels.length - 1;
  }

  navBtns.forEach(function (btn) {
    btn.addEventListener('click', function () {
      show(parseInt(btn.getAttribute('data-index'), 10) || 0);
    });
  });
  if (prevBtn) prevBtn.addEventListener('click', function () { show(current - 1); });
  if (nextBtn) nextBtn.addEventListener('click', function () { show(current + 1); });

  form.addEventListener('change', function (e) {
    if (e.target && e.target.matches('input[type="radio"]')) syncNav();
    if (e.target && e.target.classList.contains('exam-flag-input')) {
      var idx = parseInt(e.target.getAttribute('data-index'), 10) || 0;
      flagged[idx] = !!e.target.checked;
      syncNav();
    }
  });

  form.addEventListener('submit', function (e) {
    var unanswered = panels.filter(function (_, i) { return !answered(i); }).length;
    var msg = unanswered > 0
      ? 'ยังมีข้อที่ยังไม่ตอบ ' + unanswered + ' ข้อ ต้องการส่งคำตอบเลยหรือไม่?'
      : 'ยืนยันส่งคำตอบและออกจากสนามสอบ?';
    if (!confirm(msg)) {
      e.preventDefault();
      return;
    }
    if (timeSpentInput) {
      timeSpentInput.value = String(Math.max(0, Math.floor((Date.now() - started) / 1000)));
    }
  });

  <?php if ($timeLimit > 0): ?>
  var left = <?= $timeLimit ?> * 60;
  var timerEl = document.getElementById('examTimer');
  function tick() {
    var m = Math.floor(left / 60);
    var s = left % 60;
    if (timerEl) {
      timerEl.textContent = 'เหลือ ' + m + ':' + String(s).padStart(2, '0');
      timerEl.classList.toggle('is-urgent', left <= 60);
    }
    if (left <= 0) {
      if (timeSpentInput) {
        timeSpentInput.value = String(Math.max(0, Math.floor((Date.now() - started) / 1000)));
      }
      form.submit();
      return;
    }
    left -= 1;
    setTimeout(tick, 1000);
  }
  tick();
  <?php endif; ?>

  show(0);
})();
</script>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
