<?php
declare(strict_types=1);

/**
 * Seed sample exam packs for demo (idempotent by slug).
 * Run: php storage/seed_exam_packs.php
 */

require_once dirname(__DIR__) . '/includes/database.php';
require_once dirname(__DIR__) . '/includes/exam.php';

header('Content-Type: text/plain; charset=utf-8');

if (!examTablesReady()) {
    echo "FAIL: run database/run_migration_phase12.php first\n";
    exit(1);
}

$packs = [
    [
        'slug' => 'math-m3-mock-01',
        'title' => 'คณิตศาสตร์ ม.3 จำลองสนามสอบ ชุดที่ 1',
        'subtitle' => 'ฝึกจับเวลาเหมือนสอบจริง ครอบคลุมพีชคณิตและเรขาคณิต',
        'subject' => 'คณิตศาสตร์',
        'level' => 'intermediate',
        'price' => 199,
        'time_limit_minutes' => 45,
        'pass_score' => 50,
        'description' => "ชุดจำลองสนามสอบคณิตศาสตร์ ม.3 สำหรับทบทวนก่อนสอบโรงเรียนและสอบเข้า\nซื้อแล้วเข้าทำได้ไม่จำกัดครั้ง จนกว่าจะหมดสิทธิ์ตามเงื่อนไขแพ็กเกจ",
        'instructions' => "1) เตรียมกระดาษทดและเครื่องคิดเลขตามที่อนุญาต\n2) ห้ามเปิดแท็บอื่นระหว่างสอบ\n3) เมื่อหมดเวลาระบบจะส่งคำตอบให้อัตโนมัติ",
        'sort_order' => 1,
        'questions' => [
            ['ข้อใดคือผลของ 12 × 8?', ['A' => '86', 'B' => '96', 'C' => '98', 'D' => '106'], 'B', '12×8 = 96'],
            ['รากที่สองของ 81 เท่ากับเท่าใด?', ['A' => '7', 'B' => '8', 'C' => '9', 'D' => '11'], 'C', '9×9 = 81'],
            ['ถ้า 3x = 21 แล้ว x เท่ากับเท่าใด?', ['A' => '5', 'B' => '6', 'C' => '7', 'D' => '8'], 'C', 'x = 21÷3 = 7'],
            ['พื้นที่สี่เหลี่ยมมุมฉาก กว้าง 5 ยาว 12 เท่ากับเท่าใด?', ['A' => '17', 'B' => '34', 'C' => '60', 'D' => '120'], 'C', 'พื้นที่ = กว้าง × ยาว = 60'],
            ['ร้อยละ 20 ของ 250 เท่ากับเท่าใด?', ['A' => '25', 'B' => '40', 'C' => '50', 'D' => '55'], 'C', '0.2 × 250 = 50'],
            ['มุมฉากมีขนาดกี่องศา?', ['A' => '45', 'B' => '60', 'C' => '90', 'D' => '180'], 'C', 'มุมฉาก = 90 องศา'],
            ['จำนวนเฉพาะในช่วง 1–10 มีกี่จำนวน?', ['A' => '3', 'B' => '4', 'C' => '5', 'D' => '6'], 'B', '2,3,5,7'],
            ['ค่าเฉลี่ยของ 4, 6, 8, 10 เท่ากับเท่าใด?', ['A' => '6', 'B' => '7', 'C' => '8', 'D' => '9'], 'B', '(4+6+8+10)/4 = 7'],
            ['ถ้าสามเหลี่ยมมีมุมสองมุมเป็น 40° และ 60° มุมที่สามเท่ากับเท่าใด?', ['A' => '70°', 'B' => '80°', 'C' => '90°', 'D' => '100°'], 'B', '180−40−60 = 80'],
            ['2^5 เท่ากับเท่าใด?', ['A' => '10', 'B' => '16', 'C' => '25', 'D' => '32'], 'D', '2×2×2×2×2 = 32'],
        ],
    ],
    [
        'slug' => 'english-listening-reading-mock',
        'title' => 'ภาษาอังกฤษ Reading จำลองสนามสอบ',
        'subtitle' => 'ฝึกอ่านจับใจความและไวยากรณ์พื้นฐาน',
        'subject' => 'ภาษาอังกฤษ',
        'level' => 'beginner',
        'price' => 149,
        'time_limit_minutes' => 30,
        'pass_score' => 60,
        'description' => 'ชุดฝึก Reading แบบจับเวลา เหมาะกับผู้เริ่มต้นที่อยากคุ้นเคยกับรูปแบบข้อสอบ',
        'instructions' => "อ่านโจทย์ให้ครบก่อนเลือกคำตอบ\nสามารถข้ามข้อและกลับมาตอบทีหลังได้",
        'sort_order' => 2,
        'questions' => [
            ['Choose the correct article: ___ apple', ['A' => 'a', 'B' => 'an', 'C' => 'the', 'D' => 'no article'], 'B', 'apple ขึ้นต้นด้วยสระเสียง → an'],
            ['She ___ to school every day.', ['A' => 'go', 'B' => 'goes', 'C' => 'going', 'D' => 'gone'], 'B', 'She = ประธานเอกพจน์ → goes'],
            ['Synonym of “happy” is…', ['A' => 'sad', 'B' => 'angry', 'C' => 'glad', 'D' => 'tired'], 'C', 'glad ≈ happy'],
            ['What is the past tense of “eat”?', ['A' => 'eated', 'B' => 'ate', 'C' => 'eaten', 'D' => 'eating'], 'B', 'eat → ate → eaten'],
            ['“I have lived here ___ 2019.”', ['A' => 'for', 'B' => 'since', 'C' => 'during', 'D' => 'by'], 'B', 'since + จุดเวลา'],
            ['Opposite of “expensive” is…', ['A' => 'cheap', 'B' => 'large', 'C' => 'heavy', 'D' => 'fast'], 'A', 'expensive ↔ cheap'],
            ['Select the correct sentence.', ['A' => 'He don’t like tea.', 'B' => 'He doesn’t likes tea.', 'C' => 'He doesn’t like tea.', 'D' => 'He not like tea.'], 'C', 'doesn’t + V1'],
            ['“Book” is a…', ['A' => 'verb only', 'B' => 'noun', 'C' => 'adjective', 'D' => 'adverb'], 'B', 'book = คำนาม (อาจเป็นกริยาได้แต่ในตัวเลือก noun ชัดสุด)'],
        ],
    ],
    [
        'slug' => 'thai-onet-mock-short',
        'title' => 'ภาษาไทย จำลองข้อสอบสั้น',
        'subtitle' => 'ทบทวนหลักภาษาและการอ่านจับใจความ',
        'subject' => 'ภาษาไทย',
        'level' => 'intermediate',
        'price' => 129,
        'time_limit_minutes' => 25,
        'pass_score' => 50,
        'description' => 'ชุดสั้นสำหรับอุ่นเครื่องก่อนสอบจริง ซื้อแล้วเข้าสนามสอบจำลองได้ทันทีหลังยืนยันชำระเงิน',
        'instructions' => 'ตอบให้ครบทุกข้อหากทำได้ และใช้แผงหมายเลขข้อเพื่อทบทวน',
        'sort_order' => 3,
        'questions' => [
            ['คำว่า “อร่อย” เป็นชนิดของคำใด?', ['A' => 'คำนาม', 'B' => 'คำกริยา', 'C' => 'คำวิเศษณ์', 'D' => 'คำบุพบท'], 'C', 'อร่อย ขยายกริยา/นาม → คำวิเศษณ์'],
            ['ข้อใดสะกดถูกต้อง?', ['A' => 'พฤษภาคม', 'B' => 'พฤษาคม', 'C' => 'พึษภาคม', 'D' => 'พฤษภาคมม'], 'A', 'พฤษภาคม'],
            ['คำว่า “แม่น้ำ” เป็นคำประเภทใด?', ['A' => 'คำมูล', 'B' => 'คำประสม', 'C' => 'คำซ้ำ', 'D' => 'คำสมาส'], 'B', 'แม่ + น้ำ'],
            ['สำนวน “ปิดทองหลังพระ” หมายถึงอะไร?', ['A' => 'โอ้อวด', 'B' => 'ทำดีโดยไม่หวังชื่อเสียง', 'C' => 'ใช้เงินฟุ่มเฟือย', 'D' => 'แก้แค้น'], 'B', 'ทำดีเงียบ ๆ'],
            ['ข้อใดเป็นประโยคบอกเล่า?', ['A' => 'เธอไปไหน?', 'B' => 'อย่าวิ่ง!', 'C' => 'วันนี้ฝนตก', 'D' => 'ช่วยเปิดไฟหน่อย'], 'C', 'บอกเล่า'],
            ['คำที่มีความหมายตรงข้ามกับ “กว้าง” คือ?', ['A' => 'ยาว', 'B' => 'แคบ', 'C' => 'สูง', 'D' => 'ใหญ่'], 'B', 'กว้าง ↔ แคบ'],
        ],
    ],
    [
        'slug' => 'science-m2-mock-01',
        'title' => 'วิทยาศาสตร์ ม.2 จำลองสนามสอบ',
        'subtitle' => 'ทบทวนชีววิทยา เคมี และฟิสิกส์พื้นฐาน',
        'subject' => 'วิทยาศาสตร์',
        'level' => 'intermediate',
        'price' => 179,
        'time_limit_minutes' => 40,
        'pass_score' => 55,
        'description' => "ชุดจำลองสนามสอบวิทยาศาสตร์ ม.2 สำหรับทบทวนก่อนสอบกลางภาคและปลายภาค\nซื้อแล้วเข้าทำได้หลายครั้ง พร้อมเฉลยหลังส่ง",
        'instructions' => "1) อ่านโจทย์ให้ครบก่อนเลือกคำตอบ\n2) ข้อคำนวณให้ทดในกระดาษ\n3) หมดเวลาแล้วระบบส่งอัตโนมัติ",
        'sort_order' => 4,
        'questions' => [
            ['หน่วยของแรงในระบบ SI คือข้อใด?', ['A' => 'จูล', 'B' => 'วัตต์', 'C' => 'นิวตัน', 'D' => 'ปาสกาล'], 'C', 'แรงใช้หน่วยนิวตัน (N)'],
            ['น้ำเดือดที่ความดันมาตรฐานมีอุณหภูมิประมาณเท่าใด?', ['A' => '0°C', 'B' => '37°C', 'C' => '100°C', 'D' => '212°C'], 'C', 'น้ำเดือดที่ 100°C'],
            ['พืชสร้างอาหารด้วยกระบวนการใด?', ['A' => 'การหายใจ', 'B' => 'การสังเคราะห์ด้วยแสง', 'C' => 'การย่อยอาหาร', 'D' => 'การหมัก'], 'B', 'photosynthesis'],
            ['สัญลักษณ์ทางเคมีของออกซิเจนคือข้อใด?', ['A' => 'Ox', 'B' => 'O', 'C' => 'Og', 'D' => 'On'], 'B', 'Oxygen = O'],
            ['ข้อใดเป็นพลังงานจลน์?', ['A' => 'น้ำในเขื่อน', 'B' => 'ลูกบอลที่กลิ้ง', 'C' => 'แบตเตอรี่ที่ยังไม่ใช้', 'D' => 'สปริงที่ถูกกด'], 'B', 'วัตถุที่เคลื่อนที่ = พลังงานจลน์'],
            ['อวัยวะใดทำหน้าที่สูบฉีดเลือด?', ['A' => 'ปอด', 'B' => 'ตับ', 'C' => 'หัวใจ', 'D' => 'ไต'], 'C', 'หัวใจสูบฉีดเลือด'],
            ['แสงเดินทางเร็วที่สุดในตัวกลางใด?', ['A' => 'น้ำ', 'B' => 'แก้ว', 'C' => 'อากาศ', 'D' => 'สุญญากาศ'], 'D', 'แสงเร็วสุดในสุญญากาศ'],
            ['pH ของสารที่เป็นกลางมีค่าประมาณเท่าใด?', ['A' => '1', 'B' => '3', 'C' => '7', 'D' => '14'], 'C', 'กลาง ≈ 7'],
        ],
    ],
];

