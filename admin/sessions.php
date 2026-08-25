<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
requireAdmin();
require_once dirname(__DIR__) . '/includes/booking.php';
require_once dirname(__DIR__) . '/includes/media_upload.php';
require_once dirname(__DIR__) . '/includes/schema.php';

$schemaMissing = missingDatabaseTables();
$schemaError = $schemaMissing ? migrationHintMessage($schemaMissing) : '';

$filterCourse = (int) ($_GET['course_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $postAction = $_POST['action'] ?? '';
    $courseId = (int) ($_POST['course_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $startsAt = trim($_POST['starts_at'] ?? '');
    $endsAt = trim($_POST['ends_at'] ?? '');
    $capacity = max(1, (int) ($_POST['capacity'] ?? 20));
    $zoomUrl = trim($_POST['zoom_url'] ?? '');
    $imageUrl = trim($_POST['image_url'] ?? '');
    $status = $_POST['status'] ?? 'scheduled';
    $editId = (int) ($_POST['id'] ?? 0);

    if (!in_array($status, ['scheduled', 'cancelled', 'completed'], true)) {
        $status = 'scheduled';
    }

    if ($postAction === 'update' && $editId && $imageUrl === '') {
        $existing = getSessionById($editId);
        if ($existing) {
            $imageUrl = trim((string) ($existing['image_url'] ?? ''));
        }
    }

    if (!empty($_FILES['session_image']['name'])) {
        $uploaded = storeSessionImageUpload($_FILES['session_image']);
        if ($uploaded === false) {
            if ($postAction === 'update' && $editId) {
                redirect('/admin/sessions.php?action=edit&id=' . $editId);
            }
            redirect('/admin/sessions.php?action=add');
        }
        if (is_string($uploaded)) {
            $imageUrl = $uploaded;
        }
    } elseif (isset($_POST['remove_image'])) {
        $imageUrl = '';
    }

    if ($schemaMissing) {
        flash('admin_error', $schemaError);
        redirect('/admin/sessions.php');
    }

    try {
        $courseRow = getCourseById($courseId);
        $sessionPreview = [
            'course_id' => $courseId,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'status' => $status,
            'booked_count' => 0,
            'capacity' => $capacity,
        ];
        $visibilityNote = function (string $base) use ($sessionPreview, $courseRow): string {
            $reason = getSessionStudentVisibilityReason($sessionPreview, $courseRow);
            if ($reason) {
                return $base . ' — นักเรียนยังไม่เห็น: ' . $reason;
            }

            return $base . ' — แสดงหน้าเว็บให้นักเรียนจองได้แล้ว';
        };

        if ($postAction === 'create' && $courseId && $startsAt && $endsAt) {
            $stmt = db()->prepare('
                INSERT INTO course_sessions (course_id, title, starts_at, ends_at, capacity, zoom_url, image_url, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([$courseId, $title, $startsAt, $endsAt, $capacity, $zoomUrl ?: null, $imageUrl ?: null, $status]);
            flash('admin_success', $visibilityNote('เพิ่มรอบเรียนเรียบร้อย'));
        } elseif ($postAction === 'update' && $editId) {
            $existing = getSessionById($editId);
            if ($existing) {
                $sessionPreview['booked_count'] = (int) ($existing['booked_count'] ?? 0);
            }
            $stmt = db()->prepare('
                UPDATE course_sessions SET course_id=?, title=?, starts_at=?, ends_at=?, capacity=?, zoom_url=?, image_url=?, status=?
                WHERE id=?
            ');
            $stmt->execute([$courseId, $title, $startsAt, $endsAt, $capacity, $zoomUrl ?: null, $imageUrl ?: null, $status, $editId]);
            flash('admin_success', $visibilityNote('อัปเดตรอบเรียนเรียบร้อย'));
        } elseif ($postAction === 'delete' && $editId) {
            db()->prepare('DELETE FROM course_sessions WHERE id = ?')->execute([$editId]);
            flash('admin_success', 'ลบรอบเรียนเรียบร้อย');
        }
    } catch (Throwable $e) {
        flash('admin_error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
    }

    if ($postAction === 'update' && $editId) {
        redirect('/admin/sessions.php?action=edit&id=' . $editId);
    }
    redirect('/admin/sessions.php' . ($filterCourse ? '?course_id=' . $filterCourse : ''));
}

$pageTitle = 'ตารางคลาส Live';
require_once dirname(__DIR__) . '/includes/admin_header.php';

$message = flash('admin_success');
$error = flash('admin_error');
$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$editSession = $id ? getSessionById($id) : null;
$courses = getCourses(null, false);
$liveCourses = array_values(array_filter($courses, static fn($c) => isLiveCourse($c)));
$defaultStartsAt = date('Y-m-d\TH:i', strtotime('tomorrow 10:00'));
$defaultEndsAt = date('Y-m-d\TH:i', strtotime('tomorrow 11:30'));

$sql = '
    SELECT cs.*, c.title AS course_title, c.slug AS course_slug, c.course_type, c.is_active
    FROM course_sessions cs
    JOIN courses c ON c.id = cs.course_id
';
$params = [];
if ($filterCourse) {
    $sql .= ' WHERE cs.course_id = ?';
    $params[] = $filterCourse;
}
$sql .= ' ORDER BY cs.starts_at DESC LIMIT 100';
$sessions = [];
try {
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $sessions = $stmt->fetchAll();
} catch (Throwable $e) {
    // If production hasn't migrated yet, keep UI usable (empty state).
    $sessions = [];
}
?>

<?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<?php if ($schemaError && !$error): ?><div class="alert alert-warning"><?= e($schemaError) ?></div><?php endif; ?>

<?php if ($action === 'add' || ($action === 'edit' && $editSession)): ?>
<div class="admin-card">
    <div class="admin-card-header">
        <h2><?= $editSession ? 'แก้ไขรอบเรียน' : 'เพิ่มรอบเรียน Live' ?></h2>
        <a href="<?= APP_URL ?>/admin/sessions.php" class="btn btn-secondary btn-sm">กลับ</a>
    </div>
    <div class="admin-card-body">
        <?php if (!$liveCourses): ?>
        <div class="alert alert-warning" style="margin-bottom:1rem">
            ยังไม่มีคอร์สประเภท <strong>คลาสออนไลน์ Live</strong> หรือ <strong>Hybrid</strong>
            — นักเรียนจะจองรอบเรียนได้เมื่อเปลี่ยนประเภทคอร์สที่
            <a href="<?= APP_URL ?>/admin/courses.php">จัดการคอร์ส</a>
            ก่อน (เลือกคอร์สด้านล่างเพื่อเตรียมตารางไว้ล่วงหน้าได้)
        </div>
        <?php endif; ?>
        <form method="post" class="modal-form" enctype="multipart/form-data">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="<?= $editSession ? 'update' : 'create' ?>">
            <?php if ($editSession): ?><input type="hidden" name="id" value="<?= (int) $editSession['id'] ?>"><?php endif; ?>
            <div class="form-group">
                <label>คอร์ส *</label>
                <select name="course_id" class="form-control" required>
                    <option value="">— เลือกคอร์ส —</option>
                    <?php foreach ($courses as $c): ?>
                    <?php $typeLabel = courseTypeLabel($c['course_type'] ?? 'recorded'); ?>
                    <option value="<?= (int) $c['id'] ?>" <?= (int) ($editSession['course_id'] ?? $filterCourse) === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['title']) ?> · <?= e($typeLabel) ?></option>
                    <?php endforeach; ?>
                </select>
                <small class="form-hint">นักเรียนจองรอบเรียนได้เฉพาะคอร์สประเภท「คลาสออนไลน์ Live」หรือ「Hybrid」 — <a href="<?= APP_URL ?>/admin/courses.php">เปลี่ยนประเภทที่หน้าจัดการคอร์ส</a></small>
            </div>
            <div class="form-group">
                <label>ชื่อรอบ (ไม่บังคับ)</label>
                <input type="text" name="title" class="form-control" value="<?= e($editSession['title'] ?? '') ?>" placeholder="เช่น รอบเช้า HSK3">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>เริ่ม *</label>
                    <input type="datetime-local" name="starts_at" class="form-control" required value="<?= e(isset($editSession['starts_at']) ? date('Y-m-d\TH:i', strtotime($editSession['starts_at'])) : $defaultStartsAt) ?>">
                </div>
                <div class="form-group">
                    <label>สิ้นสุด *</label>
                    <input type="datetime-local" name="ends_at" class="form-control" required value="<?= e(isset($editSession['ends_at']) ? date('Y-m-d\TH:i', strtotime($editSession['ends_at'])) : $defaultEndsAt) ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>จำนวนที่นั่ง</label>
                    <input type="number" name="capacity" class="form-control" min="1" value="<?= (int) ($editSession['capacity'] ?? 20) ?>">
                </div>
                <div class="form-group">
                    <label>สถานะ</label>
                    <select name="status" class="form-control">
                        <option value="scheduled" <?= ($editSession['status'] ?? '') === 'scheduled' ? 'selected' : '' ?>>เปิดจอง</option>
                        <option value="cancelled" <?= ($editSession['status'] ?? '') === 'cancelled' ? 'selected' : '' ?>>ยกเลิก</option>
                        <option value="completed" <?= ($editSession['status'] ?? '') === 'completed' ? 'selected' : '' ?>>จบแล้ว</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>ลิงก์ Zoom (เฉพาะรอบนี้ — ว่างใช้ลิงก์คอร์ส)</label>
                <input type="url" name="zoom_url" class="form-control" value="<?= e($editSession['zoom_url'] ?? '') ?>" placeholder="https://zoom.us/j/...">
            </div>
            <div class="form-group">
                <label>รูปประกอบรอบเรียน</label>
                <input type="file" name="session_image" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif">
                <input type="text" name="image_url" class="form-control form-control-follow" value="<?= e($editSession['image_url'] ?? '') ?>" placeholder="หรือใส่ URL / path เช่น images/courses/photo.jpg">
                <small class="form-hint">อัปโหลดไฟล์ (สูงสุด 3MB) หรือใส่ลิงก์รูป — แสดงตอนนักเรียนเลือกรอบจอง</small>
                <?php if ($editSession && sessionImageUrl($editSession)): ?>
                <img src="<?= e(sessionImageUrl($editSession)) ?>" alt="" class="form-preview-img">
                <label style="display:block;margin-top:.5rem"><input type="checkbox" name="remove_image" value="1"> ลบรูปปัจจุบัน</label>
                <?php endif; ?>
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
        <h2>ตารางคลาส Live</h2>
        <div class="table-actions">
            <a href="<?= APP_URL ?>/admin/sessions.php?action=add" class="btn btn-primary btn-sm">+ เพิ่มรอบเรียน</a>
        </div>
    </div>
    <div class="admin-card-toolbar">
        <form method="get" class="admin-inline-form">
            <select name="course_id" class="form-control" onchange="this.form.submit()">
                <option value="0">ทุกคอร์ส</option>
                <?php foreach ($courses as $c): ?>
                <option value="<?= (int) $c['id'] ?>" <?= $filterCourse === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['title']) ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
    <div class="admin-card-body is-flush">
        <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>คอร์ส</th>
                    <th>รูป</th>
                    <th>รอบ / เวลา</th>
                    <th>ที่นั่ง</th>
                    <th>Zoom</th>
                    <th>สถานะ</th>
                    <th>หน้าเว็บ</th>
                    <th class="actions">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$sessions): ?>
                <tr><td colspan="8" class="table-empty">ยังไม่มีรอบเรียน</td></tr>
                <?php endif; ?>
                <?php foreach ($sessions as $s): ?>
                <?php
                    $sessionCourse = [
                        'id' => (int) ($s['course_id'] ?? 0),
                        'course_type' => $s['course_type'] ?? 'recorded',
                        'is_active' => $s['is_active'] ?? 1,
                    ];
                    $visibilityReason = getSessionStudentVisibilityReason($s, $sessionCourse);
                ?>
                <tr<?= $visibilityReason ? ' class="admin-table-row--muted"' : '' ?>>
                    <td>
                        <?= e($s['course_title']) ?><br>
                        <small><?= e(courseTypeLabel($sessionCourse['course_type'] ?? 'recorded')) ?></small>
                    </td>
                    <td>
                        <?php if (sessionImageUrl($s)): ?>
                        <img src="<?= e(sessionImageUrl($s)) ?>" alt="" style="width:48px;height:48px;object-fit:cover;border-radius:8px">
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td>
                        <strong><?= e($s['title'] ?: 'รอบเรียน') ?></strong><br>
                        <small><?= e(formatSessionRange($s)) ?></small>
                    </td>
                    <td><?= (int) $s['booked_count'] ?> / <?= (int) $s['capacity'] ?></td>
                    <td><?= ($s['zoom_url'] ?? '') ? 'มี' : '—' ?></td>
                    <td><span class="badge <?= e(sessionStatusBadgeClass($s['status'] ?? '')) ?>"><?= e(sessionStatusLabel($s['status'] ?? '')) ?></span></td>
                    <td>
                        <?php if ($visibilityReason): ?>
                        <span class="badge badge-pending" title="<?= e($visibilityReason) ?>">ซ่อน</span>
                        <small class="admin-table-hint"><?= e($visibilityReason) ?></small>
                        <?php else: ?>
                        <span class="badge badge-active">แสดง</span>
                        <?php endif; ?>
                    </td>
                    <td class="actions">
                        <div class="table-actions">
                            <a href="?action=edit&id=<?= (int) $s['id'] ?>" class="btn btn-secondary btn-sm">แก้ไข</a>
                            <form method="post" onsubmit="return confirm('ลบรอบนี้?')">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
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
