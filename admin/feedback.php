<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
requireAdmin();
require_once dirname(__DIR__) . '/includes/feedback.php';

feedbackEnsureStorage();
$config = feedbackLoadConfig();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = (string) ($_POST['action'] ?? '');

    try {
        if ($action === 'status') {
            $id = (string) ($_POST['id'] ?? '');
            $status = (string) ($_POST['status'] ?? '');
            if (!feedbackUpdateStatus($id, $status)) {
                flash('admin_error', 'อัปเดตสถานะไม่สำเร็จ');
            } else {
                flash('admin_success', 'อัปเดตสถานะ ' . $id . ' แล้ว');
            }
        } elseif ($action === 'delete') {
            $id = (string) ($_POST['id'] ?? '');
            if (!feedbackDeleteItem($id)) {
                flash('admin_error', 'ลบไม่สำเร็จ');
            } else {
                flash('admin_success', 'ลบ ' . $id . ' แล้ว');
            }
        } elseif ($action === 'settings') {
            $enabled = isset($_POST['enabled']);
            $newPass = trim((string) ($_POST['password'] ?? ''));
            $clearPass = isset($_POST['clear_password']);
            $rotate = isset($_POST['rotate_token']);
            $config['enabled'] = $enabled;
            if ($rotate || $config['token'] === '') {
                $config['token'] = bin2hex(random_bytes(16));
            }
            if ($clearPass) {
                $config['password_hash'] = '';
            } elseif ($newPass !== '') {
                $config['password_hash'] = password_hash($newPass, PASSWORD_DEFAULT);
            }
            feedbackSaveConfig($config);
            flash('admin_success', 'บันทึกการตั้งค่า Feedback แล้ว');
        } elseif ($action === 'export_json') {
            $items = feedbackLoadItems();
            header('Content-Type: application/json; charset=UTF-8');
            header('Content-Disposition: attachment; filename="feedback.json"');
            echo json_encode(['updatedAt' => date('c'), 'items' => $items], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        } elseif ($action === 'export_md') {
            $items = feedbackLoadItems();
            feedbackWriteMarkdown($items);
            header('Content-Type: text/markdown; charset=UTF-8');
            header('Content-Disposition: attachment; filename="FEEDBACK.md"');
            echo is_file(FEEDBACK_MD_PATH) ? file_get_contents(FEEDBACK_MD_PATH) : '';
            exit;
        } elseif ($action === 'export_images') {
            if (!class_exists('ZipArchive')) {
                flash('admin_error', 'เซิร์ฟเวอร์ไม่รองรับ ZIP');
                redirect('/admin/feedback.php');
            }
            $tmp = sys_get_temp_dir() . '/feedback_images_' . bin2hex(random_bytes(4)) . '.zip';
            $zip = new ZipArchive();
            $zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE);
            $added = 0;
            foreach (feedbackLoadItems() as $item) {
                $id = (string) ($item['id'] ?? '');
                $path = feedbackScreenshotPath($id);
                if ($path) {
                    $zip->addFile($path, $id . '.png');
                    $added++;
                }
            }
            $zip->close();
            if ($added === 0) {
                @unlink($tmp);
                flash('admin_error', 'ยังไม่มีภาพ Screenshot');
                redirect('/admin/feedback.php');
            }
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="feedback-screenshots.zip"');
            header('Content-Length: ' . (string) filesize($tmp));
            readfile($tmp);
            @unlink($tmp);
            exit;
        } elseif ($action === 'export_package') {
            $tmp = feedbackBuildZipPackage();
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="feedback-package.zip"');
            header('Content-Length: ' . (string) filesize($tmp));
            readfile($tmp);
            @unlink($tmp);
            exit;
        }
    } catch (Throwable $e) {
        flash('admin_error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
    }
    redirect('/admin/feedback.php');
}

$filters = [
    'page' => trim((string) ($_GET['page'] ?? '')),
    'category' => trim((string) ($_GET['category'] ?? '')),
    'priority' => trim((string) ($_GET['priority'] ?? '')),
    'status' => trim((string) ($_GET['status'] ?? '')),
];
$items = feedbackFilterItems(feedbackLoadItems(), $filters);
$allItems = feedbackLoadItems();
$counts = ['pending' => 0, 'in-progress' => 0, 'completed' => 0, 'rejected' => 0];
foreach ($allItems as $it) {
    $st = (string) ($it['status'] ?? 'pending');
    if (isset($counts[$st])) {
        $counts[$st]++;
    }
}

$categories = feedbackCategories();
$priorities = feedbackPriorities();
$statuses = feedbackStatuses();
$reviewUrl = feedbackReviewUrl();
$adminPreviewUrl = APP_URL . '/admin/dashboard.php?feedback=1';

