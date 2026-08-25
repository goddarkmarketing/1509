<?php
declare(strict_types=1);

function studentAccountTabs(): array
{
    return [
        'courses' => [
            'label' => 'คอร์สของฉัน',
            'title' => 'คอร์สของฉัน',
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
