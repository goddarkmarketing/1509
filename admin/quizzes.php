<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/quiz.php';
require_once dirname(__DIR__) . '/includes/media_upload.php';
requireAdmin();

$filterCourse = (int) ($_GET['course_id'] ?? 0);
$quizId = (int) ($_GET['quiz_id'] ?? 0);
$action = $_GET['action'] ?? 'list';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $postAction = $_POST['action'] ?? '';

    if ($postAction === 'save_quiz') {
        $id = (int) ($_POST['id'] ?? 0);
        $courseId = (int) ($_POST['course_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $passScore = max(1, min(100, (int) ($_POST['pass_score'] ?? 70)));
        $timeLimit = max(0, (int) ($_POST['time_limit_minutes'] ?? 0));
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        $published = isset($_POST['is_published']) ? 1 : 0;
        if ($courseId && $title) {
            if ($id) {
                $stmt = db()->prepare('UPDATE quizzes SET course_id=?, title=?, description=?, pass_score=?, time_limit_minutes=?, sort_order=?, is_published=? WHERE id=?');
                $stmt->execute([$courseId, $title, $description ?: null, $passScore, $timeLimit, $sortOrder, $published, $id]);
            } else {
                $stmt = db()->prepare('INSERT INTO quizzes (course_id, title, description, pass_score, time_limit_minutes, sort_order, is_published) VALUES (?,?,?,?,?,?,?)');
                $stmt->execute([$courseId, $title, $description ?: null, $passScore, $timeLimit, $sortOrder, $published]);
                $id = (int) db()->lastInsertId();
            }
            flash('admin_success', 'บันทึกแบบทดสอบเรียบร้อย');
            redirect('/admin/quizzes.php?action=questions&quiz_id=' . $id);
        }
    }

    if ($postAction === 'save_question') {
        $qid = (int) ($_POST['question_id'] ?? 0);
        $quizIdPost = (int) ($_POST['quiz_id'] ?? 0);
        $text = trim($_POST['question_text'] ?? '');
        $correct = trim($_POST['correct_key'] ?? 'A');
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        $removeAudio = isset($_POST['remove_audio']);
        $options = [];
        foreach (['A', 'B', 'C', 'D'] as $key) {
            $val = trim($_POST['option_' . $key] ?? '');
            if ($val !== '') {
                $options[$key] = $val;
            }
        }

        $existing = $qid > 0 ? getQuizQuestionById($qid) : null;
        $audioUrl = $existing['audio_url'] ?? null;

        $uploaded = storeQuizAudioUpload($_FILES['audio_file'] ?? ['error' => UPLOAD_ERR_NO_FILE]);
        if ($uploaded === false) {
            redirect('/admin/quizzes.php?action=questions&quiz_id=' . $quizIdPost . ($qid ? '&qid=' . $qid : ''));
        }
        if ($uploaded !== null) {
            if ($audioUrl) {
                deleteQuizAudioFile($audioUrl);
            }
            $audioUrl = $uploaded;
        } elseif ($removeAudio && $audioUrl) {
            deleteQuizAudioFile($audioUrl);
            $audioUrl = null;
        }

        if ($quizIdPost && $text && $options) {
            $json = json_encode($options, JSON_UNESCAPED_UNICODE);
            if ($qid) {
                $stmt = db()->prepare('UPDATE quiz_questions SET question_text=?, audio_url=?, options_json=?, correct_key=?, sort_order=? WHERE id=?');
                $stmt->execute([$text, $audioUrl, $json, $correct, $sortOrder, $qid]);
            } else {
                $stmt = db()->prepare('INSERT INTO quiz_questions (quiz_id, question_text, audio_url, options_json, correct_key, sort_order) VALUES (?,?,?,?,?,?)');
                $stmt->execute([$quizIdPost, $text, $audioUrl, $json, $correct, $sortOrder]);
            }
            flash('admin_success', 'บันทึกคำถามเรียบร้อย');
        }
        redirect('/admin/quizzes.php?action=questions&quiz_id=' . $quizIdPost);
    }

    if ($postAction === 'delete_question') {
        $qid = (int) ($_POST['question_id'] ?? 0);
        $quizIdPost = (int) ($_POST['quiz_id'] ?? 0);
        if ($qid) {
            $existing = getQuizQuestionById($qid);
            if ($existing) {
                deleteQuizAudioFile($existing['audio_url'] ?? null);
            }
            db()->prepare('DELETE FROM quiz_questions WHERE id = ?')->execute([$qid]);
            flash('admin_success', 'ลบคำถามแล้ว');
        }
        redirect('/admin/quizzes.php?action=questions&quiz_id=' . $quizIdPost);
    }

    if ($postAction === 'delete_quiz') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id) {
            foreach (getQuizQuestions($id) as $q) {
                deleteQuizAudioFile($q['audio_url'] ?? null);
            }
            db()->prepare('DELETE FROM quizzes WHERE id = ?')->execute([$id]);
            flash('admin_success', 'ลบแบบทดสอบแล้ว');
        }
        redirect('/admin/quizzes.php');
    }
}

