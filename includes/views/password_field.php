<?php

declare(strict_types=1);

$passwordName = $passwordName ?? 'password';
$passwordId = $passwordId ?? $passwordName;
$passwordValue = $passwordValue ?? '';
$passwordAttrs = $passwordAttrs ?? [];
?>
<div class="password-field">
    <input
        type="password"
        name="<?= e($passwordName) ?>"
        id="<?= e($passwordId) ?>"
        class="form-control"
        value="<?= e($passwordValue) ?>"
        <?php foreach ($passwordAttrs as $attr => $val): ?>
            <?php if ($val === true): ?>
                <?= e($attr) ?>
            <?php elseif ($val !== false && $val !== null): ?>
                <?= e($attr) ?>="<?= e((string) $val) ?>"
            <?php endif; ?>
        <?php endforeach; ?>
    >
    <button
        type="button"
        class="password-toggle"
        aria-label="แสดงรหัสผ่าน"
        aria-controls="<?= e($passwordId) ?>"
    >
        <?= lucide_icon('eye', ['size' => 20, 'class' => 'password-toggle-icon password-toggle-icon--show', 'stroke' => '1.75']) ?>
        <?= lucide_icon('eye-off', ['size' => 20, 'class' => 'password-toggle-icon password-toggle-icon--hide', 'stroke' => '1.75']) ?>
    </button>
</div>
