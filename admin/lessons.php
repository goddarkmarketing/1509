<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
requireAdmin();
require_once dirname(__DIR__) . '/includes/media_upload.php';
require_once dirname(__DIR__) . '/includes/progress.php';

if (isset($_GET['clear_filter'])) {
    unset($_SESSION['admin_lessons_filter_course']);
    redirect('/admin/lessons.php');
}

if (isset($_GET['course_id']) && $_GET['course_id'] !== '') {
    $_SESSION['admin_lessons_filter_course'] = (int) $_GET['course_id'];
}

$filterCourse = (int) ($_SESSION['admin_lessons_filter_course'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $postAction = $_POST['action'] ?? '';
    $courseId = (int) ($_POST['course_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $videoUrl = trim($_POST['video_url'] ?? '');
    $documentUrl = trim($_POST['document_url'] ?? '');
    $duration = (int) ($_POST['duration_minutes'] ?? 0);
    $sortOrder = (int) ($_POST['sort_order'] ?? 0);
    $isPreview = isset($_POST['is_free_preview']) ? 1 : 0;
    $isPublished = isset($_POST['is_published']) ? 1 : 0;
    $editId = (int) ($_POST['id'] ?? 0);

    if (!empty($_POST['filter_course_id'])) {
        $_SESSION['admin_lessons_filter_course'] = (int) $_POST['filter_course_id'];
        $filterCourse = (int) $_POST['filter_course_id'];
    }

    if (!empty($_FILES['document_file']['name'])) {
        $uploaded = storeLessonDocumentUpload($_FILES['document_file']);
        if ($uploaded === false) {
            redirect('/admin/lessons.php?action=' . ($editId ? 'edit&id=' . $editId : 'add') . ($filterCourse ? '&course_id=' . $filterCourse : ''));
        }
        if (is_string($uploaded)) {
            $documentUrl = $uploaded;
        }
    }

    try {
        if ($postAction === 'create' && $courseId && $title) {
            $stmt = db()->prepare('
                INSERT INTO lessons (course_id, title, description, video_url, document_url, duration_minutes, sort_order, is_free_preview, is_published)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([$courseId, $title, $description, $videoUrl ?: null, $documentUrl ?: null, $duration, $sortOrder, $isPreview, $isPublished]);
            syncCourseLessonCount($courseId);
            flash('admin_success', 'เพิ่มบทเรียนเรียบร้อย');
        } elseif ($postAction === 'update' && $editId) {
            $oldCourseStmt = db()->prepare('SELECT course_id FROM lessons WHERE id = ?');
            $oldCourseStmt->execute([$editId]);
            $oldCourseId = (int) $oldCourseStmt->fetchColumn();
            $stmt = db()->prepare('
                UPDATE lessons SET course_id=?, title=?, description=?, video_url=?, document_url=?, duration_minutes=?, sort_order=?, is_free_preview=?, is_published=?
                WHERE id=?
            ');
            $stmt->execute([$courseId, $title, $description, $videoUrl ?: null, $documentUrl ?: null, $duration, $sortOrder, $isPreview, $isPublished, $editId]);
            syncCourseLessonCount($courseId);
            if ($oldCourseId && $oldCourseId !== $courseId) {
                syncCourseLessonCount($oldCourseId);
            }
            flash('admin_success', 'อัปเดตบทเรียนเรียบร้อย');
        } elseif ($postAction === 'delete' && $editId) {
            $oldCourseStmt = db()->prepare('SELECT course_id FROM lessons WHERE id = ?');
            $oldCourseStmt->execute([$editId]);
            $oldCourseId = (int) $oldCourseStmt->fetchColumn();
            $stmt = db()->prepare('DELETE FROM lessons WHERE id = ?');
            $stmt->execute([$editId]);
            if ($oldCourseId) {
                syncCourseLessonCount($oldCourseId);
            }
            flash('admin_success', 'ลบบทเรียนเรียบร้อย');
        }
    } catch (Throwable $e) {
        flash('admin_error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
    }
    redirect('/admin/lessons.php');
}

$pageTitle = 'จัดการบทเรียน';
require_once dirname(__DIR__) . '/includes/admin_header.php';

$message = flash('admin_success');
$error = flash('admin_error');
$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$editLesson = null;
if ($id) {
    $stmt = db()->prepare('SELECT * FROM lessons WHERE id = ?');
    $stmt->execute([$id]);
    $editLesson = $stmt->fetch() ?: null;
}

$courses = getCourses(null, false);
$lessonsSql = 'SELECT l.*, c.title AS course_title FROM lessons l JOIN courses c ON c.id = l.course_id';
$params = [];
if ($filterCourse) {
    $lessonsSql .= ' WHERE l.course_id = ?';
    $params[] = $filterCourse;
}
$lessonsSql .= ' ORDER BY l.course_id, l.sort_order, l.id';
$stmt = db()->prepare($lessonsSql);
$stmt->execute($params);
$lessons = $stmt->fetchAll();
?>

<?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

<?php if ($action === 'add' || ($action === 'edit' && $editLesson)): ?>
<div class="admin-card">
    <div class="admin-card-header">
        <h2><?= $editLesson ? 'แก้ไขบทเรียน' : 'เพิ่มบทเรียน' ?></h2>
        <a href="<?= APP_URL ?>/admin/lessons.php<?= $filterCourse ? '?course_id=' . $filterCourse : '' ?>" class="btn btn-secondary btn-sm">กลับ</a>
    </div>
    <div class="admin-card-body">
        <form method="post" enctype="multipart/form-data">
            <?= csrfField() ?>
            <?php if ($filterCourse): ?><input type="hidden" name="filter_course_id" value="<?= $filterCourse ?>"><?php endif; ?>
            <input type="hidden" name="action" value="<?= $editLesson ? 'update' : 'create' ?>">
            <?php if ($editLesson): ?><input type="hidden" name="id" value="<?= (int) $editLesson['id'] ?>"><?php endif; ?>
            <div class="form-group">
                <label>คอร์ส *</label>
                <select name="course_id" class="form-control" required>
                    <?php foreach ($courses as $c): ?>
                        <option value="<?= (int) $c['id'] ?>" <?= (($editLesson['course_id'] ?? $filterCourse) == $c['id']) ? 'selected' : '' ?>><?= e($c['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>ชื่อบทเรียน *</label>
                <input type="text" name="title" class="form-control" required value="<?= e($editLesson['title'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>รายละเอียด</label>
                <textarea name="description" class="form-control"><?= e($editLesson['description'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label>ลิงก์วิดีโอ (YouTube / URL)</label>
                <input type="url" name="video_url" class="form-control" value="<?= e($editLesson['video_url'] ?? '') ?>" placeholder="https://...">
            </div>
            <div class="form-group">
                <label>เอกสารประกอบ</label>
                <input type="file" name="document_file" class="form-control" accept=".pdf,.doc,.docx,.ppt,.pptx">
                <input type="text" name="document_url" class="form-control form-control-follow" value="<?= e($editLesson['document_url'] ?? '') ?>" placeholder="หรือใส่ URL เอกสาร">
                <small>อัปโหลด PDF/DOC/PPT (สูงสุด 10MB) หรือใส่ลิงก์</small>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>ระยะเวลา (นาที)</label>
                    <input type="number" name="duration_minutes" class="form-control" value="<?= (int) ($editLesson['duration_minutes'] ?? 0) ?>">
                </div>
                <div class="form-group">
                    <label>ลำดับ</label>
                    <input type="number" name="sort_order" class="form-control" value="<?= (int) ($editLesson['sort_order'] ?? 0) ?>">
                </div>
            </div>
            <div class="form-group">
                <label><input type="checkbox" name="is_free_preview" <?= !empty($editLesson['is_free_preview']) ? 'checked' : '' ?>> ดูตัวอย่างฟรี</label>
            </div>
            <div class="form-group">
                <label><input type="checkbox" name="is_published" <?= ($editLesson['is_published'] ?? 1) ? 'checked' : '' ?>> เผยแพร่</label>
            </div>
            <div class="admin-form-actions">
                <button type="submit" class="btn btn-primary">บันทึก</button>
            </div>
        </form>
    </div>
</div>
<?php else: ?>

<?php
$filterCourseTitle = '';
if ($filterCourse) {
    foreach ($courses as $c) {
        if ((int) $c['id'] === $filterCourse) {
            $filterCourseTitle = (string) $c['title'];
            break;
        }
    }
}
?>

<div class="admin-card">
    <div class="admin-card-header">
        <h2>บทเรียนทั้งหมด</h2>
        <a href="?action=add<?= $filterCourse ? '&course_id=' . $filterCourse : '' ?>" class="btn btn-primary btn-sm">+ เพิ่มบทเรียน</a>
    </div>
    <div class="admin-card-toolbar">
        <div class="admin-filter-toolbar">
            <p class="admin-filter-active">
                <?php if ($filterCourse): ?>
                กำลังแสดงบทเรียนของ: <strong><?= e($filterCourseTitle) ?></strong>
                <?php else: ?>
                แสดงบทเรียนทั้งหมด
                <?php endif; ?>
            </p>
            <form method="get" class="admin-filter-bar">
                <label for="lessonFilterCourse">กรองตามคอร์ส:</label>
                <select id="lessonFilterCourse" name="course_id" class="form-control">
                    <option value="">— เลือกคอร์ส —</option>
                    <?php foreach ($courses as $c): ?>
                        <option value="<?= (int) $c['id'] ?>" <?= $filterCourse === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['title']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary btn-sm">กรอง</button>
                <?php if ($filterCourse): ?>
                <a href="<?= APP_URL ?>/admin/lessons.php?clear_filter=1" class="btn btn-outline btn-sm">ล้างตัวกรอง</a>
                <?php endif; ?>
            </form>
        </div>
    </div>
    <div class="admin-card-body is-flush">
        <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>คอร์ส</th>
                    <th>บทเรียน</th>
                    <th>วิดีโอ</th>
                    <th>เอกสาร</th>
                    <th class="actions">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($lessons as $l): ?>
                <tr>
                    <td><?= e($l['course_title']) ?></td>
                    <td><?= e($l['title']) ?></td>
                    <td><?= $l['video_url'] ? 'มี' : '-' ?></td>
                    <td><?= $l['document_url'] ? 'มี' : '-' ?></td>
                    <td class="actions">
                        <div class="table-actions">
                        <a href="?action=edit&amp;id=<?= (int) $l['id'] ?><?= $filterCourse ? '&amp;course_id=' . $filterCourse : '' ?>" class="btn btn-secondary btn-sm">แก้ไข</a>
                        <form method="post" onsubmit="return confirm('ลบบทเรียน?')">
                            <?= csrfField() ?>
                            <?php if ($filterCourse): ?><input type="hidden" name="filter_course_id" value="<?= $filterCourse ?>"><?php endif; ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int) $l['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm">ลบ</button>
                        </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once dirname(__DIR__) . '/includes/admin_footer.php'; ?>
