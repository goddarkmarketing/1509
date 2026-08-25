<?php
declare(strict_types=1);

function getHomepageStats(): array
{
    $stats = [
        'students' => 0,
        'courses' => 0,
        'lessons' => 0,
        'certificates' => 0,
    ];
    try {
        $stats['students'] = (int) db()->query('SELECT COUNT(*) FROM students')->fetchColumn();
        $stats['courses'] = (int) db()->query('SELECT COUNT(*) FROM courses WHERE is_active = 1')->fetchColumn();
        $stats['lessons'] = (int) db()->query('SELECT COUNT(*) FROM lessons WHERE is_published = 1')->fetchColumn();
        $stats['certificates'] = (int) db()->query('SELECT COUNT(*) FROM certificates')->fetchColumn();
    } catch (Throwable $e) {
    }
    return $stats;
}

function getPreviewLessonsForHome(int $limit = 4): array
{
    try {
        $stmt = db()->prepare('
            SELECT l.id, l.title, l.duration_minutes, c.title AS course_title, c.slug AS course_slug, c.id AS course_id
            FROM lessons l
            JOIN courses c ON c.id = l.course_id
            WHERE l.is_free_preview = 1 AND l.is_published = 1 AND c.is_active = 1
            ORDER BY c.sort_order ASC, l.sort_order ASC, l.id ASC
            LIMIT ?
        ');
        $stmt->execute([max(1, $limit)]);
        return $stmt->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
}

function getFirstPreviewLessonUrl(): string
{
    $lessons = getPreviewLessonsForHome(1);
    if ($lessons) {
        return APP_URL . '/public/lesson.php?lesson_id=' . (int) $lessons[0]['id'];
    }
    return APP_URL . '/public/courses.php';
}

function getFaqPageItems(): array
{
    require_once __DIR__ . '/site_content.php';
    return getFaqItemsByScope('main');
}

/** คำถามเพิ่มเติมเฉพาะหน้าแรก */
function getHomepageExtraFaqItems(): array
{
    require_once __DIR__ . '/site_content.php';
    return getFaqItemsByScope('homepage_extra');
}

function getHomepageFaqItems(): array
{
    $all = array_merge(getFaqPageItems(), getHomepageExtraFaqItems());
    return array_slice($all, 0, 10);
}

function getLearningPathItems(): array
{
    $paths = [
        ['slug' => 'hsk1-pinyin', 'level' => 'เริ่มต้น', 'label' => 'พินอิน + HSK 1'],
        ['slug' => 'hsk2', 'level' => 'ระดับ 2', 'label' => 'HSK 2'],
        ['slug' => 'hsk3', 'level' => 'ระดับ 3', 'label' => 'HSK 3'],
        ['slug' => 'hsk4', 'level' => 'ระดับ 4', 'label' => 'HSK 4'],
        ['slug' => 'hsk5', 'level' => 'ระดับ 5', 'label' => 'HSK 5'],
        ['slug' => 'exam-prep-hsk3', 'level' => 'ติวสอบ', 'label' => 'ติว HSK 3'],
    ];
    $result = [];
    foreach ($paths as $path) {
        $course = getActiveCourseBySlug($path['slug']);
        if ($course) {
            $result[] = array_merge($path, [
                'title' => $course['title'],
                'url' => APP_URL . '/public/course.php?slug=' . urlencode($path['slug']),
            ]);
        }
    }
    return $result;
}

function getFooterCourseLinks(int $limit = 8): array
{
    try {
        $links = [];
        foreach (array_slice(getCourses(), 0, max(1, $limit)) as $course) {
            $links[] = [
                'title' => (string) ($course['title'] ?? ''),
                'url' => APP_URL . '/public/course.php?slug=' . urlencode((string) ($course['slug'] ?? '')),
            ];
        }
        return $links;
    } catch (Throwable $e) {
        return [];
    }
}

function getFooterHskCourseLinks(): array
{
    $labels = [
        'hsk1-pinyin' => 'HSK 1',
        'hsk2' => 'HSK 2',
        'hsk3' => 'HSK 3',
        'hsk4' => 'HSK 4',
        'hsk5' => 'HSK 5',
    ];
    try {
        $courses = getCourses();
        $bySlug = [];
        foreach ($courses as $course) {
            $bySlug[(string) ($course['slug'] ?? '')] = $course;
        }
        $links = [];
        foreach ($labels as $slug => $label) {
            if (!isset($bySlug[$slug])) {
                continue;
            }
            $links[] = [
                'title' => $label,
                'url' => APP_URL . '/public/course.php?slug=' . urlencode($slug),
            ];
        }
        return $links;
    } catch (Throwable $e) {
        return [];
    }
}

function subscribeNewsletter(string $email): array
{
    $email = trim($email);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'message' => 'กรุณากรอกอีเมลให้ถูกต้อง'];
    }
    try {
        db()->exec('
            CREATE TABLE IF NOT EXISTS newsletter_subscribers (
              id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
              email VARCHAR(255) NOT NULL,
              created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              UNIQUE KEY uk_newsletter_email (email)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');
        $stmt = db()->prepare('INSERT INTO newsletter_subscribers (email) VALUES (?)');
        $stmt->execute([$email]);
        return ['ok' => true, 'message' => 'สมัครรับข่าวสารเรียบร้อยแล้ว ขอบคุณครับ'];
    } catch (PDOException $e) {
        if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
            return ['ok' => true, 'message' => 'อีเมลนี้สมัครรับข่าวสารไว้แล้ว'];
        }
        return ['ok' => false, 'message' => 'เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง'];
    } catch (Throwable $e) {
        return ['ok' => false, 'message' => 'เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง'];
    }
}

function lineContactUrl(): string
{
    $lineId = trim(getSetting('line_id', ''));
    if ($lineId === '') {
        return APP_URL . '/public/contact.php';
    }
    if (str_starts_with($lineId, 'http')) {
        return $lineId;
    }
    $id = ltrim($lineId, '@');
    return 'https://line.me/ti/p/~' . rawurlencode($id);
}
