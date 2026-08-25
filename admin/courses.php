<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
requireAdmin();
require_once dirname(__DIR__) . '/includes/media_upload.php';
require_once dirname(__DIR__) . '/includes/progress.php';
require_once dirname(__DIR__) . '/includes/booking.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $postAction = $_POST['action'] ?? '';
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '') ?: preg_replace('/[^a-z0-9]+/', '-', strtolower($title));
    $subtitle = trim($_POST['subtitle'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = $_POST['category'] ?? 'hsk';
    $level = $_POST['level'] ?? 'beginner';
    $price = (float) ($_POST['price'] ?? 0);
    $duration = (int) ($_POST['duration_hours'] ?? 0);
    $lessonCount = (int) ($_POST['lesson_count'] ?? 0);
    $highlights = trim($_POST['highlights'] ?? '');
    $imageUrl = trim($_POST['image_url'] ?? '');
    $sortOrder = (int) ($_POST['sort_order'] ?? 0);
    $isFeatured = parseCheckboxFlag($_POST['is_featured'] ?? 0);
    $isActive = parseCheckboxFlag($_POST['is_active'] ?? 0);
    $courseType = $_POST['course_type'] ?? 'recorded';
    $zoomUrl = trim($_POST['zoom_url'] ?? '');
    $editId = (int) ($_POST['id'] ?? 0);

    if (!in_array($courseType, ['recorded', 'live', 'hybrid'], true)) {
        $courseType = 'recorded';
    }

    if (!empty($_FILES['cover_image']['name'])) {
        $uploaded = storeCourseCoverUpload($_FILES['cover_image']);
        if ($uploaded === false) {
            redirect('/admin/courses.php' . ($editId ? '?action=edit&id=' . $editId : '?action=add'));
        }
        if (is_string($uploaded)) {
            $imageUrl = $uploaded;
        }
    }

    try {
        if ($postAction === 'create') {
            $stmt = db()->prepare('
                INSERT INTO courses (slug, title, subtitle, description, category, level, price, duration_hours, lesson_count, highlights, image_url, is_featured, is_active, course_type, zoom_url, sort_order)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([$slug, $title, $subtitle, $description, $category, $level, $price, $duration, $lessonCount, $highlights, $imageUrl ?: null, $isFeatured, $isActive, $courseType, $zoomUrl ?: null, $sortOrder]);
            flash('admin_success', 'เพิ่มคอร์สเรียบร้อย');
        } elseif ($postAction === 'update' && $editId) {
            $stmt = db()->prepare('
                UPDATE courses SET slug=?, title=?, subtitle=?, description=?, category=?, level=?, price=?, duration_hours=?, lesson_count=?, highlights=?, image_url=?, is_featured=?, is_active=?, course_type=?, zoom_url=?, sort_order=?
                WHERE id=?
            ');
            $stmt->execute([$slug, $title, $subtitle, $description, $category, $level, $price, $duration, $lessonCount, $highlights, $imageUrl ?: null, $isFeatured, $isActive, $courseType, $zoomUrl ?: null, $sortOrder, $editId]);
            flash('admin_success', 'อัปเดตคอร์สเรียบร้อย');
        } elseif ($postAction === 'delete' && $editId) {
            $stmt = db()->prepare('DELETE FROM courses WHERE id = ?');
            $stmt->execute([$editId]);
            flash('admin_success', 'ลบคอร์สเรียบร้อย');
        }
    } catch (Throwable $e) {
        flash('admin_error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
    }
    redirect('/admin/courses.php');
}

$pageTitle = 'จัดการคอร์ส';
require_once dirname(__DIR__) . '/includes/admin_header.php';

$message = flash('admin_success');
$error = flash('admin_error');
$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$editCourse = $id ? getCourseById($id) : null;
$courses = getCourses(null, false);
?>

<?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

<?php if ($action === 'add' || ($action === 'edit' && $editCourse)): ?>
<div class="admin-card">
    <div class="admin-card-header">
        <h2><?= $editCourse ? 'แก้ไขคอร์ส' : 'เพิ่มคอร์สใหม่' ?></h2>
        <a href="<?= APP_URL ?>/admin/courses.php" class="btn btn-secondary btn-sm">กลับ</a>
    </div>
    <div class="admin-card-body">
        <form method="post" class="modal-form" enctype="multipart/form-data">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="<?= $editCourse ? 'update' : 'create' ?>">
            <?php if ($editCourse): ?><input type="hidden" name="id" value="<?= (int) $editCourse['id'] ?>"><?php endif; ?>
            <div class="form-row">
                <div class="form-group">
                    <label>ชื่อคอร์ส *</label>
                    <input type="text" name="title" class="form-control" required value="<?= e($editCourse['title'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Slug (URL)</label>
                    <input type="text" name="slug" class="form-control" value="<?= e($editCourse['slug'] ?? '') ?>" placeholder="hsk3">
                </div>
            </div>
            <div class="form-group">
                <label>หัวข้อย่อย</label>
                <input type="text" name="subtitle" class="form-control" value="<?= e($editCourse['subtitle'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>รายละเอียด</label>
                <textarea name="description" class="form-control" rows="4"><?= e($editCourse['description'] ?? '') ?></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>หมวด</label>
                    <select name="category" class="form-control">
                        <option value="foundation" <?= ($editCourse['category'] ?? '') === 'foundation' ? 'selected' : '' ?>>พื้นฐาน</option>
                        <option value="hsk" <?= ($editCourse['category'] ?? 'hsk') === 'hsk' ? 'selected' : '' ?>>HSK</option>
                        <option value="exam_prep" <?= ($editCourse['category'] ?? '') === 'exam_prep' ? 'selected' : '' ?>>ติวสอบ</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>ระดับ</label>
                    <select name="level" class="form-control">
                        <option value="beginner" <?= ($editCourse['level'] ?? '') === 'beginner' ? 'selected' : '' ?>>เริ่มต้น</option>
                        <option value="intermediate" <?= ($editCourse['level'] ?? '') === 'intermediate' ? 'selected' : '' ?>>ปานกลาง</option>
                        <option value="advanced" <?= ($editCourse['level'] ?? '') === 'advanced' ? 'selected' : '' ?>>ขั้นสูง</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>ราคา (บาท)</label>
                    <input type="number" name="price" class="form-control" step="0.01" value="<?= e((string) ($editCourse['price'] ?? '')) ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>ชั่วโมง</label>
                    <input type="number" name="duration_hours" class="form-control" value="<?= (int) ($editCourse['duration_hours'] ?? 0) ?>">
                </div>
                <div class="form-group">
                    <label>จำนวนบท</label>
                    <input type="number" name="lesson_count" class="form-control" value="<?= (int) ($editCourse['lesson_count'] ?? 0) ?>">
                </div>
                <div class="form-group">
                    <label>ลำดับ</label>
                    <input type="number" name="sort_order" class="form-control" value="<?= (int) ($editCourse['sort_order'] ?? 0) ?>">
                </div>
            </div>
            <div class="form-group">
                <label>รูปปกคอร์ส</label>
                <input type="file" name="cover_image" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif">
                <input type="text" name="image_url" class="form-control form-control-follow" value="<?= e($editCourse['image_url'] ?? '') ?>" placeholder="หรือใส่ URL / path เช่น images/courses/photo.jpg">
                <small>อัปโหลดไฟล์ (สูงสุด 3MB) หรือใส่ลิงก์รูป</small>
                <?php if (!empty($editCourse)): ?>
                    <img src="<?= e(courseCoverUrl($editCourse)) ?>" alt="" class="form-preview-img">
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label>ไฮไลท์ (คั่นด้วย |)</label>
                <input type="text" name="highlights" class="form-control" value="<?= e($editCourse['highlights'] ?? '') ?>" placeholder="พินอิน|HSK 1|แบบฝึกหัด">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>ประเภทคอร์ส</label>
                    <select name="course_type" class="form-control">
                        <option value="recorded" <?= ($editCourse['course_type'] ?? 'recorded') === 'recorded' ? 'selected' : '' ?>>เรียนวิดีโอ (VOD)</option>
                        <option value="live" <?= ($editCourse['course_type'] ?? '') === 'live' ? 'selected' : '' ?>>คลาสออนไลน์ Live</option>
                        <option value="hybrid" <?= ($editCourse['course_type'] ?? '') === 'hybrid' ? 'selected' : '' ?>>Hybrid (Live + VOD)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>ลิงก์ Zoom คอร์ส (ใช้ร่วมทุกรอบถ้าไม่กำหนดแยก)</label>
                    <input type="url" name="zoom_url" class="form-control" value="<?= e($editCourse['zoom_url'] ?? '') ?>" placeholder="https://zoom.us/j/...">
                </div>
            </div>
            <div class="form-group">
                <input type="hidden" name="is_featured" value="0">
                <label><input type="checkbox" name="is_featured" value="1" <?= !empty($editCourse['is_featured']) ? 'checked' : '' ?>> แสดงในหน้าแรก</label>
            </div>
            <div class="form-group">
                <input type="hidden" name="is_active" value="0">
                <label><input type="checkbox" name="is_active" value="1" <?= ($editCourse['is_active'] ?? 1) ? 'checked' : '' ?>> เปิดใช้งาน</label>
            </div>
            <div class="admin-form-actions">
                <button type="submit" class="btn btn-primary">บันทึก</button>
            </div>
        </form>
    </div>
</div>
<?php else: ?>

<div class="admin-card">
    <div class="admin-card-header">
        <h2>รายการคอร์ส</h2>
        <a href="<?= APP_URL ?>/admin/courses.php?action=add" class="btn btn-primary btn-sm">+ เพิ่มคอร์ส</a>
    </div>
    <div class="admin-card-body is-flush">
        <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>ชื่อคอร์ส</th>
                    <th>ประเภท</th>
                    <th>หมวด</th>
                    <th>ราคา</th>
                    <th>สถานะ</th>
                    <th class="actions">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($courses as $c): ?>
                <tr>
                    <td><?= (int) $c['id'] ?></td>
                    <td><?= e($c['title']) ?></td>
                    <td><span class="badge <?= in_array($c['course_type'] ?? 'recorded', ['live', 'hybrid'], true) ? 'badge-verified' : 'badge-pending' ?>"><?= e(courseTypeLabel($c['course_type'] ?? 'recorded')) ?></span></td>
                    <td><?= e(categoryLabel($c['category'])) ?></td>
                    <td><?= e(formatPrice((float) $c['price'])) ?></td>
                    <td><?= $c['is_active'] ? '<span class="badge badge-active">เปิด</span>' : '<span class="badge badge-rejected">ปิด</span>' ?></td>
                    <td class="actions">
                        <div class="table-actions">
                        <a href="<?= APP_URL ?>/public/course.php?slug=<?= e(urlencode($c['slug'])) ?>" target="_blank" rel="noopener" class="btn btn-outline btn-sm">ดู</a>
                        <a href="?action=edit&id=<?= (int) $c['id'] ?>" class="btn btn-secondary btn-sm">แก้ไข</a>
                        <form method="post" onsubmit="return confirm('ลบคอร์สนี้?')">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
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
