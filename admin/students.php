<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
requireAdmin();
require_once dirname(__DIR__) . '/includes/checkout_flow.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';
    $studentId = (int) ($_POST['student_id'] ?? 0);

    if ($action === 'enroll') {
        $courseId = (int) ($_POST['course_id'] ?? 0);
        $status = $_POST['status'] ?? 'active';
        if ($studentId > 0 && $courseId > 0) {
            enrollStudentInCourses($studentId, [$courseId], $status);
            flash('admin_success', 'เปิดสิทธิ์คอร์สเรียบร้อย');
        }
        redirect('/admin/students.php?student_id=' . $studentId . '&open=1');
    }

    if ($action === 'update_status') {
        $enrollmentId = (int) ($_POST['enrollment_id'] ?? 0);
        $status = $_POST['status'] ?? '';
        if ($enrollmentId > 0 && in_array($status, ['pending', 'active', 'completed', 'cancelled'], true)) {
            $stmt = db()->prepare('UPDATE enrollments SET status = ? WHERE id = ?');
            $stmt->execute([$status, $enrollmentId]);
            flash('admin_success', 'อัปเดตสถานะเรียบร้อย');
        }
        redirect('/admin/students.php?student_id=' . $studentId . '&open=1');
    }
}

$pageTitle = 'นักเรียน';
require_once dirname(__DIR__) . '/includes/admin_header.php';

$message = flash('admin_success');
$error = flash('admin_error');
$openStudentId = (int) ($_GET['student_id'] ?? 0);
$autoOpenModal = isset($_GET['open']) || $openStudentId > 0;

