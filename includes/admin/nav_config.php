<?php

declare(strict_types=1);

return [
    [
        'label' => 'ภาพรวม',
        'items' => [
            ['page' => 'dashboard', 'href' => '/admin/dashboard.php', 'label' => 'แดชบอร์ด', 'icon' => 'dashboard'],
            ['page' => 'reports', 'href' => '/admin/reports.php', 'label' => 'รายงาน', 'icon' => 'dashboard'],
        ],
    ],
    [
        'label' => 'เนื้อหา',
        'items' => [
            ['page' => 'courses', 'href' => '/admin/courses.php', 'label' => 'คอร์ส', 'icon' => 'courses'],
            ['page' => 'sessions', 'href' => '/admin/sessions.php', 'label' => 'ตารางคลาส Live', 'icon' => 'courses'],
            ['page' => 'lessons', 'href' => '/admin/lessons.php', 'label' => 'บทเรียน', 'icon' => 'lessons'],
            ['page' => 'quizzes', 'href' => '/admin/quizzes.php', 'label' => 'แบบทดสอบ', 'icon' => 'quizzes'],
            ['page' => 'games', 'href' => '/admin/games.php', 'label' => 'เกมฝึกฝน', 'icon' => 'games'],
            ['page' => 'announcements', 'href' => '/admin/announcements.php', 'label' => 'ประชาสัมพันธ์', 'icon' => 'announcements'],
            ['page' => 'content', 'href' => '/admin/content.php', 'label' => 'เนื้อหาเว็บ', 'icon' => 'content'],
        ],
    ],
    [
        'label' => 'ลูกค้า & การขาย',
        'items' => [
            ['page' => 'students', 'href' => '/admin/students.php', 'label' => 'นักเรียน', 'icon' => 'students'],
            ['page' => 'bookings', 'href' => '/admin/bookings.php', 'label' => 'การจองคลาส', 'icon' => 'students'],
            ['page' => 'payments', 'href' => '/admin/payments.php', 'label' => 'การชำระเงิน', 'icon' => 'payments'],
            ['page' => 'coupons', 'href' => '/admin/coupons.php', 'label' => 'คูปอง', 'icon' => 'coupons'],
        ],
    ],
    [
        'label' => 'ระบบ',
        'items' => [
            ['page' => 'users', 'href' => '/admin/users.php', 'label' => 'ผู้ดูแล', 'icon' => 'users'],
            ['page' => 'backup', 'href' => '/admin/backup.php', 'label' => 'สำรองข้อมูล', 'icon' => 'backup'],
            ['page' => 'settings', 'href' => '/admin/settings.php', 'label' => 'ตั้งค่า', 'icon' => 'settings'],
        ],
    ],
];