$pageTitle = 'Website Feedback';
require_once dirname(__DIR__) . '/includes/admin_header.php';
$message = flash('admin_success');
$error = flash('admin_error');
?>

<?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

<div class="stats-grid" data-feedback-id="dashboard-summary">
    <div class="stat-card">
        <h3>ทั้งหมด</h3>
        <div class="stat-value"><?= count($allItems) ?></div>
    </div>
    <div class="stat-card gold">
        <h3>รอดำเนินการ</h3>
        <div class="stat-value"><?= (int) $counts['pending'] ?></div>
    </div>
    <div class="stat-card">
        <h3>กำลังแก้</h3>
        <div class="stat-value"><?= (int) $counts['in-progress'] ?></div>
    </div>
    <div class="stat-card">
        <h3>เสร็จแล้ว</h3>
        <div class="stat-value"><?= (int) $counts['completed'] ?></div>
    </div>
</div>

<div class="admin-card" style="margin-bottom:1.25rem">
    <div class="admin-card-header">
        <h2>ลิงก์ตรวจงานสำหรับลูกค้า</h2>
    </div>
    <div class="admin-card-body">
        <p style="margin-top:0">ระบบนี้ใช้ตรวจ<strong>หลังบ้าน (Admin)</strong> เท่านั้น — ส่งลิงก์ให้ลูกค้า/ทีมตรวจงาน แล้วเข้าสู่ระบบแอดมินเพื่อแจ้งแก้ไข ไม่แสดงบนเว็บหน้าบ้าน</p>
        <div class="form-group">
            <label>ลิงก์ Preview (หลังบ้าน)</label>
            <input type="text" class="form-control" readonly value="<?= e($reviewUrl) ?>" onclick="this.select()">
        </div>
        <p class="form-hint">แอดมินเปิดโหมดเอง: <a href="<?= e($adminPreviewUrl) ?>"><?= e($adminPreviewUrl) ?></a></p>
        <p class="form-hint">ไฟล์สำหรับ Cursor: <code>feedback/feedback.json</code>, <code>feedback/FEEDBACK.md</code>, <code>feedback/images/</code></p>
        <form method="post" class="admin-form-actions" style="margin-top:1rem;display:flex;flex-wrap:wrap;gap:.5rem;align-items:end">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="settings">
            <label style="display:flex;align-items:center;gap:.4rem;margin:0">
                <input type="checkbox" name="enabled" <?= !empty($config['enabled']) ? 'checked' : '' ?>> เปิดระบบ Feedback
            </label>
            <div class="form-group" style="margin:0;min-width:180px">
                <label>ตั้ง/เปลี่ยนรหัสผ่านตรวจงาน</label>
                <input type="password" name="password" class="form-control" placeholder="ว่างไว้ = ไม่เปลี่ยน" autocomplete="new-password">
            </div>
            <label style="display:flex;align-items:center;gap:.4rem;margin:0">
                <input type="checkbox" name="clear_password"> ล้างรหัสผ่าน
            </label>
            <label style="display:flex;align-items:center;gap:.4rem;margin:0">
                <input type="checkbox" name="rotate_token"> สร้างโทเคนใหม่
            </label>
            <button type="submit" class="btn btn-primary btn-sm">บันทึกการตั้งค่า</button>
        </form>
        <?php if (!empty($config['password_hash'])): ?>
        <p class="form-hint" style="color:#15803d;margin-bottom:0">กำลังใช้รหัสผ่านป้องกันหน้าตรวจงาน</p>
        <?php else: ?>
        <p class="form-hint" style="color:#b45309;margin-bottom:0">ยังไม่มีรหัสผ่าน — คนที่มีลิงก์สามารถเข้าได้ทันที</p>
        <?php endif; ?>
    </div>
</div>

