# คู่มือภาพระบบ Website Feedback (หลังบ้าน)

ใช้ตรวจและแจ้งแก้ไขหน้า **Admin** เท่านั้น ไม่ใช่หน้าบ้าน

| ไฟล์ | เนื้อหา |
|------|---------|
| `00-review-link.png` | เปิดลิงก์ตรวจงาน / กรอกรหัสผ่าน |
| `01-preview-mode.png` | ปุ่มแจ้งแก้ไขบนหลังบ้าน · แถบโหมด · กรอบไฮไลต์ |
| `02-select-and-form.png` | คลิกส่วนใน Admin · กรอกฟอร์ม · บันทึก |
| `03-admin-manage.png` | หน้า `/admin/feedback` จัดการ / Export |

แหล่งไฟล์ต้นทาง: `src/`

```bash
php -S 127.0.0.1:8765 -t docs/manual-feedback/src
# อีกเทอร์มินัล
cd docs/manual-feedback/src && npm i playwright@1.60.0 --no-save && node capture.mjs
```
