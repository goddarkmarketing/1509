<?php
declare(strict_types=1);

const MAX_COURSE_IMAGE_BYTES = 3 * 1024 * 1024;
const MAX_LESSON_DOC_BYTES = 10 * 1024 * 1024;
const MAX_QUIZ_AUDIO_BYTES = 15 * 1024 * 1024;

function ensureUploadDir(string $path): void
{
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
    }
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
