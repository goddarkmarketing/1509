<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/exam.php';
require_once dirname(__DIR__) . '/includes/student_auth.php';
require_once dirname(__DIR__) . '/includes/ui.php';
require_once dirname(__DIR__) . '/includes/icons.php';

$pageTitle = 'จำลองสนามสอบ';
$pageStylesheets = ['css/ui-shadcn.css'];
$subject = trim($_GET['subject'] ?? '');
$allPacks = getExamPacks(true);
$packs = $subject !== '' ? getExamPacks(true, $subject) : $allPacks;
$subjects = getExamSubjects(true);
$student = currentStudent();
$owned = [];
if ($student) {
    foreach (getStudentExamPurchases((int) $student['id']) as $row) {
        if (($row['purchase_status'] ?? '') === 'active') {
            $owned[(int) $row['id']] = true;
        }
    }
}

$subjectCounts = [];
foreach ($allPacks as $p) {
    $key = (string) ($p['subject'] ?? '');
    if ($key === '') {
        continue;
    }
    $subjectCounts[$key] = ($subjectCounts[$key] ?? 0) + 1;
}

$tabs = [
    [
        'label' => 'ทั้งหมด',
        'href' => APP_URL . '/public/exams.php',
        'active' => $subject === '',
        'count' => count($allPacks),
    ],
];
foreach ($subjects as $sub) {
    $tabs[] = [
        'label' => $sub,
        'href' => APP_URL . '/public/exams.php?subject=' . urlencode($sub),
        'active' => $subject === $sub,
        'count' => $subjectCounts[$sub] ?? 0,
    ];
}

require_once dirname(__DIR__) . '/includes/header.php';
?>

