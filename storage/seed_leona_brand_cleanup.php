<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/site_content.php';

$brand = 'กวดวิชาเดอะลีโอน่า';
$tagline = 'ติวสังคมศึกษา ประวัติศาสตร์ · สอบเข้า ม.1 ม.4 และ A-Level';

saveSetting('site_title', $brand);
saveSetting('site_tagline', $tagline);

$footer = getFooterContent();
$footer['copyright'] = $brand . '. All Rights Reserved.';
saveJsonSetting('content_footer_json', $footer);

$contact = getContactContent();
$contact['intro_eyebrow'] = 'THE LEONA TUTORS';
$contact['header_subtitle'] = 'ทีมงานกวดวิชาเดอะลีโอน่าพร้อมให้คำปรึกษาเรื่องคอร์ส การสมัครเรียน และการชำระเงิน';
$contact['facebook_label'] = $brand;
$contact['cta_text'] = 'โทรหาทีมงานกวดวิชาเดอะลีโอน่า เราพร้อมให้คำปรึกษาเรื่องคอร์สและการสมัครเรียน';
saveJsonSetting('content_contact_json', $contact);

$home = getHomepageContent();
$defaults = defaultHomepageContent();
$home['why'] = $defaults['why'];
$home['instructor'] = $defaults['instructor'];
$home['steps'] = $defaults['steps'];
$home['reviews'] = $defaults['reviews'];
saveJsonSetting('content_homepage_json', $home);

echo "Branding cleaned\n";
echo 'title=' . getSetting('site_title') . "\n";
echo 'tagline=' . getSetting('site_tagline') . "\n";
echo 'copyright=' . ($footer['copyright'] ?? '') . "\n";
echo 'eyebrow=' . ($contact['intro_eyebrow'] ?? '') . "\n";
