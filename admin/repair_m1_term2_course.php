<?php
declare(strict_types=1);

/**
 * One-shot repair: restore ม.1 เทอม 2 course as id=7 with fixed slug,
 * lessons, and enrollments after accidental delete.
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
    // Prefer keeping public slug m1-term2-live on canonical id=7.
    $existing7 = $pdo->query('SELECT id, slug, title FROM courses WHERE id = 7')->fetch();
    $dup = $pdo->query("SELECT id, slug, title FROM courses WHERE slug = 'm1-term2-live'")->fetch();

    if (!$existing7) {
        if ($dup && (int) $dup['id'] !== 7) {
            // Move row 10 (or other) content onto id=7, then drop duplicate.
            $srcId = (int) $dup['id'];
            $pdo->prepare('DELETE FROM courses WHERE id = ?')->execute([$srcId]);
            echo "removed duplicate course #{$srcId}\n";
        }

        $pdo->prepare('
            INSERT INTO courses
                (id, slug, title, subtitle, description, category, level, price, duration_hours, lesson_count,
                 image_url, highlights, is_featured, is_active, course_type, zoom_url, sort_order)
            VALUES
                (7, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 1, ?, NULL, ?)
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
        echo "inserted course #7 m1-term2-live\n";
    } else {
        $pdo->prepare('UPDATE courses SET slug = ?, title = ?, is_active = 1 WHERE id = 7')->execute([
            'm1-term2-live',
            'ติวเข้มสอบเข้า ม.1 เทอม 2 (สด)',
        ]);
        echo "updated existing course #7\n";
        if ($dup && (int) $dup['id'] !== 7) {
            $pdo->prepare('DELETE FROM courses WHERE id = ?')->execute([(int) $dup['id']]);
            echo "removed duplicate #" . $dup['id'] . "\n";
        }
    }

    $lessons = [
        [
            'title' => 'เศรษฐศาสตร์ เรื่อง สถาบันการเงิน',
            'description' => 'เศรษฐศาสตร์ เรื่อง สถาบันการเงิน',
            'video_url' => 'https://youtu.be/O4qxidihlME?si=7YVgQPQ502OZQtmv',
            'duration' => 51,
            'sort' => 1,
        ],
        [
            'title' => 'เศรษฐศาสตร์ เรื่อง เศรษฐศาสตร์เบื้องต้น',
            'description' => '',
            'video_url' => 'https://drive.google.com/file/d/1lsU5P_9QfvXTTFcK0KqLYIfk64HVHBH2/view?usp=drive_link',
            'duration' => 90,
            'sort' => 0,
        ],
        [
            'title' => 'เศรษฐศาสตร์ เรื่อง กลไกราคา',
            'description' => '',
            'video_url' => '',
            'duration' => 90,
            'sort' => 2,
        ],
    ];

    $lessonCount = (int) $pdo->query('SELECT COUNT(*) FROM lessons WHERE course_id = 7')->fetchColumn();
    if ($lessonCount === 0) {
        $ins = $pdo->prepare('
            INSERT INTO lessons
                (course_id, title, description, video_url, duration_minutes, sort_order, is_free_preview, is_published)
            VALUES (7, ?, ?, ?, ?, ?, 0, 1)
        ');
        foreach ($lessons as $lesson) {
            $ins->execute([
                $lesson['title'],
                $lesson['description'],
                $lesson['video_url'],
                $lesson['duration'],
                $lesson['sort'],
            ]);
            echo "lesson: {$lesson['title']}\n";
        }
    } else {
        echo "lessons already present: {$lessonCount}\n";
    }

    // Restore enrollments for students who paid for course 7 / cart note.
    $studentIds = $pdo->query("
        SELECT DISTINCT s.id
        FROM students s
        INNER JOIN payments p ON (
            (p.student_phone IS NOT NULL AND p.student_phone = s.phone)
            OR (p.student_email IS NOT NULL AND p.student_email <> '' AND p.student_email = s.email)
        )
        WHERE p.status = 'verified'
          AND (
            p.course_id = 7
            OR p.note LIKE '%cart_ids:7%'
            OR p.note LIKE '%ติวเข้มสอบเข้า ม.1 เทอม 2%'
          )
    ")->fetchAll(PDO::FETCH_COLUMN);

    $enroll = $pdo->prepare("
        INSERT INTO enrollments (student_id, course_id, status, enrolled_at)
        VALUES (?, 7, 'active', NOW())
        ON DUPLICATE KEY UPDATE status = 'active'
    ");
    foreach ($studentIds as $sid) {
        $enroll->execute([(int) $sid]);
        echo "enrolled student #{$sid}\n";
    }

    // Point orphaned payments back to course 7 when note mentions it.
    $pdo->exec("
        UPDATE payments
        SET course_id = 7
        WHERE course_id IS NULL
          AND (
            note LIKE '%cart_ids:7%'
            OR note LIKE '%ติวเข้มสอบเข้า ม.1 เทอม 2%'
          )
    ");

    $pdo->commit();
    echo "DONE\n";
    echo "Delete this file after use: admin/repair_m1_term2_course.php\n";
} catch (Throwable $e) {
    $pdo->rollBack();
    echo 'ERROR: ' . $e->getMessage() . "\n";
    http_response_code(500);
}
