<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/booking.php';
require_once dirname(__DIR__) . '/includes/checkout_flow.php';
require_once dirname(__DIR__) . '/includes/line_messaging.php';

header('Content-Type: text/plain; charset=utf-8');

$checks = [];
$slug = 'live-hsk-demo-test';
$email = 'demo1234@gmail.com';
$zoomUrl = 'https://zoom.us/j/12345678901?pwd=demo';

function check(string $label, bool $ok, string $detail = ''): void
{
    global $checks;
    $checks[] = ['label' => $label, 'ok' => $ok, 'detail' => $detail];
}

// --- ensure course + session ---
$course = getCourseBySlug($slug);
if (!$course) {
    db()->prepare('
        INSERT INTO courses (slug, title, subtitle, description, category, level, price, duration_hours, lesson_count, highlights, is_featured, is_active, course_type, zoom_url, sort_order)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 1, ?, ?, 0)
    ')->execute([
        $slug, 'คอร์ส Live ทดสอบระบบจอง (Demo)', 'ทดสอบ flow จองคลาส + LINE + Zoom',
        'คอร์สสำหรับทดสอบการจองคลาส Live แบบ end-to-end', 'hsk', 'beginner', 990, 2, 0,
        'จองรอบเรียน|แจ้งเตือน LINE|ลิงก์ Zoom', 'live', $zoomUrl,
    ]);
    $course = getCourseBySlug($slug);
}
$courseId = (int) $course['id'];
db()->prepare('UPDATE courses SET course_type=?, is_active=1, zoom_url=? WHERE id=?')->execute(['live', $zoomUrl, $courseId]);

$starts = date('Y-m-d H:i:s', strtotime('+3 days 10:00'));
$ends = date('Y-m-d H:i:s', strtotime('+3 days 11:30'));
$sStmt = db()->prepare('SELECT id FROM course_sessions WHERE course_id=? AND title=? LIMIT 1');
$sStmt->execute([$courseId, 'รอบทดสอบ Demo']);
$sessionId = (int) ($sStmt->fetchColumn() ?: 0);
if ($sessionId <= 0) {
    db()->prepare('INSERT INTO course_sessions (course_id, title, starts_at, ends_at, capacity, zoom_url, status) VALUES (?,?,?,?,?,?,"scheduled")')
        ->execute([$courseId, 'รอบทดสอบ Demo', $starts, $ends, 10, $zoomUrl]);
    $sessionId = (int) db()->lastInsertId();
} else {
    db()->prepare('UPDATE course_sessions SET starts_at=?, ends_at=?, zoom_url=?, status="scheduled", course_id=? WHERE id=?')
        ->execute([$starts, $ends, $zoomUrl, $courseId, $sessionId]);
}

$studentStmt = db()->prepare('SELECT * FROM students WHERE email=? LIMIT 1');
$studentStmt->execute([$email]);
$student = $studentStmt->fetch();
check('Student demo1234@gmail.com exists', (bool) $student, $student ? "#{$student['id']}" : '');
$studentId = (int) ($student['id'] ?? 0);

// --- reset prior demo booking for clean test ---
db()->prepare('UPDATE session_bookings SET status="cancelled" WHERE student_id=? AND session_id=? AND status IN ("pending","confirmed")')
    ->execute([$studentId, $sessionId]);
db()->prepare('UPDATE course_sessions SET booked_count = 0 WHERE id=? AND booked_count > 0')->execute([$sessionId]);

// --- Phase A: pending ---
$sessionMap = [$courseId => $sessionId];
$note = appendSessionMapToNote('cart_ids:' . $courseId, $sessionMap);
$payStmt = db()->prepare('
    INSERT INTO payments (course_id, student_name, student_email, student_phone, amount, note, status, payment_method)
    VALUES (?, ?, ?, ?, ?, ?, "pending", "transfer")
');
$payStmt->execute([$courseId, $student['full_name'], $email, $student['phone'], 990, $note]);
$paymentId = (int) db()->lastInsertId();
savePaymentItems($paymentId, [['id' => $courseId, 'title' => $course['title'], 'price' => 990]]);
enrollStudentInCourses($studentId, [$courseId], 'pending');
createBookingsForPayment($paymentId, $studentId, array_values($sessionMap), 'pending');

$pendingBooking = getStudentBookingForCourse($studentId, $courseId);
check('Pending booking created', ($pendingBooking['status'] ?? '') === 'pending');
check('Payment session summary', formatPaymentSessionSummary($note, $paymentId) !== '');
check('isLiveCourse', isLiveCourse($course));
check('courseBookOrBuyUrl points to book.php', str_contains(courseBookOrBuyUrl($course), 'book.php'));

// --- Phase B: approve ---
$pStmt = db()->prepare('SELECT * FROM payments WHERE id=?');
$pStmt->execute([$paymentId]);
enrollFromPayment($pStmt->fetch());
db()->prepare('UPDATE payments SET status="verified" WHERE id=?')->execute([$paymentId]);

$confirmedBooking = getStudentBookingForCourse($studentId, $courseId);
check('Booking confirmed after approve', ($confirmedBooking['status'] ?? '') === 'confirmed');
$zoom = $confirmedBooking ? getSessionZoomUrl($confirmedBooking, $confirmedBooking) : null;
check('Zoom URL resolves', $zoom === $zoomUrl, (string) $zoom);

$enr = db()->prepare('SELECT status FROM enrollments WHERE student_id=? AND course_id=?');
$enr->execute([$studentId, $courseId]);
check('Enrollment active', $enr->fetchColumn() === 'active');

check('LINE linked', trim((string) ($student['line_user_id'] ?? '')) !== '');
check('getBookingsByPaymentId', count(getBookingsByPaymentId($paymentId)) >= 1);

echo "=== Booking Flow Test ===\n\n";
$pass = 0;
$fail = 0;
foreach ($checks as $c) {
    $icon = $c['ok'] ? 'PASS' : 'FAIL';
    if ($c['ok']) {
        $pass++;
    } else {
        $fail++;
    }
    echo "[{$icon}] {$c['label']}";
    if ($c['detail'] !== '') {
        echo " — {$c['detail']}";
    }
    echo "\n";
}

echo "\nURLs:\n";
echo "Course: /public/course.php?slug={$slug}\n";
echo "Book: /public/book.php?course={$slug}\n";
echo "Profile bookings: /public/profile.php?tab=bookings\n";
echo "Admin payments: /admin/payments.php#payment-{$paymentId}\n";
echo "Admin bookings: /admin/bookings.php\n";
echo "\nResult: {$pass} passed, {$fail} failed\n";

exit($fail > 0 ? 1 : 0);
