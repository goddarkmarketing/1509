<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/student_auth.php';
require_once dirname(__DIR__) . '/includes/quiz.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/public/my-courses.php');
}

verifyCsrf();
requireStudentLogin();

$quizId = (int) ($_POST['quiz_id'] ?? 0);
$quiz = $quizId > 0 ? getQuizById($quizId) : null;
$student = currentStudent();

if (!$quiz || !studentCanTakeQuiz((int) $student['id'], $quiz)) {
    redirect('/public/my-courses.php');
}

$questions = getQuizQuestions($quizId);
$answers = [];
foreach ($questions as $q) {
    $qid = (int) $q['id'];
    $answers[$qid] = trim($_POST['answer_' . $qid] ?? '');
}

$result = gradeQuizAttempt($questions, $answers);
$passed = $result['score'] >= (int) $quiz['pass_score'];
saveQuizAttempt((int) $student['id'], $quizId, $result['score'], $result['total'], $passed, $answers);

$_SESSION['quiz_result'] = [
    'quiz_id' => $quizId,
    'score' => $result['score'],
    'correct' => $result['correct'],
    'total' => $result['total'],
    'passed' => $passed,
    'pass_score' => (int) $quiz['pass_score'],
    'title' => $quiz['title'],
];

redirect('/public/quiz_result.php');
