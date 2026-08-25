<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id = (int) ($_POST['id'] ?? 0);
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $type = $_POST['discount_type'] ?? 'percent';
        $value = (float) ($_POST['discount_value'] ?? 0);
        $min = (float) ($_POST['min_amount'] ?? 0);
        $maxUses = (int) ($_POST['max_uses'] ?? 0);
        $expires = trim($_POST['expires_at'] ?? '') ?: null;
        $active = isset($_POST['is_active']) ? 1 : 0;
        if ($code && $value > 0 && in_array($type, ['percent', 'fixed'], true)) {
            if ($id) {
                $stmt = db()->prepare('UPDATE coupons SET code=?, discount_type=?, discount_value=?, min_amount=?, max_uses=?, expires_at=?, is_active=? WHERE id=?');
                $stmt->execute([$code, $type, $value, $min, $maxUses, $expires, $active, $id]);
            } else {
                $stmt = db()->prepare('INSERT INTO coupons (code, discount_type, discount_value, min_amount, max_uses, expires_at, is_active) VALUES (?,?,?,?,?,?,?)');
                $stmt->execute([$code, $type, $value, $min, $maxUses, $expires, $active]);
            }
            flash('admin_success', 'บันทึกคูปองเรียบร้อย');
        }
    }
    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id) {
            db()->prepare('DELETE FROM coupons WHERE id = ?')->execute([$id]);
            flash('admin_success', 'ลบคูปองแล้ว');
        }
    }
    redirect('/admin/coupons.php');
}

$pageTitle = 'คูปองส่วนลด';
require_once dirname(__DIR__) . '/includes/admin_header.php';

$message = flash('admin_success');
$showNew = isset($_GET['new']);
$editId = (int) ($_GET['id'] ?? 0);
$edit = null;
if ($editId) {
    $stmt = db()->prepare('SELECT * FROM coupons WHERE id = ?');
    $stmt->execute([$editId]);
    $edit = $stmt->fetch() ?: null;
}

$openCouponPanel = '';
if ($showNew) {
    $openCouponPanel = 'new';
} elseif ($edit) {
    $openCouponPanel = (string) $editId;
}

$coupons = db()->query('SELECT * FROM coupons ORDER BY created_at DESC')->fetchAll();

$renderCouponForm = static function (?array $coupon): void {
    ?>
    <form method="post" class="coupon-modal-form">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="save">
        <?php if ($coupon): ?><input type="hidden" name="id" value="<?= (int) $coupon['id'] ?>"><?php endif; ?>
        <div class="form-group">
            <label>รหัสคูปอง *</label>
            <input type="text" name="code" class="form-control" required value="<?= e($coupon['code'] ?? '') ?>" style="text-transform:uppercase">
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>ประเภท</label>
                <select name="discount_type" class="form-control">
                    <option value="percent" <?= ($coupon['discount_type'] ?? 'percent') === 'percent' ? 'selected' : '' ?>>เปอร์เซ็นต์ (%)</option>
                    <option value="fixed" <?= ($coupon['discount_type'] ?? '') === 'fixed' ? 'selected' : '' ?>>ลดเป็นบาท</option>
                </select>
            </div>
            <div class="form-group">
                <label>มูลค่าส่วนลด *</label>
                <input type="number" name="discount_value" class="form-control" step="0.01" min="0" required value="<?= e((string) ($coupon['discount_value'] ?? '')) ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>ยอดขั้นต่ำ (บาท)</label>
                <input type="number" name="min_amount" class="form-control" step="0.01" min="0" value="<?= e((string) ($coupon['min_amount'] ?? '0')) ?>">
            </div>
            <div class="form-group">
                <label>ใช้ได้สูงสุด (ครั้ง, 0=ไม่จำกัด)</label>
                <input type="number" name="max_uses" class="form-control" min="0" value="<?= (int) ($coupon['max_uses'] ?? 0) ?>">
            </div>
        </div>
        <div class="form-group">
            <label>วันหมดอายุ</label>
            <input type="date" name="expires_at" class="form-control" value="<?= e($coupon['expires_at'] ?? '') ?>">
        </div>
        <label><input type="checkbox" name="is_active" <?= ($coupon['is_active'] ?? 1) ? 'checked' : '' ?>> เปิดใช้งาน</label>
        <div class="coupons-form-actions">
            <button type="submit" class="btn btn-primary">บันทึก</button>
            <button type="button" class="btn btn-secondary" data-close-coupon-modal>ยกเลิก</button>
        </div>
    </form>
    <?php
};
?>

