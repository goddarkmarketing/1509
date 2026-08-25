<?php
declare(strict_types=1);

require_once __DIR__ . '/progress.php';
require_once __DIR__ . '/quiz.php';

function certificateRequiresQuiz(): bool
{
    return getSetting('certificate_require_quiz', '0') === '1';
}

function generateCertificateCode(): string
{
    return 'WX' . strtoupper(bin2hex(random_bytes(6)));
}

function getCertificateByCode(string $code): ?array
{
    $stmt = db()->prepare('
        SELECT cert.*, s.full_name, c.title AS course_title, c.subtitle AS course_subtitle
        FROM certificates cert
        JOIN students s ON s.id = cert.student_id
        JOIN courses c ON c.id = cert.course_id
        WHERE cert.certificate_code = ?
        LIMIT 1
    ');
    $stmt->execute([trim($code)]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function getStudentCertificate(int $studentId, int $courseId): ?array
{
    $stmt = db()->prepare('SELECT * FROM certificates WHERE student_id = ? AND course_id = ? LIMIT 1');
    $stmt->execute([$studentId, $courseId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function issueCertificateIfEligible(int $studentId, int $courseId): ?array
{
    $existing = getStudentCertificate($studentId, $courseId);
    if ($existing) {
        return $existing;
    }
    $progress = getCourseProgress($studentId, $courseId);
    if ($progress['percent'] < 100 || $progress['total'] <= 0) {
        return null;
    }
    if (certificateRequiresQuiz() && !studentPassedAllCourseQuizzes($studentId, $courseId)) {
        return null;
    }
    $code = generateCertificateCode();
    $stmt = db()->prepare('INSERT INTO certificates (student_id, course_id, certificate_code) VALUES (?, ?, ?)');
    $stmt->execute([$studentId, $courseId, $code]);
    return getStudentCertificate($studentId, $courseId);
}

function getStudentCertificates(int $studentId): array
{
    try {
        $stmt = db()->prepare('
            SELECT cert.*, c.title AS course_title, c.slug AS course_slug
            FROM certificates cert
            JOIN courses c ON c.id = cert.course_id
            WHERE cert.student_id = ?
            ORDER BY cert.issued_at DESC
        ');
        $stmt->execute([$studentId]);
        return $stmt->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
}
