<?php
declare(strict_types=1);

/**
 * One-shot repair for ม.1 เทอม 2 course after cascade delete.
 * Restores lessons + enrollments onto the live course (id 10 or 7).
 * Open while logged in as admin, then delete this file.
 */

require_once dirname(__DIR__) . '/includes/auth.php';
requireAdmin();

header('Content-Type: text/plain; charset=UTF-8');

$pdo = db();
$pdo->exec('SET NAMES utf8mb4');

echo "=== repair m1-term2 course ===\n";

$pdo->beginTransaction();
try {
    $course = $pdo->query("SELECT id, slug, title FROM courses WHERE slug = 'm1-term2-live' OR title LIKE '%ม.1 เทอม 2%' ORDER BY id DESC LIMIT 1")->fetch();
    if (!$course) {
        $pdo->prepare('
            INSERT INTO courses
                (slug, title, subtitle, description, category, level, price, duration_hours, lesson_count,
                 image_url, highlights, is_featured, is_active, course_type, zoom_url, sort_order)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 1, ?, NULL, ?)
        ')->execute([
            'm1-term2-live',
            'ติวเข้มสอบเข้า ม.1 เทอม 2 (สด)',
            'สังคมศึกษา · รอบสด',
            'ติวพื้นฐานเนื้อวิชา สาระการเรียนรู้ ภูมิศาสตร์ เศรษฐศาสตร์ หน้าที่พลเมือง',
            'exam_prep',
            'intermediate',
            4500,
            35,
            3,
            'uploads/courses/cover_20261005_162344_62d97df0.png',
            '',
            'live',
            2,
        ]);
        $courseId = (int) $pdo->lastInsertId();
        echo "created course #{$courseId}\n";
    } else {
        $courseId = (int) $course['id'];
        $pdo->prepare('UPDATE courses SET slug = ?, title = ?, is_active = 1 WHERE id = ?')->execute([
            'm1-term2-live',
            'ติวเข้มสอบเข้า ม.1 เทอม 2 (สด)',
            $courseId,
        ]);
        echo "using course #{$courseId}\n";
    }

    $lessons = [
        ['เศรษฐศาสตร์ เรื่อง สถาบันการเงิน', 'เศรษฐศาสตร์ เรื่อง สถาบันการเงิน', 'https://youtu.be/O4qxidihlME?si=7YVgQPQ502OZQtmv', 51, 1],
        ['เศรษฐศาสตร์ เรื่อง เศรษฐศาสตร์เบื้องต้น', '', 'https://drive.google.com/file/d/1lsU5P_9QfvXTTFcK0KqLYIfk64HVHBH2/view?usp=drive_link', 90, 0],
        ['เศรษฐศาสตร์ เรื่อง กลไกราคา', '', '', 90, 2],
    ];

    $lessonCount = (int) $pdo->prepare('SELECT COUNT(*) FROM lessons WHERE course_id = ?')->execute([$courseId]) ?: 0;
    $cntStmt = $pdo->prepare('SELECT COUNT(*) FROM lessons WHERE course_id = ?');
    $cntStmt->execute([$courseId]);
    $lessonCount = (int) $cntStmt->fetchColumn();

    if ($lessonCount === 0) {
        $ins = $pdo->prepare('
            INSERT INTO lessons
                (course_id, title, description, video_url, duration_minutes, sort_order, is_free_preview, is_published)
            VALUES (?, ?, ?, ?, ?, ?, 0, 1)
        ');
        foreach ($lessons as $lesson) {
            $ins->execute([$courseId, $lesson[0], $lesson[1], $lesson[2] ?: null, $lesson[3], $lesson[4]]);
            echo "lesson: {$lesson[0]}\n";
        }
    } else {
        echo "lessons already present: {$lessonCount}\n";
    }

    $studentIds = $pdo->query("
        SELECT DISTINCT s.id
        FROM students s
        INNER JOIN payments p ON (
            (p.student_phone IS NOT NULL AND p.student_phone <> '' AND p.student_phone = s.phone)
            OR (p.student_email IS NOT NULL AND p.student_email <> '' AND p.student_email = s.email)
        )
        WHERE p.status = 'verified'
          AND (
            p.course_id IN (7, {$courseId})
            OR p.note LIKE '%cart_ids:7%'
            OR p.note LIKE '%ติวเข้มสอบเข้า ม.1 เทอม 2%'
          )
    ")->fetchAll(PDO::FETCH_COLUMN);

    $enroll = $pdo->prepare("
        INSERT INTO enrollments (student_id, course_id, status, enrolled_at)
        VALUES (?, ?, 'active', NOW())
        ON DUPLICATE KEY UPDATE status = 'active'
    ");
    foreach ($studentIds as $sid) {
        $enroll->execute([(int) $sid, $courseId]);
        echo "enrolled student #{$sid} -> course #{$courseId}\n";
    }

    $pdo->prepare("
        UPDATE payments
        SET course_id = ?
        WHERE (course_id IS NULL OR course_id = 7)
          AND (
            note LIKE '%cart_ids:7%'
            OR note LIKE '%ติวเข้มสอบเข้า ม.1 เทอม 2%'
          )
    ")->execute([$courseId]);

    $pdo->commit();
    echo "DONE course_id={$courseId}\n";
    echo "Delete this file after use: admin/repair_m1_term2_course.php\n";
} catch (Throwable $e) {
    $pdo->rollBack();
    echo 'ERROR: ' . $e->getMessage() . "\n";
    http_response_code(500);
}
