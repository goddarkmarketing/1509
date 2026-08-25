<?php
declare(strict_types=1);

/**
 * ใส่เนื้อหาบทเรียน + วิดีโอ YouTube ให้ทุกคอร์ส
 * รัน: php database/seed_content.php
 */
require_once dirname(__DIR__) . '/includes/database.php';
require_once dirname(__DIR__) . '/includes/progress.php';

header('Content-Type: text/plain; charset=utf-8');

/** คลิปสอนภาษาจีน/HSK จาก YouTube (ใช้ทดสอบเรียนได้) */
$videos = [
    'pinyin_intro'    => 'https://www.youtube.com/watch?v=B6uKm_84Ffo',
    'pinyin_basics'   => 'https://www.youtube.com/watch?v=d0yGdNEWdn0',
    'tones'           => 'https://www.youtube.com/watch?v=1hpDUStopE8',
    'greetings'       => 'https://www.youtube.com/watch?v=LQnUyF8WZ9E',
    'numbers'         => 'https://www.youtube.com/watch?v=K6MlrqLvNvs',
    'hsk1_vocab'      => 'https://www.youtube.com/watch?v=R4j5MaYvvt8',
    'daily_phrases'   => 'https://www.youtube.com/watch?v=sQq_VyBVizI',
    'grammar_basic'   => 'https://www.youtube.com/watch?v=8WnTxAa0Aj8',
    'hsk2_vocab'      => 'https://www.youtube.com/watch?v=0p00osMmP2A',
    'hsk2_grammar'    => 'https://www.youtube.com/watch?v=nZ_p9YW1TkM',
    'hsk3_listen'     => 'https://www.youtube.com/watch?v=Cl8YdGxpjK0',
    'hsk3_speak'      => 'https://www.youtube.com/watch?v=_BJH-bHHM0Y',
    'hsk4_read'       => 'https://www.youtube.com/watch?v=MkzuC1-cjE4',
    'hsk4_write'      => 'https://www.youtube.com/watch?v=LZj_W639oRo',
    'hsk5_adv'        => 'https://www.youtube.com/watch?v=1Bvvhayb_TI',
    'exam_tips'       => 'https://www.youtube.com/watch?v=RpE-hViagww',
    'exam_listening'  => 'https://www.youtube.com/watch?v=L4Xm6AMS3GE',
    'exam_reading'    => 'https://www.youtube.com/watch?v=nAeij3FzGzI',
];

/**
 * @return array<string, list<array{title:string,description:string,video:string,duration:int,preview:bool}>>
 */
