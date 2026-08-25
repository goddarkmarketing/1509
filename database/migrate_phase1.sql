-- Phase 1: Student auth, enrollment hardening
-- Run: mysql -u root --default-character-set=utf8mb4 wenxin_lms < database/migrate_phase1.sql

USE wenxin_lms;

ALTER TABLE students
  ADD COLUMN password_hash VARCHAR(255) DEFAULT NULL AFTER line_id;

-- Remove duplicate phones before unique index (keeps lowest id)
DELETE s1 FROM students s1
INNER JOIN students s2
  ON s1.phone = s2.phone AND s1.phone IS NOT NULL AND s1.phone != '' AND s1.id > s2.id;

ALTER TABLE students
  ADD UNIQUE KEY uk_students_phone (phone);

ALTER TABLE enrollments
  ADD UNIQUE KEY uk_enrollment_student_course (student_id, course_id);