$pageTitle = 'แบบทดสอบ';
require_once dirname(__DIR__) . '/includes/admin_header.php';

$message = flash('admin_success');
$errorMessage = flash('admin_error');

$courses = getCourses(null, false);
$quizzes = [];
$sql = 'SELECT q.*, c.title AS course_title FROM quizzes q JOIN courses c ON c.id = q.course_id';
$params = [];
if ($filterCourse) {
    $sql .= ' WHERE q.course_id = ?';
    $params[] = $filterCourse;
}
$sql .= ' ORDER BY q.course_id, q.sort_order, q.id';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$quizzes = $stmt->fetchAll();

$editQuiz = $quizId && $action === 'edit' ? getQuizById($quizId) : null;
$manageQuiz = $quizId && $action === 'questions' ? getQuizById($quizId) : null;
$questions = $manageQuiz ? getQuizQuestions($quizId) : [];
$editQuestionId = (int) ($_GET['qid'] ?? 0);
$editQuestion = null;
if ($editQuestionId && $manageQuiz) {
    foreach ($questions as $q) {
        if ((int) $q['id'] === $editQuestionId) {
            $editQuestion = $q;
            break;
        }
    }
}
?>

<?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
<?php if ($errorMessage): ?><div class="alert alert-error"><?= e($errorMessage) ?></div><?php endif; ?>

