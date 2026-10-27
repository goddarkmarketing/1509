<?php
declare(strict_types=1);

const MAX_COURSE_IMAGE_BYTES = 3 * 1024 * 1024;
const MAX_LESSON_DOC_BYTES = 10 * 1024 * 1024;
const MAX_LESSON_VIDEO_BYTES = 200 * 1024 * 1024;
const MAX_QUIZ_AUDIO_BYTES = 15 * 1024 * 1024;

function ensureUploadDir(string $path): void
{
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
    }
    if (str_ends_with(str_replace('\\', '/', $path), '/uploads/courses')) {
        $deny = $path . '/.htaccess';
        if (!is_file($deny)) {
            @file_put_contents($deny, "Options -Indexes\nRequire all denied\n");
        }
    }
}

function phpIniSizeToBytes(string $value): int
{
    $value = trim($value);
    if ($value === '') {
        return 0;
    }
    $unit = strtolower(substr($value, -1));
    $num = (float) $value;
    return (int) match ($unit) {
        'g' => $num * 1024 * 1024 * 1024,
        'm' => $num * 1024 * 1024,
        'k' => $num * 1024,
        default => (float) $value,
    };
}

function phpEffectiveUploadLimitBytes(): int
{
    $upload = phpIniSizeToBytes((string) ini_get('upload_max_filesize'));
    $post = phpIniSizeToBytes((string) ini_get('post_max_size'));
    $limits = array_filter([$upload, $post, MAX_LESSON_VIDEO_BYTES], static fn($v) => $v > 0);
    return $limits ? (int) min($limits) : MAX_LESSON_VIDEO_BYTES;
}

function formatUploadBytesLabel(int $bytes): string
{
    if ($bytes >= 1024 * 1024 * 1024) {
        return rtrim(rtrim(number_format($bytes / (1024 * 1024 * 1024), 1), '0'), '.') . 'GB';
    }
    if ($bytes >= 1024 * 1024) {
        return (string) (int) round($bytes / (1024 * 1024)) . 'MB';
    }
    if ($bytes >= 1024) {
        return (string) (int) round($bytes / 1024) . 'KB';
    }
    return $bytes . 'B';
}

function isExternalShareableVideoUrl(string $url): bool
{
    $url = strtolower(trim($url));
    if ($url === '' || str_starts_with($url, 'uploads/courses/')) {
        return false;
    }
    return str_contains($url, 'drive.google.com')
        || str_contains($url, 'docs.google.com')
        || str_contains($url, 'youtu.be')
        || str_contains($url, 'youtube.com');
}

function storeCourseCoverUpload(array $file): string|false|null
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        flash('admin_error', 'อัปโหลดรูปปกไม่สำเร็จ');
        return false;
    }
    if (($file['size'] ?? 0) > MAX_COURSE_IMAGE_BYTES) {
        flash('admin_error', 'รูปปกใหญ่เกิน 3MB');
        return false;
    }
    $ext = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
        flash('admin_error', 'รูปปกรองรับเฉพาะ JPG, PNG, WEBP, GIF');
        return false;
    }
    ensureUploadDir(UPLOAD_COURSES_PATH);
    $filename = 'cover_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = UPLOAD_COURSES_PATH . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        flash('admin_error', 'บันทึกรูปปกไม่สำเร็จ');
        return false;
    }
    return 'uploads/courses/' . $filename;
}

function storeSessionImageUpload(array $file): string|false|null
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        flash('admin_error', 'อัปโหลดรูปรอบเรียนไม่สำเร็จ');
        return false;
    }
    if (($file['size'] ?? 0) > MAX_COURSE_IMAGE_BYTES) {
        flash('admin_error', 'รูปรอบเรียนใหญ่เกิน 3MB');
        return false;
    }
    $ext = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
        flash('admin_error', 'รูปรอบเรียนรองรับเฉพาะ JPG, PNG, WEBP, GIF');
        return false;
    }
    ensureUploadDir(UPLOAD_SESSIONS_PATH);
    $filename = 'session_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = UPLOAD_SESSIONS_PATH . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        flash('admin_error', 'บันทึกรูปรอบเรียนไม่สำเร็จ');
        return false;
    }
    return 'uploads/sessions/' . $filename;
}

function storeAnnouncementImageUpload(array $file): string|false|null
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        flash('admin_error', 'อัปโหลดรูปไม่สำเร็จ');
        return false;
    }
    if (($file['size'] ?? 0) > MAX_COURSE_IMAGE_BYTES) {
        flash('admin_error', 'รูปใหญ่เกิน 3MB');
        return false;
    }
    $ext = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
        flash('admin_error', 'รูปรองรับเฉพาะ JPG, PNG, WEBP, GIF');
        return false;
    }
    ensureUploadDir(UPLOAD_ANNOUNCEMENTS_PATH);
    $filename = 'ann_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = UPLOAD_ANNOUNCEMENTS_PATH . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        flash('admin_error', 'บันทึกรูปไม่สำเร็จ');
        return false;
    }
    return 'uploads/announcements/' . $filename;
}

function storeAnnouncementPdfUpload(array $file): string|false|null
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        flash('admin_error', 'อัปโหลดไฟล์ PDF ไม่สำเร็จ');
        return false;
    }
    if (($file['size'] ?? 0) > MAX_LESSON_DOC_BYTES) {
        flash('admin_error', 'ไฟล์ PDF ใหญ่เกิน 10MB');
        return false;
    }
    $ext = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
    if ($ext !== 'pdf') {
        flash('admin_error', 'แนบได้เฉพาะไฟล์ PDF');
        return false;
    }
    ensureUploadDir(UPLOAD_ANNOUNCEMENTS_PATH);
    $filename = 'ann_pdf_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.pdf';
    $dest = UPLOAD_ANNOUNCEMENTS_PATH . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        flash('admin_error', 'บันทึกไฟล์ PDF ไม่สำเร็จ');
        return false;
    }
    return 'uploads/announcements/' . $filename;
}

