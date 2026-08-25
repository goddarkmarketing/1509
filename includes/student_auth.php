<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

function isStudentLoggedIn(): bool
{
    return !empty($_SESSION['student_id']);
}

function requireStudentLogin(?string $redirectAfter = null): void
{
    if (isStudentLoggedIn()) {
        return;
    }
    if ($redirectAfter !== null && isSafeLocalReturn($redirectAfter)) {
        $_SESSION['login_redirect'] = $redirectAfter;
    }
    flash('login_error', 'กรุณาเข้าสู่ระบบเพื่อดำเนินการต่อ');
    redirect('/public/login.php');
}

function currentStudent(): ?array
{
    if (!isStudentLoggedIn()) {
        return null;
    }
    static $student = null;
    if ($student !== null) {
        return $student;
    }

    try {
        $stmt = db()->prepare('SELECT id, full_name, email, phone, line_id, line_user_id, created_at FROM students WHERE id = ? LIMIT 1');
        $stmt->execute([$_SESSION['student_id']]);
        $student = $stmt->fetch() ?: null;
        return $student;
    } catch (Throwable $e) {
        try {
            $stmt = db()->prepare('SELECT id, full_name, email, phone, line_id, created_at FROM students WHERE id = ? LIMIT 1');
            $stmt->execute([$_SESSION['student_id']]);
            $row = $stmt->fetch() ?: null;
            if ($row) {
                $row['line_user_id'] = null;
            }
            $student = $row;
            return $student;
        } catch (Throwable $e2) {
            return null;
        }
    }
}

function attemptStudentLogin(string $identifier, string $password): bool
{
    $identifier = trim($identifier);
    if ($identifier === '' || $password === '') {
        return false;
    }

    $student = findStudentByIdentifier($identifier);
    if (!$student || empty($student['password_hash'])) {
        return false;
    }
    if (!password_verify($password, $student['password_hash'])) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['student_id'] = (int) $student['id'];
    $_SESSION['student_name'] = $student['full_name'];
    return true;
}

function registerStudent(string $name, string $phone, string $password, ?string $email = null, ?string $lineId = null): array
{
    $name = trim($name);
    $phone = trim($phone);
    $email = $email !== null && trim($email) !== '' ? trim($email) : null;
    $lineId = $lineId !== null && trim($lineId) !== '' ? trim($lineId) : null;

    if ($name === '' || $phone === '' || strlen($password) < 6) {
        return ['ok' => false, 'message' => 'กรุณากรอกข้อมูลให้ครบ และรหัสผ่านอย่างน้อย 6 ตัวอักษร'];
    }

    $stmt = db()->prepare('SELECT id, password_hash FROM students WHERE phone = ? LIMIT 1');
    $stmt->execute([$phone]);
    $existing = $stmt->fetch();

    $hash = password_hash($password, PASSWORD_DEFAULT);

    if ($existing) {
        if (!empty($existing['password_hash'])) {
            return ['ok' => false, 'message' => 'เบอร์โทรนี้ลงทะเบียนแล้ว กรุณาเข้าสู่ระบบ'];
        }
        $upd = db()->prepare('UPDATE students SET full_name = ?, email = COALESCE(?, email), line_id = COALESCE(?, line_id), password_hash = ? WHERE id = ?');
        $upd->execute([$name, $email, $lineId, $hash, (int) $existing['id']]);
        session_regenerate_id(true);
        $_SESSION['student_id'] = (int) $existing['id'];
        $_SESSION['student_name'] = $name;
        return ['ok' => true, 'message' => 'ตั้งรหัสผ่านและเปิดใช้งานบัญชีเรียบร้อยแล้ว'];
    }

    $ins = db()->prepare('INSERT INTO students (full_name, email, phone, line_id, password_hash) VALUES (?, ?, ?, ?, ?)');
    $ins->execute([$name, $email, $phone, $lineId, $hash]);
    session_regenerate_id(true);
    $_SESSION['student_id'] = (int) db()->lastInsertId();
    $_SESSION['student_name'] = $name;
    return ['ok' => true, 'message' => 'สมัครสมาชิกเรียบร้อยแล้ว'];
}

function studentLogout(): void
{
    unset($_SESSION['student_id'], $_SESSION['student_name'], $_SESSION['checkout_phone']);
}

function consumeLoginRedirect(): string
{
    $path = $_SESSION['login_redirect'] ?? '/public/profile.php?tab=courses';
    unset($_SESSION['login_redirect']);
    return isSafeLocalReturn($path) ? $path : '/public/profile.php?tab=courses';
}

function findStudentByIdentifier(string $identifier): ?array
{
    $identifier = trim($identifier);
    if ($identifier === '') {
        return null;
    }
    if (str_contains($identifier, '@')) {
        $stmt = db()->prepare('SELECT * FROM students WHERE email = ? LIMIT 1');
        $stmt->execute([$identifier]);
    } else {
        $stmt = db()->prepare('SELECT * FROM students WHERE phone = ? LIMIT 1');
        $stmt->execute([$identifier]);
    }
    $row = $stmt->fetch();
    return $row ?: null;
}

