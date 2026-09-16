<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/exam.php';
requireAdmin();

if (!examTablesReady()) {
    $pageTitle = 'จำลองสนามสอบ';
    require_once dirname(__DIR__) . '/includes/admin_header.php';
    echo '<div class="alert alert-error">ยังไม่มีตาราง exam_* — รัน <code>/database/run_migration_phase12.php</code> ก่อน</div>';
    require_once dirname(__DIR__) . '/includes/admin_footer.php';
    exit;
}

$packId = (int) ($_GET['pack_id'] ?? 0);
$action = $_GET['action'] ?? 'list';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $postAction = $_POST['action'] ?? '';

    if ($postAction === 'save_pack') {
        $id = (int) ($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $slug = trim($_POST['slug'] ?? '') ?: preg_replace('/[^a-z0-9]+/', '-', strtolower($title));
        $slug = trim((string) $slug, '-');
        $subtitle = trim($_POST['subtitle'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $instructions = trim($_POST['instructions'] ?? '');
        $subject = trim($_POST['subject'] ?? 'ทั่วไป') ?: 'ทั่วไป';
        $level = $_POST['level'] ?? 'intermediate';
        if (!in_array($level, ['beginner', 'intermediate', 'advanced'], true)) {
            $level = 'intermediate';
        }
        $price = max(0, (float) ($_POST['price'] ?? 0));
        $timeLimit = max(1, (int) ($_POST['time_limit_minutes'] ?? 60));
        $passScore = max(1, min(100, (int) ($_POST['pass_score'] ?? 50)));
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        $published = isset($_POST['is_published']) ? 1 : 0;
        $featured = isset($_POST['is_featured']) ? 1 : 0;

        if ($title !== '' && $slug !== '') {
            if ($id) {
                $stmt = db()->prepare('
                    UPDATE exam_packs SET slug=?, title=?, subtitle=?, description=?, instructions=?, subject=?, level=?, price=?, time_limit_minutes=?, pass_score=?, sort_order=?, is_published=?, is_featured=?
                    WHERE id=?
                ');
                $stmt->execute([$slug, $title, $subtitle ?: null, $description ?: null, $instructions ?: null, $subject, $level, $price, $timeLimit, $passScore, $sortOrder, $published, $featured, $id]);
            } else {
                $stmt = db()->prepare('
                    INSERT INTO exam_packs (slug, title, subtitle, description, instructions, subject, level, price, time_limit_minutes, pass_score, sort_order, is_published, is_featured)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)
                ');
                $stmt->execute([$slug, $title, $subtitle ?: null, $description ?: null, $instructions ?: null, $subject, $level, $price, $timeLimit, $passScore, $sortOrder, $published, $featured]);
                $id = (int) db()->lastInsertId();
            }
            flash('admin_success', 'บันทึกชุดข้อสอบเรียบร้อย');
            redirect('/admin/exams.php?action=questions&pack_id=' . $id);
        }
        flash('admin_error', 'กรุณากรอกชื่อและ slug');
        redirect('/admin/exams.php?action=add');
    }

    if ($postAction === 'save_question') {
        $qid = (int) ($_POST['question_id'] ?? 0);
        $packIdPost = (int) ($_POST['exam_pack_id'] ?? 0);
        $text = trim($_POST['question_text'] ?? '');
        $correct = trim($_POST['correct_key'] ?? 'A');
        $explanation = trim($_POST['explanation'] ?? '');
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        $options = [];
        foreach (['A', 'B', 'C', 'D'] as $key) {
            $val = trim($_POST['option_' . $key] ?? '');
            if ($val !== '') {
                $options[$key] = $val;
            }
        }
        if ($packIdPost && $text && $options) {
            $json = json_encode($options, JSON_UNESCAPED_UNICODE);
            if ($qid) {
                $stmt = db()->prepare('UPDATE exam_questions SET question_text=?, options_json=?, correct_key=?, explanation=?, sort_order=? WHERE id=?');
                $stmt->execute([$text, $json, $correct, $explanation ?: null, $sortOrder, $qid]);
            } else {
                $stmt = db()->prepare('INSERT INTO exam_questions (exam_pack_id, question_text, options_json, correct_key, explanation, sort_order) VALUES (?,?,?,?,?,?)');
                $stmt->execute([$packIdPost, $text, $json, $correct, $explanation ?: null, $sortOrder]);
            }
            flash('admin_success', 'บันทึกคำถามเรียบร้อย');
        }
        redirect('/admin/exams.php?action=questions&pack_id=' . $packIdPost);
    }

    if ($postAction === 'delete_question') {
        $qid = (int) ($_POST['question_id'] ?? 0);
        $packIdPost = (int) ($_POST['exam_pack_id'] ?? 0);
        if ($qid) {
            db()->prepare('DELETE FROM exam_questions WHERE id = ?')->execute([$qid]);
            flash('admin_success', 'ลบคำถามแล้ว');
        }
        redirect('/admin/exams.php?action=questions&pack_id=' . $packIdPost);
    }

    if ($postAction === 'delete_pack') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id) {
            db()->prepare('DELETE FROM exam_packs WHERE id = ?')->execute([$id]);
            flash('admin_success', 'ลบชุดข้อสอบแล้ว');
        }
        redirect('/admin/exams.php');
    }
}

$pageTitle = 'จำลองสนามสอบ';
require_once dirname(__DIR__) . '/includes/admin_header.php';

$message = flash('admin_success');
$errorMessage = flash('admin_error');
$packs = getExamPacks(false);
$editPack = $packId && $action === 'edit' ? getExamPackById($packId) : null;
$managePack = $packId && $action === 'questions' ? getExamPackById($packId) : null;
$questions = $managePack ? getExamQuestions($packId) : [];
$editQuestionId = (int) ($_GET['qid'] ?? 0);
$editQuestion = null;
if ($editQuestionId && $managePack) {
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

<?php if ($action === 'add' || $editPack): ?>
<div class="admin-card">
    <div class="admin-card-header">
        <h2><?= $editPack ? 'แก้ไขชุดข้อสอบ' : 'เพิ่มชุดข้อสอบ' ?></h2>
        <a href="<?= APP_URL ?>/admin/exams.php" class="btn btn-secondary btn-sm">กลับ</a>
    </div>
    <div class="admin-card-body">
        <form method="post">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="save_pack">
            <?php if ($editPack): ?><input type="hidden" name="id" value="<?= (int) $editPack['id'] ?>"><?php endif; ?>
            <div class="form-group">
                <label>ชื่อชุดข้อสอบ *</label>
                <input type="text" name="title" class="form-control" required value="<?= e($editPack['title'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Slug *</label>
                <input type="text" name="slug" class="form-control" value="<?= e($editPack['slug'] ?? '') ?>" placeholder="เว้นว่างให้สร้างอัตโนมัติ">
            </div>
            <div class="form-group">
                <label>คำโปรย</label>
                <input type="text" name="subtitle" class="form-control" value="<?= e($editPack['subtitle'] ?? '') ?>">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>วิชา / หมวด</label>
                    <input type="text" name="subject" class="form-control" value="<?= e($editPack['subject'] ?? 'คณิตศาสตร์') ?>">
                </div>
                <div class="form-group">
                    <label>ระดับ</label>
                    <select name="level" class="form-control">
                        <?php foreach (['beginner' => 'เริ่มต้น', 'intermediate' => 'กลาง', 'advanced' => 'ขั้นสูง'] as $val => $label): ?>
                        <option value="<?= $val ?>" <?= ($editPack['level'] ?? 'intermediate') === $val ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>ราคา (บาท)</label>
                    <input type="number" name="price" class="form-control" min="0" step="0.01" value="<?= e((string) ($editPack['price'] ?? '199')) ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>เวลาสอบ (นาที)</label>
                    <input type="number" name="time_limit_minutes" class="form-control" min="1" value="<?= (int) ($editPack['time_limit_minutes'] ?? 60) ?>">
                </div>
                <div class="form-group">
                    <label>เกณฑ์ผ่าน (%)</label>
                    <input type="number" name="pass_score" class="form-control" min="1" max="100" value="<?= (int) ($editPack['pass_score'] ?? 50) ?>">
                </div>
                <div class="form-group">
                    <label>ลำดับ</label>
                    <input type="number" name="sort_order" class="form-control" value="<?= (int) ($editPack['sort_order'] ?? 0) ?>">
                </div>
            </div>
            <div class="form-group">
                <label>รายละเอียด</label>
                <textarea name="description" class="form-control" rows="4"><?= e($editPack['description'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label>คำแนะนำก่อนสอบ</label>
                <textarea name="instructions" class="form-control" rows="3"><?= e($editPack['instructions'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label><input type="checkbox" name="is_published" <?= ($editPack['is_published'] ?? 1) ? 'checked' : '' ?>> เผยแพร่</label>
                <label style="margin-left:1rem"><input type="checkbox" name="is_featured" <?= ($editPack['is_featured'] ?? 0) ? 'checked' : '' ?>> แนะนำ</label>
            </div>
            <div class="admin-form-actions">
                <button type="submit" class="btn btn-primary">บันทึก</button>
            </div>
        </form>
    </div>
</div>

<?php elseif ($managePack): ?>
<div class="admin-card">
    <div class="admin-card-header">
        <h2>คำถาม: <?= e($managePack['title']) ?></h2>
        <div>
            <a href="<?= APP_URL ?>/admin/exams.php?action=edit&pack_id=<?= $packId ?>" class="btn btn-secondary btn-sm">แก้ไขชุด</a>
            <a href="<?= APP_URL ?>/admin/exams.php" class="btn btn-secondary btn-sm">กลับ</a>
        </div>
    </div>
    <div class="admin-card-body">
        <form method="post" class="admin-subform-panel">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="save_question">
            <input type="hidden" name="exam_pack_id" value="<?= (int) $packId ?>">
            <?php if ($editQuestion): ?><input type="hidden" name="question_id" value="<?= (int) $editQuestion['id'] ?>"><?php endif; ?>
            <div class="form-group">
                <label>คำถาม *</label>
                <textarea name="question_text" class="form-control" required><?= e($editQuestion['question_text'] ?? '') ?></textarea>
            </div>
            <?php $opts = $editQuestion ? parseExamQuestionOptions($editQuestion) : []; ?>
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
                    <input type="number" name="sort_order" class="form-control" value="<?= (int) ($editQuestion['sort_order'] ?? count($questions) + 1) ?>">
                </div>
            </div>
            <div class="form-group">
                <label>คำอธิบายเฉลย</label>
                <textarea name="explanation" class="form-control"><?= e($editQuestion['explanation'] ?? '') ?></textarea>
            </div>
            <div class="admin-form-actions admin-form-actions--compact">
                <button type="submit" class="btn btn-primary btn-sm"><?= $editQuestion ? 'อัปเดตคำถาม' : 'เพิ่มคำถาม' ?></button>
                <?php if ($editQuestion): ?>
                <a href="<?= APP_URL ?>/admin/exams.php?action=questions&pack_id=<?= $packId ?>" class="btn btn-secondary btn-sm">ยกเลิกแก้ไข</a>
                <?php endif; ?>
            </div>
        </form>

        <div class="table-responsive">
        <table class="data-table">
            <thead><tr><th>#</th><th>คำถาม</th><th>คำตอบ</th><th class="actions">จัดการ</th></tr></thead>
            <tbody>
                <?php foreach ($questions as $i => $q): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= e($q['question_text']) ?></td>
                    <td><?= e($q['correct_key']) ?></td>
                    <td class="actions">
                        <div class="table-actions">
                        <a href="<?= APP_URL ?>/admin/exams.php?action=questions&pack_id=<?= $packId ?>&qid=<?= (int) $q['id'] ?>" class="btn btn-secondary btn-sm">แก้ไข</a>
                        <form method="post" onsubmit="return confirm('ลบคำถาม?')">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="delete_question">
                            <input type="hidden" name="exam_pack_id" value="<?= $packId ?>">
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
        <h2>ชุดข้อสอบจำลองสนามสอบ</h2>
        <a href="<?= APP_URL ?>/admin/exams.php?action=add" class="btn btn-primary btn-sm">เพิ่มชุดข้อสอบ</a>
    </div>
    <div class="admin-card-body is-flush">
        <?php if ($packs): ?>
        <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr><th>ชื่อ</th><th>วิชา</th><th>ข้อ</th><th>ราคา</th><th>สถานะ</th><th class="actions">จัดการ</th></tr>
            </thead>
            <tbody>
                <?php foreach ($packs as $p): ?>
                <tr>
                    <td><?= e($p['title']) ?></td>
                    <td><?= e($p['subject']) ?></td>
                    <td><?= (int) ($p['question_count'] ?? 0) ?></td>
                    <td><?= e(formatPrice((float) $p['price'])) ?></td>
                    <td><?= $p['is_published'] ? 'เผยแพร่' : 'ซ่อน' ?></td>
                    <td class="actions">
                        <div class="table-actions">
                        <a href="<?= APP_URL ?>/admin/exams.php?action=questions&pack_id=<?= (int) $p['id'] ?>" class="btn btn-outline btn-sm">คำถาม</a>
                        <a href="<?= APP_URL ?>/admin/exams.php?action=edit&pack_id=<?= (int) $p['id'] ?>" class="btn btn-secondary btn-sm">แก้ไข</a>
                        <a href="<?= APP_URL ?>/public/exam.php?slug=<?= urlencode($p['slug']) ?>" class="btn btn-secondary btn-sm" target="_blank">ดู</a>
                        <form method="post" onsubmit="return confirm('ลบชุดข้อสอบนี้?')">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="delete_pack">
                            <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
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
        <p class="table-empty">ยังไม่มีชุดข้อสอบ — <a href="<?= APP_URL ?>/admin/exams.php?action=add">เพิ่มชุดแรก</a> หรือรัน seed</p>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php require_once dirname(__DIR__) . '/includes/admin_footer.php'; ?>
