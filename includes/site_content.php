<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

function getJsonSetting(string $key, array $default): array
{
    $raw = trim(getSetting($key, ''));
    if ($raw === '') {
        return $default;
    }
    try {
        $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        return is_array($decoded) ? $decoded : $default;
    } catch (Throwable) {
        return $default;
    }
}

function saveJsonSetting(string $key, array $value): void
{
    saveSetting($key, json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

function defaultFaqItems(): array
{
    $main = [
        ['q' => 'ไม่เคยเรียนภาษาจีนมาก่อน ควรเริ่มคอร์สไหน?', 'a' => 'แนะนำเริ่มจากคอร์สพินอิน HSK 1 เพื่อปูพื้นฐานเสียงและคำศัพท์ จากนั้นค่อยต่อ HSK 2–3 ตามเป้าหมาย หรือทดลองเรียนบทฟรีก่อนตัดสินใจซื้อคอร์ส', 'scope' => 'main'],
        ['q' => 'สมัครสมาชิกต้องใช้อะไรบ้าง?', 'a' => 'ใช้เบอร์โทรศัพท์มือถือ ชื่อ-นามสกุล และตั้งรหัสผ่าน สมัครได้ที่หน้า「สมัครสมาชิก」ใช้เวลาไม่กี่นาที ไม่ต้องแนบเอกสาร', 'scope' => 'main'],
        ['q' => 'ชำระเงินแล้วกี่วันถึงจะเปิดสิทธิ์เรียน?', 'a' => 'การโอนเงินและแจ้งสลิป ทีมงานจะตรวจสอบและเปิดสิทธิ์ภายใน 24 ชั่วโมงทำการ หากชำระผ่านช่องทางออนไลน์ (Omise) ระบบเปิดสิทธิ์อัตโนมัติทันทีเมื่อชำระสำเร็จ', 'scope' => 'main'],
        ['q' => 'ชำระเงินด้วยวิธีใดบ้าง?', 'a' => 'รองรับโอนเงินผ่านธนาคาร สแกน PromptPay แนบสลิป และชำระออนไลน์ด้วยบัตร/PromptPay ผ่าน Omise (เปิดสิทธิ์ทันที)', 'scope' => 'main'],
        ['q' => 'ซื้อหลายคอร์สในครั้งเดียวได้ไหม?', 'a' => 'ได้ครับ เพิ่มคอร์สลงตะกร้าได้หลายคอร์ส แล้วชำระเงินครั้งเดียวในหน้า「ชำระเงิน」ทีมงานจะเปิดสิทธิ์ทุกคอร์สที่ชำระแล้ว', 'scope' => 'main'],
        ['q' => 'ใช้คูปองส่วนลดอย่างไร?', 'a' => 'กรอกรหัสคูปองในหน้าชำระเงินก่อนยืนยันการโอน ระบบจะหักส่วนลดจากยอดรวมอัตโนมัติ หากคูปองใช้ไม่ได้ ตรวจสอบวันหมดอายุหรือเงื่อนไขคอร์สที่ร่วมรายการ', 'scope' => 'main'],
        ['q' => 'เรียนบนมือถือหรือแท็บเล็ตได้ไหม?', 'a' => 'ได้ครับ เว็บไซต์รองรับมือถือและแท็บเล็ต ดูวิดีโอและเอกสารได้ทุกที่ทุกเวลา หลังเข้าสู่ระบบแล้วไปที่หน้า「เริ่มเรียน」', 'scope' => 'main'],
        ['q' => 'เรียนได้นานแค่ไหนหลังซื้อคอร์ส?', 'a' => 'หลังเปิดสิทธิ์แล้วเข้าเรียนได้ตลอดอายุการใช้งานบัญชี ดูวิดีโอย้อนหลังได้ไม่จำกัดจำนวนครั้งภายในคอร์สที่ซื้อแล้ว', 'scope' => 'main'],
        ['q' => 'ทดลองเรียนก่อนซื้อได้ไหม?', 'a' => 'ได้ครับ หลายคอร์สมีบท「ทดลองเรียนฟรี」ให้ดูวิดีโอได้โดยไม่ต้องซื้อ ดูรายการบททดลองได้ที่หน้ารายละเอียดคอร์ส', 'scope' => 'main'],
        ['q' => 'มีใบประกาศนียบัตรเมื่อจบคอร์สไหม?', 'a' => 'มีครับ เมื่อเรียนครบ 100% และผ่านเงื่อนไขแบบทดสอบ (ถ้าคอร์สมี Quiz) ระบบออกใบประกาศนียบัตรให้อัตโนมัติ ดาวน์โหลดและพิมพ์ได้จากหน้าโปรไฟล์', 'scope' => 'main'],
        ['q' => 'ทำแบบทดสอบท้ายคอร์สกี่ครั้งก็ได้ไหม?', 'a' => 'ทำซ้ำได้ตามที่คอร์สกำหนด ระบบบันทึกคะแนนล่าสุด หากคอร์สตั้งค่าให้ต้องผ่าน Quiz ก่อนรับใบประกาศ ต้องทำให้ผ่านเกณฑ์ที่กำหนด', 'scope' => 'main'],
        ['q' => 'ลืมรหัสผ่านทำอย่างไร?', 'a' => 'ไปที่หน้า「ลืมรหัสผ่าน」จากหน้าเข้าสู่ระบบ กรอกเบอร์โทรที่ลงทะเบียน ระบบจะส่งลิงก์รีเซ็ตรหัสผ่านทางอีเมล (ต้องตั้งค่า SMTP ในระบบ)', 'scope' => 'main'],
        ['q' => 'คืนเงินหรือยกเลิกคอร์สได้ไหม?', 'a' => 'เมื่อเปิดสิทธิ์เรียนและเข้าถึงเนื้อหาแล้ว โดยทั่วไปไม่สามารถคืนเงินได้ หากมีปัญหาการชำระเงินซ้ำหรือเปิดสิทธิ์ผิดพลาด ติดต่อทีมงานภายใน 7 วัน', 'scope' => 'main'],
        ['q' => 'ติดต่อทีมงานได้ช่องทางไหนบ้าง?', 'a' => 'ติดต่อได้ผ่าน Line, Facebook, โทรศัพท์ หรืออีเมลตามข้อมูลในหน้า「ติดต่อเรา」ท้ายเว็บไซต์ ทีมงานตอบกลับในวันและเวลาทำการ', 'scope' => 'main'],
    ];
    $extra = [
        ['q' => 'HSK คืออะไร ทำไมควรเรียนให้ครบระดับ?', 'a' => 'HSK (汉语水平考试) คือการสอบวัดระดับภาษาจีนที่ได้รับการยอมรับทั่วโลก การเรียนตามลำดับ HSK 1–5 ช่วยปูพื้นฐานไปสู่การใช้งานจริงและเตรียมสอบได้อย่างเป็นระบบ', 'scope' => 'homepage_extra'],
        ['q' => 'มีเอกสารประกอบการเรียนไหม?', 'a' => 'มีครับ หลายบทเรียนมีไฟล์เอกสารแนบให้ดาวน์โหล์ได้ในหน้าบทเรียน สามารถเก็บไว้ทบทวนหรือพิมพ์ออกมาเรียนได้ตามสะดวก', 'scope' => 'homepage_extra'],
        ['q' => 'ดูความคืบหน้าการเรียนได้ที่ไหน?', 'a' => 'หลังเข้าสู่ระบบไปที่หน้า「คอร์สของฉัน」หรือ「เริ่มเรียน」ระบบแสดงเปอร์เซ็นต์ความคืบหน้าแต่ละคอร์ส และบทที่เรียนแล้ว/ยังไม่เรียน', 'scope' => 'homepage_extra'],
        ['q' => 'แจ้งชำระเงินแล้วแต่ยังเข้าเรียนไม่ได้ทำอย่างไร?', 'a' => 'ตรวจสอบว่าสลิปถูกต้องและยอดเงินตรงกับคอร์สที่สั่งซื้อ จากนั้นติดต่อทีมงานผ่าน Line พร้อมเบอร์โทรที่สมัครและหลักฐานการโอน ทีมงานจะตรวจสอบและเปิดสิทธิ์ให้โดยเร็ว', 'scope' => 'homepage_extra'],
        ['q' => 'สมัครเรียนให้บุตรหลานหรือญาติได้ไหม?', 'a' => 'ได้ครับ ให้ผู้เรียนสมัครบัญชีด้วยเบอร์โทรของตนเอง หรือผู้ปกครองสมัครแล้วชำระเงินแทนได้ แต่ควรใช้บัญชีของผู้เรียนจริงเพื่อติดตามความคืบหน้าและรับใบประกาศ', 'scope' => 'homepage_extra'],
        ['q' => 'ต้องเรียนตามลำดับบทหรือข้ามบทได้?', 'a' => 'แนะนำเรียนตามลำดับบทเพื่อความต่อเนื่องของเนื้อหา โดยเฉพาะคอร์สพื้นฐาน หากมีพื้นฐานมาแล้วสามารถเลือกบทที่ต้องการทบทวนได้ภายในคอร์สที่ซื้อแล้ว', 'scope' => 'homepage_extra'],
    ];
    return array_merge($main, $extra);
}

function getStoredFaqItems(): array
{
    return getJsonSetting('content_faq_json', defaultFaqItems());
}

function getFaqItemsByScope(string $scope): array
{
    return array_values(array_filter(
        getStoredFaqItems(),
        static fn(array $item): bool => ($item['scope'] ?? 'main') === $scope && trim($item['q'] ?? '') !== ''
    ));
}

function defaultHomepageContent(): array
{
    return [
        'why' => [
            'eyebrow' => 'จุดเด่นของเรา',
            'title' => 'ทำไมต้องเรียนกับเดอะลีโอน่า',
            'subtitle' => 'ติวสังคมศึกษา ประวัติศาสตร์ เน้นเทคนิคทำข้อสอบและผลลัพธ์จริง',
            'cards' => [
                ['title' => 'ติวเตอร์คุณภาพ', 'text' => 'สอนโดยครูบอล ผู้เชี่ยวชาญสังคมศึกษาและประวัติศาสตร์'],
                ['title' => 'หลักสูตรตรงจุด', 'text' => 'ครบทั้งติวเข้า ม.1 ม.4 A-Level และปรับพื้นฐาน'],
                ['title' => 'สด + ออนไลน์', 'text' => 'เลือกได้ตามไลฟ์สไตล์ ทบทวนซ้ำได้หลังสมัคร'],
                ['title' => 'ดูแลใกล้ชิด', 'text' => 'ทีมงานพร้อมให้คำปรึกษาตลอดการเรียน'],
            ],
        ],
        'courses' => [
            'title' => 'คอร์สเรียนยอดนิยม',
            'subtitle' => 'เลือกคอร์สที่เหมาะกับระดับและเป้าหมายของคุณ',
        ],
        'instructor' => [
            'title' => 'พบกับครูบอล',
        ],
        'reviews' => [
            'title' => 'รีวิวจากผู้ปกครอง',
            'subtitle' => 'เสียงจากผู้ปกครองที่ไว้วางใจกวดวิชาเดอะลีโอน่า',
            'items' => [
                ['quote' => 'เนื้อหาเข้าใจง่าย มีเทคนิคทำข้อสอบชัดเจน ลูกเตรียมสอบเข้า ม.1 ได้มั่นใจขึ้นมาก', 'name' => 'คุณแม่มุก', 'course' => 'ติวเข้า ม.1', 'initial' => 'ม', 'hue' => 160],
                ['quote' => 'ครูบอลอธิบายละเอียด เป็นกันเอง ลูกชอบเรียนและทำข้อสอบได้ดีขึ้นชัดเจน', 'name' => 'คุณพ่อก้อง', 'course' => 'สังคมศึกษา', 'initial' => 'ก', 'hue' => 25],
                ['quote' => 'เลือกได้ทั้งสดและออนไลน์ สะดวกมาก ลูกเรียนซ้ำทบทวนได้ตามจังหวะตัวเอง', 'name' => 'คุณแม่นิด', 'course' => 'ประวัติศาสตร์', 'initial' => 'น', 'hue' => 200],
                ['quote' => 'คอร์สปรับพื้นฐานช่วยให้ลูกตามทันเพื่อน เริ่มจากไม่มั่นใจ ตอนนี้ทำข้อสอบได้แล้ว', 'name' => 'คุณแม่บี', 'course' => 'ปรับพื้นฐาน ม.ต้น', 'initial' => 'บ', 'hue' => 140],
                ['quote' => 'เอกสารครบ เทคนิคตรงจุด ลูกสอบเข้า ม.4 ได้ตามเป้า ขอบคุณทีมเดอะลีโอน่ามากค่ะ', 'name' => 'คุณแม่มิ้น', 'course' => 'ติวเข้า ม.4', 'initial' => 'ม', 'hue' => 310],
                ['quote' => 'ติว A-Level เข้มข้น โฟกัสข้อสอบจริง ลูกมั่นใจขึ้นเยอะก่อนวันสอบ', 'name' => 'คุณพ่อโอ', 'course' => 'A-Level', 'initial' => 'อ', 'hue' => 15],
                ['quote' => 'ติดต่อสอบถามง่าย ตอบไว จัดคอร์สให้เหมาะกับระดับของลูกได้ดี', 'name' => 'คุณแม่เจ', 'course' => 'หน้าที่พลเมือง', 'initial' => 'จ', 'hue' => 260],
                ['quote' => 'เคยเรียนที่อื่นแล้วตามไม่ทัน ที่นี่สอนเป็นขั้นตอน ไม่รีบ ลูกสนุกและได้เกรดดีขึ้น', 'name' => 'คุณแม่ฟ้า', 'course' => 'ภูมิศาสตร์', 'initial' => 'ฟ', 'hue' => 190],
                ['quote' => 'ราคาคุ้ม เนื้อหาแน่น ใช้โค้ด FIRST25 ได้ส่วนลดจริง แนะนำเพื่อนผู้ปกครองแล้ว', 'name' => 'คุณพ่อต้', 'course' => 'เศรษฐศาสตร์', 'initial' => 'ต', 'hue' => 45],
                ['quote' => 'ลูกเข้าถึงคอร์สได้ทั้งปี ทบทวนก่อนสอบสะดวก ไม่ต้องกังวลเรื่องเวลาเรียน', 'name' => 'คุณแม่ปิ่น', 'course' => 'กฎหมาย', 'initial' => 'ป', 'hue' => 330],
            ],
        ],
        'steps' => [
            'title' => 'ขั้นตอนการเรียน',
            'subtitle' => 'เริ่มต้นติวกับเดอะลีโอน่าได้ใน 4 ขั้นตอนง่ายๆ',
            'items' => [
                ['title' => 'สมัครสมาชิก', 'text' => 'ลงทะเบียนด้วยเบอร์โทรและเลือกคอร์สที่ต้องการ'],
                ['title' => 'ชำระเงิน', 'text' => 'โอนเงิน สแกน PromptPay หรือชำระออนไลน์'],
                ['title' => 'เรียนออนไลน์', 'text' => 'ดูวิดีโอและเอกสารได้ทุกที่ทุกเวลา'],
                ['title' => 'รับใบประกาศ', 'text' => 'เรียนครบและผ่าน Quiz รับใบประกาศนียบัตร'],
            ],
        ],
        'faq' => [
            'title' => 'คำถามที่พบบ่อย',
            'subtitle' => 'คำตอบสำหรับคำถามที่ผู้เรียนถามบ่อยที่สุด',
        ],
        'newsletter' => [
            'title' => 'รับข่าวสารและโปรโมชั่นพิเศษ',
            'subtitle' => 'ไม่พลาดทุกคอร์สเรียนและกิจกรรมดีๆ',
            'placeholder' => 'อีเมลของคุณ',
            'button' => 'สมัครรับข่าวสาร',
        ],
        'trust' => [
            ['label' => 'ผู้เรียนทั่วประเทศ', 'value' => '', 'mode' => 'students'],
            ['label' => 'คอร์สออนไลน์', 'value' => '', 'mode' => 'courses'],
            ['label' => 'บทเรียนวิดีโอ', 'value' => '', 'mode' => 'lessons'],
            ['label' => 'ครบทุกระดับ', 'value' => 'HSK 1–5', 'mode' => 'manual'],
        ],
    ];
}

function getHomepageContent(): array
{
    $stored = getJsonSetting('content_homepage_json', defaultHomepageContent());
    return array_replace_recursive(defaultHomepageContent(), $stored);
}

function getHomeReviews(): array
{
    $items = getHomepageContent()['reviews']['items'] ?? [];
    return is_array($items) ? $items : [];
}

function defaultContactContent(): array
{
    return [
        'header_title' => 'ติดต่อเรา',
        'header_subtitle' => 'ทีมงานกวดวิชาเดอะลีโอน่าพร้อมให้คำปรึกษาเรื่องคอร์ส การสมัครเรียน และการชำระเงิน',
        'intro_eyebrow' => 'THE LEONA TUTORS',
        'intro_title' => 'มีคำถาม? เราช่วยได้',
        'intro_lead_suffix' => 'เลือกช่องทางที่สะดวกที่สุด ทีมงานจะตอบกลับโดยเร็วที่สุด',
        'perks' => [
            'ปรึกษาคอร์สติวสอบและการเรียนได้ฟรี',
            'ตอบกลับภายใน 24 ชั่วโมง (วันทำการ)',
            'ข้อมูลของคุณปลอดภัย ไม่เปิดเผยต่อบุคคลที่สาม',
        ],
        'bottom_title' => 'พร้อมเริ่มเรียนแล้ว?',
        'bottom_text' => 'เลือกคอร์สที่เหมาะกับระดับของคุณ แล้วสมัครเรียนได้ทันที',
        'phone_hours' => 'จันทร์–อาทิตย์ 10:00–20:00 น.',
        'facebook_label' => 'กวดวิชาเดอะลีโอน่า',
    ];
}

function getContactContent(): array
{
    $stored = getJsonSetting('content_contact_json', defaultContactContent());
    return array_replace_recursive(defaultContactContent(), $stored);
}

function publicContactEmail(): string
{
    $email = trim(getSetting('contact_email', ''));
    if ($email !== '') {
        return $email;
    }
    return trim(getSetting('email_admin', ''));
}

function defaultFooterContent(): array
{
    return [
        'copyright' => 'กวดวิชาเดอะลีโอน่า. All Rights Reserved.',
        'col_courses' => 'คอร์สเรียน',
        'col_about' => 'เกี่ยวกับเรา',
        'col_help' => 'ช่วยเหลือ',
        'col_contact' => 'ติดต่อเรา',
        'about_links' => [
            ['label' => 'เกี่ยวกับเรา', 'url' => '/public/index.php'],
            ['label' => 'อาจารย์ผู้สอน', 'url' => '/public/instructor.php'],
            ['label' => 'รีวิวจากผู้เรียน', 'url' => '/public/index.php#reviews'],
            ['label' => 'บอร์ดประชาสัมพันธ์', 'url' => '/public/announcements.php'],
        ],
        'help_links' => [
            ['label' => 'คำถามที่พบบ่อย', 'url' => '/public/faq.php'],
            ['label' => 'วิธีการสมัครเรียน', 'url' => '/public/register.php'],
            ['label' => 'แจ้งชำระเงิน', 'url' => '__checkout__'],
            ['label' => 'ติดต่อเรา', 'url' => '/public/contact.php'],
        ],
    ];
}

function getFooterContent(): array
{
    $stored = getJsonSetting('content_footer_json', defaultFooterContent());
    return array_replace_recursive(defaultFooterContent(), $stored);
}

function defaultFaqPageContent(): array
{
    return [
        'page_title' => 'คำถามที่พบบ่อย',
        'page_subtitle' => 'รวบรวมคำตอบสำหรับคำถามที่ผู้เรียนถามบ่อยที่สุด',
        'cta_title' => 'ไม่พบคำตอบที่ต้องการ?',
        'cta_text' => 'โทรหาทีมงานกวดวิชาเดอะลีโอน่า เราพร้อมให้คำปรึกษาเรื่องคอร์สและการสมัครเรียน',
        'promo_eyebrow' => 'ยังมีคำถาม?',
        'promo_title' => 'ทีมงานพร้อมช่วยเหลือ',
        'promo_text' => 'สอบถามเรื่องคอร์ส การชำระเงิน หรือการใช้งานระบบได้ทันที',
    ];
}

function getFaqPageContent(): array
{
    $stored = getJsonSetting('content_faq_page_json', defaultFaqPageContent());
    return array_replace_recursive(defaultFaqPageContent(), $stored);
}

function parseFaqItemsFromPost(array $questions, array $answers, array $scopes): array
{
    $items = [];
    foreach ($questions as $i => $q) {
        $q = trim((string) $q);
        $a = trim((string) ($answers[$i] ?? ''));
        if ($q === '' && $a === '') {
            continue;
        }
        $scope = (string) ($scopes[$i] ?? 'main');
        if (!in_array($scope, ['main', 'homepage_extra'], true)) {
            $scope = 'main';
        }
        $items[] = ['q' => $q, 'a' => $a, 'scope' => $scope];
    }
    return $items;
}

function parseReviewItemsFromPost(array $quotes, array $names, array $courses, array $initials, array $hues): array
{
    $items = [];
    foreach ($quotes as $i => $quote) {
        $quote = trim((string) $quote);
        if ($quote === '') {
            continue;
        }
        $name = trim((string) ($names[$i] ?? ''));
        $items[] = [
            'quote' => $quote,
            'name' => $name,
            'course' => trim((string) ($courses[$i] ?? '')),
            'initial' => trim((string) ($initials[$i] ?? mb_substr($name, 0, 1))),
            'hue' => (int) ($hues[$i] ?? 0),
        ];
    }
    return $items;
}

function faqScopeLabel(string $scope): string
{
    return $scope === 'homepage_extra' ? 'หน้าแรก' : 'หน้า FAQ';
}

function getContentSectionRows(): array
{
    $home = getHomepageContent();
    $contact = getContactContent();
    $footer = getFooterContent();
    $faqPage = getFaqPageContent();
    $faqItems = getStoredFaqItems();
    $faqMainCount = count(getFaqItemsByScope('main'));
    $faqHomeCount = count(getFaqItemsByScope('homepage_extra'));

    return [
        [
            'tab' => 'home',
            'title' => $home['why']['title'] ?? 'หน้าแรก',
            'summary' => $home['why']['subtitle'] ?? '',
            'category' => 'หน้าแรก',
            'meta' => count($home['reviews']['items'] ?? []) . ' รีวิว · ' . count($home['steps']['items'] ?? []) . ' ขั้นตอน',
            'status' => 'เผยแพร่',
            'view_url' => APP_URL . '/public/index.php',
            'icon' => 'home',
        ],
        [
            'tab' => 'contact',
            'title' => $contact['header_title'] ?? 'ติดต่อเรา',
            'summary' => $contact['header_subtitle'] ?? '',
            'category' => 'ติดต่อ',
            'meta' => trim(getSetting('line_id', '')) !== '' ? 'มี Line' : 'ยังไม่ตั้ง Line',
            'status' => 'เผยแพร่',
            'view_url' => APP_URL . '/public/contact.php',
            'icon' => 'contact',
        ],
        [
            'tab' => 'footer',
            'title' => 'Footer — ' . ($footer['col_contact'] ?? 'ติดต่อเรา'),
            'summary' => getSetting('site_tagline', ''),
            'category' => 'ท้ายเว็บ',
            'meta' => (count($footer['about_links'] ?? []) + count($footer['help_links'] ?? [])) . ' ลิงก์',
            'status' => 'เผยแพร่',
            'view_url' => APP_URL . '/public/index.php#contact',
            'icon' => 'footer',
        ],
        [
            'tab' => 'faq',
            'title' => $faqPage['page_title'] ?? 'คำถามที่พบบ่อย',
            'summary' => $faqPage['page_subtitle'] ?? '',
            'category' => 'FAQ',
            'meta' => $faqMainCount . ' ข้อ FAQ · ' . $faqHomeCount . ' ข้อหน้าแรก',
            'status' => 'เผยแพร่',
            'view_url' => APP_URL . '/public/faq.php',
            'icon' => 'faq',
        ],
    ];
}

function contentBlockLucideIcon(string $icon, int $size = 18): string
{
    $map = [
        'sparkles' => 'sparkles',
        'chart-column' => 'chart-column',
        'graduation-cap' => 'graduation-cap',
        'book-open' => 'book-open',
        'users' => 'users',
        'star' => 'star',
        'list-ordered' => 'list-ordered',
        'circle-help' => 'circle-help',
        'mail' => 'mail',
        'phone' => 'phone',
        'map-pinned' => 'map-pinned',
        'link' => 'link',
        'life-buoy' => 'life-buoy',
        'panel-bottom' => 'panel-bottom',
        'file-text' => 'file-text',
        'messages-square' => 'messages-square',
    ];

    return lucide_icon($map[$icon] ?? 'file-text', ['size' => $size, 'stroke' => '1.75']);
}

function getContentBlockRows(string $tab): array
{
    $home = getHomepageContent();
    $contact = getContactContent();
    $footer = getFooterContent();
    $faqPage = getFaqPageContent();
    $faqItems = getStoredFaqItems();
    $tagline = trim(getSetting('site_tagline', ''));

    return match ($tab) {
        'home' => [
            [
                'block' => 'general',
                'title' => 'ข้อมูลทั่วไป & Hero',
                'summary' => $tagline,
                'category' => 'หน้าแรก',
                'meta' => 'สโลแกน · Hero',
                'status' => 'เผยแพร่',
                'icon' => 'sparkles',
                'view_url' => APP_URL . '/public/index.php',
            ],
            [
                'block' => 'trust',
                'title' => 'แถบสถิติ',
                'summary' => $home['trust'][0]['label'] ?? '',
                'category' => 'หน้าแรก',
                'meta' => count($home['trust'] ?? []) . ' รายการ',
                'status' => 'เผยแพร่',
                'icon' => 'chart-column',
                'view_url' => APP_URL . '/public/index.php',
            ],
            [
                'block' => 'why',
                'title' => $home['why']['title'] ?? 'ทำไมต้องเรียนกับ Wenxin',
                'summary' => $home['why']['subtitle'] ?? '',
                'category' => 'หน้าแรก',
                'meta' => count($home['why']['cards'] ?? []) . ' การ์ด',
                'status' => 'เผยแพร่',
                'icon' => 'graduation-cap',
                'view_url' => APP_URL . '/public/index.php',
            ],
            [
                'block' => 'courses',
                'title' => $home['courses']['title'] ?? 'คอร์สยอดนิยม',
                'summary' => $home['courses']['subtitle'] ?? '',
                'category' => 'หน้าแรก',
                'meta' => 'หัวข้อส่วนคอร์ส',
                'status' => 'เผยแพร่',
                'icon' => 'book-open',
                'view_url' => APP_URL . '/public/index.php#courses',
            ],
            [
                'block' => 'instructor',
                'title' => $home['instructor']['title'] ?? 'ผู้สอน',
                'summary' => getSetting('instructor_name', ''),
                'category' => 'หน้าแรก',
                'meta' => 'หัวข้อส่วนผู้สอน',
                'status' => 'เผยแพร่',
                'icon' => 'users',
                'view_url' => APP_URL . '/public/index.php#instructor',
            ],
            [
                'block' => 'reviews',
                'title' => $home['reviews']['title'] ?? 'รีวิวผู้เรียน',
                'summary' => $home['reviews']['subtitle'] ?? '',
                'category' => 'หน้าแรก',
                'meta' => count($home['reviews']['items'] ?? []) . ' รีวิว',
                'status' => 'เผยแพร่',
                'icon' => 'star',
                'view_url' => APP_URL . '/public/index.php',
            ],
            [
                'block' => 'steps',
                'title' => $home['steps']['title'] ?? 'ขั้นตอนการเรียน',
                'summary' => $home['steps']['subtitle'] ?? '',
                'category' => 'หน้าแรก',
                'meta' => count($home['steps']['items'] ?? []) . ' ขั้นตอน',
                'status' => 'เผยแพร่',
                'icon' => 'list-ordered',
                'view_url' => APP_URL . '/public/index.php',
            ],
            [
                'block' => 'faq',
                'title' => $home['faq']['title'] ?? 'FAQ บนหน้าแรก',
                'summary' => $home['faq']['subtitle'] ?? '',
                'category' => 'หน้าแรก',
                'meta' => 'หัวข้อส่วน FAQ',
                'status' => 'เผยแพร่',
                'icon' => 'circle-help',
                'view_url' => APP_URL . '/public/index.php#faq',
            ],
            [
                'block' => 'newsletter',
                'title' => $home['newsletter']['title'] ?? 'รับข่าวสาร',
                'summary' => $home['newsletter']['subtitle'] ?? '',
                'category' => 'หน้าแรก',
                'meta' => 'ฟอร์มสมัครรับข่าว',
                'status' => 'เผยแพร่',
                'icon' => 'mail',
                'view_url' => APP_URL . '/public/index.php',
            ],
        ],
        'contact' => [
            [
                'block' => 'channels',
                'title' => 'ช่องทางติดต่อ',
                'summary' => trim(getSetting('line_id', '')) !== '' ? 'Line: ' . getSetting('line_id') : 'ยังไม่ตั้ง Line',
                'category' => 'ติดต่อ',
                'meta' => 'Line · โทร · โซเชียล',
                'status' => 'เผยแพร่',
                'icon' => 'phone',
                'view_url' => APP_URL . '/public/contact.php',
            ],
            [
                'block' => 'page',
                'title' => $contact['header_title'] ?? 'ข้อความหน้าติดต่อ',
                'summary' => $contact['header_subtitle'] ?? '',
                'category' => 'ติดต่อ',
                'meta' => count($contact['perks'] ?? []) . ' จุดเด่น',
                'status' => 'เผยแพร่',
                'icon' => 'map-pinned',
                'view_url' => APP_URL . '/public/contact.php',
            ],
        ],
        'footer' => [
            [
                'block' => 'general',
                'title' => 'ข้อมูลทั่วไป',
                'summary' => $footer['copyright'] ?? '',
                'category' => 'ท้ายเว็บ',
                'meta' => 'สโลแกน · ลิขสิทธิ์ · หัวคอลัมน์',
                'status' => 'เผยแพร่',
                'icon' => 'panel-bottom',
                'view_url' => APP_URL . '/public/index.php#contact',
            ],
            [
                'block' => 'about_links',
                'title' => 'ลิงก์เกี่ยวกับเรา',
                'summary' => $footer['col_about'] ?? '',
                'category' => 'ท้ายเว็บ',
                'meta' => count($footer['about_links'] ?? []) . ' ลิงก์',
                'status' => 'เผยแพร่',
                'icon' => 'link',
                'view_url' => APP_URL . '/public/index.php#contact',
            ],
            [
                'block' => 'help_links',
                'title' => 'ลิงก์ช่วยเหลือ',
                'summary' => $footer['col_help'] ?? '',
                'category' => 'ท้ายเว็บ',
                'meta' => count($footer['help_links'] ?? []) . ' ลิงก์',
                'status' => 'เผยแพร่',
                'icon' => 'life-buoy',
                'view_url' => APP_URL . '/public/index.php#contact',
            ],
        ],
        'faq' => [
            [
                'block' => 'page',
                'title' => $faqPage['page_title'] ?? 'ตั้งค่าหน้า FAQ',
                'summary' => $faqPage['page_subtitle'] ?? '',
                'category' => 'FAQ',
                'meta' => 'หัวข้อ · CTA',
                'status' => 'เผยแพร่',
                'icon' => 'file-text',
                'view_url' => APP_URL . '/public/faq.php',
            ],
            [
                'block' => 'items',
                'title' => 'รายการคำถาม',
                'summary' => 'จัดการคำถาม-คำตอบทั้งหมด',
                'category' => 'FAQ',
                'meta' => count($faqItems) . ' ข้อ',
                'status' => 'เผยแพร่',
                'icon' => 'messages-square',
                'view_url' => APP_URL . '/public/faq.php',
            ],
        ],
        default => [],
    };
}

function getFaqItemAdminRows(): array
{
    $rows = [];
    foreach (getStoredFaqItems() as $index => $item) {
        $rows[] = [
            'index' => $index,
            'title' => $item['q'] ?? '',
            'category' => faqScopeLabel($item['scope'] ?? 'main'),
            'meta' => mb_strimwidth(strip_tags($item['a'] ?? ''), 0, 60, '...'),
            'status' => 'เผยแพร่',
            'icon' => 'circle-help',
            'view_url' => APP_URL . '/public/faq.php',
        ];
    }

    return $rows;
}

function contentBlockTitle(string $tab, string $block): string
{
    foreach (getContentBlockRows($tab) as $row) {
        if (($row['block'] ?? '') === $block) {
            return (string) ($row['title'] ?? $block);
        }
    }
    if ($tab === 'faq' && $block === 'item') {
        return 'แก้ไขคำถาม';
    }

    return $block;
}

function contentBlockAllowed(string $tab, string $block): bool
{
    if ($block === '') {
        return false;
    }
    if ($tab === 'faq' && in_array($block, ['item', 'items'], true)) {
        return true;
    }

    foreach (getContentBlockRows($tab) as $row) {
        if (($row['block'] ?? '') === $block) {
            return true;
        }
    }

    return false;
}
