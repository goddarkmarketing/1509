<?php

declare(strict_types=1);

/** @var string $tab */
/** @var string $block */
/** @var int $itemIndex */
/** @var array $settings */
/** @var array $home */
/** @var array $contact */
/** @var array $footer */
/** @var array $faqItems */
/** @var array $faqPage */

$formOpen = static function (string $section, string $blockName) use ($tab): void {
    echo '<form method="post" class="modal-form content-editor-form">';
    echo csrfField();
    echo '<input type="hidden" name="section" value="' . e($section) . '">';
    echo '<input type="hidden" name="block" value="' . e($blockName) . '">';
};

$formClose = static function (): void {
    echo '<div class="admin-form-actions"><button type="submit" class="btn btn-primary">บันทึก</button></div>';
    echo '</form>';
};

if ($tab === 'home' && $block === 'general'):
    $formOpen('home', 'general');
    ?>
    <h3>ข้อมูลทั่วไป & Hero</h3>
    <div class="form-group">
        <label>สโลแกนเว็บไซต์</label>
        <input type="text" name="site_tagline" class="form-control" value="<?= e($settings['site_tagline'] ?? '') ?>">
    </div>
    <div class="form-row">
        <div class="form-group">
            <label>หัวข้อ Hero (สำรอง)</label>
            <input type="text" name="hero_title" class="form-control" value="<?= e($settings['hero_title'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label>คำอธิบาย Hero (สำรอง)</label>
            <input type="text" name="hero_subtitle" class="form-control" value="<?= e($settings['hero_subtitle'] ?? '') ?>">
        </div>
    </div>
    <?php
    $formClose();

elseif ($tab === 'home' && $block === 'trust'):
    $formOpen('home', 'trust');
    ?>
    <h3>แถบสถิติ</h3>
    <div class="content-repeat-list">
        <?php foreach (($home['trust'] ?? []) as $i => $trust): ?>
        <div class="content-repeat-item">
            <div class="form-row">
                <div class="form-group">
                    <label>ข้อความ</label>
                    <input type="text" name="trust_label[]" class="form-control" value="<?= e($trust['label'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>ตัวเลข/ข้อความแสดง</label>
                    <input type="text" name="trust_value[]" class="form-control" value="<?= e($trust['value'] ?? '') ?>" placeholder="ว่าง = ดึงจากระบบอัตโนมัติ">
                </div>
                <div class="form-group">
                    <label>โหมด</label>
                    <select name="trust_mode[]" class="form-control">
                        <option value="students" <?= ($trust['mode'] ?? '') === 'students' ? 'selected' : '' ?>>จำนวนผู้เรียน</option>
                        <option value="courses" <?= ($trust['mode'] ?? '') === 'courses' ? 'selected' : '' ?>>จำนวนคอร์ส</option>
                        <option value="lessons" <?= ($trust['mode'] ?? '') === 'lessons' ? 'selected' : '' ?>>จำนวนบทเรียน</option>
                        <option value="manual" <?= ($trust['mode'] ?? 'manual') === 'manual' ? 'selected' : '' ?>>กำหนดเอง</option>
                    </select>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php
    $formClose();

elseif ($tab === 'home' && $block === 'why'):
    $formOpen('home', 'why');
    ?>
    <h3>ทำไมต้องเรียนกับเดอะลีโอน่า</h3>
    <div class="form-row">
        <div class="form-group"><label>ป้ายบน</label><input type="text" name="why_eyebrow" class="form-control" value="<?= e($home['why']['eyebrow'] ?? '') ?>"></div>
        <div class="form-group"><label>หัวข้อ</label><input type="text" name="why_title" class="form-control" value="<?= e($home['why']['title'] ?? '') ?>"></div>
    </div>
    <div class="form-group"><label>คำอธิบาย</label><textarea name="why_subtitle" class="form-control" rows="2"><?= e($home['why']['subtitle'] ?? '') ?></textarea></div>
    <?php foreach (($home['why']['cards'] ?? []) as $i => $card): ?>
    <div class="admin-subform-panel">
        <h4>การ์ด <?= $i + 1 ?></h4>
        <div class="form-group"><label>หัวข้อ</label><input type="text" name="why_card_title[]" class="form-control" value="<?= e($card['title'] ?? '') ?>"></div>
        <div class="form-group"><label>รายละเอียด</label><textarea name="why_card_text[]" class="form-control" rows="2"><?= e($card['text'] ?? '') ?></textarea></div>
    </div>
    <?php endforeach;
    $formClose();

