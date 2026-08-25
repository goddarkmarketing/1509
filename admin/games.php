<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/game.php';
requireAdmin();

$filterCourse = (int) ($_GET['course_id'] ?? 0);
$gameId = (int) ($_GET['game_id'] ?? 0);
$action = $_GET['action'] ?? 'list';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $postAction = $_POST['action'] ?? '';

    if ($postAction === 'save_game') {
        $id = (int) ($_POST['id'] ?? 0);
        $courseId = (int) ($_POST['course_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $gameUrl = normalizeGameUrl($_POST['game_url'] ?? '');
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        $published = isset($_POST['is_published']) ? 1 : 0;

        if ($courseId && $title && $gameUrl) {
            if ($id) {
                $stmt = db()->prepare('
                    UPDATE course_games
                    SET course_id = ?, title = ?, description = ?, game_url = ?, sort_order = ?, is_published = ?
                    WHERE id = ?
                ');
                $stmt->execute([$courseId, $title, $description ?: null, $gameUrl, $sortOrder, $published, $id]);
            } else {
                $stmt = db()->prepare('
                    INSERT INTO course_games (course_id, title, description, game_url, sort_order, is_published)
                    VALUES (?, ?, ?, ?, ?, ?)
                ');
                $stmt->execute([$courseId, $title, $description ?: null, $gameUrl, $sortOrder, $published]);
            }
            flash('admin_success', 'บันทึกเกมเรียบร้อย');
        } else {
            flash('admin_error', 'กรุณากรอกชื่อเกมและ URL ที่ถูกต้อง (ขึ้นต้นด้วย http:// หรือ https://)');
        }
        redirect('/admin/games.php' . ($id ? '?action=edit&game_id=' . $id : ($filterCourse ? '?course_id=' . $filterCourse : '')));
    }

    if ($postAction === 'delete_game') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id) {
            db()->prepare('DELETE FROM course_games WHERE id = ?')->execute([$id]);
            flash('admin_success', 'ลบเกมแล้ว');
        }
        redirect('/admin/games.php' . ($filterCourse ? '?course_id=' . $filterCourse : ''));
    }
}

$pageTitle = 'เกมฝึกฝน';
require_once dirname(__DIR__) . '/includes/admin_header.php';

$message = flash('admin_success');

$courses = getCourses(null, false);
$games = [];
$sql = 'SELECT g.*, c.title AS course_title FROM course_games g JOIN courses c ON c.id = g.course_id';
$params = [];
if ($filterCourse) {
    $sql .= ' WHERE g.course_id = ?';
    $params[] = $filterCourse;
}
$sql .= ' ORDER BY g.course_id, g.sort_order, g.id';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$games = $stmt->fetchAll();

$editGame = $gameId && $action === 'edit' ? getGameById($gameId) : null;
$errorMessage = flash('admin_error');
?>

<?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
<?php if ($errorMessage): ?><div class="alert alert-danger"><?= e($errorMessage) ?></div><?php endif; ?>

<?php if ($action === 'add' || $editGame): ?>
<div class="admin-card">
    <div class="admin-card-header">
        <h2><?= $editGame ? 'แก้ไขเกม' : 'เพิ่มเกม' ?></h2>
        <a href="<?= APP_URL ?>/admin/games.php<?= $filterCourse ? '?course_id=' . $filterCourse : '' ?>" class="btn btn-secondary btn-sm">กลับ</a>
    </div>
    <div class="admin-card-body">
        <form method="post">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="save_game">
            <?php if ($editGame): ?><input type="hidden" name="id" value="<?= (int) $editGame['id'] ?>"><?php endif; ?>
            <div class="form-group">
                <label>คอร์ส *</label>
                <select name="course_id" class="form-control" required>
                    <?php foreach ($courses as $c): ?>
                    <option value="<?= (int) $c['id'] ?>" <?= (($editGame['course_id'] ?? $filterCourse) == $c['id']) ? 'selected' : '' ?>><?= e($c['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>ชื่อเกม *</label>
                <input type="text" name="title" class="form-control" required value="<?= e($editGame['title'] ?? '') ?>" placeholder="เช่น เกมฝึกคำศัพท์ HSK 1">
            </div>
            <div class="form-group">
                <label>รายละเอียด</label>
                <textarea name="description" class="form-control" rows="3" placeholder="อธิบายสั้นๆ ว่าเกมนี้ฝึกอะไร"><?= e($editGame['description'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label>ลิงก์เกม (URL) *</label>
                <input type="url" name="game_url" class="form-control" required value="<?= e($editGame['game_url'] ?? '') ?>" placeholder="https://example.com/game">
                <small>ใส่ลิงก์ไปยังแพลตฟอร์มเกมภายนอก นักเรียนจะถูกส่งออกไปเล่นที่ลิงก์นี้</small>
            </div>
            <div class="form-group">
                <label>ลำดับ</label>
                <input type="number" name="sort_order" class="form-control" value="<?= (int) ($editGame['sort_order'] ?? 0) ?>">
            </div>
            <div class="form-group">
                <label><input type="checkbox" name="is_published" <?= ($editGame['is_published'] ?? 1) ? 'checked' : '' ?>> เผยแพร่</label>
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
        <h2>เกมฝึกฝน (ลิงก์ภายนอก)</h2>
        <a href="<?= APP_URL ?>/admin/games.php?action=add<?= $filterCourse ? '&course_id=' . $filterCourse : '' ?>" class="btn btn-primary btn-sm">+ เพิ่มเกม</a>
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
        <?php if ($games): ?>
        <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr><th>คอร์ส</th><th>ชื่อเกม</th><th>ลิงก์</th><th>สถานะ</th><th class="actions">จัดการ</th></tr>
            </thead>
            <tbody>
                <?php foreach ($games as $g): ?>
                <tr>
                    <td><?= e($g['course_title']) ?></td>
                    <td><?= e($g['title']) ?></td>
                    <td><a href="<?= e($g['game_url']) ?>" target="_blank" rel="noopener"><?= e(mb_strimwidth($g['game_url'], 0, 40, '...')) ?></a></td>
                    <td><?= $g['is_published'] ? 'เผยแพร่' : 'ซ่อน' ?></td>
                    <td class="actions">
                        <div class="table-actions">
                        <a href="<?= APP_URL ?>/public/game.php?game_id=<?= (int) $g['id'] ?>" target="_blank" rel="noopener" class="btn btn-outline btn-sm">ดู</a>
                        <a href="<?= APP_URL ?>/admin/games.php?action=edit&game_id=<?= (int) $g['id'] ?>" class="btn btn-secondary btn-sm">แก้ไข</a>
                        <form method="post" onsubmit="return confirm('ลบเกมนี้?')">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="delete_game">
                            <input type="hidden" name="id" value="<?= (int) $g['id'] ?>">
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
        <p class="table-empty">ยังไม่มีเกม — <a href="<?= APP_URL ?>/admin/games.php?action=add<?= $filterCourse ? '&course_id=' . $filterCourse : '' ?>">เพิ่มลิงก์เกมภายนอก</a></p>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php require_once dirname(__DIR__) . '/includes/admin_footer.php'; ?>
