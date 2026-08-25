# LMS UI Tests (Playwright)

ตรวจสอบความสูงปุ่ม ระยะห่าง layout และการทำงานของ UI ทั้งหน้า Public และ Admin

## ความต้องการ

- XAMPP / Apache รันอยู่ที่ `http://localhost/LMS/`
- บัญชี Admin: `admin` / `admin123` (หรือตั้งผ่าน env)

## ติดตั้ง

```bash
cd c:\xampp\htdocs\LMS
npm install
npx playwright install chromium
```

## รันเทสต์

```bash
npm test
```

เทสต์ flow ผู้เรียน + ผู้ดูแล (E2E เบราว์เซอร์):

```bash
npm run test:e2e
```

เทสต์ backend แบบ PHP (schema + booking + integration):

```bash
npm run test:php
```

เฉพาะ schema / booking:

```bash
npm run test:php:schema
npm run test:php:booking
```

**ก่อนรันเทสต์บนเซิร์ฟเวอร์** ต้องรัน migration ให้ครบ (โดยเฉพาะ phase 9 สำหรับ `course_sessions`):

`https://your-domain/database/run_all_migrations.php`

รันทั้ง PHP + E2E:

```bash
npm run test:flow
```

รันแบบมี UI:

```bash
npm run test:ui
```

ดูรายงาน HTML:

```bash
npm run test:report
```

## ตัวแปรสภาพแวดล้อม (ไม่บังคับ)

| ตัวแปร | ค่าเริ่มต้น |
|--------|-------------|
| `LMS_BASE_URL` | `http://localhost/LMS/` |
| `LMS_ADMIN_USER` | `admin` |
| `LMS_ADMIN_PASS` | `admin123` |

## ไฟล์เทสต์

| ไฟล์ | ตรวจสอบ |
|------|---------|
| `php/run_all.php` | รัน schema + booking + integration |
| `php/schema_test.php` | ตรวจตาราง/column migration ครบ |
| `php/courses_test.php` | เปิด/ปิดคอร์ส (is_active) + catalog helpers |
| `php/integration_flow_test.php` | สมัคร → แจ้งโอน → admin ยืนยัน → เปิดสิทธิ์ (CLI) |
| `e2e/student-admin-flow.spec.js` | flow ผู้เรียน + ผู้ดูแลครบวงจรในเบราว์เซอร์ |
| `e2e/admin-operations.spec.js` | หน้า admin ชำระเงิน / นักเรียน / จองคลาส / LINE |
| `ui/button-consistency.spec.js` | ความสูงปุ่มใน toolbar / filter bar / card header |
| `ui/layout-spacing.spec.js` | gap ของ grid และ flex บนแดชบอร์ดและหน้าคอร์ส |
| `ui/functionality.spec.js` | ติดต่อเรา, กรอง, ตัวกรองบทเรียน, modal คูปอง, โหลดหน้า admin |
