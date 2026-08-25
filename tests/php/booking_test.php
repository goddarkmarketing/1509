<?php

declare(strict_types=1);

/**
 * Booking module tests — ทุกฟังก์ชันใน includes/booking.php
 *
 * รัน: php tests/php/booking_test.php
 */

require_once dirname(__DIR__, 2) . '/includes/functions.php';
require_once dirname(__DIR__, 2) . '/includes/booking.php';
require_once dirname(__DIR__, 2) . '/includes/schema.php';
require_once __DIR__ . '/test_helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

test_reset_checks();

// --- Pure helpers (ไม่ต้องใช้ DB) ---
test_check('courseTypeLabel(live)', courseTypeLabel('live') === 'คลาสออนไลน์ (Live)');
test_check('courseTypeLabel(recorded)', courseTypeLabel('recorded') === 'เรียนวิดีโอ');
test_check('isLiveCourse(live)', isLiveCourse(['course_type' => 'live']));
test_check('isLiveCourse(hybrid)', isLiveCourse(['course_type' => 'hybrid']));
test_check('isLiveCourse(recorded)', !isLiveCourse(['course_type' => 'recorded']));
test_check('getCourseZoomUrl', getCourseZoomUrl(['zoom_url' => 'https://zoom.us/j/1']) === 'https://zoom.us/j/1');
test_check('getCourseZoomUrl empty', getCourseZoomUrl(['zoom_url' => '']) === null);

$sessionSample = ['starts_at' => '2026-07-10 10:00:00', 'ends_at' => '2026-07-10 11:30:00'];
test_check('formatSessionRange', str_contains(formatSessionRange($sessionSample), '10/07/2026'));
test_check('formatSessionDateLabel', str_contains(formatSessionDateLabel($sessionSample), '10/07/2026'));
test_check('formatSessionTimeLabel', str_contains(formatSessionTimeLabel($sessionSample), '10:00'));
test_check('sessionSeatsStatus ok', sessionSeatsStatus(10, 20) === 'ok');
test_check('sessionSeatsStatus low', sessionSeatsStatus(2, 20) === 'low');
test_check('bookingStatusLabel', bookingStatusLabel('confirmed') === 'ยืนยันแล้ว');
test_check('bookingStatusBadgeClass', bookingStatusBadgeClass('pending') === 'badge-pending');

$vodCourse = ['course_type' => 'recorded', 'is_active' => 1];
$liveCourseRow = ['course_type' => 'live', 'is_active' => 1];
$futureSession = [
    'course_id' => 1,
    'status' => 'scheduled',
    'starts_at' => date('Y-m-d H:i:s', strtotime('+2 days 10:00')),
    'ends_at' => date('Y-m-d H:i:s', strtotime('+2 days 11:30')),
    'booked_count' => 0,
    'capacity' => 20,
];
test_check('visibility: live future', getSessionStudentVisibilityReason($futureSession, $liveCourseRow) === null);
test_check('visibility: recorded course', getSessionStudentVisibilityReason($futureSession, $vodCourse) === 'คอร์สไม่ใช่ประเภท Live/Hybrid');
$pastSession = $futureSession;
$pastSession['starts_at'] = date('Y-m-d H:i:s', strtotime('-1 hour'));
test_check('visibility: past session', getSessionStudentVisibilityReason($pastSession, $liveCourseRow) === 'เลยเวลาเริ่มแล้ว');
test_check('sessionStatusLabel', sessionStatusLabel('scheduled') === 'เปิดจอง');

$liveCourse = ['slug' => 'live-hsk-demo-test', 'zoom_url' => 'https://zoom.us/j/course'];
$confirmedBooking = ['status' => 'confirmed', 'zoom_url' => 'https://zoom.us/j/session'];
test_check('courseLiveStartUrl zoom', courseLiveStartUrl($liveCourse, $confirmedBooking) === 'https://zoom.us/j/session');
test_check('courseLiveStartLabel zoom', courseLiveStartLabel($liveCourse, $confirmedBooking) === 'เข้า Zoom');
test_check('courseLiveStartOpensInNewTab', courseLiveStartOpensInNewTab($liveCourse, $confirmedBooking));
test_check('courseLiveStartUrl no booking', str_contains(courseLiveStartUrl($liveCourse, null), 'book.php'));

