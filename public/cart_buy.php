<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/cart.php';

$courseId = (int) ($_REQUEST['course_id'] ?? 0);
if ($courseId <= 0) {
    redirect('/public/courses.php');
}

$course = getActiveCourseById($courseId);
if (!$course) {
    redirect('/public/courses.php');
}

addToCartCourse($courseId);
flash('cart_success', 'เพิ่มคอร์สลงตะกร้าเรียบร้อย');

redirect('/public/cart.php');

