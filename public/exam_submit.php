<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/student_auth.php';
require_once dirname(__DIR__) . '/includes/exam.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/public/exams.php');
}

verifyCsrf();
requireStudentLogin();

$packId = (int) ($_POST['exam_pack_id'] ?? 0);
$pack = $packId > 0 ? getExamPackById($packId) : null;
$student = currentStudent();

if (!$pack || !studentHasExamAccess((int) $student['id'], $packId)) {
    redirect('/public/exams.php');
}

$questions = getExamQuestions($packId);
$answers = [];
foreach ($questions as $q) {
    $qid = (int) $q['id'];
    $answers[$qid] = trim((string) ($_POST['answer_' . $qid] ?? ''));
}

$result = gradeExamAttempt($questions, $answers);
$passed = $result['score'] >= (int) $pack['pass_score'];
$timeSpent = max(0, (int) ($_POST['time_spent_seconds'] ?? 0));
$startedAt = trim((string) ($_POST['started_at'] ?? ''));
if ($startedAt !== '' && !preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $startedAt)) {
    $startedAt = '';
}

$attemptId = saveExamAttempt(
    (int) $student['id'],
    $packId,
    $result['score'],
    $result['correct'],
    $result['total'],
    $passed,
    $answers,
    $timeSpent,
    $startedAt !== '' ? $startedAt : null
);

$_SESSION['exam_result'] = [
    'attempt_id' => $attemptId,
    'exam_pack_id' => $packId,
    'slug' => $pack['slug'],
    'title' => $pack['title'],
    'score' => $result['score'],
    'correct' => $result['correct'],
    'total' => $result['total'],
    'passed' => $passed,
    'pass_score' => (int) $pack['pass_score'],
    'time_spent_seconds' => $timeSpent,
    'detail' => $result['detail'],
];

redirect('/public/exam_result.php');
