-- ล้าง image_url เก่าของคอร์สติวสอบ เพื่อใช้ปก default จาก assets/images/courses/
UPDATE courses
SET image_url = NULL
WHERE slug IN ('exam-prep-hsk3', 'exam-prep-hsk4', 'exam-prep-hsk5');
