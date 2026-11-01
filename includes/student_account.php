<?php
declare(strict_types=1);

function studentAccountTabs(): array
{
    return [
        'courses' => [
            'label' => 'คอร์สของฉัน',
            'title' => 'คอร์สของฉัน',
        ],
        'exams' => [
            'label' => 'ชุดข้อสอบ',
            'title' => 'ชุดข้อสอบของฉัน',
        ],
        'bookings' => [
            'label' => 'การจองคลาส',
            'title' => 'การจองคลาส Live',
        ],
        'profile' => [
            'label' => 'ข้อมูลส่วนตัว',
            'title' => 'ข้อมูลส่วนตัว',
        ],
        'password' => [
            'label' => 'เปลี่ยนรหัสผ่าน',
            'title' => 'เปลี่ยนรหัสผ่าน',
        ],
        'certificates' => [
            'label' => 'ใบประกาศ',
            'title' => 'ใบประกาศนียบัตร',
        ],
    ];
}

function studentAccountTab(string $tab): string
{
    $tabs = studentAccountTabs();
    return isset($tabs[$tab]) ? $tab : 'courses';
}

function studentAccountUrl(string $tab = 'courses'): string
{
    $tab = studentAccountTab($tab);
    return APP_URL . '/public/profile.php?tab=' . urlencode($tab);
}

function studentAccountInitial(string $fullName): string
{
    $initial = mb_strtoupper(mb_substr(trim($fullName), 0, 1, 'UTF-8'));
    return $initial !== '' ? $initial : '?';
}

/** Student learning home — always the enrolled-courses panel, not the marketing site. */
function studentStartLearningUrl(): string
{
    return studentAccountUrl('courses');
}

function studentAvatarUrl(?array $student): string
{
    $path = trim((string) ($student['avatar_url'] ?? ''));
    if ($path === '') {
        return '';
    }
    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
        return $path;
    }
    if (str_starts_with($path, 'uploads/students/')) {
        return APP_URL . '/' . ltrim($path, '/');
    }
    return APP_URL . '/' . ltrim($path, '/');
}

function updateStudentAvatar(int $studentId, string $avatarRelPath): array
{
    ensureStudentAvatarColumn();
    try {
        db()->prepare('UPDATE students SET avatar_url = ? WHERE id = ?')->execute([$avatarRelPath, $studentId]);
        return ['ok' => true, 'message' => 'อัปเดตรูปโปรไฟล์เรียบร้อย'];
    } catch (Throwable $e) {
        return ['ok' => false, 'message' => 'บันทึกรูปโปรไฟล์ไม่สำเร็จ'];
    }
}