elseif ($tab === 'home' && $block === 'courses'):
    $formOpen('home', 'courses');
    ?>
    <h3>คอร์สยอดนิยม</h3>
    <div class="form-row">
        <div class="form-group"><label>หัวข้อ</label><input type="text" name="courses_title" class="form-control" value="<?= e($home['courses']['title'] ?? '') ?>"></div>
        <div class="form-group"><label>คำอธิบาย</label><input type="text" name="courses_subtitle" class="form-control" value="<?= e($home['courses']['subtitle'] ?? '') ?>"></div>
    </div>
    <?php
    $formClose();

elseif ($tab === 'home' && $block === 'instructor'):
    $formOpen('home', 'instructor');
    ?>
    <h3>ผู้สอน</h3>
    <div class="form-group"><label>หัวข้อส่วนผู้สอน</label><input type="text" name="instructor_title" class="form-control" value="<?= e($home['instructor']['title'] ?? '') ?>"></div>
    <p class="content-hint">รายละเอียดผู้สอนแก้ได้ที่ <a href="<?= APP_URL ?>/admin/settings.php">ตั้งค่าเว็บไซต์ → โปรไฟล์ผู้สอน</a></p>
    <?php
    $formClose();

elseif ($tab === 'home' && $block === 'reviews'):
    $formOpen('home', 'reviews');
    ?>
    <h3>รีวิวผู้เรียน</h3>
    <div class="form-row">
        <div class="form-group"><label>หัวข้อ</label><input type="text" name="reviews_title" class="form-control" value="<?= e($home['reviews']['title'] ?? '') ?>"></div>
        <div class="form-group"><label>คำอธิบาย</label><input type="text" name="reviews_subtitle" class="form-control" value="<?= e($home['reviews']['subtitle'] ?? '') ?>"></div>
    </div>
    <div class="content-repeat-list" id="reviewList">
        <?php foreach (($home['reviews']['items'] ?? []) as $review): ?>
        <div class="content-repeat-item admin-subform-panel">
            <div class="form-group"><label>รีวิว</label><textarea name="review_quote[]" class="form-control" rows="2"><?= e($review['quote'] ?? '') ?></textarea></div>
            <div class="form-row form-row-3">
                <div class="form-group"><label>ชื่อ</label><input type="text" name="review_name[]" class="form-control" value="<?= e($review['name'] ?? '') ?>"></div>
                <div class="form-group"><label>คอร์ส</label><input type="text" name="review_course[]" class="form-control" value="<?= e($review['course'] ?? '') ?>"></div>
                <div class="form-group"><label>ตัวย่อ</label><input type="text" name="review_initial[]" class="form-control" value="<?= e($review['initial'] ?? '') ?>" maxlength="2"></div>
            </div>
            <input type="hidden" name="review_hue[]" value="<?= (int) ($review['hue'] ?? 0) ?>">
        </div>
        <?php endforeach; ?>
    </div>
    <button type="button" class="btn btn-outline btn-sm" id="addReviewRow">+ เพิ่มรีวิว</button>
    <?php
    $formClose();

elseif ($tab === 'home' && $block === 'steps'):
    $formOpen('home', 'steps');
    ?>
    <h3>ขั้นตอนการเรียน</h3>
    <div class="form-row">
        <div class="form-group"><label>หัวข้อ</label><input type="text" name="steps_title" class="form-control" value="<?= e($home['steps']['title'] ?? '') ?>"></div>
        <div class="form-group"><label>คำอธิบาย</label><input type="text" name="steps_subtitle" class="form-control" value="<?= e($home['steps']['subtitle'] ?? '') ?>"></div>
    </div>
    <?php foreach (($home['steps']['items'] ?? []) as $i => $step): ?>
    <div class="admin-subform-panel">
        <h4>ขั้นตอน <?= $i + 1 ?></h4>
        <div class="form-group"><label>หัวข้อ</label><input type="text" name="step_title[]" class="form-control" value="<?= e($step['title'] ?? '') ?>"></div>
        <div class="form-group"><label>รายละเอียด</label><input type="text" name="step_text[]" class="form-control" value="<?= e($step['text'] ?? '') ?>"></div>
    </div>
    <?php endforeach;
    $formClose();