$students = db()->query('
    SELECT s.*,
        (SELECT COUNT(*) FROM enrollments e WHERE e.student_id = s.id AND e.status IN ("active","completed")) AS active_courses,
        (SELECT COUNT(*) FROM enrollments e WHERE e.student_id = s.id AND e.status = "pending") AS pending_courses
    FROM students s
    ORDER BY s.created_at DESC
')->fetchAll();

$enrollmentsByStudent = [];
if ($students) {
    $studentIds = array_map(static fn($s) => (int) $s['id'], $students);
    $placeholders = implode(',', array_fill(0, count($studentIds), '?'));
    $stmt = db()->prepare("
        SELECT e.*, c.title AS course_title, c.slug AS course_slug, e.student_id
        FROM enrollments e
        JOIN courses c ON c.id = e.course_id
        WHERE e.student_id IN ($placeholders)
        ORDER BY e.enrolled_at DESC
    ");
    $stmt->execute($studentIds);
    foreach ($stmt->fetchAll() as $row) {
        $enrollmentsByStudent[(int) $row['student_id']][] = $row;
    }
}

$courses = getCourses(null, false);

$statusLabels = [
    'pending' => 'รอเปิดสิทธิ์',
    'active' => 'เปิดสิทธิ์แล้ว',
    'completed' => 'จบคอร์ส',
    'cancelled' => 'ยกเลิก',
];

$studentStatusBadge = static function (array $student) use ($statusLabels): array {
    $pending = (int) ($student['pending_courses'] ?? 0);
    $active = (int) ($student['active_courses'] ?? 0);
    if ($pending > 0) {
        return ['pending', $statusLabels['pending']];
    }
    if ($active > 0) {
        return ['active', $statusLabels['active']];
    }
    return ['none', 'ยังไม่มีคอร์ส'];
};
?>

<?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

<div class="admin-card students-list-card">
    <div class="admin-card-header">
        <h2>รายชื่อนักเรียน (<?= count($students) ?>)</h2>
    </div>
    <div class="admin-card-body is-flush students-list-scroll">
        <table class="data-table students-compact-table">
            <thead>
                <tr>
                    <th>ชื่อ</th>
                    <th>เบอร์โทร</th>
                    <th>อีเมล</th>
                    <th>สมัครเมื่อ</th>
                    <th>สถานะ</th>
                    <th class="students-col-actions">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $s): ?>
                <?php [$badgeClass, $badgeLabel] = $studentStatusBadge($s); ?>
                <tr class="<?= $openStudentId === (int) $s['id'] ? 'is-selected' : '' ?>">
                    <td>
                        <span class="students-name"><?= e($s['full_name']) ?></span>
                        <?php if (empty($s['password_hash'])): ?>
                        <small class="students-note">ยังไม่ตั้งรหัสผ่าน</small>
                        <?php endif; ?>
                    </td>
                    <td><?= e($s['phone'] ?? '-') ?></td>
                    <td class="students-col-email"><?= e($s['email'] ?? '-') ?></td>
                    <td class="students-col-date"><?= e(date('d/m/Y', strtotime($s['created_at']))) ?></td>
                    <td>
                        <span class="badge badge-<?= e($badgeClass === 'none' ? 'pending' : $badgeClass) ?> students-status-badge<?= $badgeClass === 'none' ? ' students-status-badge--muted' : '' ?>">
                            <?= e($badgeLabel) ?>
                        </span>
                    </td>
                    <td class="students-col-actions">
                        <button type="button" class="students-icon-btn" data-open-student="<?= (int) $s['id'] ?>" aria-label="จัดการสิทธิ์ <?= e($s['full_name']) ?>" title="จัดการสิทธิ์">
                            <?= lucide_icon('square-pen', ['size' => 18]) ?>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php if (!$students): ?>
        <p class="table-empty">ยังไม่มีนักเรียน</p>
        <?php endif; ?>
    </div>
</div>

<div class="admin-modal" id="studentManageModal" hidden>
    <div class="admin-modal-backdrop" data-close-student-modal></div>
    <div class="admin-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="studentModalTitle">
        <button type="button" class="admin-modal-close" data-close-student-modal aria-label="ปิด">
            <?= lucide_icon('x', ['size' => 20]) ?>
        </button>
        <?php foreach ($students as $s): ?>
        <?php $studentEnrollments = $enrollmentsByStudent[(int) $s['id']] ?? []; ?>
        <div class="student-modal-panel" id="student-panel-<?= (int) $s['id'] ?>" data-student-panel hidden>
            <div class="admin-modal-header">
                <h2 id="studentModalTitle-<?= (int) $s['id'] ?>"><?= e($s['full_name']) ?></h2>
                <?php [$badgeClass, $badgeLabel] = $studentStatusBadge($s); ?>
                <span class="badge badge-<?= e($badgeClass === 'none' ? 'pending' : $badgeClass) ?> students-status-badge<?= $badgeClass === 'none' ? ' students-status-badge--muted' : '' ?>"><?= e($badgeLabel) ?></span>
            </div>
            <div class="admin-modal-body">
                <ul class="student-modal-meta">
                    <li><strong>เบอร์:</strong> <?= e($s['phone'] ?? '-') ?></li>
                    <li><strong>อีเมล:</strong> <?= e($s['email'] ?? '-') ?></li>
                    <li><strong>Line:</strong> <?= e($s['line_id'] ?? '-') ?></li>
                    <li><strong>สมัครเมื่อ:</strong> <?= e(date('d/m/Y', strtotime($s['created_at']))) ?></li>
                </ul>

                <h3 class="student-modal-section-title">คอร์สที่ลงทะเบียน</h3>
                <?php if ($studentEnrollments): ?>
                <table class="data-table student-enroll-table">
                    <thead>
                        <tr>
                            <th>คอร์ส</th>
                            <th>สถานะ</th>
                            <th class="students-col-actions">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($studentEnrollments as $en): ?>
                        <?php $enStatus = (string) ($en['status'] ?? 'pending'); ?>
                        <tr>
                            <td><?= e($en['course_title']) ?></td>
                            <td><span class="badge badge-<?= e($enStatus) ?>"><?= e($statusLabels[$enStatus] ?? $enStatus) ?></span></td>
                            <td class="students-col-actions">
                                <div class="students-icon-actions">
                                    <?php if (in_array($enStatus, ['pending', 'cancelled'], true)): ?>
                                    <form method="post" class="students-icon-form">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="enrollment_id" value="<?= (int) $en['id'] ?>">
                                        <input type="hidden" name="student_id" value="<?= (int) $s['id'] ?>">
                                        <button type="submit" name="status" value="active" class="students-icon-btn students-icon-btn--open" title="เปิดสิทธิ์" aria-label="เปิดสิทธิ์">
                                            <?= lucide_icon('user-check', ['size' => 18]) ?>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                    <?php if (in_array($enStatus, ['pending', 'active'], true)): ?>
                                    <form method="post" class="students-icon-form">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="enrollment_id" value="<?= (int) $en['id'] ?>">
                                        <input type="hidden" name="student_id" value="<?= (int) $s['id'] ?>">
                                        <button type="submit" name="status" value="cancelled" class="students-icon-btn students-icon-btn--cancel" title="ยกเลิกสิทธิ์" aria-label="ยกเลิกสิทธิ์">
                                            <?= lucide_icon('x', ['size' => 18]) ?>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                    <?php if (!in_array($enStatus, ['pending', 'active', 'cancelled'], true)): ?>
                                    <span class="payment-action-done">—</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p class="student-modal-empty">ยังไม่มีคอร์สที่ลงทะเบียน</p>
                <?php endif; ?>

                <h3 class="student-modal-section-title">เปิดสิทธิ์คอร์สใหม่</h3>
                <form method="post" class="admin-inline-form student-enroll-form">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="enroll">
                    <input type="hidden" name="student_id" value="<?= (int) $s['id'] ?>">
                    <select name="course_id" class="form-control" required>
                        <option value="">-- เลือกคอร์ส --</option>
                        <?php foreach ($courses as $c): ?>
                        <option value="<?= (int) $c['id'] ?>"><?= e($c['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" name="status" value="active">
                    <button type="submit" class="btn btn-primary btn-sm">เปิดสิทธิ์</button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php if ($autoOpenModal && $openStudentId > 0): ?>
<script>window.__openStudentId = <?= (int) $openStudentId ?>;</script>
<?php endif; ?>

<?php require_once dirname(__DIR__) . '/includes/admin_footer.php'; ?>
