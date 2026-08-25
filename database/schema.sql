-- Wenxin Chinese LMS Database Schema
-- Import: mysql -u root --default-character-set=utf8mb4 < database/schema.sql

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

CREATE DATABASE IF NOT EXISTS wenxin_lms
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE wenxin_lms;

-- Admin users
CREATE TABLE IF NOT EXISTS admin_users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  full_name VARCHAR(100) NOT NULL,
  email VARCHAR(100) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Site settings
CREATE TABLE IF NOT EXISTS site_settings (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  setting_key VARCHAR(100) NOT NULL UNIQUE,
  setting_value TEXT,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Courses
CREATE TABLE IF NOT EXISTS courses (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(120) NOT NULL UNIQUE,
  title VARCHAR(200) NOT NULL,
  subtitle VARCHAR(255) DEFAULT NULL,
  description TEXT,
  category ENUM('foundation','hsk','exam_prep') NOT NULL DEFAULT 'hsk',
  level ENUM('beginner','intermediate','advanced') NOT NULL DEFAULT 'beginner',
  price DECIMAL(10,2) DEFAULT 0,
  duration_hours INT UNSIGNED DEFAULT 0,
  lesson_count INT UNSIGNED DEFAULT 0,
  image_url VARCHAR(255) DEFAULT NULL,
  highlights TEXT,
  is_featured TINYINT(1) DEFAULT 0,
  is_active TINYINT(1) DEFAULT 1,
  sort_order INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Lessons
CREATE TABLE IF NOT EXISTS lessons (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  course_id INT UNSIGNED NOT NULL,
  title VARCHAR(200) NOT NULL,
  description TEXT,
  video_url VARCHAR(500) DEFAULT NULL,
  document_url VARCHAR(500) DEFAULT NULL,
  duration_minutes INT UNSIGNED DEFAULT 0,
  sort_order INT DEFAULT 0,
  is_free_preview TINYINT(1) DEFAULT 0,
  is_published TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Students
CREATE TABLE IF NOT EXISTS students (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(150) NOT NULL,
  email VARCHAR(150) DEFAULT NULL,
  phone VARCHAR(20) DEFAULT NULL,
  line_id VARCHAR(100) DEFAULT NULL,
  password_hash VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_students_phone (phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Enrollments
CREATE TABLE IF NOT EXISTS enrollments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id INT UNSIGNED NOT NULL,
  course_id INT UNSIGNED NOT NULL,
  status ENUM('pending','active','completed','cancelled') DEFAULT 'pending',
  enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_enrollment_student_course (student_id, course_id),
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Payments
CREATE TABLE IF NOT EXISTS payments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  course_id INT UNSIGNED DEFAULT NULL,
  student_name VARCHAR(150) NOT NULL,
  student_email VARCHAR(150) DEFAULT NULL,
  student_phone VARCHAR(20) NOT NULL,
  amount DECIMAL(10,2) DEFAULT 0,
  transfer_date DATE DEFAULT NULL,
  transfer_time VARCHAR(20) DEFAULT NULL,
  slip_image VARCHAR(255) DEFAULT NULL,
  note TEXT,
  status ENUM('pending','verified','rejected') DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payment_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  payment_id INT UNSIGNED NOT NULL,
  course_id INT UNSIGNED NOT NULL,
  amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE,
  FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS lesson_progress (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id INT UNSIGNED NOT NULL,
  lesson_id INT UNSIGNED NOT NULL,
  course_id INT UNSIGNED NOT NULL,
  completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_lesson_progress (student_id, lesson_id),
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE,
  FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS newsletter_subscribers (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_newsletter_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default admin: username admin / password admin123
INSERT INTO admin_users (username, password_hash, full_name, email) VALUES
('admin', '$2y$10$PLAKOEvKVH5ACIGoqzwZxe05HUOfAo7ucKNEMm4Pdol0CrNon.MwO', 'ผู้ดูแลระบบ', 'admin@wenxin.local');

-- Site settings
INSERT INTO site_settings (setting_key, setting_value) VALUES
('site_title', 'Wenxin Chinese'),
('site_tagline', 'เหวินซิน ปั้นภาษาจีนให้เป็นเรื่องง่าย'),
('bank_account_name', 'นางสาว พวงพกา แสนคำแพ'),
('bank_name', 'กสิกร'),
('bank_account_number', '0691172899'),
('payment_note', 'กรุณาแจ้งหลักฐานการโอน เพื่อความรวดเร็วในการดำเนินการ ขอบคุณค่ะ'),
('facebook_url', 'https://www.facebook.com/profile.php?id=61567439751026'),
('line_id', '@janeyangpeiling'),
('phone', '0895567438'),
('hero_title', 'เรียนภาษาจีนกับ Wenxin Chinese'),
('hero_subtitle', 'คอร์สออนไลน์ครบวงจร ตั้งแต่พินอิน HSK 1 ถึง HSK 5 พร้อมคอร์สติวสอบ'),
('email_enabled', '0'),
('email_from', 'noreply@wenxin.local'),
('email_from_name', 'Wenxin Chinese'),
('email_admin', 'admin@wenxin.local'),
('site_url', 'http://localhost/LMS');

-- Courses seed
INSERT INTO courses (slug, title, subtitle, description, category, level, price, duration_hours, lesson_count, highlights, is_featured, sort_order) VALUES
('hsk1-pinyin', 'เรียนภาษาจีนพื้นฐาน พินอิน HSK 1', 'เริ่มต้นภาษาจีนอย่างถูกต้อง', 'คอร์สสำหรับผู้เริ่มต้น เรียนรู้พินอิน คำศัพท์พื้นฐาน และเตรียมสอบ HSK 1 อย่างเป็นระบบ', 'foundation', 'beginner', 2990, 30, 24, 'พินอิน|คำศัพท์พื้นฐาน|HSK 1|แบบฝึกหัด', 1, 1),
('hsk2', 'เรียน HSK 2', 'ต่อยอดจากพื้นฐานสู่ระดับ 2', 'เสริมทักษะฟัง พูด อ่าน เขียน ครอบคลุมคำศัพท์และไวยากรณ์ HSK 2', 'hsk', 'beginner', 3490, 35, 28, 'คำศัพท์ HSK 2|ไวยากรณ์|แบบทดสอบ', 1, 2),
('hsk3', 'เรียน HSK 3', 'ยกระดับภาษาจีนระดับกลาง', 'พัฒนาทักษะการสื่อสารในชีวิตประจำวันและเตรียมสอบ HSK 3', 'hsk', 'intermediate', 3990, 40, 32, 'สนทนา|อ่านเข้าใจ|HSK 3', 1, 3),
('hsk4', 'เรียน HSK 4', 'ภาษาจีนระดับกลาง-สูง', 'เน้นการใช้ภาษาจีนในสถานการณ์จริงและเตรียมสอบ HSK 4', 'hsk', 'intermediate', 4490, 45, 36, 'ไวยากรณ์ขั้นสูง|อ่าน|HSK 4', 1, 4),
('hsk5', 'เรียน HSK 5', 'ระดับสูง ใกล้เคียงเจ้าของภาษา', 'พัฒนาทักษะภาษาจีนระดับสูงสำหรับการเรียน การทำงาน และสอบ HSK 5', 'hsk', 'advanced', 4990, 50, 40, 'คำศัพท์ขั้นสูง|อ่านเชิงลึก|HSK 5', 1, 5),
('exam-prep-hsk3', 'คอร์สติวเพื่อสอบ HSK 3', 'เจาะลึกข้อสอบ HSK 3', 'ติวเข้มเทคนิคทำข้อสอบ ฝึกข้อสอบจริง และทบทวนจุดอ่อนก่อนสอบ', 'exam_prep', 'intermediate', 2490, 15, 12, 'ข้อสอบจริง|เทคนิค|ทบทวน', 0, 6),
('exam-prep-hsk4', 'คอร์สติวเพื่อสอบ HSK 4', 'เจาะลึกข้อสอบ HSK 4', 'เน้นการฝึกทำข้อสอบ วิเคราะห์ข้อผิดพลาด และเพิ่มความมั่นใจก่อนสอบ', 'exam_prep', 'intermediate', 2790, 18, 14, 'ข้อสอบ|วิเคราะห์|ฝึกเข้ม', 0, 7),
('exam-prep-hsk5', 'คอร์สติวเพื่อสอบ HSK 5', 'เจาะลึกข้อสอบ HSK 5', 'ติวเข้มระดับสูง ครอบคลุมทุกทักษะและเทคนิคการทำข้อสอบ HSK 5', 'exam_prep', 'advanced', 2990, 20, 16, 'ข้อสอบ HSK 5|เทคนิค|ฝึกเข้ม', 0, 8);

-- บทเรียนตัวอย่าง (แนะนำรัน php database/seed_content.php เพื่อใส่เนื้อหา+YouTube ครบทุกคอร์ส)
INSERT INTO lessons (course_id, title, description, video_url, sort_order, duration_minutes, is_free_preview, is_published) VALUES
(1, 'บทที่ 1: แนะนำพินอิน', 'เรียนรู้เสียงและวรรณยุกต์ภาษาจีน', 'https://www.youtube.com/watch?v=B6uKm_84Ffo', 1, 25, 1, 1),
(1, 'บทที่ 2: วรรณยุกต์ 4 เสียง', 'ฝึกออกเสียงวรรณยุกต์', 'https://www.youtube.com/watch?v=1hpDUStopE8', 2, 22, 0, 1),
(2, 'บทที่ 1: ทบทวน HSK 1', 'ทบทวนก่อนเริ่ม HSK 2', 'https://www.youtube.com/watch?v=R4j5MaYvvt8', 1, 20, 1, 1);
