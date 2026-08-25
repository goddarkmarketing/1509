<?php

declare(strict_types=1);

/**
 * Integration test: ผู้เรียน (สมัคร → แจ้งชำระ) + ผู้ดูแล (ยืนยัน → เปิดสิทธิ์)
 *
 * รัน: php tests/php/integration_flow_test.php
 */

require_once dirname(__DIR__, 2) . '/includes/functions.php';
require_once dirname(__DIR__, 2) . '/includes/student_auth.php';
require_once dirname(__DIR__, 2) . '/includes/checkout_flow.php';
require_once dirname(__DIR__, 2) . '/includes/booking.php';
require_once dirname(__DIR__, 2) . '/includes/cart.php';
require_once dirname(__DIR__, 2) . '/includes/schema.php';
require_once __DIR__ . '/test_helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

test_reset_checks();

foreach (requiredDatabaseTables() as $table) {
    test_check("schema: ตาราง {$table}", databaseTableExists($table));
}
$missing = missingDatabaseTables();
if ($missing) {
    test_print_summary('LMS Integration Flow Test');
    echo "\nหยุด: " . migrationHintMessage($missing) . "\n";
    exit(1);
}

function ensureRecordedTestCourse(): array
{
    $slug = 'e2e-recorded-course';
    $course = getCourseBySlug($slug);
    if ($course) {
        return $course;
    }

    db()->prepare('
        INSERT INTO courses (slug, title, subtitle, description, category, level, price, duration_hours, lesson_count, highlights, is_featured, is_active, sort_order)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 1, 999)
    ')->execute([
        $slug,
        'คอร์ส E2E ทดสอบระบบ (VOD)',
        'สำหรับ automated test',
        'คอร์สทดสอบการสมัครและชำระเงิน',
        'hsk',
        'beginner',
        500,
        1,
        0,
        'ทดสอบระบบ',
    ]);

    return getCourseBySlug($slug) ?: [];
}

$runId = date('ymdHis');
$phone = '09' . substr($runId, -8);
$email = "e2e{$runId}@wenxin-test.local";
$password = 'test1234';
$name = "E2E Tester {$runId}";

// --- ผู้เรียน: สมัครสมาชิก ---
$registerResult = registerStudent($name, $phone, $password, $email);
test_check('สมัครสมาชิกสำเร็จ', $registerResult['ok'], $registerResult['message'] ?? '');
$studentId = (int) ($_SESSION['student_id'] ?? 0);
test_check('มี student_id ใน session', $studentId > 0, "#{$studentId}");

$student = currentStudent();
test_check('โหลดโปรไฟล์นักเรียนได้', $student !== null && ($student['phone'] ?? '') === $phone);

// --- ผู้เรียน: ใส่ตะกร้า + แจ้งโอน ---
$course = ensureRecordedTestCourse();
$courseId = (int) ($course['id'] ?? 0);
test_check('มีคอร์สทดสอบ VOD', $courseId > 0, $course['slug'] ?? '');

$_SESSION['cart_course_ids'] = [$courseId];
test_check('ตะกร้ามีคอร์ส', cartCount() === 1);

$amount = cartTotal();
$note = appendCartIdsToNote('E2E integration test');
$paymentId = insertBankTransferPayment(
    $courseId,
    $name,
    $email,
    $phone,
    $amount,
    date('Y-m-d'),
    '14:30',
    null,
    $note,
    null
);
test_check('บันทึกการแจ้งชำระ (โอนธนาคาร)', $paymentId > 0, "#{$paymentId}");

savePaymentItems($paymentId, cartItems());
enrollStudentInCourses($studentId, [$courseId], 'pending');

$pendingStmt = db()->prepare('SELECT status FROM enrollments WHERE student_id = ? AND course_id = ? LIMIT 1');
$pendingStmt->execute([$studentId, $courseId]);
$pendingStatus = (string) ($pendingStmt->fetchColumn() ?: '');
test_check('สถานะลงทะเบียน = รอตรวจสอบ (pending)', $pendingStatus === 'pending', $pendingStatus);