<main class="ui-scope ui-exams-page">
    <div class="container">
        <?= uiBreadcrumb([
            ['label' => 'หน้าแรก', 'href' => APP_URL . '/public/index.php'],
            ['label' => 'จำลองสนามสอบ', 'active' => true],
        ]) ?>

        <header class="ui-page-header">
            <div class="ui-page-header-text">
                <div class="ui-page-header-row">
                    <h1>จำลองสนามสอบ</h1>
                    <?= uiBadge(count($packs) . ' ชุด', 'secondary') ?>
                </div>
                <p class="ui-page-desc">ซื้อชุดข้อสอบแล้วเข้าทำแบบจับเวลา เหมือนสอบจริง — ดูผลคะแนนและเฉลยทันทีหลังส่ง</p>
            </div>
            <?php if ($student): ?>
                <?= uiButton(
                    lucide_icon('clipboard-list', ['size' => 16]) . ' ชุดของฉัน',
                    APP_URL . '/public/profile.php?tab=exams',
                    'outline',
                    'default'
                ) ?>
            <?php else: ?>
                <?= uiButton(
                    lucide_icon('log-in', ['size' => 16]) . ' เข้าสู่ระบบ',
                    APP_URL . '/public/login.php',
                    'outline',
                    'default'
                ) ?>
            <?php endif; ?>
        </header>

        <?= uiSeparator(['class' => 'ui-page-sep']) ?>

        <div class="ui-exams-toolbar">
            <?= uiTabs($tabs, 'หมวดวิชา') ?>
            <?php if ($subject !== ''): ?>
                <?= uiButton('ล้างตัวกรอง', APP_URL . '/public/exams.php', 'ghost', 'sm') ?>
            <?php endif; ?>
        </div>

        <?php if (!$packs): ?>
            <?= uiEmpty(
                $subject !== '' ? 'ไม่พบชุดในหมวดนี้' : 'ยังไม่มีชุดข้อสอบ',
                $subject !== ''
                    ? 'ลองเลือกหมวดอื่น หรือดูชุดทั้งหมด'
                    : (examTablesReady() ? 'รอแอดมินเพิ่มชุดจำลองสนามสอบ' : 'ยังไม่ได้รัน migration phase 12'),
                $subject !== ''
                    ? uiButton('ดูทั้งหมด', APP_URL . '/public/exams.php', 'default', 'sm')
                    : uiButton('กลับหน้าแรก', APP_URL . '/public/index.php', 'outline', 'sm'),
                lucide_icon('file-question', ['size' => 22])
            ) ?>
        <?php else: ?>
        <div class="ui-exams-grid">
            <?php foreach ($packs as $pack): ?>
            <?php
                $id = (int) $pack['id'];
                $hasAccess = !empty($owned[$id]);
                $qCount = (int) ($pack['question_count'] ?? 0);
                $detailUrl = APP_URL . '/public/exam.php?slug=' . urlencode($pack['slug']);
                $takeUrl = APP_URL . '/public/exam_take.php?slug=' . urlencode($pack['slug']);
            ?>
            <?= uiCardOpen(['class' => 'ui-exam-pack-card']) ?>
                <?= examPackMediaHtml($pack, $detailUrl) ?>
                <?= uiCardHeaderOpen(['class' => 'ui-exam-pack-header']) ?>
                    <?= uiCardTitle('<a href="' . e($detailUrl) . '">' . e($pack['title']) . '</a>', false) ?>
                    <?= uiCardDescription($pack['subtitle'] ?: 'จำลองสนามสอบ · ' . examLevelLabel((string) $pack['level'])) ?>
                    <?= uiCardAction(
                        $hasAccess
                            ? uiBadge('พร้อมสอบ', 'default')
                            : uiBadge((string) $pack['subject'], 'secondary')
                    ) ?>
                <?= uiCardCloseSection() ?>
                <?= uiCardContentOpen(['class' => 'ui-exam-pack-content']) ?>
                    <div class="ui-exam-stats ui-exam-stats--grid">
                        <span class="ui-exam-stat"><?= $qCount ?> ข้อ</span>
                        <span class="ui-exam-stat"><?= (int) $pack['time_limit_minutes'] ?> นาที</span>
                        <span class="ui-exam-stat">ผ่าน <?= (int) $pack['pass_score'] ?>%</span>
                        <span class="ui-exam-stat"><?= e(examLevelLabel((string) $pack['level'])) ?></span>
                    </div>
                <?= uiCardCloseSection() ?>
                <?= uiCardFooterOpen(['class' => 'ui-exam-pack-footer']) ?>
                    <div class="ui-exam-price-wrap">
                        <span class="ui-exam-price-label">ราคา</span>
                        <span class="ui-exam-price"><?= e(formatPrice((float) $pack['price'])) ?></span>
                    </div>
                    <?php if ($hasAccess): ?>
                        <?= uiButton('เข้าสอบ', $takeUrl, 'default', 'sm') ?>
                    <?php else: ?>
                        <?= uiButton('ดูรายละเอียด', $detailUrl, 'outline', 'sm') ?>
                    <?php endif; ?>
                <?= uiCardCloseSection() ?>
            <?= uiCardClose() ?>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <section class="ui-exams-howto">
            <?= uiSeparator() ?>
            <div class="ui-exams-howto-grid">
                <div class="ui-howto-item">
                    <div class="ui-howto-icon"><?= lucide_icon('shopping-bag', ['size' => 18]) ?></div>
                    <strong>1. ซื้อชุดข้อสอบ</strong>
                    <span>เลือกชุดแล้วใส่ตะกร้าชำระเงิน</span>
                </div>
                <div class="ui-howto-item">
                    <div class="ui-howto-icon"><?= lucide_icon('shield-check', ['size' => 18]) ?></div>
                    <strong>2. รอยืนยัน</strong>
                    <span>แอดมินตรวจสลิปแล้วปลดล็อก</span>
                </div>
                <div class="ui-howto-item">
                    <div class="ui-howto-icon"><?= lucide_icon('timer', ['size' => 18]) ?></div>
                    <strong>3. เข้าสนามสอบ</strong>
                    <span>จับเวลา ข้ามข้อ ทบทวนได้</span>
                </div>
                <div class="ui-howto-item">
                    <div class="ui-howto-icon"><?= lucide_icon('badge-check', ['size' => 18]) ?></div>
                    <strong>4. ดูผล + เฉลย</strong>
                    <span>รู้คะแนนทันทีหลังส่ง</span>
                </div>
            </div>
        </section>
    </div>
</main>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
