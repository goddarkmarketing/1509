<?php
declare(strict_types=1);

require_once __DIR__ . '/student_auth.php';

function studentHasCourseAccess(int $studentId, int $courseId): bool
{
    if ($studentId <= 0 || $courseId <= 0) {
        return false;
    }
    $stmt = db()->prepare('
        SELECT id FROM enrollments
        WHERE student_id = ? AND course_id = ? AND status IN ("active", "completed")
        LIMIT 1
    ');
    $stmt->execute([$studentId, $courseId]);
    return (bool) $stmt->fetchColumn();
}

function canAccessLesson(array $lesson): bool
{
    if (empty($lesson['is_published'])) {
        return false;
    }
    if (!empty($lesson['is_free_preview'])) {
        return true;
    }
    $student = currentStudent();
    if (!$student) {
        return false;
    }
    return studentHasCourseAccess((int) $student['id'], (int) ($lesson['course_id'] ?? 0));
}

function getLessonWithCourse(int $lessonId): ?array
{
    $stmt = db()->prepare('
        SELECT l.*, c.slug AS course_slug, c.title AS course_title, c.category AS course_category
        FROM lessons l
        JOIN courses c ON c.id = l.course_id
        WHERE l.id = ? LIMIT 1
    ');
    $stmt->execute([$lessonId]);
    $row = $stmt->fetch();
    return $row ?: null;
}
