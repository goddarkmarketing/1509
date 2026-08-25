<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';
    $admin = currentAdmin();

    if ($action === 'create') {
        $result = createAdminUser(
            trim($_POST['username'] ?? ''),
            $_POST['password'] ?? '',
            trim($_POST['full_name'] ?? ''),
            trim($_POST['email'] ?? '') ?: null
        );
        flash($result['ok'] ? 'admin_success' : 'admin_error', $result['message']);
        redirect('/admin/users.php');
    }

    if ($action === 'update') {
        $adminId = (int) ($_POST['admin_id'] ?? 0);
        $result = updateAdminUser(
            $adminId,
            trim($_POST['full_name'] ?? ''),
            trim($_POST['email'] ?? '') ?: null,
            trim($_POST['username'] ?? '') ?: null
        );
        flash($result['ok'] ? 'admin_success' : 'admin_error', $result['message']);
        redirect('/admin/users.php?edit=' . $adminId);
    }

    if ($action === 'password') {
        $adminId = (int) ($_POST['admin_id'] ?? 0);
        $isSelf = (int) ($admin['id'] ?? 0) === $adminId;
        $result = changeAdminPassword(
            $adminId,
            $_POST['new_password'] ?? '',
            $isSelf ? ($_POST['current_password'] ?? '') : null
        );
        flash($result['ok'] ? 'admin_success' : 'admin_error', $result['message']);
        redirect('/admin/users.php?edit=' . $adminId);
    }

    if ($action === 'delete') {
        $result = deleteAdminUser((int) ($_POST['admin_id'] ?? 0));
        flash($result['ok'] ? 'admin_success' : 'admin_error', $result['message']);
        redirect('/admin/users.php');
    }
}

$pageTitle = 'ผู้ดูแลระบบ';
require_once dirname(__DIR__) . '/includes/admin_header.php';

$message = flash('admin_success');
$error = flash('admin_error');
$editId = (int) ($_GET['edit'] ?? 0);

$admins = getAllAdmins();
$editAdmin = null;
if ($editId > 0) {
    foreach ($admins as $a) {
        if ((int) $a['id'] === $editId) {
            $editAdmin = $a;
            break;
        }
    }
}
?>

<?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

<div class="admin-grid-2">
    <div class="admin-card">
        <div class="admin-card-header"><h2>รายชื่อผู้ดูแล</h2></div>
        <div class="admin-card-body is-flush">
            <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ชื่อผู้ใช้</th>
                        <th>ชื่อ</th>
                        <th>อีเมล</th>
                        <th class="actions">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($admins as $a): ?>
                    <tr>
                        <td><?= e($a['username']) ?></td>
                        <td><?= e($a['full_name']) ?></td>
                        <td><?= e($a['email'] ?? '-') ?></td>
                        <td class="actions">
                            <div class="table-actions">
                            <a href="?edit=<?= (int) $a['id'] ?>" class="btn btn-secondary btn-sm">แก้ไข</a>
                            <?php if ((int) $a['id'] !== (int) ($admin['id'] ?? 0) && countAdmins() > 1): ?>
                            <form method="post" onsubmit="return confirm('ลบผู้ดูแลนี้?')">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="admin_id" value="<?= (int) $a['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm">ลบ</button>
                            </form>
                            <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>

    <div class="admin-card">
        <div class="admin-card-header">
            <h2><?= $editAdmin ? 'แก้ไขผู้ดูแล' : 'เพิ่มผู้ดูแลใหม่' ?></h2>
        </div>
        <div class="admin-card-body">
            <?php if ($editAdmin): ?>
            <form method="post" class="admin-subform-panel">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="admin_id" value="<?= (int) $editAdmin['id'] ?>">
                <div class="form-group">
                    <label>ชื่อผู้ใช้</label>
                    <input type="text" name="username" class="form-control" required value="<?= e($editAdmin['username']) ?>">
                </div>
                <div class="form-group">
                    <label>ชื่อ-นามสกุล</label>
                    <input type="text" name="full_name" class="form-control" required value="<?= e($editAdmin['full_name']) ?>">
                </div>
                <div class="form-group">
                    <label>อีเมล</label>
                    <input type="email" name="email" class="form-control" value="<?= e($editAdmin['email'] ?? '') ?>">
                </div>
                <button type="submit" class="btn btn-primary">บันทึก</button>
                <a href="<?= APP_URL ?>/admin/users.php" class="btn btn-outline">ยกเลิก</a>
            </form>

            <h3 class="admin-subsection-title">เปลี่ยนรหัสผ่าน</h3>
            <form method="post">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="password">
                <input type="hidden" name="admin_id" value="<?= (int) $editAdmin['id'] ?>">
                <?php if ((int) $editAdmin['id'] === (int) ($admin['id'] ?? 0)): ?>
                <div class="form-group">
                    <label>รหัสผ่านปัจจุบัน</label>
                    <input type="password" name="current_password" class="form-control" required>
                </div>
                <?php endif; ?>
                <div class="form-group">
                    <label>รหัสผ่านใหม่</label>
                    <input type="password" name="new_password" class="form-control" required minlength="6">
                </div>
                <button type="submit" class="btn btn-primary">เปลี่ยนรหัสผ่าน</button>
            </form>
            <?php else: ?>
            <form method="post">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="create">
                <div class="form-group">
                    <label>ชื่อผู้ใช้ *</label>
                    <input type="text" name="username" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>ชื่อ-นามสกุล *</label>
                    <input type="text" name="full_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>อีเมล</label>
                    <input type="email" name="email" class="form-control">
                </div>
                <div class="form-group">
                    <label>รหัสผ่าน *</label>
                    <input type="password" name="password" class="form-control" required minlength="6">
                </div>
                <button type="submit" class="btn btn-primary">เพิ่มผู้ดูแล</button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/admin_footer.php'; ?>
