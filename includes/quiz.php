<?php
declare(strict_types=1);

require_once __DIR__ . '/access.php';

function getQuizzesByCourse(int $courseId, bool $publishedOnly = true): array
{
    try {
        $sql = 'SELECT * FROM quizzes WHERE course_id = ?';
        if ($publishedOnly) {
            $sql .= ' AND is_published = 1';
        }
        $sql .= ' ORDER BY sort_order ASC, id ASC';
        $stmt = db()->prepare($sql);
        $stmt->execute([$courseId]);
        return $stmt->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
}

function getQuizById(int $quizId): ?array
{
    $stmt = db()->prepare('SELECT * FROM quizzes WHERE id = ? LIMIT 1');
    $stmt->execute([$quizId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function getQuizQuestions(int $quizId): array
{
    $stmt = db()->prepare('SELECT * FROM quiz_questions WHERE quiz_id = ? ORDER BY sort_order ASC, id ASC');
    $stmt->execute([$quizId]);
    return $stmt->fetchAll();
}

function getQuizQuestionById(int $questionId): ?array
{
    $stmt = db()->prepare('SELECT * FROM quiz_questions WHERE id = ? LIMIT 1');
    $stmt->execute([$questionId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function quizQuestionHasAudio(array $question): bool
{
    return trim((string) ($question['audio_url'] ?? '')) !== '';
}

function quizQuestionAudioUrl(array $question): ?string
{
    if (!quizQuestionHasAudio($question)) {
        return null;
    }
    return APP_URL . '/public/quiz_audio.php?qid=' . (int) ($question['id'] ?? 0);
}

function studentCanTakeQuiz(int $studentId, array $quiz): bool
{
    return studentHasCourseAccess($studentId, (int) ($quiz['course_id'] ?? 0));
}

function getBestQuizAttempt(int $studentId, int $quizId): ?array
{
    $stmt = db()->prepare('
        SELECT * FROM quiz_attempts
        WHERE student_id = ? AND quiz_id = ?
        ORDER BY score DESC, completed_at DESC
        LIMIT 1
    ');
    $stmt->execute([$studentId, $quizId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function gradeQuizAttempt(array $questions, array $answers): array
{
    $correct = 0;
    $total = count($questions);
    $detail = [];
    foreach ($questions as $q) {
        $qid = (int) $q['id'];
        $chosen = $answers[$qid] ?? '';
        $isCorrect = $chosen !== '' && $chosen === ($q['correct_key'] ?? '');
        if ($isCorrect) {
            $correct++;
        }
        $detail[$qid] = $isCorrect;
    }
    $score = $total > 0 ? (int) round(($correct / $total) * 100) : 0;
    return ['score' => $score, 'correct' => $correct, 'total' => $total, 'detail' => $detail];
}

function saveQuizAttempt(int $studentId, int $quizId, int $score, int $total, bool $passed, array $answers): void
{
    $stmt = db()->prepare('
        INSERT INTO quiz_attempts (student_id, quiz_id, score, total_questions, passed, answers_json)
        VALUES (?, ?, ?, ?, ?, ?)
    ');
    $stmt->execute([
        $studentId,
        $quizId,
        $score,
        $total,
        $passed ? 1 : 0,
        json_encode($answers, JSON_UNESCAPED_UNICODE),
    ]);
}

function parseQuestionOptions(array $question): array
{
    $opts = json_decode($question['options_json'] ?? '{}', true);
    return is_array($opts) ? $opts : [];
}

function studentPassedAllCourseQuizzes(int $studentId, int $courseId): bool
{
    $quizzes = getQuizzesByCourse($courseId, true);
    if ($quizzes === []) {
        return true;
    }
    foreach ($quizzes as $quiz) {
        $best = getBestQuizAttempt($studentId, (int) $quiz['id']);
        if (!$best || !(int) $best['passed']) {
            return false;
        }
    }
    return true;
}