function updateStudentProfile(int $studentId, string $name, ?string $email, ?string $lineId, ?string $phone = null): array
{
    $name = trim($name);
    if ($name === '') {
        return ['ok' => false, 'message' => 'กรุณากรอกชื่อ'];
    }

    if ($phone !== null) {
        $phone = trim($phone);
        if ($phone === '') {
            return ['ok' => false, 'message' => 'กรุณากรอกเบอร์โทร'];
        }
        $chk = db()->prepare('SELECT id FROM students WHERE phone = ? AND id != ? LIMIT 1');
        $chk->execute([$phone, $studentId]);
        if ($chk->fetch()) {
            return ['ok' => false, 'message' => 'เบอร์โทรนี้ถูกใช้แล้ว'];
        }
    }

    if ($email !== null && trim($email) !== '') {
        $email = trim($email);
        $chk = db()->prepare('SELECT id FROM students WHERE email = ? AND id != ? LIMIT 1');
        $chk->execute([$email, $studentId]);
        if ($chk->fetch()) {
            return ['ok' => false, 'message' => 'อีเมลนี้ถูกใช้แล้ว'];
        }
    } else {
        $email = null;
    }

    $lineId = $lineId !== null && trim($lineId) !== '' ? trim($lineId) : null;

    if ($phone !== null) {
        $stmt = db()->prepare('UPDATE students SET full_name = ?, email = ?, line_id = ?, phone = ? WHERE id = ?');
        $stmt->execute([$name, $email, $lineId, $phone, $studentId]);
    } else {
        $stmt = db()->prepare('UPDATE students SET full_name = ?, email = ?, line_id = ? WHERE id = ?');
        $stmt->execute([$name, $email, $lineId, $studentId]);
    }

    $_SESSION['student_name'] = $name;
    return ['ok' => true, 'message' => 'บันทึกโปรไฟล์เรียบร้อย'];
}

function changeStudentPassword(int $studentId, string $currentPassword, string $newPassword): array
{
    if (strlen($newPassword) < 6) {
        return ['ok' => false, 'message' => 'รหัสผ่านใหม่ต้องมีอย่างน้อย 6 ตัวอักษร'];
    }

    $stmt = db()->prepare('SELECT password_hash FROM students WHERE id = ? LIMIT 1');
    $stmt->execute([$studentId]);
    $row = $stmt->fetch();
    if (!$row || empty($row['password_hash']) || !password_verify($currentPassword, $row['password_hash'])) {
        return ['ok' => false, 'message' => 'รหัสผ่านปัจจุบันไม่ถูกต้อง'];
    }

    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    db()->prepare('UPDATE students SET password_hash = ? WHERE id = ?')->execute([$hash, $studentId]);
    return ['ok' => true, 'message' => 'เปลี่ยนรหัสผ่านเรียบร้อย'];
}

function createPasswordResetToken(string $identifier): array
{
    $student = findStudentByIdentifier($identifier);
    if (!$student) {
        return ['ok' => true, 'message' => 'หากมีบัญชีที่ตรงกับข้อมูลนี้ เราจะส่งลิงก์รีเซ็ตรหัสผ่านให้'];
    }
    if (empty($student['email'])) {
        return ['ok' => false, 'message' => 'บัญชีนี้ยังไม่มีอีเมล กรุณาติดต่อทีมงานเพื่อรีเซ็ตรหัสผ่าน'];
    }
    if (empty($student['password_hash'])) {
        return ['ok' => false, 'message' => 'บัญชีนี้ยังไม่ได้ตั้งรหัสผ่าน กรุณาสมัครสมาชิกเพื่อตั้งรหัสผ่าน'];
    }

    $token = bin2hex(random_bytes(32));
    $hash = hash('sha256', $token);
    $expires = date('Y-m-d H:i:s', time() + 3600);

    db()->prepare('UPDATE password_reset_tokens SET used_at = NOW() WHERE student_id = ? AND used_at IS NULL')
        ->execute([(int) $student['id']]);

    db()->prepare('INSERT INTO password_reset_tokens (student_id, token_hash, expires_at) VALUES (?, ?, ?)')
        ->execute([(int) $student['id'], $hash, $expires]);

    require_once __DIR__ . '/mailer.php';
    $resetUrl = siteBaseUrl() . '/public/reset-password.php?token=' . urlencode($token);
    sendPasswordResetEmail($student['email'], $student['full_name'], $resetUrl);

    return ['ok' => true, 'message' => 'หากมีบัญชีที่ตรงกับข้อมูลนี้ เราจะส่งลิงก์รีเซ็ตรหัสผ่านให้'];
}

function validatePasswordResetToken(string $token): ?array
{
    $token = trim($token);
    if ($token === '') {
        return null;
    }
    $hash = hash('sha256', $token);
    $stmt = db()->prepare('
        SELECT prt.*, s.full_name, s.email
        FROM password_reset_tokens prt
        JOIN students s ON s.id = prt.student_id
        WHERE prt.token_hash = ? AND prt.used_at IS NULL AND prt.expires_at > NOW()
        LIMIT 1
    ');
    $stmt->execute([$hash]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function resetPasswordWithToken(string $token, string $newPassword): array
{
    if (strlen($newPassword) < 6) {
        return ['ok' => false, 'message' => 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร'];
    }

    $record = validatePasswordResetToken($token);
    if (!$record) {
        return ['ok' => false, 'message' => 'ลิงก์หมดอายุหรือไม่ถูกต้อง กรุณาขอรีเซ็ตใหม่'];
    }

    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    $pdo = db();
    $pdo->prepare('UPDATE students SET password_hash = ? WHERE id = ?')->execute([$hash, (int) $record['student_id']]);
    $pdo->prepare('UPDATE password_reset_tokens SET used_at = NOW() WHERE id = ?')->execute([(int) $record['id']]);

    return ['ok' => true, 'message' => 'ตั้งรหัสผ่านใหม่เรียบร้อยแล้ว กรุณาเข้าสู่ระบบ'];
}