function courseLessonPlans(array $videos): array
{
    return [
        'hsk1-pinyin' => [
            ['title' => 'บทที่ 1: แนะนำพินอิน', 'description' => 'เสียงและระบบพินอินภาษาจีน — จุดเริ่มต้นที่ถูกต้อง', 'video' => $videos['pinyin_intro'], 'duration' => 18, 'preview' => true],
            ['title' => 'บทที่ 2: วรรณยุกต์ 4 เสียง', 'description' => 'ฝึกออกเสียงวรรณยุกต์ ā á ǎ à ให้ชัด', 'video' => $videos['tones'], 'duration' => 22, 'preview' => false],
            ['title' => 'บทที่ 3: พยัญชนะพินอิน', 'description' => 'อ่านพินอินได้ครบ เริ่มผสมเสียง', 'video' => $videos['pinyin_basics'], 'duration' => 25, 'preview' => false],
            ['title' => 'บทที่ 4: คำทักทาย', 'description' => '你好 谢谢 再见 และประโยคใช้บ่อย', 'video' => $videos['greetings'], 'duration' => 20, 'preview' => false],
            ['title' => 'บทที่ 5: ตัวเลขและเวลา', 'description' => 'เลข 1–100 และบอกเวลาง่ายๆ', 'video' => $videos['numbers'], 'duration' => 24, 'preview' => false],
            ['title' => 'บทที่ 6: คำศัพท์ HSK 1 (1)', 'description' => 'คำศัพท์พื้นฐานชุดแรกสำหรับสอบ HSK 1', 'video' => $videos['hsk1_vocab'], 'duration' => 28, 'preview' => false],
            ['title' => 'บทที่ 7: ประโยคในชีวิตประจำวัน', 'description' => 'พูดจีนในสถานการณ์จริง — ซื้อของ ถามทาง', 'video' => $videos['daily_phrases'], 'duration' => 26, 'preview' => false],
            ['title' => 'บทที่ 8: ไวยากรณ์เบื้องต้น', 'description' => 'โครงประโยคจีนง่ายๆ ที่ต้องรู้', 'video' => $videos['grammar_basic'], 'duration' => 30, 'preview' => false],
        ],
        'hsk2' => [
            ['title' => 'บทที่ 1: ทบทวน HSK 1', 'description' => 'เตรียมพร้อมก่อนเข้า HSK 2', 'video' => $videos['hsk1_vocab'], 'duration' => 20, 'preview' => true],
            ['title' => 'บทที่ 2: คำศัพท์ HSK 2 (1)', 'description' => 'คำศัพท์ใหม่ระดับ 2 — ชุดที่ 1', 'video' => $videos['hsk2_vocab'], 'duration' => 28, 'preview' => false],
            ['title' => 'บทที่ 3: คำศัพท์ HSK 2 (2)', 'description' => 'ขยายคำศัพท์และคำผสม', 'video' => $videos['daily_phrases'], 'duration' => 26, 'preview' => false],
            ['title' => 'บทที่ 4: ไวยากรณ์ HSK 2', 'description' => '了 过 着 และโครงประโยคสำคัญ', 'video' => $videos['hsk2_grammar'], 'duration' => 32, 'preview' => false],
            ['title' => 'บทที่ 5: สนทนาสถานการณ์จริง', 'description' => 'ฝึกฟัง-พูดในบทสนทนา', 'video' => $videos['greetings'], 'duration' => 24, 'preview' => false],
            ['title' => 'บทที่ 6: แบบทดสอบท้ายคอร์ส', 'description' => 'ทบทวนและเตรียมสอบ HSK 2', 'video' => $videos['exam_tips'], 'duration' => 22, 'preview' => false],
        ],
        'hsk3' => [
            ['title' => 'บทที่ 1: ภาพรวม HSK 3', 'description' => 'ระดับกลาง — เป้าหมายและทักษะที่ต้องมี', 'video' => $videos['hsk3_listen'], 'duration' => 18, 'preview' => true],
            ['title' => 'บทที่ 2: ฟังและเข้าใจ', 'description' => 'ฝึกทักษะการฟังระดับ HSK 3', 'video' => $videos['hsk3_listen'], 'duration' => 30, 'preview' => false],
            ['title' => 'บทที่ 3: พูดและสื่อสาร', 'description' => 'ประโยคสนทนาในชีวิตประจำวัน', 'video' => $videos['hsk3_speak'], 'duration' => 28, 'preview' => false],
            ['title' => 'บทที่ 4: อ่านและเขียน', 'description' => 'ข้อความสั้นและการใช้คำเชื่อม', 'video' => $videos['hsk4_read'], 'duration' => 32, 'preview' => false],
            ['title' => 'บทที่ 5: ไวยากรณ์ขยาย', 'description' => 'โครงสร้างประโยคที่พบบ่อยใน HSK 3', 'video' => $videos['grammar_basic'], 'duration' => 30, 'preview' => false],
            ['title' => 'บทที่ 6: เตรียมสอบ HSK 3', 'description' => 'เทคนิคทำข้อสอบและทบทวน', 'video' => $videos['exam_tips'], 'duration' => 25, 'preview' => false],
        ],
        'hsk4' => [
            ['title' => 'บทที่ 1: เริ่มต้น HSK 4', 'description' => 'ภาพรวมคอร์สและทักษะระดับกลาง-สูง', 'video' => $videos['hsk4_read'], 'duration' => 20, 'preview' => true],
            ['title' => 'บทที่ 2: การอ่านเชิงลึก', 'description' => 'บทความยาวและคำศัพท์เฉพาะทาง', 'video' => $videos['hsk4_read'], 'duration' => 35, 'preview' => false],
            ['title' => 'บทที่ 3: การเขียน', 'description' => 'ประโยคซับซ้อนและการใช้คำให้ถูกต้อง', 'video' => $videos['hsk4_write'], 'duration' => 32, 'preview' => false],
            ['title' => 'บทที่ 4: ฟังขั้นสูง', 'description' => 'บทสนทนาและรายงานข่าวง่ายๆ', 'video' => $videos['exam_listening'], 'duration' => 30, 'preview' => false],
            ['title' => 'บทที่ 5: ทบทวนก่อนสอบ', 'description' => 'สรุปจุดสำคัญ HSK 4', 'video' => $videos['exam_tips'], 'duration' => 28, 'preview' => false],
        ],
        'hsk5' => [
            ['title' => 'บทที่ 1: ภาพรวม HSK 5', 'description' => 'ระดับสูง — เป้าหมายและแผนการเรียน', 'video' => $videos['hsk5_adv'], 'duration' => 22, 'preview' => true],
            ['title' => 'บทที่ 2: คำศัพท์ขั้นสูง', 'description' => 'คำศัพท์เชิงวิชาการและทางการ', 'video' => $videos['hsk5_adv'], 'duration' => 38, 'preview' => false],
            ['title' => 'บทที่ 3: อ่านวรรณกรรมสั้น', 'description' => 'ฝึกอ่านและวิเคราะห์ข้อความ', 'video' => $videos['exam_reading'], 'duration' => 40, 'preview' => false],
            ['title' => 'บทที่ 4: ฟังและสรุป', 'description' => 'ฝึกฟังและจดใจความสำคัญ', 'video' => $videos['exam_listening'], 'duration' => 36, 'preview' => false],
            ['title' => 'บทที่ 5: เตรียมสอบ HSK 5', 'description' => 'กลยุทธ์ทำข้อสอบระดับสูง', 'video' => $videos['exam_tips'], 'duration' => 30, 'preview' => false],
        ],
        'exam-prep-hsk3' => [
            ['title' => 'ติวที่ 1: โครงสร้างข้อสอบ HSK 3', 'description' => 'รู้จักรูปแบบข้อสอบแต่ละพาร์ท', 'video' => $videos['exam_tips'], 'duration' => 25, 'preview' => true],
            ['title' => 'ติวที่ 2: พาร์ทฟัง', 'description' => 'เทคนิคทำข้อสอบฟัง HSK 3', 'video' => $videos['exam_listening'], 'duration' => 30, 'preview' => false],
            ['title' => 'ติวที่ 3: พาร์ทอ่าน', 'description' => 'อ่านเร็วและหาคำตอบแม่นยำ', 'video' => $videos['exam_reading'], 'duration' => 28, 'preview' => false],
            ['title' => 'ติวที่ 4: จำลองสอบ', 'description' => 'ฝึกทำข้อสอบจริงและทบทวนจุดอ่อน', 'video' => $videos['hsk3_listen'], 'duration' => 35, 'preview' => false],
        ],
        'exam-prep-hsk4' => [
            ['title' => 'ติวที่ 1: ภาพรวมข้อสอบ HSK 4', 'description' => 'คะแนน โครงสร้าง และการจัดเวลา', 'video' => $videos['exam_tips'], 'duration' => 22, 'preview' => true],
            ['title' => 'ติวที่ 2: ฟังขั้นสูง', 'description' => 'ฝึกฟังบทสนทนายาว', 'video' => $videos['exam_listening'], 'duration' => 32, 'preview' => false],
            ['title' => 'ติวที่ 3: อ่านและไวยากรณ์', 'description' => 'ข้อสอบอ่านและโครงประโยคที่ออกบ่อย', 'video' => $videos['hsk4_read'], 'duration' => 34, 'preview' => false],
            ['title' => 'ติวที่ 4: เขียนและสรุป', 'description' => 'เทคนิคพาร์ทเขียนและทบทวนทั้งคอร์ส', 'video' => $videos['hsk4_write'], 'duration' => 30, 'preview' => false],
        ],
        'exam-prep-hsk5' => [
            ['title' => 'ติวที่ 1: กลยุทธ์ HSK 5', 'description' => 'วางแผนสอบและจัดการเวลา', 'video' => $videos['exam_tips'], 'duration' => 24, 'preview' => true],
            ['title' => 'ติวที่ 2: คำศัพท์ที่ออกสอบบ่อย', 'description' => 'ทบทวนคำศัพท์ระดับสูง', 'video' => $videos['hsk5_adv'], 'duration' => 36, 'preview' => false],
            ['title' => 'ติวที่ 3: อ่านเชิงวิเคราะห์', 'description' => 'บทความยาวและคำถามเชิงลึก', 'video' => $videos['exam_reading'], 'duration' => 38, 'preview' => false],
            ['title' => 'ติวที่ 4: จำลองสอบเต็มรูปแบบ', 'description' => 'ฝึกเข้มก่อนวันสอบจริง', 'video' => $videos['exam_listening'], 'duration' => 40, 'preview' => false],
        ],
    ];
}

