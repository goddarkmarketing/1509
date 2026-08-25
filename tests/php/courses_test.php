<?php

declare(strict_types=1);

/**
 * Course catalog + admin visibility tests
 *
 * รัน: php tests/php/courses_test.php
 */

require_once dirname(__DIR__, 2) . '/includes/functions.php';
require_once __DIR__ . '/test_helpers.php';

test_reset_checks();

test_check('parseCheckboxFlag(1)', parseCheckboxFlag('1') === 1);
test_check('parseCheckboxFlag(0)', parseCheckboxFlag('0') === 0);
test_check('parseCheckboxFlag(on)', parseCheckboxFlag('on') === 1);
test_check('parseCheckboxFlag(null)', parseCheckboxFlag(null) === 0);

$slug = 'e2e-course-visibility';
$existing = getCourseBySlug($slug);
$courseId = (int) ($existing['id'] ?? 0);

if ($courseId <= 0) {
    db()->prepare('
        INSERT INTO courses (slug, title, subtitle, description, category, level, price, duration_hours, lesson_count, highlights, is_featured, is_active, sort_order, course_type)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 1, 997, "recorded")
    ')->execute([
        $slug,
        'คอร์สทดสอบเปิด/ปิด',
        'visibility test',
        'ทดสอบ is_active',
        'hsk',
        'beginner',
        100,
        1,
        0,
        'test',
    ]);
    $courseId = (int) db()->lastInsertId();
}

test_check('มีคอร์สทดสอบ', $courseId > 0, "#{$courseId}");

db()->prepare('UPDATE courses SET is_active = 1, is_featured = 0 WHERE id = ?')->execute([$courseId]);
$active = getActiveCourseBySlug($slug);
test_check('เปิดใช้งาน: getActiveCourseBySlug', $active !== null);
test_check('เปิดใช้งาน: อยู่ใน getCourses', in_array($courseId, array_map(static fn($c) => (int) $c['id'], getCourses()), true));

db()->prepare('UPDATE courses SET is_active = 0 WHERE id = ?')->execute([$courseId]);
$inactive = getCourseBySlug($slug);
test_check('ปิดใช้งาน: getCourseBySlug ยังมี', $inactive !== null && !isCourseActive($inactive));
test_check('ปิดใช้งาน: getActiveCourseBySlug เป็น null', getActiveCourseBySlug($slug) === null);
test_check('ปิดใช้งาน: getActiveCourseById เป็น null', getActiveCourseById($courseId) === null);
test_check('ปิดใช้งาน: ไม่อยู่ใน getCourses', !in_array($courseId, array_map(static fn($c) => (int) $c['id'], getCourses()), true));

db()->prepare('UPDATE courses SET is_active = 1 WHERE id = ?')->execute([$courseId]);

// simulate admin POST save with hidden checkbox fields
$isFeatured = parseCheckboxFlag('0');
$isActive = parseCheckboxFlag('0');
db()->prepare('UPDATE courses SET is_featured = ?, is_active = ? WHERE id = ?')->execute([$isFeatured, $isActive, $courseId]);
$row = getCourseById($courseId);
test_check('บันทึกแบบไม่ติ๊กเปิดใช้งาน → is_active=0', (int) ($row['is_active'] ?? 1) === 0);
test_check('หลังบันทึกปิด: ไม่แสดงใน catalog', getActiveCourseBySlug($slug) === null);

$isActive = parseCheckboxFlag('1');
db()->prepare('UPDATE courses SET is_active = ? WHERE id = ?')->execute([$isActive, $courseId]);
test_check('บันทึกแบบติ๊กเปิดใช้งาน → is_active=1', (int) (getCourseById($courseId)['is_active'] ?? 0) === 1);

test_check('categoryLabel', categoryLabel('hsk') === 'HSK');
test_check('levelBadge', levelBadge('beginner') === 'เริ่มต้น');
test_check('formatPrice', str_contains(formatPrice(990), '990'));

$result = test_print_summary('LMS Courses Test');
exit($result['fail'] > 0 ? 1 : 0);
