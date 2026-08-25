<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/homepage.php';

function instructorDefaultCredentials(): array
{
    return [
        'ปริญญาโท การสอนภาษาจีน มหาวิทยาลัยปักกิ่ง',
        'ประสบการณ์สอนภาษาจีนมากกว่า 8 ปี',
        'สอนผู้เรียนออนไลน์มากกว่า 5,000+ คน',
        'เชี่ยวชาญการเตรียมสอบ HSK ตั้งแต่ระดับ 1–5',
    ];
}

function instructorDefaultHighlights(): array
{
    return [
        'สอนเข้าใจง่าย เน้นใช้งานจริงในชีวิตประจำวัน',
        'เนื้อหาเป็นระบบ เรียนซ้ำได้ไม่จำกัดเวลา',
        'แบบฝึกหัดและแบบทดสอบครบทุกคอร์ส',
        'เน้นเทคนิคเตรียมสอบ HSK ที่ใช้ได้จริง',
    ];
}

function instructorParseList(string $value, array $defaults): array
{
    $items = array_values(array_filter(array_map('trim', explode('|', $value)), static fn($item) => $item !== ''));
    return $items ?: $defaults;
}

function getInstructorProfile(?array $homeStats = null): array
{
    $homeStats ??= getHomepageStats();
    $credentials = instructorParseList(
        getSetting('instructor_credentials', implode('|', instructorDefaultCredentials())),
        instructorDefaultCredentials()
    );
    $highlights = instructorParseList(
        getSetting('instructor_highlights', implode('|', instructorDefaultHighlights())),
        instructorDefaultHighlights()
    );

    $studentStat = trim(getSetting('instructor_stat_students', '5000+'));
    if ($studentStat === '' && (int) $homeStats['students'] > 0) {
        $studentStat = number_format((int) $homeStats['students']) . '+';
    }

    return [
        'name' => getSetting('instructor_name', 'ครูบอล'),
        'role' => getSetting('instructor_role', 'ผู้สอนหลัก · กวดวิชาเดอะลีโอน่า'),
        'tagline' => getSetting('instructor_tagline', 'ติวสังคมศึกษาและประวัติศาสตร์ · เตรียมสอบเข้า ม.1 ม.4 และ A-Level'),
        'bio' => getSetting('instructor_bio', 'ครูบอลเป็นติวเตอร์สังคมศึกษาและประวัติศาสตร์ที่มุ่งเน้นเทคนิคทำข้อสอบและการปรับพื้นฐานอย่างเป็นระบบ ออกแบบคอร์สให้เหมาะทั้งติวเข้มสอบเข้าและเรียนเสริมตามหลักสูตร'),
        'quote' => getSetting('instructor_quote', 'ติวให้เข้าใจจริง ทำข้อสอบได้ มั่นใจก่อนวันสอบ'),
        'photo_path' => trim(getSetting('instructor_photo', '')),
        'credentials' => $credentials,
        'highlights' => $highlights,
        'stats' => [
            [
                'value' => $studentStat ?: '5,000+',
                'label' => 'ผู้เรียนทั่วประเทศ',
                'icon' => 'users',
            ],
            [
                'value' => max(1, (int) $homeStats['courses']) . '+',
                'label' => 'คอร์สออนไลน์',
                'icon' => 'courses',
            ],
            [
                'value' => getSetting('instructor_stat_satisfaction', '95%'),
                'label' => 'ความพึงพอใจผู้เรียน',
                'icon' => 'star',
            ],
            [
                'value' => max(1, (int) $homeStats['lessons']) . '+',
                'label' => 'บทเรียนวิดีโอ',
                'icon' => 'video',
            ],
        ],
    ];
}

function instructorPhotoUrl(array $profile): string
{
    $path = trim($profile['photo_path'] ?? '');
    if ($path !== '') {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        if (str_starts_with($path, 'uploads/')) {
            return APP_URL . '/public/' . ltrim($path, '/');
        }
        return asset(ltrim($path, '/'));
    }
    return imageAsset('images/instructor/wenxin-portrait.png', 'images/instructor/wenxin-portrait.svg');
}

function instructorCredentialIconSvg(): array
{
    static $icons = null;
    if ($icons === null) {
        $map = ['degree' => 0, 'time' => 1, 'users' => 2, 'award' => 3];
        $icons = [];
        foreach ($map as $key => $index) {
            $icons[$key] = instructorCredentialIcon($index);
        }
    }
    return $icons;
}

function instructorStatIconSvg(string $icon): string
{
    return instructorStatIcon($icon);
}
