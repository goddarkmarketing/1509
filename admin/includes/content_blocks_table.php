<?php

declare(strict_types=1);

/** @var array<int, array<string, mixed>> $rows */
/** @var string $tab */
/** @var string $editKey */
/** @var bool $showView */

$editKey = $editKey ?? 'block';
$showView = $showView ?? true;

?>
<div class="table-responsive">
    <table class="admin-table content-sections-table">
        <thead>
            <tr>
                <th class="col-thumb">ไอคอน</th>
                <th>หัวข้อ</th>
                <th>หมวด</th>
                <th>รายละเอียด</th>
                <th>สถานะ</th>
                <th class="actions">จัดการ</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $row): ?>
            <?php
                $editParam = urlencode((string) ($row[$editKey] ?? ''));
                $editHref = '?action=edit&amp;tab=' . urlencode($tab) . '&amp;block=' . $editParam;
                if ($editKey === 'index') {
                    $editHref = '?action=edit&amp;tab=' . urlencode($tab) . '&amp;block=item&amp;item=' . (int) ($row['index'] ?? 0);
                }
            ?>
            <tr>
                <td class="col-thumb">
                    <span class="admin-table-thumb content-section-thumb content-section-thumb--<?= e((string) ($row['icon'] ?? 'file-text')) ?>" aria-hidden="true">
                        <?= contentBlockLucideIcon((string) ($row['icon'] ?? 'file-text')) ?>
                    </span>
                </td>
                <td>
                    <strong class="content-section-title"><?= e((string) ($row['title'] ?? '')) ?></strong>
                    <?php if (!empty($row['summary'])): ?>
                        <small class="content-section-summary"><?= e(mb_strimwidth((string) $row['summary'], 0, 80, '...')) ?></small>
                    <?php endif; ?>
                </td>
                <td><span class="badge badge-active"><?= e((string) ($row['category'] ?? '')) ?></span></td>
                <td class="content-section-meta"><?= e((string) ($row['meta'] ?? '')) ?></td>
                <td><span class="badge badge-verified"><?= e((string) ($row['status'] ?? 'เผยแพร่')) ?></span></td>
                <td class="actions">
                    <div class="table-actions">
                        <?php if ($showView && !empty($row['view_url'])): ?>
                        <a href="<?= e((string) $row['view_url']) ?>" target="_blank" rel="noopener" class="btn btn-outline btn-sm btn-with-icon">
                            <?= adminBtnIcon('eye') ?> ดู
                        </a>
                        <?php endif; ?>
                        <a href="<?= $editHref ?>" class="btn btn-secondary btn-sm btn-with-icon">
                            <?= adminBtnIcon('square-pen') ?> แก้ไข
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
