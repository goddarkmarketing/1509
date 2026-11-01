<?php
declare(strict_types=1);
/** @var array $student */
/** @var int $studentId */
/** @var array $enrolled */
/** @var array $progressMap */
/** @var array $certByCourse */
/** @var array $bookingsByCourse */
$bookingsByCourse = $bookingsByCourse ?? [];
if (!function_exists('isLiveCourse')) {
    require_once dirname(__DIR__) . '/booking.php';
}
?>
<div class="account-panel-head">
    <div>
        <h1>คอร์สของฉัน</h1>
        <p class="account-panel-desc">
            กด <strong>บทเรียน</strong> เพื่อเข้าเรียนได้ทันที
            <?php if ($activeCourseCount > 0): ?> · เปิดสิทธิ์แล้ว <?= (int) $activeCourseCount ?> คอร์ส<?php endif; ?>
            <?php if ($pendingCourseCount > 0): ?> · รอตรวจสอบ <?= (int) $pendingCourseCount ?><?php endif; ?>
        </p>
    </div>
    <?php if ($enrolled): ?>
    <a href="<?= APP_URL ?>/public/courses.php" class="btn btn-outline btn-sm">เลือกคอร์สเพิ่ม</a>
    <?php endif; ?>
</div>

<?php if ($enrolled): ?>
<ul class="my-courses-list">
    <?php foreach ($enrolled as $course): ?>
    <?php
        $cid = (int) $course['id'];
        $enrollmentStatus = (string) ($course['status'] ?? 'active');
        $isPending = $enrollmentStatus === 'pending';
        $prog = $isPending
            ? ['percent' => 0, 'done' => 0, 'total' => 0]
            : ($progressMap[$cid] ?? ['percent' => 0, 'done' => 0, 'total' => 0]);
        $lessonId = getFirstLessonIdForCourse($cid);
        $courseBooking = $bookingsByCourse[$cid] ?? null;
        $isLiveCourseItem = isLiveCourse($course);
        $hasLessonProgress = (int) ($prog['total'] ?? 0) > 0 || (bool) $lessonId;
        $lessonsUrl = APP_URL . '/public/course.php?slug=' . urlencode((string) $course['slug']) . '#curriculum';
        if ($lessonId && !$isLiveCourseItem) {
            // Recorded: jump straight into first lesson for fewer clicks
            $lessonsUrl = APP_URL . '/public/lesson.php?lesson_id=' . $lessonId;
        } elseif ($isLiveCourseItem && !$lessonId) {
            $lessonsUrl = APP_URL . '/public/course.php?slug=' . urlencode((string) $course['slug']) . '#schedule';
        }
        $liveUrl = null;
        $liveLabel = '';
        $liveNewTab = false;
        if (!$isPending && $isLiveCourseItem) {
            $liveUrl = courseLiveStartUrl($course, $courseBooking);
            $liveLabel = courseLiveStartLabel($course, $courseBooking);
            $liveNewTab = courseLiveStartOpensInNewTab($course, $courseBooking);
        }
        $quizzes = $isPending ? [] : getQuizzesByCourse($cid);
        $games = $isPending ? [] : getGamesByCourse($cid);
        $cert = $certByCourse[$cid] ?? null;
        if (!$isPending && $prog['percent'] >= 100) {
            maybeMarkEnrollmentCompleted($studentId, $cid);
        }
        if (!$isPending && !$cert && $prog['percent'] >= 100) {
            $cert = issueCertificateIfEligible($studentId, $cid);
            if ($cert) {
                $certByCourse[$cid] = $cert;
            }
        }
    ?>
    <li class="my-courses-item<?= $isPending ? ' my-courses-item--pending' : '' ?>">
        <div class="my-courses-item-cover">
            <a href="<?= e($lessonsUrl) ?>">
                <img src="<?= e(courseCoverUrl($course)) ?>" alt="<?= e($course['title']) ?>" loading="lazy">
            </a>
        </div>
        <div class="my-courses-item-body">
            <div class="my-courses-item-head">
                <h2><a href="<?= e($lessonsUrl) ?>" class="my-courses-title-link"><?= e($course['title']) ?></a></h2>
            </div>
            <?php if (!empty($course['subtitle']) && !$isPending): ?>
            <p class="my-courses-item-sub"><?= e($course['subtitle']) ?></p>
            <?php endif; ?>
            <?php if ($isPending): ?>
            <p class="my-courses-item-meta my-courses-item-meta--pending">
                <?= lucide_icon('clock', ['size' => 14]) ?>
                <span>รอตรวจสอบการชำระเงิน<?php if ($isLiveCourseItem && $courseBooking): ?> · <?= e(formatSessionRange($courseBooking)) ?><?php endif; ?></span>
            </p>
            <?php else: ?>
            <?php if ($isLiveCourseItem): ?>
            <p class="my-courses-item-meta">
                <span class="my-courses-meta-live">Live</span>
                <?php if ($courseBooking): ?>
                · <?= e(formatSessionRange($courseBooking)) ?>
                <?php else: ?>
                · ยังไม่ได้จองรอบ
                <?php endif; ?>
            </p>
            <?php endif; ?>
            <?php if ($hasLessonProgress): ?>
            <div class="course-progress-bar-wrap course-progress-bar-wrap--compact">
                <div class="course-progress-label">
                    <span>ความคืบหน้า</span>
                    <strong><?= (int) $prog['percent'] ?>%</strong>
                </div>
                <div class="course-progress-bar" role="progressbar" aria-valuenow="<?= (int) $prog['percent'] ?>" aria-valuemin="0" aria-valuemax="100">
                    <span style="width:<?= (int) $prog['percent'] ?>%"></span>
                </div>
                <small><?= (int) $prog['done'] ?> / <?= (int) $prog['total'] ?> บท</small>
            </div>
            <?php if ($quizzes): ?>
            <div class="my-courses-quizzes">
                <span class="my-courses-quizzes-label">แบบทดสอบ:</span>
                <?php foreach ($quizzes as $qz): ?>
                <a href="<?= APP_URL ?>/public/quiz.php?quiz_id=<?= (int) $qz['id'] ?>" class="my-courses-quiz-link"><?= e($qz['title']) ?></a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <?php elseif ($isLiveCourseItem): ?>
            <p class="my-courses-item-meta">เข้าห้องเรียนสดตามรอบที่จอง หรือเปิดบทเรียนถ้ามีเนื้อหาวิดีโอ</p>
            <?php else: ?>
            <p class="my-courses-item-meta">ยังไม่มีบทเรียนเผยแพร่ — กดปุ่มบทเรียนเพื่อดูหน้าคอร์ส</p>
            <?php endif; ?>
            <?php endif; ?>
        </div>
        <div class="my-courses-item-actions">
            <?php if ($isPending): ?>
            <span class="my-courses-badge my-courses-badge--pending">รอตรวจสอบ</span>
            <?php else: ?>
            <span class="my-courses-badge my-courses-badge--active">เปิดสิทธิ์แล้ว</span>
            <a href="<?= e($lessonsUrl) ?>" class="btn btn-primary btn-sm">บทเรียน</a>
            <?php if ($liveUrl): ?>
            <a href="<?= e($liveUrl) ?>" class="btn btn-outline btn-sm"<?= $liveNewTab ? ' target="_blank" rel="noopener"' : '' ?>><?= e($liveLabel) ?></a>
            <?php endif; ?>
            <?php if ($cert): ?>
            <a href="<?= APP_URL ?>/public/certificate.php?code=<?= urlencode($cert['certificate_code']) ?>" class="btn btn-outline btn-sm" target="_blank">ใบประกาศ</a>
            <?php elseif ($prog['percent'] >= 100 && (!certificateRequiresQuiz() || studentPassedAllCourseQuizzes($studentId, $cid))): ?>
            <a href="<?= APP_URL ?>/public/certificate.php?course_id=<?= $cid ?>" class="btn btn-outline btn-sm">รับใบประกาศ</a>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </li>
    <?php endforeach; ?>
</ul>
<?php else: ?>
<div class="cart-page-empty account-empty-state">
    <p>ยังไม่มีคอร์สในบัญชีของคุณ หากชำระเงินแล้วให้เข้าสู่ระบบด้วยเบอร์โทรหรืออีเมลที่ใช้แจ้งชำระเงิน หรือเลือกคอร์สใหม่ได้เลย</p>
    <a href="<?= APP_URL ?>/public/courses.php" class="btn btn-primary">เลือกคอร์สเรียน</a>
</div>
<?php endif; ?>
