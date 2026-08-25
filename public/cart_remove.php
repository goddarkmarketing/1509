<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/cart.php';

$courseId = (int) ($_REQUEST['course_id'] ?? 0);
if ($courseId <= 0) {
    clearCart();
    redirect('/public/cart.php');
}

removeFromCartCourse($courseId);
flash('cart_success', 'นำคอร์สออกจากตะกร้าแล้ว');

redirectBack('/public/courses.php');

