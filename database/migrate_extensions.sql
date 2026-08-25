-- Wenxin LMS — ตารางเพิ่มเติมหลัง schema.sql (รันใน phpMyAdmin หลังเลือกฐานข้อมูลแล้ว)
-- จำเป็นสำหรับ: สมัครสมาชิก, ใบประกาศ, คูปอง, รีเซ็ตรหัสผ่าน, Omise

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS quizzes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  course_id INT UNSIGNED NOT NULL,
  title VARCHAR(200) NOT NULL,
  description TEXT,
  pass_score INT UNSIGNED DEFAULT 70,
  time_limit_minutes INT UNSIGNED DEFAULT 0,
  is_published TINYINT(1) DEFAULT 1,
  sort_order INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS quiz_questions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  quiz_id INT UNSIGNED NOT NULL,
  question_text TEXT NOT NULL,
  audio_url VARCHAR(500) DEFAULT NULL,
  options_json TEXT NOT NULL,
  correct_key VARCHAR(10) NOT NULL,
  sort_order INT DEFAULT 0,
  FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS quiz_attempts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id INT UNSIGNED NOT NULL,
  quiz_id INT UNSIGNED NOT NULL,
  score INT UNSIGNED DEFAULT 0,
  total_questions INT UNSIGNED DEFAULT 0,
  passed TINYINT(1) DEFAULT 0,
  answers_json TEXT,
  completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS certificates (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id INT UNSIGNED NOT NULL,
  course_id INT UNSIGNED NOT NULL,
  certificate_code VARCHAR(32) NOT NULL UNIQUE,
  issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_cert_student_course (student_id, course_id),
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS coupons (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(50) NOT NULL UNIQUE,
  discount_type ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
  discount_value DECIMAL(10,2) NOT NULL DEFAULT 0,
  min_amount DECIMAL(10,2) DEFAULT 0,
  max_uses INT UNSIGNED DEFAULT 0,
  used_count INT UNSIGNED DEFAULT 0,
  expires_at DATE DEFAULT NULL,
  is_active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS password_reset_tokens (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id INT UNSIGNED NOT NULL,
  token_hash VARCHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  used_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_token_hash (token_hash),
  INDEX idx_student_id (student_id),
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_attempts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  attempt_type ENUM('student','admin') NOT NULL,
  identifier VARCHAR(150) NOT NULL,
  ip_address VARCHAR(45) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_attempt_lookup (attempt_type, identifier, created_at),
  INDEX idx_attempt_ip (attempt_type, ip_address, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- payments columns (ignore error if already exists)
ALTER TABLE payments ADD COLUMN coupon_code VARCHAR(50) DEFAULT NULL AFTER note;
ALTER TABLE payments ADD COLUMN payment_method VARCHAR(20) NOT NULL DEFAULT 'transfer' AFTER status;
ALTER TABLE payments ADD COLUMN omise_charge_id VARCHAR(50) DEFAULT NULL AFTER payment_method;

INSERT IGNORE INTO site_settings (setting_key, setting_value) VALUES
('line_notify_enabled', '0'),
('line_notify_token', ''),
('promptpay_enabled', '1'),
('promptpay_id', ''),
('promptpay_id_type', 'phone'),
('email_transport', 'mail'),
('smtp_host', ''),
('smtp_port', '587'),
('smtp_user', ''),
('smtp_pass', ''),
('smtp_encryption', 'tls'),
('certificate_require_quiz', '0'),
('privacy_policy_html', ''),
('terms_html', ''),
('omise_enabled', '0'),
('omise_public_key', ''),
('omise_secret_key', '');

INSERT IGNORE INTO coupons (code, discount_type, discount_value, min_amount, max_uses, is_active) VALUES
('WENXIN10', 'percent', 10, 0, 0, 1);

CREATE TABLE IF NOT EXISTS announcements (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(120) NOT NULL UNIQUE,
  title VARCHAR(200) NOT NULL,
  excerpt VARCHAR(500) DEFAULT NULL,
  body TEXT NOT NULL,
  image_url VARCHAR(255) DEFAULT NULL,
  attachment_url VARCHAR(255) DEFAULT NULL,
  attachment_name VARCHAR(255) DEFAULT NULL,
  category ENUM('general','promo','course','event') NOT NULL DEFAULT 'general',
  is_pinned TINYINT(1) DEFAULT 0,
  is_published TINYINT(1) DEFAULT 1,
  published_at DATETIME DEFAULT NULL,
  sort_order INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_announcements_published (is_published, published_at, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS course_games (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  course_id INT UNSIGNED NOT NULL,
  title VARCHAR(200) NOT NULL,
  description TEXT,
  game_url VARCHAR(500) NOT NULL,
  is_published TINYINT(1) DEFAULT 1,
  sort_order INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
