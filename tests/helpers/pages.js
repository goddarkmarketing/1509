// @ts-check

/** @type {{ path: string; name: string; requiresAdmin?: boolean }[]} */
const PUBLIC_PAGES = [
  { path: 'public/index.php', name: 'หน้าแรก' },
  { path: 'public/courses.php', name: 'รายการคอร์ส' },
  { path: 'public/faq.php', name: 'FAQ' },
  { path: 'public/login.php', name: 'เข้าสู่ระบบ' },
  { path: 'public/register.php', name: 'สมัครสมาชิก' },
  { path: 'public/instructor.php', name: 'อาจารย์' },
  { path: 'public/cart.php', name: 'ตะกร้า' },
];

/** @type {{ path: string; name: string }[]} */
const ADMIN_PAGES = [
  { path: 'admin/dashboard.php', name: 'แดชบอร์ด' },
  { path: 'admin/courses.php', name: 'คอร์ส' },
  { path: 'admin/lessons.php', name: 'บทเรียน' },
  { path: 'admin/students.php', name: 'นักเรียน' },
  { path: 'admin/coupons.php', name: 'คูปอง' },
  { path: 'admin/payments.php', name: 'ชำระเงิน' },
  { path: 'admin/quizzes.php', name: 'แบบทดสอบ' },
  { path: 'admin/users.php', name: 'ผู้ดูแล' },
  { path: 'admin/settings.php', name: 'ตั้งค่า' },
  { path: 'admin/backup.php', name: 'สำรองข้อมูล' },
];

module.exports = { PUBLIC_PAGES, ADMIN_PAGES };