function storeLessonDocumentUpload(array $file): string|false|null
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        flash('admin_error', 'อัปโหลดเอกสารไม่สำเร็จ');
        return false;
    }
    if (($file['size'] ?? 0) > MAX_LESSON_DOC_BYTES) {
        flash('admin_error', 'เอกสารใหญ่เกิน 10MB');
        return false;
    }
    $ext = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
    if (!in_array($ext, ['pdf', 'doc', 'docx', 'ppt', 'pptx'], true)) {
        flash('admin_error', 'เอกสารรองรับ PDF, DOC, DOCX, PPT, PPTX');
        return false;
    }
    ensureUploadDir(UPLOAD_COURSES_PATH);
    $filename = 'doc_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = UPLOAD_COURSES_PATH . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        flash('admin_error', 'บันทึกเอกสารไม่สำเร็จ');
        return false;
    }
    return 'uploads/courses/' . $filename;
}

function storeLessonVideoUpload(array $file): string|false|null
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        $code = (int) ($file['error'] ?? 0);
        $limitLabel = formatUploadBytesLabel(phpEffectiveUploadLimitBytes());
        $hint = match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'ไฟล์ใหญ่เกินขีดจำกัดเซิร์ฟเวอร์ (สูงสุดประมาณ ' . $limitLabel . ') — ลดขนาดไฟล์หรือเพิ่ม upload_max_filesize / post_max_size',
            UPLOAD_ERR_PARTIAL => 'อัปโหลดวิดีโอไม่ครบ กรุณาลองใหม่',
            UPLOAD_ERR_NO_TMP_DIR => 'เซิร์ฟเวอร์ไม่มีโฟลเดอร์ชั่วคราวสำหรับอัปโหลด',
            UPLOAD_ERR_CANT_WRITE => 'เซิร์ฟเวอร์เขียนไฟล์วิดีโอไม่สำเร็จ',
            default => 'อัปโหลดวิดีโอไม่สำเร็จ (รหัส ' . $code . ')',
        };
        flash('admin_error', $hint);
        return false;
    }
    $effectiveLimit = phpEffectiveUploadLimitBytes();
    if (($file['size'] ?? 0) > $effectiveLimit) {
        flash('admin_error', 'วิดีโอใหญ่เกิน ' . formatUploadBytesLabel($effectiveLimit));
        return false;
    }
    $ext = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
    if (!in_array($ext, ['mp4', 'webm', 'ogg'], true)) {
        flash('admin_error', 'วิดีโอรองรับ MP4, WEBM, OGG');
        return false;
    }
    ensureUploadDir(UPLOAD_COURSES_PATH);
    $filename = 'video_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = UPLOAD_COURSES_PATH . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        flash('admin_error', 'บันทึกวิดีโอไม่สำเร็จ');
        return false;
    }
    return 'uploads/courses/' . $filename;
}

function storeQuizAudioUpload(array $file): string|false|null
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        flash('admin_error', 'อัปโหลดไฟล์เสียงไม่สำเร็จ');
        return false;
    }
    if (($file['size'] ?? 0) > MAX_QUIZ_AUDIO_BYTES) {
        flash('admin_error', 'ไฟล์เสียงใหญ่เกิน 15MB');
        return false;
    }
    $ext = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
    if (!in_array($ext, ['mp3', 'wav', 'ogg', 'm4a', 'aac'], true)) {
        flash('admin_error', 'ไฟล์เสียงรองรับเฉพาะ MP3, WAV, OGG, M4A, AAC');
        return false;
    }
    ensureUploadDir(UPLOAD_QUIZZES_PATH);
    $filename = 'quiz_audio_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = UPLOAD_QUIZZES_PATH . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        flash('admin_error', 'บันทึกไฟล์เสียงไม่สำเร็จ');
        return false;
    }
    return 'uploads/quizzes/' . $filename;
}

function deleteQuizAudioFile(?string $storedPath): void
{
    $storedPath = trim((string) $storedPath);
    if ($storedPath === '' || !str_starts_with($storedPath, 'uploads/quizzes/')) {
        return;
    }
    $basename = basename($storedPath);
    if ($basename === '' || $basename === '.' || $basename === '..') {
        return;
    }
    $full = UPLOAD_QUIZZES_PATH . '/' . $basename;
    if (is_file($full)) {
        @unlink($full);
    }
}

function mediaPublicUrl(string $storedPath): string
{
    if (str_starts_with($storedPath, 'http://') || str_starts_with($storedPath, 'https://')) {
        return $storedPath;
    }
    if (str_starts_with($storedPath, 'uploads/courses/')) {
        return APP_URL . '/public/download.php?file=' . urlencode(basename($storedPath));
    }
    if (str_starts_with($storedPath, 'uploads/sessions/')) {
        return APP_URL . '/public/download.php?file=' . urlencode(basename($storedPath));
    }
    if (str_starts_with($storedPath, 'uploads/quizzes/')) {
        return UPLOAD_QUIZZES_URL . '/' . rawurlencode(basename($storedPath));
    }
    return asset(ltrim($storedPath, '/'));
}