$insertedPacks = 0;
$insertedQuestions = 0;

foreach ($packs as $pack) {
    $existing = getExamPackBySlug($pack['slug']);
    if ($existing) {
        $packId = (int) $existing['id'];
        echo "SKIP pack exists: {$pack['slug']} (#{$packId})\n";
    } else {
        $stmt = db()->prepare('
            INSERT INTO exam_packs
              (slug, title, subtitle, description, instructions, subject, level, price, time_limit_minutes, pass_score, is_published, is_featured, sort_order)
            VALUES (?,?,?,?,?,?,?,?,?,?,1,1,?)
        ');
        $stmt->execute([
            $pack['slug'],
            $pack['title'],
            $pack['subtitle'],
            $pack['description'],
            $pack['instructions'],
            $pack['subject'],
            $pack['level'],
            $pack['price'],
            $pack['time_limit_minutes'],
            $pack['pass_score'],
            $pack['sort_order'],
        ]);
        $packId = (int) db()->lastInsertId();
        $insertedPacks++;
        echo "OK pack: {$pack['slug']} (#{$packId})\n";
    }

    $countStmt = db()->prepare('SELECT COUNT(*) FROM exam_questions WHERE exam_pack_id = ?');
    $countStmt->execute([$packId]);
    if ((int) $countStmt->fetchColumn() > 0) {
        echo "  SKIP questions already present\n";
        continue;
    }

    $qStmt = db()->prepare('
        INSERT INTO exam_questions (exam_pack_id, question_text, options_json, correct_key, explanation, sort_order)
        VALUES (?,?,?,?,?,?)
    ');
    foreach ($pack['questions'] as $i => $q) {
        [$text, $opts, $correct, $explain] = $q;
        $qStmt->execute([
            $packId,
            $text,
            json_encode($opts, JSON_UNESCAPED_UNICODE),
            $correct,
            $explain,
            $i + 1,
        ]);
        $insertedQuestions++;
    }
    echo '  OK questions: ' . count($pack['questions']) . "\n";
}

echo "\nDone. packs_added={$insertedPacks} questions_added={$insertedQuestions}\n";
