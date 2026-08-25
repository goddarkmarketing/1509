<?php
declare(strict_types=1);

function syncCourseLessonCount(int $courseId): void
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM lessons WHERE course_id = ? AND is_published = 1');
    $stmt->execute([$courseId]);
    $count = (int) $stmt->fetchColumn();
    $upd = db()->prepare('UPDATE courses SET lesson_count = ? WHERE id = ?');
    $upd->execute([$count, $courseId]);
}

function markLessonComplete(int $studentId, int $lessonId, int $courseId): bool
{
    if ($studentId <= 0 || $lessonId <= 0 || $courseId <= 0) {
        return false;
    }
    $stmt = db()->prepare('
        INSERT INTO lesson_progress (student_id, lesson_id, course_id)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE completed_at = CURRENT_TIMESTAMP
    ');
    $stmt->execute([$studentId, $lessonId, $courseId]);
    return true;
}

function getCompletedLessonIds(int $studentId, int $courseId): array
{
    $stmt = db()->prepare('SELECT lesson_id FROM lesson_progress WHERE student_id = ? AND course_id = ?');
    $stmt->execute([$studentId, $courseId]);
    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

function isLessonCompleted(int $studentId, int $lessonId): bool
{
    $stmt = db()->prepare('SELECT id FROM lesson_progress WHERE student_id = ? AND lesson_id = ? LIMIT 1');
    $stmt->execute([$studentId, $lessonId]);
    return (bool) $stmt->fetchColumn();
}

function getCourseProgress(int $studentId, int $courseId): array
{
    $totalStmt = db()->prepare('SELECT COUNT(*) FROM lessons WHERE course_id = ? AND is_published = 1');
    $totalStmt->execute([$courseId]);
    $total = (int) $totalStmt->fetchColumn();

    $doneStmt = db()->prepare('SELECT COUNT(*) FROM lesson_progress WHERE student_id = ? AND course_id = ?');
    $doneStmt->execute([$studentId, $courseId]);
    $done = (int) $doneStmt->fetchColumn();

    $percent = $total > 0 ? (int) round(($done / $total) * 100) : 0;

    return ['total' => $total, 'done' => $done, 'percent' => min(100, $percent)];
}

function getCourseProgressForStudent(int $studentId, array $courseIds): array
{
    $result = [];
    foreach ($courseIds as $courseId) {
        $result[(int) $courseId] = getCourseProgress($studentId, (int) $courseId);
    }
    return $result;
}

function maybeMarkEnrollmentCompleted(int $studentId, int $courseId): bool
{
    if ($studentId <= 0 || $courseId <= 0) {
        return false;
    }

    $progress = getCourseProgress($studentId, $courseId);
    if ($progress['percent'] < 100 || $progress['total'] <= 0) {
        return false;
    }

    $stmt = db()->prepare('
        UPDATE enrollments SET status = "completed"
        WHERE student_id = ? AND course_id = ? AND status = "active"
    ');
    $stmt->execute([$studentId, $courseId]);
    return $stmt->rowCount() > 0;
}