$plans = courseLessonPlans($videos);
$pdo = db();
$courseRows = $pdo->query('SELECT id, slug, title FROM courses ORDER BY sort_order')->fetchAll();
$insert = $pdo->prepare('
    INSERT INTO lessons (course_id, title, description, video_url, duration_minutes, sort_order, is_free_preview, is_published)
    VALUES (?, ?, ?, ?, ?, ?, ?, 1)
');

$totalInserted = 0;
$lines = [];

foreach ($courseRows as $course) {
    $slug = $course['slug'];
    $courseId = (int) $course['id'];
    $lessons = $plans[$slug] ?? [];

    if ($lessons === []) {
        $lines[] = "SKIP {$slug}: no lesson plan";
        continue;
    }

    $pdo->prepare('DELETE FROM lessons WHERE course_id = ?')->execute([$courseId]);

    $order = 1;
    foreach ($lessons as $lesson) {
        $insert->execute([
            $courseId,
            $lesson['title'],
            $lesson['description'],
            $lesson['video'],
            $lesson['duration'],
            $order,
            $lesson['preview'] ? 1 : 0,
        ]);
        $order++;
        $totalInserted++;
    }

    syncCourseLessonCount($courseId);
    $count = $order - 1;
    $lines[] = "OK {$slug}: {$count} lessons";
}

echo "Wenxin LMS content seed complete\n";
echo "Total lessons inserted: {$totalInserted}\n\n";
foreach ($lines as $line) {
    echo "- {$line}\n";
}