test_check('getSessionZoomUrl session', getSessionZoomUrl(['zoom_url' => 'https://zoom.us/j/s']) === 'https://zoom.us/j/s');
test_check('getSessionZoomUrl fallback course', getSessionZoomUrl(['zoom_url' => ''], ['zoom_url' => 'https://zoom.us/j/c']) === 'https://zoom.us/j/c');

$_SESSION['cart_session_map'] = [];
test_check('getCartSessionMap empty', getCartSessionMap() === []);
setCartSessionForCourse(5, 9);
test_check('setCartSessionForCourse', getCartSessionMap() === [5 => 9]);
removeCartSessionForCourse(5);
test_check('removeCartSessionForCourse', getCartSessionMap() === []);
clearCartSessions();
test_check('clearCartSessions', !isset($_SESSION['cart_session_map']));

$note = appendSessionMapToNote('test note', [3 => 7, 4 => 8]);
test_check('appendSessionMapToNote', str_contains($note, 'session_map:3:7,4:8'));
$parsed = parseSessionMapFromNote($note);
test_check('parseSessionMapFromNote', $parsed === [3 => 7, 4 => 8]);
test_check('parseSessionMapFromNote empty', parseSessionMapFromNote('no map') === []);

// --- DB-dependent ---
if (!databaseSchemaReady()) {
    test_check('ข้าม DB tests', false, migrationHintMessage(missingDatabaseTables()));
    $result = test_print_summary('LMS Booking Test');
    exit($result['fail'] > 0 ? 1 : 0);
}

function ensureLiveTestCourse(): array
{
    $slug = 'e2e-live-course';
    $course = getCourseBySlug($slug);
    if ($course) {
        return $course;
    }

    db()->prepare('
        INSERT INTO courses (slug, title, subtitle, description, category, level, price, duration_hours, lesson_count, highlights, is_featured, is_active, sort_order, course_type)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 1, 998, "live")
    ')->execute([
        $slug,
        'คอร์ส E2E Live ทดสอบ',
        'สำหรับ automated test',
        'ทดสอบจองรอบเรียน',
        'hsk',
        'beginner',
        990,
        1,
        0,
        'ทดสอบ Live',
    ]);

    return getCourseBySlug($slug) ?: [];
}

function ensureTestStudent(): int
{
    $phone = '0899990001';
    $stmt = db()->prepare('SELECT id FROM students WHERE phone = ? LIMIT 1');
    $stmt->execute([$phone]);
    $id = (int) ($stmt->fetchColumn() ?: 0);
    if ($id > 0) {
        return $id;
    }

    db()->prepare('
        INSERT INTO students (full_name, phone, email, password_hash)
        VALUES (?, ?, ?, ?)
    ')->execute([
        'Booking Test Student',
        $phone,
        'booking-test@wenxin-test.local',
        password_hash('test1234', PASSWORD_DEFAULT),
    ]);

    return (int) db()->lastInsertId();
}

$course = ensureLiveTestCourse();
$courseId = (int) ($course['id'] ?? 0);
test_check('มีคอร์ส Live ทดสอบ', $courseId > 0, "#{$courseId}");

$startsAt = date('Y-m-d H:i:s', strtotime('+3 days 10:00'));
$endsAt = date('Y-m-d H:i:s', strtotime('+3 days 11:30'));
$sessionTitle = 'E2E Session ' . date('His');

