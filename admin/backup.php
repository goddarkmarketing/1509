<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/backup.php';
requireAdmin();

$action = (string) ($_POST['action'] ?? $_GET['action'] ?? '');

if ($action === 'download') {
    $filename = basename((string) ($_GET['file'] ?? ''));
    $path = getBackupFilePath($filename);
    if ($path === null) {
        flash('admin_error', 'ไม่พบไฟล์สำรองที่ต้องการดาวน์โหลด');
        redirect('/admin/backup.php');
    }

    header('Content-Type: application/sql; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . (string) filesize($path));
    readfile($path);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    if ($action === 'create') {
        try {
            $created = createBackupFile();
            flash(
                'admin_success',
                'สร้างไฟล์สำรองเรียบร้อย: ' . $created['filename']
            );
        } catch (Throwable $e) {
            flash('admin_error', 'สร้างไฟล์สำรองไม่สำเร็จ: ' . $e->getMessage());
        }
        redirect('/admin/backup.php');
    }

    if ($action === 'delete') {
        $filename = basename((string) ($_POST['file'] ?? ''));
        if (deleteBackupFile($filename)) {
            flash('admin_success', 'ลบไฟล์สำรองเรียบร้อย');
        } else {
            flash('admin_error', 'ลบไฟล์ไม่สำเร็จ หรือไม่พบไฟล์');
        }
        redirect('/admin/backup.php');
    }
}

$message = flash('admin_success');
$error = flash('admin_error');
$backups = listBackupFiles();

$pageTitle = 'สำรองข้อมูล';
require_once dirname(__DIR__) . '/includes/admin_header.php';
?>

<?php if ($message): ?>
<div class="alert alert-success"><?= e($message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert alert-error"><?= e($error) ?></div>
<?php endif; ?>

<div class="admin-card">
    <div class="admin-card-header">
        <h2>สำรองฐานข้อมูล</h2>
        <form method="post" class="backup-create-form">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="create">
            <button type="submit" class="btn btn-primary btn-sm">+ สร้างไฟล์สำรองใหม่</button>
        </form>
    </div>
    <div class="admin-card-body">
        <p class="backup-intro">
            สร้างไฟล์ SQL สำรองข้อมูลทั้งหมดของระบบ เก็บไว้บนเซิร์ฟเวอร์ และดาวน์โหลดหรือลบได้ตามต้องการ
        </p>
        <p class="backup-intro-note">
            ไฟล์จะรวมตารางทั้งหมด รวมถึงนักเรียน การชำระเงิน คอร์ส และการตั้งค่า
        </p>
    </div>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <h2>ไฟล์สำรองทั้งหมด</h2>
        <span class="backup-count"><?= count($backups) ?> ไฟล์</span>
    </div>
    <div class="admin-card-body is-flush">
        <?php if ($backups): ?>
        <div class="table-responsive">
            <table class="admin-table backup-table">
                <thead>
                    <tr>
                        <th>ชื่อไฟล์</th>
                        <th>วันที่สร้าง</th>
                        <th>เวลา</th>
                        <th>ขนาด</th>
                        <th class="actions">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($backups as $backup): ?>
                    <tr>
                        <td><code class="backup-filename"><?= e($backup['filename']) ?></code></td>
                        <td><?= e(date('d/m/Y', $backup['created_at'])) ?></td>
                        <td><?= e(date('H:i:s', $backup['created_at'])) ?></td>
                        <td><?= e(formatBackupSize($backup['size'])) ?></td>
                        <td class="actions">
                            <div class="table-actions">
                            <a
                                href="<?= e(APP_URL . '/admin/backup.php?action=download&file=' . urlencode($backup['filename'])) ?>"
                                class="btn btn-outline btn-sm"
                            >ดาวน์โหลด</a>
                            <form method="post" class="backup-delete-form" onsubmit="return confirm('ลบไฟล์สำรองนี้?');">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="file" value="<?= e($backup['filename']) ?>">
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
        <p class="table-empty">ยังไม่มีไฟล์สำรอง — กดปุ่ม「สร้างไฟล์สำรองใหม่」ด้านบน</p>
        <?php endif; ?>
    </div>
</div>

<div class="admin-card">
    <div class="admin-card-header"><h2>คำแนะนำ</h2></div>
    <div class="admin-card-body">
        <ul class="backup-tips">
            <li>เก็บไฟล์สำรองในที่ปลอดภัย อย่าแชร์สาธารณะ (มีข้อมูลส่วนตัว)</li>
            <li>บน production ควรตั้ง cron สำรองอัตโนมัติทุกวัน</li>
            <li>ทดสอบ restore บนเครื่องทดสอบก่อนใช้จริง</li>
        </ul>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/admin_footer.php'; ?>