<?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>

<div class="coupons-admin-page">
    <div class="admin-card coupons-list-card">
        <div class="admin-card-header">
            <h2>รายการคูปอง (<?= count($coupons) ?>)</h2>
            <button type="button" class="btn btn-primary btn-sm" data-open-coupon="new">+ เพิ่มคูปอง</button>
        </div>
        <div class="admin-card-body is-flush coupons-list-scroll">
            <table class="data-table coupons-compact-table">
                <thead>
                    <tr>
                        <th>รหัส</th>
                        <th>ส่วนลด</th>
                        <th>ยอดขั้นต่ำ</th>
                        <th>ใช้แล้ว</th>
                        <th>หมดอายุ</th>
                        <th>สถานะ</th>
                        <th class="coupons-col-actions">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$coupons): ?>
                    <tr>
                        <td colspan="7" class="coupons-empty">ยังไม่มีคูปอง — กด «เพิ่มคูปอง» เพื่อสร้างรายการแรก</td>
                    </tr>
                    <?php endif; ?>
                    <?php foreach ($coupons as $c): ?>
                    <tr data-coupon-row="<?= (int) $c['id'] ?>">
                        <td><strong class="coupons-code"><?= e($c['code']) ?></strong></td>
                        <td><?= $c['discount_type'] === 'fixed' ? formatPrice((float) $c['discount_value']) : (int) $c['discount_value'] . '%' ?></td>
                        <td><?= (float) $c['min_amount'] > 0 ? formatPrice((float) $c['min_amount']) : '-' ?></td>
                        <td><?= (int) $c['used_count'] ?><?= (int) $c['max_uses'] ? ' / ' . (int) $c['max_uses'] : '' ?></td>
                        <td class="coupons-col-date"><?= $c['expires_at'] ? e(date('d/m/Y', strtotime($c['expires_at']))) : '-' ?></td>
                        <td>
                            <span class="badge <?= $c['is_active'] ? 'badge-active coupons-status-badge' : 'coupons-status-badge coupons-status-badge--muted' ?>">
                                <?= $c['is_active'] ? 'เปิด' : 'ปิด' ?>
                            </span>
                        </td>
                        <td class="coupons-col-actions">
                            <div class="table-actions coupons-row-actions">
                                <button type="button" class="btn btn-secondary btn-sm" data-open-coupon="<?= (int) $c['id'] ?>">แก้ไข</button>
                                <form method="post" class="coupons-delete-form" onsubmit="return confirm('ลบ?')">
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

<div class="admin-modal" id="couponFormModal" hidden>
    <div class="admin-modal-backdrop" data-close-coupon-modal></div>
    <div class="admin-modal-dialog coupon-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="couponModalTitle">
        <button type="button" class="admin-modal-close" data-close-coupon-modal aria-label="ปิด">
            <?= lucide_icon('x', ['size' => 20]) ?>
        </button>

        <div class="coupon-modal-panel" id="coupon-panel-new" data-coupon-panel hidden>
            <div class="admin-modal-header">
                <h2 id="couponModalTitle">เพิ่มคูปอง</h2>
            </div>
            <div class="admin-modal-body">
                <?php $renderCouponForm(null); ?>
            </div>
        </div>

        <?php foreach ($coupons as $c): ?>
        <div class="coupon-modal-panel" id="coupon-panel-<?= (int) $c['id'] ?>" data-coupon-panel hidden>
            <div class="admin-modal-header">
                <h2 id="couponModalTitle-<?= (int) $c['id'] ?>">แก้ไขคูปอง</h2>
                <span class="coupons-code coupons-code--pill"><?= e($c['code']) ?></span>
            </div>
            <div class="admin-modal-body">
                <?php $renderCouponForm($c); ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php if ($openCouponPanel !== ''): ?>
<script>window.__openCouponPanel = <?= json_encode($openCouponPanel, JSON_UNESCAPED_UNICODE) ?>;</script>
<?php endif; ?>

<?php require_once dirname(__DIR__) . '/includes/admin_footer.php'; ?>
