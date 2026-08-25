<?php

declare(strict_types=1);

function announcementCategoryLabel(string $category): string
{
    return match ($category) {
        'promo' => 'โปรโมชัน',
        'course' => 'คอร์ส',
        'event' => 'กิจกรรม',
        default => 'ทั่วไป',
    };
}

function announcementImageUrl(?string $imageUrl): ?string
{
    if ($imageUrl === null || trim($imageUrl) === '') {
        return null;
    }
    $imageUrl = trim($imageUrl);
    if (str_starts_with($imageUrl, 'http://') || str_starts_with($imageUrl, 'https://')) {
        return $imageUrl;
    }
    if (str_starts_with($imageUrl, 'uploads/announcements/')) {
        return APP_URL . '/' . $imageUrl;
    }
    return asset(ltrim($imageUrl, '/'));
}

function makeAnnouncementSlug(string $title, ?int $excludeId = null): string
{
    $base = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($title)) ?? '', '-');
    if ($base === '') {
        $base = 'post';
    }
    $slug = $base;
    $i = 2;
    while (announcementSlugExists($slug, $excludeId)) {
        $slug = $base . '-' . $i;
        $i++;
    }
    return $slug;
}

function announcementSlugExists(string $slug, ?int $excludeId = null): bool
{
    $sql = 'SELECT id FROM announcements WHERE slug = ?';
    $params = [$slug];
    if ($excludeId !== null) {
        $sql .= ' AND id != ?';
        $params[] = $excludeId;
    }
    $sql .= ' LIMIT 1';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return (bool) $stmt->fetch();
}

function getAnnouncements(bool $publishedOnly = true, ?string $category = null): array
{
    try {
        $sql = 'SELECT * FROM announcements WHERE 1=1';
        $params = [];
        if ($publishedOnly) {
            $sql .= ' AND is_published = 1 AND (published_at IS NULL OR published_at <= NOW())';
        }
        if ($category !== null && $category !== '' && $category !== 'all') {
            $sql .= ' AND category = ?';
            $params[] = $category;
        }
        $sql .= ' ORDER BY is_pinned DESC, COALESCE(published_at, created_at) DESC, sort_order ASC, id DESC';
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
}

function getAnnouncementById(int $id): ?array
{
    try {
        $stmt = db()->prepare('SELECT * FROM announcements WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

function getAnnouncementBySlug(string $slug, bool $publishedOnly = true): ?array
{
    try {
        $sql = 'SELECT * FROM announcements WHERE slug = ?';
        if ($publishedOnly) {
            $sql .= ' AND is_published = 1 AND (published_at IS NULL OR published_at <= NOW())';
        }
        $sql .= ' LIMIT 1';
        $stmt = db()->prepare($sql);
        $stmt->execute([$slug]);
        $row = $stmt->fetch();
        return $row ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

function formatAnnouncementDate(?string $datetime): string
{
    if ($datetime === null || $datetime === '') {
        return '';
    }
    $ts = strtotime($datetime);
    if ($ts === false) {
        return '';
    }
    return date('d/m/Y', $ts);
}

function announcementHasAttachment(array $announcement): bool
{
    return trim($announcement['attachment_url'] ?? '') !== '';
}

function announcementAttachmentLabel(array $announcement): string
{
    $name = trim($announcement['attachment_name'] ?? '');
    if ($name !== '') {
        return $name;
    }
    $url = trim($announcement['attachment_url'] ?? '');
    return $url !== '' ? basename($url) : 'ไฟล์แนบ.pdf';
}

function announcementAttachmentDownloadUrl(array $announcement): ?string
{
    if (!announcementHasAttachment($announcement)) {
        return null;
    }
    return APP_URL . '/public/announcement_file.php?slug=' . urlencode($announcement['slug']);
}
