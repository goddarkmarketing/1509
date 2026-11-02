<?php

declare(strict_types=1);

/**
 * Interactive admin guide tours.
 * Each step may jump across admin pages via href + page key.
 *
 * @return list<array<string, mixed>>
 */
function adminGuideTours(): array
{
    $base = rtrim(APP_URL, '/');

    return [
        [
            'id' => 'getting-started',
            'title' => 'เริ่มต้นใช้หลังบ้าน',
            'summary' => 'รู้จักเมนูหลัก แดชบอร์ด และลำดับงานประจำวันของแอดมิน',
            'duration' => 'ประมาณ 4 นาที',
            'audience' => 'แอดมินใหม่',
            'steps' => [
                [
                    'page' => 'guide',
                    'href' => $base . '/admin/guide.php',
                    'target' => '[data-guide="guide-hub"]',
                    'title' => 'ยินดีต้อนรับสู่คู่มือการใช้งาน',
                    'body' => 'หน้านี้รวมทัวร์ทุกกระบวนการของหลังบ้าน กด “เริ่มทัวร์” ในแต่ละหัวข้อ ระบบจะพาคลิกไล่จุดสำคัญทีละขั้นจนครบ flow จริง',
                ],
                [
                    'page' => 'dashboard',
                    'href' => $base . '/admin/dashboard.php',
                    'target' => '[data-guide-nav="dashboard"]',
                    'title' => 'เมนูแดชบอร์ด',
                    'body' => 'เริ่มงานทุกวันที่นี่ เพื่อดูภาพรวมนักเรียน สิทธิ์เรียน รายได้ และรายการที่รอตรวจ',
                ],
                [
                    'page' => 'dashboard',
                    'href' => $base . '/admin/dashboard.php',
                    'target' => '[data-guide="dashboard-summary"]',
                    'title' => 'การ์ดสรุปตัวเลข',
                    'body' => "ดูตัวเลขสำคัญ:\n• นักเรียน / เปิดสิทธิ์เรียน\n• รายได้รวมและเดือนนี้\n• รอตรวจสอบชำระเงิน → กดดูรายการเพื่อไปอนุมัติ\n• จองรอยืนยัน สำหรับคลาส Live",
                ],
                [
                    'page' => 'dashboard',
                    'href' => $base . '/admin/dashboard.php',
                    'target' => '[data-guide="dashboard-shortcuts"]',
                    'title' => 'ทางลัดงานบ่อย',
                    'body' => 'ใช้ทางลัดเพื่อกระโดดไปเพิ่มคอร์ส เปิดคูปอง ดูตาราง Live หรือจัดการนักเรียน โดยไม่ต้องไล่หาในเมนู',
                ],
                [
                    'page' => 'guide',
                    'href' => $base . '/admin/guide.php',
                    'target' => '[data-guide="guide-catalog"]',
                    'title' => 'เลือกทัวร์ตามงานที่ทำ',
                    'body' => 'เมื่อรู้จักภาพรวมแล้ว เลือกทัวร์ถัดไปตามงานจริง เช่น สร้างคอร์ส ตรวจชำระเงิน หรือเปิดสิทธิ์นักเรียนทุน',
                ],
            ],
        ],
        [
            'id' => 'course-content',
            'title' => 'สร้างคอร์สและบทเรียน',
            'summary' => 'ตั้งแต่เปิดคอร์สใหม่ ใส่รายละเอียด อัปโหลดเนื้อหา ไปจนถึงเผยแพร่ให้นักเรียนเห็น',
            'duration' => 'ประมาณ 6 นาที',
            'audience' => 'ทีมเนื้อหา',
            'steps' => [
                [
                    'page' => 'courses',
                    'href' => $base . '/admin/courses.php',
                    'target' => '[data-guide-nav="courses"]',
                    'title' => 'เข้าเมนูคอร์ส',
                    'body' => 'เมนูนี้ใช้สร้างและแก้ไขคอร์สทั้งหมด ทั้งคอร์สวิดีโอ (VOD) และคลาสสด (Live)',
                ],
                [
                    'page' => 'courses',
                    'href' => $base . '/admin/courses.php',
                    'target' => '[data-guide="courses-add"]',
                    'title' => 'เพิ่มคอร์สใหม่',
                    'body' => 'กดเพิ่มคอร์ส แล้วกรอกชื่อ คำโปรย รายละเอียด หมวดวิชา ระดับ ความยาว และราคา ตั้งค่าประเภทคอร์สให้ถูก (recorded / live / hybrid)',
                ],
                [
                    'page' => 'courses',
                    'href' => $base . '/admin/courses.php',
                    'target' => '[data-guide="courses-add"]',
                    'title' => 'รายการคอร์สและการเปิดใช้',
                    'body' => "หลังบันทึก คอร์สจะโผล่ในตารางด้านล่าง\nตรวจสถานะ「เปิด」และปุ่มดู/แก้ไขก่อนโปรโมทให้นักเรียน\nปุ่ม + เพิ่มคอร์ส อยู่มุมขวาบนของการ์ดนี้",
                ],
                [
                    'page' => 'lessons',
                    'href' => $base . '/admin/lessons.php',
                    'target' => '[data-guide-nav="lessons"]',
                    'title' => 'ไปที่บทเรียน',
                    'body' => 'แต่ละคอร์สควรมีบทเรียนเรียงลำดับ นักเรียนที่เปิดสิทธิ์แล้วจะเข้าเรียนจากเมนู “เริ่มเรียน”',
                ],
                [
                    'page' => 'lessons',
                    'href' => $base . '/admin/lessons.php',
                    'target' => '[data-guide="lessons-form"]',
                    'title' => 'เพิ่ม/แก้ไขบทเรียน',
                    'body' => "เลือกคอร์ส → ใส่ชื่อบท → อัปโหลดวิดีโอหรือวางลิงก์ → ใส่เอกสารประกอบ\nติ๊ก “ดูตัวอย่างฟรี” ได้เฉพาะบททดลอง\nติ๊กเผยแพร่เมื่อพร้อมให้นักเรียนเห็น",
                ],
                [
                    'page' => 'quizzes',
                    'href' => $base . '/admin/quizzes.php',
                    'target' => '[data-guide-nav="quizzes"]',
                    'title' => 'แบบทดสอบ (ถ้ามี)',
                    'body' => 'ถ้าคอร์สมีเงื่อนไขสอบก่อนได้ใบประกาศ ให้สร้างแบบทดสอบผูกกับคอร์ส และกำหนดคะแนนผ่าน',
                ],
                [
                    'page' => 'courses',
                    'href' => $base . '/admin/courses.php',
                    'target' => '[data-guide="courses-add"]',
                    'title' => 'ตรวจผลฝั่งนักเรียน',
                    'body' => 'จากตารางคอร์ส กดปุ่ม「ดู」เพื่อเปิดหน้าเว็บจริง ตรวจรายละเอียด ราคา และปุ่มซื้อ/จองให้ตรงประเภทคอร์ส',
                ],
            ],
        ],
        [
            'id' => 'live-booking',
            'title' => 'คลาส Live: ตาราง จอง ยืนยัน',
            'summary' => 'เปิดรอบเรียน ให้นักเรียนจอง และยืนยันการจองหลังชำระเงินหรือมีสิทธิ์แล้ว',
            'duration' => 'ประมาณ 5 นาที',
            'audience' => 'แอดมินคลาสสด',
            'steps' => [
                [
                    'page' => 'sessions',
                    'href' => $base . '/admin/sessions.php',
                    'target' => '[data-guide-nav="sessions"]',
                    'title' => 'ตารางคลาส Live',
                    'body' => 'ก่อนให้นักเรียนจอง ต้องสร้างรอบเรียน (วัน เวลา ความจุ ลิงก์ Meet/Zoom) ผูกกับคอร์ส Live',
                ],
                [
                    'page' => 'sessions',
                    'href' => $base . '/admin/sessions.php',
                    'target' => '[data-guide="sessions-form"]',
                    'title' => 'สร้างรอบเรียน',
                    'body' => "เลือกคอร์ส Live → ตั้งวันเวลาเริ่ม-จบ → ความจุที่นั่ง → ใส่ลิงก์ห้องเรียน\nสถานะ scheduled = เปิดให้นักเรียนเห็นและจองได้",
                ],
                [
                    'page' => 'bookings',
                    'href' => $base . '/admin/bookings.php',
                    'target' => '[data-guide-nav="bookings"]',
                    'title' => 'เมนูการจองคลาส',
                    'body' => 'เมื่อนักเรียนจองรอบ รายการจะโผล่ที่นี่ สถานะ pending รอตรวจชำระ / confirmed พร้อมเข้าเรียน',
                ],
                [
                    'page' => 'bookings',
                    'href' => $base . '/admin/bookings.php',
                    'target' => '[data-guide="bookings-list"]',
                    'title' => 'ตรวจและจัดการการจอง',
                    'body' => "ตรวจชื่อ เบอร์ รอบเวลา และสถานะ\nหลังยืนยันชำระเงิน ระบบจะยืนยันการจองให้อัตโนมัติ หรือแอดมินยืนยันเองได้จากหน้านี้",
                ],
                [
                    'page' => 'students',
                    'href' => $base . '/admin/students.php',
                    'target' => '[data-guide-nav="students"]',
                    'title' => 'กรณีซื้อคอร์สแล้วแต่ยังไม่จองรอบ',
                    'body' => 'ถ้านักเรียนมีสิทธิ์คอร์สแล้ว ให้เขาเข้าสู่ระบบแล้วไปหน้าจองคลาส — ระบบจะไม่เรียกเก็บเงินซ้ำ แต่ให้ยืนยันรอบเรียนอย่างเดียว',
                ],
            ],
        ],
        [
            'id' => 'payments-enroll',
            'title' => 'ตรวจชำระเงินและเปิดสิทธิ์',
            'summary' => 'ไล่จากสลิปที่รอตรวจ → กดยืนยัน → นักเรียนเข้าเรียนได้',
            'duration' => 'ประมาณ 5 นาที',
            'audience' => 'แอดมินการเงิน',
            'steps' => [
                [
                    'page' => 'payments',
                    'href' => $base . '/admin/payments.php',
                    'target' => '[data-guide-nav="payments"]',
                    'title' => 'เข้าเมนูการชำระเงิน',
                    'body' => 'จุดศูนย์กลางของงานเปิดสิทธิ์หลังโอนเงิน ดูทั้งรายการรอตรวจและรายการที่ยืนยันแล้ว',
                ],
                [
                    'page' => 'payments',
                    'href' => $base . '/admin/payments.php',
                    'target' => '[data-guide="payments-pending-enroll"]',
                    'title' => 'นักเรียนรอเปิดสิทธิ์',
                    'body' => 'ถ้ามี enrollment สถานะ pending จะโผล่ด้านบน กด “เปิดสิทธิ์” เพื่อไปหน้านักเรียนและตั้งสถานะ active ได้เร็ว',
                ],
                [
                    'page' => 'payments',
                    'href' => $base . '/admin/payments.php',
                    'target' => '[data-guide="payments-list"]',
                    'title' => 'รายการแจ้งชำระเงิน',
                    'body' => "อ่านคอลัมน์สำคัญ:\n• ลูกค้า / เบอร์\n• คอร์สและรอบที่เลือก\n• ยอดเงิน\n• สถานะ pending / verified / rejected",
                ],
                [
                    'page' => 'payments',
                    'href' => $base . '/admin/payments.php',
                    'target' => '[data-guide="payments-actions"]',
                    'title' => 'ดูสลิปแล้วกดยืนยัน',
                    'body' => "1) กดปุ่มสลิปเพื่อตรวจยอดและเวลาโอน\n2) ถ้าถูกต้อง กด “ยืนยัน” → ระบบเปิดสิทธิ์เรียนอัตโนมัติ\n3) ถ้าผิด กด “ปฏิเสธ” และแจ้งนักเรียน",
                ],
                [
                    'page' => 'students',
                    'href' => $base . '/admin/students.php',
                    'target' => '[data-guide="students-list"]',
                    'title' => 'ตรวจสิทธิ์ในหน้านักเรียน',
                    'body' => 'หลังยืนยัน เปิดโปรไฟล์นักเรียน ตรวจว่าคอร์สเป็น “เปิดสิทธิ์แล้ว” และให้นักเรียนกด “เริ่มเรียน” บนเว็บ',
                ],
            ],
        ],
        [
            'id' => 'scholarship-manual',
            'title' => 'นักเรียนทุน: เปิดสิทธิ์ด้วยมือ',
            'summary' => 'กรณีให้ทุน/ส่วนลดพิเศษที่ไม่สะดวกใช้คูปอง หรือต้องการเปิดสิทธิ์ทันที',
            'duration' => 'ประมาณ 3 นาที',
            'audience' => 'แอดมิน',
            'steps' => [
                [
                    'page' => 'students',
                    'href' => $base . '/admin/students.php',
                    'target' => '[data-guide-nav="students"]',
                    'title' => 'เข้าเมนูนักเรียน',
                    'body' => 'ใช้เมื่อนักเรียนทุนสมัครบัญชีแล้ว แต่ยังเข้าเรียนไม่ได้ หรือต้องการเปิดคอร์สให้โดยตรง',
                ],
                [
                    'page' => 'students',
                    'href' => $base . '/admin/students.php',
                    'target' => '[data-guide="students-list"]',
                    'title' => 'ค้นหาและเปิดโปรไฟล์',
                    'body' => 'หานักเรียนจากชื่อ/เบอร์ แล้วกดจัดการ เพื่อดูคอร์สที่มีอยู่และสถานะสิทธิ์',
                ],
                [
                    'page' => 'students',
                    'href' => $base . '/admin/students.php',
                    'target' => '[data-guide="students-enroll-form"]',
                    'title' => 'เปิดสิทธิ์คอร์สใหม่',
                    'body' => "เลือกคอร์สจากรายการ → กด “เปิดสิทธิ์”\nสถานะจะเป็น active ทันที นักเรียนล็อกอินแล้วเข้าเรียน/จองรอบได้โดยไม่ต้องโอนเงิน",
                ],
                [
                    'page' => 'coupons',
                    'href' => $base . '/admin/coupons.php',
                    'target' => '[data-guide-nav="coupons"]',
                    'title' => 'ทางเลือก: ใช้คูปอง 100%',
                    'body' => 'ถ้าอยากให้นักเรียนกดใช้โค้ดเอง สร้างคูปองเปอร์เซ็นต์ 100% จำกัดจำนวนครั้ง (เช่น 2 ครั้งสำหรับทุน 2 คน) แล้วให้รหัสไปใช้ตอนชำระเงิน',
                ],
            ],
        ],
        [
            'id' => 'coupons',
            'title' => 'สร้างและใช้คูปองส่วนลด',
            'summary' => 'ตั้งรหัส ส่วนลด เงื่อนไขการใช้งาน และวิธีให้นักเรียนใส่โค้ดตอนชำระเงิน',
            'duration' => 'ประมาณ 4 นาที',
            'audience' => 'การตลาด / แอดมิน',
            'steps' => [
                [
                    'page' => 'coupons',
                    'href' => $base . '/admin/coupons.php',
                    'target' => '[data-guide-nav="coupons"]',
                    'title' => 'เมนูคูปอง',
                    'body' => 'จัดการรหัสส่วนลดทั้งหมด ทั้งโปรโมชันทั่วไปและโค้ดทุนการศึกษา',
                ],
                [
                    'page' => 'coupons',
                    'href' => $base . '/admin/coupons.php',
                    'target' => '[data-guide="coupons-add"]',
                    'title' => 'เพิ่มคูปอง',
                    'body' => "กำหนด:\n• รหัส (ตัวพิมพ์ใหญ่)\n• ประเภท % หรือลดเป็นบาท\n• มูลค่า (ทุนเต็ม = 100%)\n• ยอดขั้นต่ำ / จำนวนครั้งสูงสุด / วันหมดอายุ",
                ],
                [
                    'page' => 'coupons',
                    'href' => $base . '/admin/coupons.php',
                    'target' => '[data-guide="coupons-list"]',
                    'title' => 'ตรวจสถานะการใช้งาน',
                    'body' => 'ดูว่าคูปองเปิดอยู่หรือไม่ และถูกใช้ไปกี่ครั้ง หากครบ max uses ระบบจะไม่ให้ใช้ต่อ',
                ],
                [
                    'page' => 'guide',
                    'href' => $base . '/admin/guide.php',
                    'target' => '[data-guide="guide-student-coupon"]',
                    'title' => 'ฝั่งนักเรียนใช้โค้ดอย่างไร',
                    'body' => "1) เพิ่มคอร์สลงตะกร้า\n2) กรอกรหัสส่วนลด\n3) ถ้ายอดเหลือ 0 บาท (โค้ด 100%) ระบบจะให้ยืนยันลงทะเบียนฟรีและเปิดสิทธิ์ทันที\n4) ถ้ายอดยังเหลือ โอนตามยอดสุทธิแล้วแนบสลิป",
                ],
            ],
        ],
        [
            'id' => 'content-announce',
            'title' => 'เนื้อหาเว็บและประชาสัมพันธ์',
            'summary' => 'อัปเดตข้อความหน้าเว็บ ข่าวสาร และแบนเนอร์ที่นักเรียนเห็น',
            'duration' => 'ประมาณ 3 นาที',
            'audience' => 'แอดมินคอนเทนต์',
            'steps' => [
                [
                    'page' => 'announcements',
                    'href' => $base . '/admin/announcements.php',
                    'target' => '[data-guide-nav="announcements"]',
                    'title' => 'ประชาสัมพันธ์',
                    'body' => 'โพสต์ข่าว โปรโมชัน หรือประกาศสำคัญ ให้นักเรียนเห็นในหน้าข่าวสาร',
                ],
                [
                    'page' => 'content',
                    'href' => $base . '/admin/content.php',
                    'target' => '[data-guide-nav="content"]',
                    'title' => 'เนื้อหาเว็บ',
                    'body' => 'แก้ข้อความหน้าแรก FAQ รีวิว และบล็อกต่าง ๆ โดยไม่ต้องแก้โค้ด',
                ],
                [
                    'page' => 'content',
                    'href' => $base . '/admin/content.php',
                    'target' => '[data-guide="content-editor"]',
                    'title' => 'บันทึกแล้วตรวจหน้าเว็บ',
                    'body' => 'หลังบันทึก ให้เปิด “ดูเว็บไซต์” ในเมนูซ้าย รีเฟรชหน้าเพื่อยืนยันว่าข้อความและรูปขึ้นถูกต้อง',
                ],
            ],
        ],
        [
            'id' => 'settings-backup',
            'title' => 'ตั้งค่าระบบและสำรองข้อมูล',
            'summary' => 'บัญชีธนาคาร การแจ้งเตือน อีเมล และการสำรองก่อนอัปเดตใหญ่',
            'duration' => 'ประมาณ 4 นาที',
            'audience' => 'แอดมินระบบ',
            'steps' => [
                [
                    'page' => 'settings',
                    'href' => $base . '/admin/settings.php',
                    'target' => '[data-guide-nav="settings"]',
                    'title' => 'เข้าเมนูตั้งค่า',
                    'body' => 'รวมค่าติดต่อ บัญชีรับโอน PromptPay อีเมล SMTP LINE และเกตเวย์ชำระเงิน',
                ],
                [
                    'page' => 'settings',
                    'href' => $base . '/admin/settings.php',
                    'target' => '[data-guide="settings-payment"]',
                    'title' => 'ข้อมูลรับชำระเงิน',
                    'body' => 'ตรวจชื่อบัญชี เลขบัญชี และ PromptPay ให้ตรงกับของจริง มิฉะนั้นหน้า checkout ของนักเรียนจะโชว์ข้อมูลผิด',
                ],
                [
                    'page' => 'backup',
                    'href' => $base . '/admin/backup.php',
                    'target' => '[data-guide-nav="backup"]',
                    'title' => 'สำรองข้อมูล',
                    'body' => 'ก่อนอัปเดตระบบใหญ่หรือแก้ข้อมูลจำนวนมาก ควรสำรองฐานข้อมูลไว้ก่อนทุกครั้ง',
                ],
                [
                    'page' => 'users',
                    'href' => $base . '/admin/users.php',
                    'target' => '[data-guide-nav="users"]',
                    'title' => 'ผู้ดูแลระบบ',
                    'body' => 'เพิ่ม/ลดบัญชีแอดมิน และเปลี่ยนรหัสผ่าน ไม่ควรแชร์รหัส admin หลักให้หลายคน',
                ],
            ],
        ],
        [
            'id' => 'feedback-tool',
            'title' => 'Website Feedback',
            'summary' => 'เปิดโหมดรีวิวเว็บ จิ้มจุดบนหน้า แล้วรวบรวมความเห็นทีมงาน',
            'duration' => 'ประมาณ 3 นาที',
            'audience' => 'ทีมรีวิวเว็บ',
            'steps' => [
                [
                    'page' => 'feedback',
                    'href' => $base . '/admin/feedback.php',
                    'target' => '[data-guide-nav="feedback"]',
                    'title' => 'เมนู Website Feedback',
                    'body' => 'ใช้เก็บคอมเมนต์บนหน้าเว็บจริงระหว่างรีวิว UI/ข้อความ โดยไม่ให้นักเรียนทั่วไปเห็นปุ่มนี้',
                ],
                [
                    'page' => 'feedback',
                    'href' => $base . '/admin/feedback.php',
                    'target' => '[data-guide="feedback-settings"]',
                    'title' => 'เปิดโหมดและลิงก์รีวิว',
                    'body' => 'เปิดใช้งาน ตั้งรหัสผ่าน (ถ้าต้องการ) แล้วใช้ลิงก์พรีวิว/รีวิวเพื่อเข้าโหมดคอมเมนต์',
                ],
                [
                    'page' => 'feedback',
                    'href' => $base . '/admin/feedback.php',
                    'target' => '[data-guide="feedback-list"]',
                    'title' => 'จัดการความเห็นที่ส่งมา',
                    'body' => 'อ่านรายการ เปลี่ยนสถานะ และปิดงานเมื่อแก้บนเว็บเสร็จแล้ว',
                ],
            ],
        ],
        [
            'id' => 'student-journey',
            'title' => 'ภาพรวมเส้นทางนักเรียน',
            'summary' => 'เข้าใจสิ่งที่นักเรียนเจอตั้งแต่สมัคร ซื้อคอร์ส จองรอบ ไปจนถึงเริ่มเรียน',
            'duration' => 'ประมาณ 5 นาที',
            'audience' => 'ทุกคนในทีม',
            'steps' => [
                [
                    'page' => 'guide',
                    'href' => $base . '/admin/guide.php',
                    'target' => '[data-guide="guide-student-journey"]',
                    'title' => 'ภาพรวมฝั่งนักเรียน',
                    'body' => "ลำดับปกติ:\nสมัคร/เข้าสู่ระบบ → เลือกคอร์ส → ใส่ตะกร้า → ใส่คูปอง (ถ้ามี) → ชำระเงิน → รอเปิดสิทธิ์ → กดเริ่มเรียน / จองรอบ Live",
                ],
                [
                    'page' => 'payments',
                    'href' => $base . '/admin/payments.php',
                    'target' => '[data-guide-nav="payments"]',
                    'title' => 'จุดที่แอดมินต้องช่วย',
                    'body' => 'หลังนักเรียนแจ้งโอน ทีมงานตรวจสลิปและกดยืนยัน หากเป็นโค้ด 100% ระบบเปิดสิทธิ์ทันทีโดยไม่ต้องรอสลิป',
                ],
                [
                    'page' => 'students',
                    'href' => $base . '/admin/students.php',
                    'target' => '[data-guide-nav="students"]',
                    'title' => 'เมื่อนักเรียนเรียนไม่ได้',
                    'body' => "เช็คตามลำดับ:\n1) ล็อกอินถูกบัญชีหรือยัง\n2) สิทธิ์คอร์สเป็น active หรือไม่\n3) คอร์ส Live จองรอบแล้วหรือยัง\n4) บทเรียนถูกเผยแพร่แล้วหรือไม่",
                ],
                [
                    'page' => 'bookings',
                    'href' => $base . '/admin/bookings.php',
                    'target' => '[data-guide-nav="bookings"]',
                    'title' => 'คลาสสดต้องมีรอบจอง',
                    'body' => 'มีสิทธิ์คอร์สอย่างเดียวไม่พอสำหรับ Live — นักเรียนต้องจองรอบ และควรได้ลิงก์ห้องเรียนหลังยืนยัน',
                ],
                [
                    'page' => 'guide',
                    'href' => $base . '/admin/guide.php',
                    'target' => '[data-guide="guide-catalog"]',
                    'title' => 'เลือกทัวร์เชิงลึกต่อ',
                    'body' => 'กลับมาที่รายการคู่มือ แล้วเลือกทัวร์ย่อยตามงานที่กำลังทำ เพื่อไล่ทีละปุ่มบนหน้าจริง',
                ],
            ],
        ],
    ];
}

function adminGuideTourById(string $id): ?array
{
    foreach (adminGuideTours() as $tour) {
        if (($tour['id'] ?? '') === $id) {
            return $tour;
        }
    }
    return null;
}