elseif ($tab === 'home' && $block === 'faq'):
    $formOpen('home', 'faq');
    ?>
    <h3>FAQ บนหน้าแรก</h3>
    <div class="form-row">
        <div class="form-group"><label>หัวข้อ</label><input type="text" name="faq_title" class="form-control" value="<?= e($home['faq']['title'] ?? '') ?>"></div>
        <div class="form-group"><label>คำอธิบาย</label><input type="text" name="faq_subtitle" class="form-control" value="<?= e($home['faq']['subtitle'] ?? '') ?>"></div>
    </div>
    <?php
    $formClose();

elseif ($tab === 'home' && $block === 'newsletter'):
    $formOpen('home', 'newsletter');
    ?>
    <h3>รับข่าวสาร</h3>
    <div class="form-row">
        <div class="form-group"><label>หัวข้อ</label><input type="text" name="newsletter_title" class="form-control" value="<?= e($home['newsletter']['title'] ?? '') ?>"></div>
        <div class="form-group"><label>คำอธิบาย</label><input type="text" name="newsletter_subtitle" class="form-control" value="<?= e($home['newsletter']['subtitle'] ?? '') ?>"></div>
    </div>
    <div class="form-row">
        <div class="form-group"><label>Placeholder อีเมล</label><input type="text" name="newsletter_placeholder" class="form-control" value="<?= e($home['newsletter']['placeholder'] ?? '') ?>"></div>
        <div class="form-group"><label>ปุ่ม</label><input type="text" name="newsletter_button" class="form-control" value="<?= e($home['newsletter']['button'] ?? '') ?>"></div>
    </div>
    <?php
    $formClose();

elseif ($tab === 'contact' && $block === 'channels'):
    $formOpen('contact', 'channels');
    ?>
    <h3>ช่องทางติดต่อ</h3>
    <div class="form-row">
        <div class="form-group"><label>Line ID / URL</label><input type="text" name="line_id" class="form-control" value="<?= e($settings['line_id'] ?? '') ?>"></div>
        <div class="form-group"><label>เบอร์โทร</label><input type="text" name="phone" class="form-control" value="<?= e($settings['phone'] ?? '') ?>"></div>
    </div>
    <div class="form-group"><label>อีเมลแสดงบนเว็บ</label><input type="email" name="contact_email" class="form-control" value="<?= e($settings['contact_email'] ?? $settings['email_admin'] ?? '') ?>"></div>
    <div class="form-row">
        <div class="form-group"><label>Facebook URL</label><input type="url" name="facebook_url" class="form-control" value="<?= e($settings['facebook_url'] ?? '') ?>"></div>
        <div class="form-group"><label>YouTube URL</label><input type="url" name="youtube_url" class="form-control" value="<?= e($settings['youtube_url'] ?? '') ?>"></div>
    </div>
    <div class="form-group"><label>TikTok URL</label><input type="url" name="tiktok_url" class="form-control" value="<?= e($settings['tiktok_url'] ?? '') ?>"></div>
    <div class="form-group"><label>สโลแกน</label><input type="text" name="site_tagline" class="form-control" value="<?= e($settings['site_tagline'] ?? '') ?>"></div>
    <?php
    $formClose();