<?php if ($action === 'add' || $editQuiz): ?>
<div class="admin-card">
    <div class="admin-card-header">
        <h2><?= $editQuiz ? 'แก้ไขแบบทดสอบ' : 'เพิ่มแบบทดสอบ' ?></h2>
        <a href="<?= APP_URL ?>/admin/quizzes.php" class="btn btn-secondary btn-sm">กลับ</a>
    </div>
    <div class="admin-card-body">
        <form method="post">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="save_quiz">
            <?php if ($editQuiz): ?><input type="hidden" name="id" value="<?= (int) $editQuiz['id'] ?>"><?php endif; ?>
            <div class="form-group">
                <label>คอร์ส *</label>
                <select name="course_id" class="form-control" required>
                    <?php foreach ($courses as $c): ?>
                    <option value="<?= (int) $c['id'] ?>" <?= (($editQuiz['course_id'] ?? $filterCourse) == $c['id']) ? 'selected' : '' ?>><?= e($c['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>ชื่อแบบทดสอบ *</label>
                <input type="text" name="title" class="form-control" required value="<?= e($editQuiz['title'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>รายละเอียด</label>
                <textarea name="description" class="form-control"><?= e($editQuiz['description'] ?? '') ?></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>คะแนนผ่าน (%)</label>
                    <input type="number" name="pass_score" class="form-control" min="1" max="100" value="<?= (int) ($editQuiz['pass_score'] ?? 70) ?>">
                </div>
                <div class="form-group">
                    <label>จำกัดเวลา (นาที, 0=ไม่จำกัด)</label>
                    <input type="number" name="time_limit_minutes" class="form-control" min="0" value="<?= (int) ($editQuiz['time_limit_minutes'] ?? 0) ?>">
                </div>
                <div class="form-group">
                    <label>ลำดับ</label>
                    <input type="number" name="sort_order" class="form-control" value="<?= (int) ($editQuiz['sort_order'] ?? 0) ?>">
                </div>
            </div>
            <div class="form-group">
                <label><input type="checkbox" name="is_published" <?= ($editQuiz['is_published'] ?? 1) ? 'checked' : '' ?>> เผยแพร่</label>
            </div>
            <div class="admin-form-actions">
                <button type="submit" class="btn btn-primary">บันทึก</button>
            </div>
        </form>
    </div>
</div>

<?php elseif ($manageQuiz): ?>
<div class="admin-card">
    <div class="admin-card-header">
        <h2>คำถาม: <?= e($manageQuiz['title']) ?></h2>
        <a href="<?= APP_URL ?>/admin/quizzes.php" class="btn btn-secondary btn-sm">กลับ</a>
    </div>
    <div class="admin-card-body">
        <form method="post" class="admin-subform-panel" enctype="multipart/form-data">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="save_question">
            <input type="hidden" name="quiz_id" value="<?= (int) $quizId ?>">
            <?php if ($editQuestion): ?><input type="hidden" name="question_id" value="<?= (int) $editQuestion['id'] ?>"><?php endif; ?>
            <div class="form-group">
                <label>คำถาม *</label>
                <textarea name="question_text" class="form-control" required><?= e($editQuestion['question_text'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label>ไฟล์เสียง (ทักษะฟัง — ไม่บังคับ)</label>
                <input type="file" name="audio_file" class="form-control" accept=".mp3,.wav,.ogg,.m4a,.aac,audio/*">
                <small class="form-hint">รองรับ MP3, WAV, OGG, M4A, AAC สูงสุด 15MB</small>
                <?php if ($editQuestion && quizQuestionHasAudio($editQuestion)): ?>
                <div class="quiz-admin-audio-preview" style="margin-top:.75rem">
                    <audio controls preload="metadata" src="<?= e(quizQuestionAudioUrl($editQuestion) ?? '') ?>" style="width:100%;max-width:420px"></audio>
                    <label style="display:block;margin-top:.5rem">
                        <input type="checkbox" name="remove_audio" value="1"> ลบไฟล์เสียงออกจากคำถามนี้
                    </label>
                </div>
                <?php endif; ?>
            </div>
            <?php $opts = $editQuestion ? parseQuestionOptions($editQuestion) : []; ?>
            <?php foreach (['A', 'B', 'C', 'D'] as $key): ?>
            <div class="form-group">
                <label>ตัวเลือก <?= $key ?></label>
                <input type="text" name="option_<?= $key ?>" class="form-control" value="<?= e($opts[$key] ?? '') ?>">
            </div>
            <?php endforeach; ?>
            <div class="form-row">
                <div class="form-group">
                    <label>คำตอบที่ถูก</label>
                    <select name="correct_key" class="form-control">
                        <?php foreach (['A','B','C','D'] as $key): ?>
                        <option value="<?= $key ?>" <?= ($editQuestion['correct_key'] ?? 'A') === $key ? 'selected' : '' ?>><?= $key ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>ลำดับ</label>
                    <input type="number" name="sort_order" class="form-control" value="<?= (int) ($editQuestion['sort_order'] ?? 0) ?>">
                </div>
            </div>
            <div class="admin-form-actions admin-form-actions--compact">
            <button type="submit" class="btn btn-primary btn-sm"><?= $editQuestion ? 'อัปเดตคำถาม' : 'เพิ่มคำถาม' ?></button>
            <?php if ($editQuestion): ?>
            <a href="<?= APP_URL ?>/admin/quizzes.php?action=questions&quiz_id=<?= $quizId ?>" class="btn btn-secondary btn-sm">ยกเลิกแก้ไข</a>
            <?php endif; ?>
            </div>
        </form>

        <div class="table-responsive">
        <table class="data-table">
            <thead><tr><th>#</th><th>คำถาม</th><th>เสียง</th><th>คำตอบ</th><th class="actions">จัดการ</th></tr></thead>
            <tbody>
                <?php foreach ($questions as $i => $q): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= e($q['question_text']) ?></td>
                    <td><?= quizQuestionHasAudio($q) ? 'มี' : '-' ?></td>
                    <td><?= e($q['correct_key']) ?></td>
                    <td class="actions">
                        <div class="table-actions">
                        <a href="<?= APP_URL ?>/admin/quizzes.php?action=questions&quiz_id=<?= $quizId ?>&qid=<?= (int) $q['id'] ?>" class="btn btn-secondary btn-sm">แก้ไข</a>
                        <form method="post" onsubmit="return confirm('ลบคำถาม?')">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="delete_question">
                            <input type="hidden" name="quiz_id" value="<?= $quizId ?>">
                            <input type="hidden" name="question_id" value="<?= (int) $q['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm">ลบ</button>
                        </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php if (!$questions): ?><p class="table-empty">ยังไม่มีคำถาม</p><?php endif; ?>
    </div>
</div>

<?php else: ?>
<div class="admin-card">
    <div class="admin-card-header">
        <h2>แบบทดสอบ (Quiz)</h2>
        <a href="<?= APP_URL ?>/admin/quizzes.php?action=add" class="btn btn-primary btn-sm">เพิ่มแบบทดสอบ</a>
    </div>
    <div class="admin-card-toolbar">
        <form method="get" class="admin-inline-form">
            <select name="course_id" class="form-control">
                <option value="0">ทุกคอร์ส</option>
                <?php foreach ($courses as $c): ?>
                <option value="<?= (int) $c['id'] ?>" <?= $filterCourse === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['title']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-secondary btn-sm">กรอง</button>
        </form>
    </div>
    <div class="admin-card-body is-flush">
        <?php if ($quizzes): ?>
        <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr><th>คอร์ส</th><th>ชื่อ</th><th>ผ่าน</th><th>สถานะ</th><th class="actions">จัดการ</th></tr>
            </thead>
            <tbody>
                <?php foreach ($quizzes as $q): ?>
                <tr>
                    <td><?= e($q['course_title']) ?></td>
                    <td><?= e($q['title']) ?></td>
                    <td><?= (int) $q['pass_score'] ?>%</td>
                    <td><?= $q['is_published'] ? 'เผยแพร่' : 'ซ่อน' ?></td>
                    <td class="actions">
                        <div class="table-actions">
                        <a href="<?= APP_URL ?>/admin/quizzes.php?action=questions&quiz_id=<?= (int) $q['id'] ?>" class="btn btn-outline btn-sm">คำถาม</a>
                        <a href="<?= APP_URL ?>/admin/quizzes.php?action=edit&quiz_id=<?= (int) $q['id'] ?>" class="btn btn-secondary btn-sm">แก้ไข</a>
                        <form method="post" onsubmit="return confirm('ลบแบบทดสอบ?')">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="delete_quiz">
                            <input type="hidden" name="id" value="<?= (int) $q['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm">ลบ</button>
                        </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php else: ?>
        <p class="table-empty">ยังไม่มีแบบทดสอบ — <a href="<?= APP_URL ?>/admin/quizzes.php?action=add">เพิ่มแบบทดสอบ</a></p>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php require_once dirname(__DIR__) . '/includes/admin_footer.php'; ?>