db()->prepare('
    INSERT INTO course_sessions (course_id, title, starts_at, ends_at, capacity, zoom_url, status)
    VALUES (?, ?, ?, ?, 5, ?, "scheduled")
')->execute([$courseId, $sessionTitle, $startsAt, $endsAt, 'https://zoom.us/j/e2e-test']);
$sessionId = (int) db()->lastInsertId();
test_check('สร้างรอบเรียน (admin + เพิ่มรอบเรียน)', $sessionId > 0, "#{$sessionId}");

$loaded = getSessionById($sessionId);
test_check('getSessionById', $loaded !== null && (int) $loaded['course_id'] === $courseId);

$available = getAvailableSessions($courseId);
test_check('getAvailableSessions', count($available) >= 1);
test_check('sessionHasCapacity', sessionHasCapacity($sessionId));

$upcoming = getUpcomingSessions(5);
test_check('getUpcomingSessions', count($upcoming) >= 1);

$studentId = ensureTestStudent();
test_check('มีนักเรียนทดสอบ', $studentId > 0, "#{$studentId}");

$bookingId = createSessionBooking($sessionId, $studentId, null, 'pending');
test_check('createSessionBooking', $bookingId !== null && $bookingId > 0, "#{$bookingId}");

$dupBookingId = createSessionBooking($sessionId, $studentId, null, 'pending');
test_check('createSessionBooking idempotent', $dupBookingId === $bookingId);

confirmSessionBooking((int) $bookingId);
$statusStmt = db()->prepare('SELECT status FROM session_bookings WHERE id = ?');
$statusStmt->execute([$bookingId]);
test_check('confirmSessionBooking', $statusStmt->fetchColumn() === 'confirmed');

$studentBookings = getStudentBookings($studentId);
test_check('getStudentBookings', count($studentBookings) >= 1);

$courseBooking = getStudentBookingForCourse($studentId, $courseId);
test_check('getStudentBookingForCourse', $courseBooking !== null);

$adminBookings = getAdminBookings('confirmed', 10);
test_check('getAdminBookings', count($adminBookings) >= 1);

$stats = getBookingReportStats();
test_check('getBookingReportStats confirmed', $stats['bookings_confirmed'] >= 1);
test_check('getBookingReportStats upcoming', $stats['sessions_upcoming'] >= 1);

$revenue = getCourseRevenueReport(5);
test_check('getCourseRevenueReport', is_array($revenue));

$summary = formatPaymentSessionTimeLabel($note);
test_check('formatPaymentSessionTimeLabel from note', is_string($summary));

// cleanup booking for capacity test
cancelSessionBooking((int) $bookingId);
$statusStmt->execute([$bookingId]);
test_check('cancelSessionBooking', $statusStmt->fetchColumn() === 'cancelled');

// update + delete session (admin CRUD)
db()->prepare('
    UPDATE course_sessions SET title = ?, capacity = 8 WHERE id = ?
')->execute(['E2E Updated', $sessionId]);
$updated = getSessionById($sessionId);
test_check('อัปเดตรอบเรียน', ($updated['title'] ?? '') === 'E2E Updated');

db()->prepare('DELETE FROM course_sessions WHERE id = ?')->execute([$sessionId]);
test_check('ลบรอบเรียน', getSessionById($sessionId) === null);

// --- sync from payment note (stale session id fallback) ---
$syncCourse = ensureLiveTestCourse();
$syncCourseId = (int) $syncCourse['id'];
$syncStarts = date('Y-m-d H:i:s', strtotime('+5 days 14:00'));
$syncEnds = date('Y-m-d H:i:s', strtotime('+5 days 15:30'));
db()->prepare('
    INSERT INTO course_sessions (course_id, title, starts_at, ends_at, capacity, status)
    VALUES (?, ?, ?, ?, 5, "scheduled")
')->execute([$syncCourseId, 'Sync Test Session', $syncStarts, $syncEnds]);
$validSessionId = (int) db()->lastInsertId();
$staleSessionId = 999991;
$syncNote = appendSessionMapToNote('sync test', [$syncCourseId => $staleSessionId]);
db()->prepare('
    INSERT INTO payments (course_id, student_name, student_email, student_phone, amount, note, status, payment_method)
    VALUES (?, ?, ?, ?, ?, ?, "verified", "transfer")
')->execute([
    $syncCourseId,
    'Sync Test',
    'sync-booking-test@wenxin-test.local',
    '0899990099',
    990,
    $syncNote,
]);
$syncPaymentId = (int) db()->lastInsertId();
$syncedCount = syncSessionBookingsFromPayment($syncPaymentId);
test_check('syncSessionBookingsFromPayment จาก note', $syncedCount >= 1, "#{$syncPaymentId}");
$syncBookings = getBookingsByPaymentId($syncPaymentId);
test_check('sync ใช้รอบเรียนที่มีจริง', (int) ($syncBookings[0]['session_id'] ?? 0) > 0 && getSessionById((int) $syncBookings[0]['session_id']) !== null);

$result = test_print_summary('LMS Booking Test');
exit($result['fail'] > 0 ? 1 : 0);