elseif ($tab === 'contact' && $block === 'page'):
    $formOpen('contact', 'page');
    ?>
    <h3>ข้อความหน้าติดต่อเรา</h3>
    <div class="form-group"><label>หัวข้อหน้า</label><input type="text" name="header_title" class="form-control" value="<?= e($contact['header_title'] ?? '') ?>"></div>
    <div class="form-group"><label>คำอธิบายใต้หัวข้อ</label><textarea name="header_subtitle" class="form-control" rows="2"><?= e($contact['header_subtitle'] ?? '') ?></textarea></div>
    <div class="form-row">
        <div class="form-group"><label>ป้ายบนการ์ด</label><input type="text" name="intro_eyebrow" class="form-control" value="<?= e($contact['intro_eyebrow'] ?? '') ?>"></div>
        <div class="form-group"><label>หัวข้อการ์ด</label><input type="text" name="intro_title" class="form-control" value="<?= e($contact['intro_title'] ?? '') ?>"></div>
    </div>
    <div class="form-group"><label>ข้อความต่อท้ายสโลแกน</label><input type="text" name="intro_lead_suffix" class="form-control" value="<?= e($contact['intro_lead_suffix'] ?? '') ?>"></div>
    <?php foreach (($contact['perks'] ?? []) as $i => $perk): ?>
    <div class="form-group"><label>จุดเด่น <?= $i + 1 ?></label><input type="text" name="perks[]" class="form-control" value="<?= e($perk) ?>"></div>
    <?php endforeach; ?>
    <div class="form-group"><label>เวลาให้บริการโทร</label><input type="text" name="phone_hours" class="form-control" value="<?= e($contact['phone_hours'] ?? '') ?>"></div>
    <div class="form-group"><label>ชื่อแสดง Facebook</label><input type="text" name="facebook_label" class="form-control" value="<?= e($contact['facebook_label'] ?? '') ?>"></div>
    <div class="form-row">
        <div class="form-group"><label>หัวข้อส่วนล่าง</label><input type="text" name="bottom_title" class="form-control" value="<?= e($contact['bottom_title'] ?? '') ?>"></div>
        <div class="form-group"><label>ข้อความส่วนล่าง</label><input type="text" name="bottom_text" class="form-control" value="<?= e($contact['bottom_text'] ?? '') ?>"></div>
    </div>
    <?php
    $formClose();

elseif ($tab === 'footer' && $block === 'general'):
    $formOpen('footer', 'general');
    ?>
    <div class="form-group"><label>สโลแกนใต้โลโก้</label><input type="text" name="site_tagline" class="form-control" value="<?= e($settings['site_tagline'] ?? '') ?>"></div>
    <div class="form-group"><label>ข้อความลิขสิทธิ์</label><input type="text" name="copyright" class="form-control" value="<?= e($footer['copyright'] ?? '') ?>"></div>
    <div class="form-row form-row-4">
        <div class="form-group"><label>หัวคอลัมน์คอร์ส</label><input type="text" name="col_courses" class="form-control" value="<?= e($footer['col_courses'] ?? '') ?>"></div>
        <div class="form-group"><label>หัวคอลัมน์เกี่ยวกับเรา</label><input type="text" name="col_about" class="form-control" value="<?= e($footer['col_about'] ?? '') ?>"></div>
        <div class="form-group"><label>หัวคอลัมน์ช่วยเหลือ</label><input type="text" name="col_help" class="form-control" value="<?= e($footer['col_help'] ?? '') ?>"></div>
        <div class="form-group"><label>หัวคอลัมน์ติดต่อ</label><input type="text" name="col_contact" class="form-control" value="<?= e($footer['col_contact'] ?? '') ?>"></div>
    </div>
    <p class="content-hint">ลิงก์คอร์ส HSK ดึงจากคอร์สในระบบอัตโนมัติ · ช่องทางติดต่อแก้ที่ <a href="?action=edit&amp;tab=contact&amp;block=channels">ติดต่อเรา → ช่องทางติดต่อ</a></p>
    <?php
    $formClose();

elseif ($tab === 'footer' && $block === 'about_links'):
    $formOpen('footer', 'about_links');
    ?>
    <h3>ลิงก์เกี่ยวกับเรา</h3>
    <?php foreach (($footer['about_links'] ?? []) as $link): ?>
    <div class="form-row content-link-row">
        <div class="form-group"><label>ข้อความ</label><input type="text" name="about_label[]" class="form-control" value="<?= e($link['label'] ?? '') ?>"></div>
        <div class="form-group"><label>URL (เช่น /public/instructor.php)</label><input type="text" name="about_url[]" class="form-control" value="<?= e($link['url'] ?? '') ?>"></div>
    </div>
    <?php endforeach;
    $formClose();