<div class="admin-card" style="margin-bottom:1.25rem">
    <div class="admin-card-header">
        <h2>Export</h2>
    </div>
    <div class="admin-card-body" style="display:flex;flex-wrap:wrap;gap:.5rem">
        <form method="post"><?= csrfField() ?><input type="hidden" name="action" value="export_json"><button class="btn btn-outline btn-sm" type="submit">Export JSON</button></form>
        <form method="post"><?= csrfField() ?><input type="hidden" name="action" value="export_md"><button class="btn btn-outline btn-sm" type="submit">Export Markdown</button></form>
        <form method="post"><?= csrfField() ?><input type="hidden" name="action" value="export_images"><button class="btn btn-outline btn-sm" type="submit">Download All Screenshots</button></form>
        <form method="post"><?= csrfField() ?><input type="hidden" name="action" value="export_package"><button class="btn btn-primary btn-sm" type="submit">Export Feedback Package</button></form>
    </div>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <h2>รายการ Feedback (<?= count($items) ?>)</h2>
    </div>
    <div class="admin-card-body">
        <form method="get" class="form-row" style="margin-bottom:1rem;align-items:end">
            <div class="form-group">
                <label>หน้า / route</label>
                <input type="text" name="page" class="form-control" value="<?= e($filters['page']) ?>" placeholder="เช่น courses">
            </div>
            <div class="form-group">
                <label>ประเภท</label>
                <select name="category" class="form-control">
                    <option value="">ทั้งหมด</option>
                    <?php foreach ($categories as $k => $label): ?>
                    <option value="<?= e($k) ?>" <?= $filters['category'] === $k ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>ความสำคัญ</label>
                <select name="priority" class="form-control">
                    <option value="">ทั้งหมด</option>
                    <?php foreach ($priorities as $k => $label): ?>
                    <option value="<?= e($k) ?>" <?= $filters['priority'] === $k ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>สถานะ</label>
                <select name="status" class="form-control">
                    <option value="">ทั้งหมด</option>
                    <?php foreach ($statuses as $k => $label): ?>
                    <option value="<?= e($k) ?>" <?= $filters['status'] === $k ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-primary btn-sm">กรอง</button>
                <a href="<?= APP_URL ?>/admin/feedback.php" class="btn btn-outline btn-sm">ล้าง</a>
            </div>
        </form>

        <?php if (!$items): ?>
        <div class="lesson-empty">ยังไม่มีรายการ Feedback<?= ($filters['page'] || $filters['category'] || $filters['priority'] || $filters['status']) ? ' ตามตัวกรองนี้' : '' ?></div>
        <?php else: ?>
        <div class="data-table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>หมายเลข</th>
                        <th>ภาพ</th>
                        <th>หน้า</th>
                        <th>รายละเอียด</th>
                        <th>ประเภท</th>
                        <th>ความสำคัญ</th>
                        <th>สถานะ</th>
                        <th>วันที่</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                    <?php
                        $id = (string) ($item['id'] ?? '');
                        $hasImg = feedbackScreenshotPath($id) !== null;
                        $created = (string) ($item['createdAt'] ?? '');
                        $createdLabel = $created !== '' ? date('d/m/Y H:i', strtotime($created) ?: time()) : '-';
                    ?>
                    <tr>
                        <td><strong><?= e($id) ?></strong></td>
                        <td>
                            <?php if ($hasImg): ?>
                            <a href="<?= APP_URL ?>/admin/feedback_image.php?id=<?= e(urlencode($id)) ?>" target="_blank" rel="noopener">
                                <img src="<?= APP_URL ?>/admin/feedback_image.php?id=<?= e(urlencode($id)) ?>" alt="<?= e($id) ?>" style="width:88px;height:56px;object-fit:cover;border-radius:6px;border:1px solid #e5e7eb">
                            </a>
                            <?php else: ?>
                            <span class="form-hint">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div><?= e((string) ($item['page'] ?? '')) ?></div>
                            <small class="form-hint"><?= e((string) ($item['route'] ?? '')) ?></small>
                            <?php if (!empty($item['feedbackId'])): ?>
                            <div><code><?= e((string) $item['feedbackId']) ?></code></div>
                            <?php endif; ?>
                        </td>
                        <td style="max-width:280px">
                            <div><?= e((string) ($item['comment'] ?? '')) ?></div>
                            <?php if (!empty($item['clientName'])): ?>
                            <small class="form-hint">โดย <?= e((string) $item['clientName']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?= e($categories[$item['category'] ?? ''] ?? (string) ($item['category'] ?? '')) ?></td>
                        <td><?= e($priorities[$item['priority'] ?? ''] ?? (string) ($item['priority'] ?? '')) ?></td>
                        <td>
                            <form method="post" style="margin:0">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="status">
                                <input type="hidden" name="id" value="<?= e($id) ?>">
                                <select name="status" class="form-control" style="min-width:120px" onchange="this.form.submit()">
                                    <?php foreach ($statuses as $k => $label): ?>
                                    <option value="<?= e($k) ?>" <?= ($item['status'] ?? '') === $k ? 'selected' : '' ?>><?= e($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                        </td>
                        <td><?= e($createdLabel) ?></td>
                        <td>
                            <form method="post" onsubmit="return confirm('ลบ <?= e($id) ?> ?')">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= e($id) ?>">
                                <button type="submit" class="btn btn-outline btn-sm">ลบ</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/admin_footer.php'; ?>
