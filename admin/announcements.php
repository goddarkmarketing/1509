<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/announcement.php';
require_once dirname(__DIR__) . '/includes/media_upload.php';
requireAdmin();

$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $postAction = $_POST['action'] ?? '';
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $excerpt = trim($_POST['excerpt'] ?? '');
    $body = trim($_POST['body'] ?? '');
    $category = $_POST['category'] ?? 'general';
    $imageUrl = trim($_POST['image_url'] ?? '');
    $attachmentUrl = trim($_POST['existing_attachment_url'] ?? '');
    $attachmentName = trim($_POST['existing_attachment_name'] ?? '');
    $sortOrder = (int) ($_POST['sort_order'] ?? 0);
    $isPinned = isset($_POST['is_pinned']) ? 1 : 0;
    $isPublished = isset($_POST['is_published']) ? 1 : 0;
    $publishedAt = trim($_POST['published_at'] ?? '') ?: null;
    $editId = (int) ($_POST['id'] ?? 0);

    if (!in_array($category, ['general', 'promo', 'course', 'event'], true)) {
        $category = 'general';
    }

    if ($title === '' || $body === '') {
        flash('admin_error', 'กรุณากรอกหัวข้อและเนื้อหา');
        redirect('/admin/announcements.php' . ($editId ? '?action=edit&id=' . $editId : '?action=add'));
    }

    if ($slug === '') {
        $slug = makeAnnouncementSlug($title, $editId ?: null);
    } else {
        $slug = trim(preg_replace('/[^a-z0-9-]+/', '-', strtolower($slug)) ?? '', '-');
        if (announcementSlugExists($slug, $editId ?: null)) {
            flash('admin_error', 'Slug นี้ถูกใช้แล้ว');
            redirect('/admin/announcements.php' . ($editId ? '?action=edit&id=' . $editId : '?action=add'));
        }
    }

    if (!empty($_FILES['cover_image']['name'])) {
        $uploaded = storeAnnouncementImageUpload($_FILES['cover_image']);
        if ($uploaded === false) {
            redirect('/admin/announcements.php' . ($editId ? '?action=edit&id=' . $editId : '?action=add'));
        }
        if (is_string($uploaded)) {
            $imageUrl = $uploaded;
        }
    }

    if (isset($_POST['remove_attachment'])) {
        $attachmentUrl = '';
        $attachmentName = '';
    } elseif (!empty($_FILES['attachment_pdf']['name'])) {
        $uploadedPdf = storeAnnouncementPdfUpload($_FILES['attachment_pdf']);
        if ($uploadedPdf === false) {
            redirect('/admin/announcements.php' . ($editId ? '?action=edit&id=' . $editId : '?action=add'));
        }
        if (is_string($uploadedPdf)) {
            $attachmentUrl = $uploadedPdf;
            $attachmentName = trim((string) ($_FILES['attachment_pdf']['name'] ?? ''));
        }
    }

    try {
        if ($postAction === 'create') {
            $stmt = db()->prepare('
                INSERT INTO announcements (slug, title, excerpt, body, image_url, attachment_url, attachment_name, category, is_pinned, is_published, published_at, sort_order)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([
                $slug,
                $title,
                $excerpt ?: null,
                $body,
                $imageUrl ?: null,
                $attachmentUrl ?: null,
                $attachmentName ?: null,
                $category,
                $isPinned,
                $isPublished,
                $publishedAt,
                $sortOrder,
            ]);
            flash('admin_success', 'เพิ่มประกาศเรียบร้อย');
        } elseif ($postAction === 'update' && $editId) {
            $stmt = db()->prepare('
                UPDATE announcements
                SET slug=?, title=?, excerpt=?, body=?, image_url=?, attachment_url=?, attachment_name=?, category=?, is_pinned=?, is_published=?, published_at=?, sort_order=?
                WHERE id=?
            ');
            $stmt->execute([
                $slug,
                $title,
                $excerpt ?: null,
                $body,
                $imageUrl ?: null,
                $attachmentUrl ?: null,
                $attachmentName ?: null,
                $category,
                $isPinned,
                $isPublished,
                $publishedAt,
                $sortOrder,
                $editId,
            ]);
            flash('admin_success', 'อัปเดตประกาศเรียบร้อย');
        } elseif ($postAction === 'delete' && $editId) {
            db()->prepare('DELETE FROM announcements WHERE id = ?')->execute([$editId]);
            flash('admin_success', 'ลบประกาศเรียบร้อย');
        }
    } catch (Throwable $e) {
        flash('admin_error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
    }
    redirect('/admin/announcements.php');
}

$pageTitle = 'บอร์ดประชาสัมพันธ์';
require_once dirname(__DIR__) . '/includes/admin_header.php';

$message = flash('admin_success');
$error = flash('admin_error');
$editItem = $id ? getAnnouncementById($id) : null;
$announcements = getAnnouncements(false);
?>

<?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

<?php if ($action === 'add' || ($action === 'edit' && $editItem)): ?>
<div class="admin-card">
    <div class="admin-card-header">
        <h2><?= $editItem ? 'แก้ไขประกาศ' : 'เพิ่มประกาศใหม่' ?></h2>
        <a href="<?= APP_URL ?>/admin/announcements.php" class="btn btn-secondary btn-sm">กลับ</a>
    </div>
    <div class="admin-card-body">
        <form method="post" class="modal-form" enctype="multipart/form-data">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="<?= $editItem ? 'update' : 'create' ?>">
            <?php if ($editItem): ?><input type="hidden" name="id" value="<?= (int) $editItem['id'] ?>"><?php endif; ?>
            <div class="form-row">
                <div class="form-group">
                    <label>หัวข้อ *</label>
                    <input type="text" name="title" class="form-control" required value="<?= e($editItem['title'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Slug (URL)</label>
                    <input type="text" name="slug" class="form-control" value="<?= e($editItem['slug'] ?? '') ?>" placeholder="promo-hsk3">
                </div>
            </div>
            <div class="form-group">
                <label>คำเกริ่น (แสดงในรายการ)</label>
                <input type="text" name="excerpt" class="form-control" maxlength="500" value="<?= e($editItem['excerpt'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>เนื้อหา *</label>
                <textarea name="body" class="form-control" rows="8" required><?= e($editItem['body'] ?? '') ?></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>หมวดหมู่</label>
                    <select name="category" class="form-control">
                        <?php foreach (['general', 'promo', 'course', 'event'] as $cat): ?>
                        <option value="<?= $cat ?>" <?= ($editItem['category'] ?? 'general') === $cat ? 'selected' : '' ?>><?= e(announcementCategoryLabel($cat)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>วันที่เผยแพร่</label>
                    <input type="datetime-local" name="published_at" class="form-control" value="<?= e(isset($editItem['published_at']) && $editItem['published_at'] ? date('Y-m-d\TH:i', strtotime($editItem['published_at'])) : '') ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>รูปประกอบ</label>
                    <input type="file" name="cover_image" class="form-control" accept="image/*">
                    <?php if (!empty($editItem['image_url'])): ?>
                        <small style="color:var(--text-muted)">รูปปัจจุบัน: <?= e($editItem['image_url']) ?></small>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label>ไฟล์ PDF แนบ</label>
                    <input type="file" name="attachment_pdf" class="form-control" accept="application/pdf,.pdf">
                    <?php if (!empty($editItem['attachment_url'])): ?>
                        <input type="hidden" name="existing_attachment_url" value="<?= e($editItem['attachment_url']) ?>">
                        <input type="hidden" name="existing_attachment_name" value="<?= e($editItem['attachment_name'] ?? '') ?>">
                        <small style="color:var(--text-muted)">ไฟล์ปัจจุบัน: <?= e(announcementAttachmentLabel($editItem)) ?></small>
                        <label class="form-control-follow"><input type="checkbox" name="remove_attachment"> ลบไฟล์ PDF แนบ</label>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label>ลำดับแสดง</label>
                    <input type="number" name="sort_order" class="form-control" value="<?= (int) ($editItem['sort_order'] ?? 0) ?>">
                </div>
            </div>
            <div class="form-row">
                <label><input type="checkbox" name="is_pinned" <?= ($editItem['is_pinned'] ?? 0) ? 'checked' : '' ?>> ปักหมุด</label>
                <label><input type="checkbox" name="is_published" <?= ($editItem['is_published'] ?? 1) ? 'checked' : '' ?>> เผยแพร่</label>
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
        <h2>รายการประกาศ (<?= count($announcements) ?>)</h2>
        <a href="<?= APP_URL ?>/admin/announcements.php?action=add" class="btn btn-primary btn-sm">+ เพิ่มประกาศ</a>
    </div>
    <div class="admin-card-body is-flush">
        <?php if ($announcements): ?>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th class="col-thumb">รูป</th>
                        <th>หัวข้อ</th>
                        <th>หมวด</th>
                        <th>สถานะ</th>
                        <th>วันที่</th>
                        <th class="actions">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($announcements as $item): ?>
                    <?php $thumbUrl = announcementImageUrl($item['image_url'] ?? null); ?>
                    <tr>
                        <td class="col-thumb">
                            <?php if ($thumbUrl): ?>
                                <img src="<?= e($thumbUrl) ?>" alt="" class="admin-table-thumb" loading="lazy">
                            <?php else: ?>
                                <span class="admin-table-thumb admin-table-thumb--empty" aria-hidden="true">
                                    <?= lucide_icon('image', ['size' => 18, 'stroke' => '1.5']) ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($item['is_pinned'])): ?><span class="badge badge-gold">ปักหมุด</span> <?php endif; ?>
                            <?= e($item['title']) ?>
                        </td>
                        <td><?= e(announcementCategoryLabel($item['category'] ?? 'general')) ?></td>
                        <td><?= !empty($item['is_published']) ? 'เผยแพร่' : 'ซ่อน' ?></td>
                        <td><?= e(formatAnnouncementDate($item['published_at'] ?? $item['created_at'] ?? null)) ?></td>
                        <td class="actions">
                            <div class="table-actions">
                            <a href="<?= APP_URL ?>/public/announcement.php?slug=<?= urlencode($item['slug']) ?>" target="_blank" rel="noopener" class="btn btn-outline btn-sm">ดู</a>
                            <a href="?action=edit&id=<?= (int) $item['id'] ?>" class="btn btn-secondary btn-sm">แก้ไข</a>
                            <form method="post" onsubmit="return confirm('ลบประกาศนี้?')">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
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
        <p class="table-empty">ยังไม่มีประกาศ — <a href="<?= APP_URL ?>/admin/announcements.php?action=add">เพิ่มประกาศแรก</a></p>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php require_once dirname(__DIR__) . '/includes/admin_footer.php'; ?>