elseif ($tab === 'footer' && $block === 'help_links'):
    $formOpen('footer', 'help_links');
    ?>
    <h3>ลิงก์ช่วยเหลือ</h3>
    <p class="content-hint">ใช้ <code>__checkout__</code> สำหรับลิงก์แจ้งชำระเงิน</p>
    <?php foreach (($footer['help_links'] ?? []) as $link): ?>
    <div class="form-row content-link-row">
        <div class="form-group"><label>ข้อความ</label><input type="text" name="help_label[]" class="form-control" value="<?= e($link['label'] ?? '') ?>"></div>
        <div class="form-group"><label>URL</label><input type="text" name="help_url[]" class="form-control" value="<?= e($link['url'] ?? '') ?>"></div>
    </div>
    <?php endforeach;
    $formClose();

elseif ($tab === 'faq' && $block === 'page'):
    $formOpen('faq', 'page');
    ?>
    <h3>ตั้งค่าหน้า FAQ</h3>
    <div class="form-group"><label>หัวข้อหน้า</label><input type="text" name="page_title" class="form-control" value="<?= e($faqPage['page_title'] ?? '') ?>"></div>
    <div class="form-group"><label>คำอธิบาย</label><textarea name="page_subtitle" class="form-control" rows="2"><?= e($faqPage['page_subtitle'] ?? '') ?></textarea></div>
    <div class="form-group"><label>หัวข้อ CTA ล่าง</label><input type="text" name="cta_title" class="form-control" value="<?= e($faqPage['cta_title'] ?? '') ?>"></div>
    <div class="form-group"><label>ข้อความ CTA</label><textarea name="cta_text" class="form-control" rows="2"><?= e($faqPage['cta_text'] ?? '') ?></textarea></div>
    <div class="form-row">
        <div class="form-group"><label>ป้ายการ์ดด้านข้าง</label><input type="text" name="promo_eyebrow" class="form-control" value="<?= e($faqPage['promo_eyebrow'] ?? '') ?>"></div>
        <div class="form-group"><label>หัวข้อการ์ดด้านข้าง</label><input type="text" name="promo_title" class="form-control" value="<?= e($faqPage['promo_title'] ?? '') ?>"></div>
    </div>
    <div class="form-group"><label>ข้อความการ์ดด้านข้าง</label><textarea name="promo_text" class="form-control" rows="2"><?= e($faqPage['promo_text'] ?? '') ?></textarea></div>
    <?php
    $formClose();

elseif ($tab === 'faq' && $block === 'item'):
    $faqItem = $itemIndex >= 0 && isset($faqItems[$itemIndex]) ? $faqItems[$itemIndex] : ['q' => '', 'a' => '', 'scope' => 'main'];
    $formOpen('faq', 'item');
    ?>
    <input type="hidden" name="item" value="<?= (int) $itemIndex ?>">
    <h3><?= $itemIndex >= 0 ? 'แก้ไขคำถาม' : 'เพิ่มคำถามใหม่' ?></h3>
    <div class="form-group">
        <label>คำถาม</label>
        <input type="text" name="faq_q" class="form-control" value="<?= e($faqItem['q'] ?? '') ?>" required>
    </div>
    <div class="form-group">
        <label>คำตอบ</label>
        <textarea name="faq_a" class="form-control" rows="5" required><?= e($faqItem['a'] ?? '') ?></textarea>
    </div>
    <div class="form-group">
        <label>แสดงที่</label>
        <select name="faq_scope" class="form-control">
            <option value="main" <?= ($faqItem['scope'] ?? 'main') === 'main' ? 'selected' : '' ?>>หน้า FAQ</option>
            <option value="homepage_extra" <?= ($faqItem['scope'] ?? '') === 'homepage_extra' ? 'selected' : '' ?>>หน้าแรกเพิ่มเติม</option>
        </select>
    </div>
    <?php
    $formClose();

endif;
