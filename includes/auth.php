<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

function isLoggedIn(): bool
{
    return !empty($_SESSION['admin_id']);
}

function isAdminLoggedIn(): bool
{
    return isLoggedIn();
}

function requireAdmin(): void
{
    if (!isAdminLoggedIn()) {
        redirect('/admin/login.php');
    }
}

function currentAdmin(): ?array
{
    if (!isLoggedIn()) {
        return null;
    }
    static $admin = null;
    if ($admin !== null) {
        return $admin;
    }
    $stmt = db()->prepare('SELECT id, username, full_name, email FROM admin_users WHERE id = ? LIMIT 1');
    $stmt->execute([$_SESSION['admin_id']]);
    $admin = $stmt->fetch() ?: null;
    return $admin;
}

function attemptLogin(string $username, string $password): bool
{
    $stmt = db()->prepare('SELECT * FROM admin_users WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = (int) $user['id'];
        $_SESSION['admin_username'] = $user['username'];
        return true;
    }
    return false;
}

function logout(): void
{
    unset($_SESSION['admin_id'], $_SESSION['admin_username']);
}

function getAllAdmins(): array
{
    return db()->query('SELECT id, username, full_name, email, created_at FROM admin_users ORDER BY id ASC')->fetchAll();
}

function countAdmins(): int
{
    return (int) db()->query('SELECT COUNT(*) FROM admin_users')->fetchColumn();
}

function createAdminUser(string $username, string $password, string $fullName, ?string $email = null): array
{
    $username = trim($username);
    $fullName = trim($fullName);
    if ($username === '' || $fullName === '' || strlen($password) < 6) {
        return ['ok' => false, 'message' => 'กรุณากรอกข้อมูลให้ครบ และรหัสผ่านอย่างน้อย 6 ตัวอักษร'];
    }

    $chk = db()->prepare('SELECT id FROM admin_users WHERE username = ? LIMIT 1');
    $chk->execute([$username]);
    if ($chk->fetch()) {
        return ['ok' => false, 'message' => 'ชื่อผู้ใช้นี้ถูกใช้แล้ว'];
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    db()->prepare('INSERT INTO admin_users (username, password_hash, full_name, email) VALUES (?, ?, ?, ?)')
        ->execute([$username, $hash, $fullName, $email ?: null]);

    return ['ok' => true, 'message' => 'เพิ่มผู้ดูแลระบบเรียบร้อย'];
}

function updateAdminUser(int $adminId, string $fullName, ?string $email, ?string $username = null): array
{
    $fullName = trim($fullName);
    if ($fullName === '') {
        return ['ok' => false, 'message' => 'กรุณากรอกชื่อ'];
    }

    if ($username !== null) {
        $username = trim($username);
        if ($username === '') {
            return ['ok' => false, 'message' => 'กรุณากรอกชื่อผู้ใช้'];
        }
        $chk = db()->prepare('SELECT id FROM admin_users WHERE username = ? AND id != ? LIMIT 1');
        $chk->execute([$username, $adminId]);
        if ($chk->fetch()) {
            return ['ok' => false, 'message' => 'ชื่อผู้ใช้นี้ถูกใช้แล้ว'];
        }
        db()->prepare('UPDATE admin_users SET full_name = ?, email = ?, username = ? WHERE id = ?')
            ->execute([$fullName, $email ?: null, $username, $adminId]);
    } else {
        db()->prepare('UPDATE admin_users SET full_name = ?, email = ? WHERE id = ?')
            ->execute([$fullName, $email ?: null, $adminId]);
    }

    if ((int) ($_SESSION['admin_id'] ?? 0) === $adminId && $username !== null) {
        $_SESSION['admin_username'] = $username;
    }

    return ['ok' => true, 'message' => 'บันทึกข้อมูลเรียบร้อย'];
}

function changeAdminPassword(int $adminId, string $newPassword, ?string $currentPassword = null): array
{
    if (strlen($newPassword) < 6) {
        return ['ok' => false, 'message' => 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร'];
    }

    $isSelf = (int) ($_SESSION['admin_id'] ?? 0) === $adminId;
    if ($isSelf) {
        $stmt = db()->prepare('SELECT password_hash FROM admin_users WHERE id = ? LIMIT 1');
        $stmt->execute([$adminId]);
        $row = $stmt->fetch();
        if (!$row || $currentPassword === null || !password_verify($currentPassword, $row['password_hash'])) {
            return ['ok' => false, 'message' => 'รหัสผ่านปัจจุบันไม่ถูกต้อง'];
        }
    }

    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    db()->prepare('UPDATE admin_users SET password_hash = ? WHERE id = ?')->execute([$hash, $adminId]);
    return ['ok' => true, 'message' => 'เปลี่ยนรหัสผ่านเรียบร้อย'];
}

function deleteAdminUser(int $adminId): array
{
    if (countAdmins() <= 1) {
        return ['ok' => false, 'message' => 'ไม่สามารถลบผู้ดูแลคนสุดท้ายได้'];
    }
    if ((int) ($_SESSION['admin_id'] ?? 0) === $adminId) {
        return ['ok' => false, 'message' => 'ไม่สามารถลบบัญชีที่กำลังใช้งานอยู่ได้'];
    }

    db()->prepare('DELETE FROM admin_users WHERE id = ?')->execute([$adminId]);
    return ['ok' => true, 'message' => 'ลบผู้ดูแลระบบเรียบร้อย'];
}
