# Wenxin Chinese LMS

ระบบ LMS ภาษาไทยสำหรับ Wenxin Chinese พัฒนาด้วย PHP + MySQL บน XAMPP

## ความต้องการ

- XAMPP (Apache + MySQL + PHP 8+)
- เปิด Apache และ MySQL

## ติดตั้ง

1. วางโปรเจกต์ที่ `C:\xampp\htdocs\LMS`
2. เปิด phpMyAdmin: http://localhost/phpmyadmin
3. Import ไฟล์ `database/schema.sql` (สร้าง DB `wenxin_lms` พร้อมข้อมูลเริ่มต้น)

   **สำคัญ:** ต้อง import แบบ UTF-8 เพื่อไม่ให้ภาษาไทยเพี้ยน

   ```bat
   cmd /c "C:\xampp\mysql\bin\mysql.exe -u root --default-character-set=utf8mb4 < C:\xampp\htdocs\LMS\database\schema.sql"
   ```

   หรือใน phpMyAdmin เลือก Import แล้วตั้ง charset เป็น `utf8mb4`

   **อัปเดตฐานข้อมูลเดิม (Phase 1):** รัน `database/run_migration.php` หรือ import `database/migrate_phase1.sql`

   **อัปเดต Phase 2:** รัน `database/run_migration_phase2.php` หรือ import `database/migrate_phase2.sql`

   **อัปเดต Phase 3:** รัน `database/run_migration_phase3.php`
4. ตรวจสอบการตั้งค่า DB ใน `includes/config.php` หากจำเป็น:
   - DB_HOST: localhost
   - DB_NAME: wenxin_lms
   - DB_USER: root
   - DB_PASS: (ว่างตามค่าเริ่มต้น XAMPP)

## การใช้งาน

| หน้า | URL |
|------|-----|
| หน้าแรก | http://localhost/LMS/public/ |
| คอร์สทั้งหมด | http://localhost/LMS/public/courses.php |
| เข้าสู่ระบบนักเรียน | http://localhost/LMS/public/login.php |
| เริ่มเรียน (หลัง login) | http://localhost/LMS/public/my-courses.php |
| เข้าสู่ระบบ Admin | http://localhost/LMS/public/admin-login.php |
| แดชบอร์ด Admin | http://localhost/LMS/admin/dashboard.php |

### บัญชี Admin เริ่มต้น

- **Username:** admin
- **Password:** admin123

## คอร์สเริ่มต้น (8 คอร์ส)

1. เรียนภาษาจีนพื้นฐาน พินอิน HSK 1
2. เรียน HSK 2
3. เรียน HSK 3
4. เรียน HSK 4
5. เรียน HSK 5
6. คอร์สติวเพื่อสอบ HSK 3
7. คอร์สติวเพื่อสอบ HSK 4
8. คอร์สติวเพื่อสอบ HSK 5

## ฟีเจอร์

- หน้าเว็บ Front-end ภาษาไทย โทนสีแบรนด์ (แดง/ทอง)
- การ์ดคอร์ส + หน้ารายละเอียดคอร์ส
- สมัครสมาชิก / เข้าสู่ระบบนักเรียน (เบอร์โทร + รหัสผ่าน)
- ล็อกบทเรียน — เฉพาะผู้มีสิทธิ์หรือบททดลองฟรี
- แบบฟอร์มแจ้งชำระเงิน + อัปโหลดสลิป (CSRF + ตรวจไฟล์)
- ติดตามความคืบหน้าเรียน (% จบคอร์ส, ทำเครื่องหมายบทจบ)
- อีเมลแจ้ง Admin เมื่อมีการชำระเงิน / แจ้งนักเรียนเมื่อเปิดสิทธิ์ (ตั้งค่าใน Admin → ตั้งค่า)
- รายการชำระเงินหลายคอร์ส (`payment_items`)
- อัปโหลดรูปปกคอร์สและเอกสารบทเรียนในหลังบ้าน
- ค้นหาคอร์สฝั่ง server + หน้า 404
- แบบทดสอบ Quiz (Admin สร้างคำถาม, นักเรียนทำแบบทดสอบ)
- ใบประกาศนียบัตรเมื่อเรียนครบ 100%
- PromptPay QR หน้าชำระเงิน
- Line Notify แจ้งชำระเงิน / เปิดสิทธิ์
- คูปองส่วนลด + แดชบอร์ดรายงานยอดขาย

## เพิ่มวิดีโอและเอกสาร

1. เข้า Admin > บทเรียน
2. เพิ่ม/แก้ไขบทเรียน
3. ใส่ลิงก์ YouTube หรือ URL เอกสาร PDF

## โครงสร้างโฟลเดอร์

```
LMS/
├── admin/          # หลังบ้าน
├── assets/         # CSS, JS, รูป
├── database/       # schema.sql
├── includes/       # config, auth, layout
├── public/         # หน้าเว็บหลัก
└── uploads/        # สลิปโอนเงิน
```
