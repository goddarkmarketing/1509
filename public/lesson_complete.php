<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/access.php';
require_once dirname(__DIR__) . '/includes/progress.php';
require_once dirname(__DIR__) . '/includes/certificate.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/public/courses.php');
}

verifyCsrf();
requireStudentLogin();

$lessonId = (int) ($_POST['lesson_id'] ?? 0);
$return = trim($_POST['return'] ?? '');
$lesson = $lessonId > 0 ? getLessonWithCourse($lessonId) : null;

if (!$lesson || !canAccessLesson($lesson)) {
    flash('payment_error', 'ไม่สามารถบันทึกความคืบหน้าได้');
    redirect(isSafeLocalReturn($return) ? $return : '/public/my-courses.php');
}

$student = currentStudent();
$courseId = (int) $lesson['course_id'];
markLessonComplete((int) $student['id'], $lessonId, $courseId);
maybeMarkEnrollmentCompleted((int) $student['id'], $courseId);

$cert = issueCertificateIfEligible((int) $student['id'], $courseId);
if ($cert) {
    flash('payment_success', 'เรียนจบบทนี้แล้ว และได้รับใบประกาศนียบัตร! ดูได้ที่คอร์สของฉัน');
} else {
    flash('payment_success', 'บันทึกว่าเรียนจบบทนี้แล้ว');
}

redirect(isSafeLocalReturn($return) ? $return : '/public/lesson.php?lesson_id=' . $lessonId);