$payStatusStmt = db()->prepare('SELECT status FROM payments WHERE id = ? LIMIT 1');
$payStatusStmt->execute([$paymentId]);
test_check('สถานะการชำระ = pending', $payStatusStmt->fetchColumn() === 'pending');

// --- ผู้ดูแล: ยืนยันการชำระ ---
$paymentRow = db()->prepare('SELECT * FROM payments WHERE id = ? LIMIT 1');
$paymentRow->execute([$paymentId]);
$payment = $paymentRow->fetch() ?: [];
test_check('ดึงรายการชำระเงินใน admin ได้', $payment !== []);

enrollFromPayment($payment);
db()->prepare('UPDATE payments SET status = "verified" WHERE id = ?')->execute([$paymentId]);

$activeStmt = db()->prepare('SELECT status FROM enrollments WHERE student_id = ? AND course_id = ? LIMIT 1');
$activeStmt->execute([$studentId, $courseId]);
$activeStatus = (string) ($activeStmt->fetchColumn() ?: '');
test_check('หลังยืนยัน: เปิดสิทธิ์เรียน (active)', $activeStatus === 'active', $activeStatus);

// --- duplicate pending payments must not revert active on profile sync ---
$dupPaymentId = insertBankTransferPayment(
    $courseId,
    $name,
    $email,
    $phone,
    $amount,
    date('Y-m-d'),
    '15:00',
    null,
    $note,
    null
);
test_check('สร้างรายการแจ้งชำระซ้ำได้', $dupPaymentId > 0, "#{$dupPaymentId}");
syncEnrollmentsFromPaymentsForStudent($studentId);
$activeStmt->execute([$studentId, $courseId]);
test_check('หลัง sync: ยังคงเปิดสิทธิ์ (active)', $activeStmt->fetchColumn() === 'active');

$payStatusStmt->execute([$paymentId]);
test_check('หลังยืนยัน: สถานะการชำระ = verified', $payStatusStmt->fetchColumn() === 'verified');

$courseIds = getPaymentCourseIds($paymentId);
test_check('payment_items บันทึกคอร์สได้', in_array($courseId, $courseIds, true), implode(',', $courseIds));

$stalePaymentId = insertBankTransferPayment(
    null,
    $name,
    $email,
    $phone,
    $amount,
    date('Y-m-d'),
    '16:00',
    null,
    "stale course test\ncart_ids:999998",
    null
);
test_check('สร้าง payment อ้างคอร์สที่ไม่มีแล้วได้', $stalePaymentId > 0);
$syncStaleOk = true;
try {
    syncEnrollmentsFromPaymentsForStudent($studentId);
} catch (Throwable $e) {
    $syncStaleOk = false;
}
test_check('sync ข้ามคอร์สที่ถูกลบแล้ว', $syncStaleOk);
test_check('filterValidCourseIds ตัด id ปลอม', filterValidCourseIds([$courseId, 999998]) === [$courseId]);

// --- ผู้เรียน: อัปเดตโปรไฟล์ ---
$profileResult = updateStudentProfile($studentId, "{$name} Updated", $email, null);
test_check('แก้ไขโปรไฟล์ได้', $profileResult['ok'], $profileResult['message'] ?? '');

$nameCheck = db()->prepare('SELECT full_name FROM students WHERE id = ? LIMIT 1');
$nameCheck->execute([$studentId]);
test_check('ชื่อโปรไฟล์อัปเดตแล้ว', $nameCheck->fetchColumn() === "{$name} Updated");

// --- สรุป ---
echo "Student: {$phone} / {$email}\n";
echo "Payment: #{$paymentId}\n\n";

$result = test_print_summary('LMS Integration Flow Test');
exit($result['fail'] > 0 ? 1 : 0);
