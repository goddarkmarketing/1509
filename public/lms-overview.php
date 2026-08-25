<?php

declare(strict_types=1);

/**
 * แลนดิ้งอธิบายฟังก์ชั่นระบบ LMS สำหรับส่ง URL ให้ลูกค้า
 * ไม่ลิงก์จากเมนูเว็บหลัก — เข้าได้เฉพาะจากลิงก์โดยตรง
 */

require_once dirname(__DIR__) . '/includes/functions.php';

$pageTitle = 'ระบบ LMS — รายละเอียดฟังก์ชั่น';
$lineUrl = 'https://lin.ee/QlZ3xn9';
$lineId = '@939dgokv';
$phone = '0824835020';
$phoneTel = '0824835020';
$videoSrc = asset('videos/lms-overview-web.mp4');
$functionSlides = [
    [
        'src' => imageAsset('images/lms-overview/slide-home.png'),
        'label' => 'หน้าแรก',
        'desc' => 'แบนเนอร์และภาพรวมสถาบัน',
    ],
    [
        'src' => imageAsset('images/lms-overview/slide-courses.png'),
        'label' => 'รายการคอร์ส',
        'desc' => 'ค้นหา กรอง และเลือกคอร์ส',
    ],
    [
        'src' => imageAsset('images/lms-overview/slide-course.png'),
        'label' => 'รายละเอียดคอร์ส',
        'desc' => 'ข้อมูลคอร์ส ราคา และปุ่มซื้อ',
    ],
    [
        'src' => imageAsset('images/lms-overview/slide-cart.png'),
        'label' => 'ตะกร้าสินค้า',
        'desc' => 'สรุปรายการและรหัสส่วนลด',
    ],
    [
        'src' => imageAsset('images/lms-overview/slide-payment.png'),
        'label' => 'ชำระเงิน',
        'desc' => 'โอนธนาคารและบัตรเครดิต',
    ],
];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($pageTitle) ?> | กวดวิชาเดอะลีโอน่า</title>
    <meta name="description" content="สรุปฟังก์ชั่นระบบ LMS: ขายคอร์ส ชำระเงิน เรียนวิดีโอ คลาส Live และหลังบ้านผู้ดูแล">
    <?php require dirname(__DIR__) . '/includes/views/fonts_head.php'; ?>
    <link rel="stylesheet" href="<?= e(asset('css/lms-overview.css')) ?>?v=<?= e((string) (@filemtime(BASE_PATH . '/assets/css/lms-overview.css') ?: 1)) ?>">
</head>
<body class="lms-overview">
    <main>
        <section class="lo-hero">
            <div class="lo-shell lo-hero-grid">
                <div class="lo-hero-left">
                    <p class="lo-label">เอกสารแนะนำระบบ</p>
                    <h1>ระบบ LMS ออนไลน์<br>ครบวงจร</h1>
                </div>
                <div class="lo-hero-right">
                    <p>แพลตฟอร์มสำหรับขายคอร์ส จัดการการเรียน การชำระเงิน และการเปิดสิทธิ์ผู้เรียน ในระบบเดียว</p>
                    <div class="lo-actions">
                        <a class="lo-btn" href="#functions">ดูฟังก์ชั่นทั้งหมด</a>
                        <a class="lo-btn lo-btn--line" href="#contact">ขอรายละเอียด</a>
                    </div>
                </div>
            </div>
        </section>

        <section class="lo-block lo-block--border" id="video">
            <div class="lo-shell">
                <div class="lo-video-head">
                    <h2>ดูตัวอย่างระบบ</h2>
                </div>

                <div class="lo-video-showcase">
                    <div class="lo-slide-wrap" data-lo-slider>
                        <div class="lo-slide-viewport">
                            <div class="lo-slide-track">
                                <?php foreach ($functionSlides as $i => $slide): ?>
                                <figure class="lo-slide-item<?= $i === 0 ? ' is-active' : '' ?>">
                                    <div class="lo-phone">
                                        <img
                                            src="<?= e($slide['src']) ?>"
                                            alt="<?= e($slide['label']) ?>"
                                            loading="<?= $i === 0 ? 'eager' : 'lazy' ?>"
                                            decoding="async"
                                        >
                                    </div>
                                    <figcaption>
                                        <strong><?= e($slide['label']) ?></strong>
                                        <span><?= e($slide['desc']) ?></span>
                                    </figcaption>
                                </figure>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="lo-slide-nav">
                            <div class="lo-slide-dots" role="tablist" aria-label="จุดสไลด์">
                                <?php foreach ($functionSlides as $i => $slide): ?>
                                <button
                                    type="button"
                                    class="lo-slide-dot<?= $i === 0 ? ' is-active' : '' ?>"
                                    data-lo-dot="<?= $i ?>"
                                    aria-label="<?= e($slide['label']) ?>"
                                    aria-selected="<?= $i === 0 ? 'true' : 'false' ?>"
                                ></button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="lo-video-player" data-lo-video>
                        <video
                            controls
                            playsinline
                            preload="metadata"
                            controlslist="nodownload"
                            title="วิดีโอแนะนำระบบ LMS"
                        >
                            <source src="<?= e($videoSrc) ?>" type="video/mp4">
                            เบราว์เซอร์ของคุณไม่รองรับการเล่นวิดีโอ
                        </video>
                        <button type="button" class="lo-video-play-btn" data-lo-play aria-label="เล่นวิดีโอ">
                            <?= lucide_icon('play', ['size' => 36, 'stroke' => '2']) ?>
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <section class="lo-block" id="summary">
            <div class="lo-shell lo-two">
                <div class="lo-col">
                    <h2>ภาพรวมระบบ</h2>
                    <p>เหมาะกับสถาบันสอนออนไลน์ที่ต้องการมีเว็บขายคอร์ส พื้นที่เรียน และหลังบ้านบริหารในที่เดียว</p>
                </div>
                <div class="lo-col">
                    <ul class="lo-plain">
                        <li>
                            <span class="lo-li-icon" aria-hidden="true"><?= lucide_icon('list-video', ['size' => 18, 'stroke' => '1.75']) ?></span>
                            <span>รองรับคอร์สวิดีโอ (Recorded)</span>
                        </li>
                        <li>
                            <span class="lo-li-icon" aria-hidden="true"><?= lucide_icon('video', ['size' => 18, 'stroke' => '1.75']) ?></span>
                            <span>รองรับคลาสสด (Live) และ Hybrid</span>
                        </li>
                        <li>
                            <span class="lo-li-icon" aria-hidden="true"><?= lucide_icon('credit-card', ['size' => 18, 'stroke' => '1.75']) ?></span>
                            <span>ชำระเงินหลายช่องทาง</span>
                        </li>
                        <li>
                            <span class="lo-li-icon" aria-hidden="true"><?= lucide_icon('mail', ['size' => 18, 'stroke' => '1.75']) ?></span>
                            <span>แจ้งเตือนผ่านอีเมลและ LINE</span>
                        </li>
                    </ul>
                </div>
            </div>
        </section>

        <section class="lo-block lo-block--border" id="functions">
            <div class="lo-shell">
                <div class="lo-section-title lo-two">
                    <h2>ฟังก์ชั่นในระบบ</h2>
                    <p>สรุปรายการหลักที่ผู้เรียนและผู้ดูแลใช้งานได้</p>
                </div>

                <div class="lo-two lo-gap">
                    <article class="lo-panel">
                        <h3>ฝั่งผู้เรียน</h3>
                        <ul>
                            <li>
                                <span class="lo-li-icon" aria-hidden="true"><?= lucide_icon('user-plus', ['size' => 18, 'stroke' => '1.75']) ?></span>
                                <span>สมัครสมาชิก / เข้าสู่ระบบ / ลืมรหัสผ่าน</span>
                            </li>
                            <li>
                                <span class="lo-li-icon" aria-hidden="true"><?= lucide_icon('search', ['size' => 18, 'stroke' => '1.75']) ?></span>
                                <span>ดูคอร์ส ค้นหา และดูรายละเอียดคอร์ส</span>
                            </li>
                            <li>
                                <span class="lo-li-icon" aria-hidden="true"><?= lucide_icon('shopping-cart', ['size' => 18, 'stroke' => '1.75']) ?></span>
                                <span>ตะกร้าสินค้า ซื้อหลายคอร์สพร้อมกัน</span>
                            </li>
                            <li>
                                <span class="lo-li-icon" aria-hidden="true"><?= lucide_icon('ticket', ['size' => 18, 'stroke' => '1.75']) ?></span>
                                <span>ใช้คูปองส่วนลด</span>
                            </li>
                            <li>
                                <span class="lo-li-icon" aria-hidden="true"><?= lucide_icon('landmark', ['size' => 18, 'stroke' => '1.75']) ?></span>
                                <span>ชำระเงิน: โอนธนาคาร + แนบสลิป</span>
                            </li>
                            <li>
                                <span class="lo-li-icon" aria-hidden="true"><?= lucide_icon('smartphone', ['size' => 18, 'stroke' => '1.75']) ?></span>
                                <span>ชำระเงิน: PromptPay QR</span>
                            </li>
                            <li>
                                <span class="lo-li-icon" aria-hidden="true"><?= lucide_icon('credit-card', ['size' => 18, 'stroke' => '1.75']) ?></span>
                                <span>ชำระเงิน: บัตรเครดิต/เดบิต (Omise)</span>
                            </li>
                            <li>
                                <span class="lo-li-icon" aria-hidden="true"><?= lucide_icon('circle-play', ['size' => 18, 'stroke' => '1.75']) ?></span>
                                <span>เรียนวิดีโอ + ติดตามความคืบหน้า</span>
                            </li>
                            <li>
                                <span class="lo-li-icon" aria-hidden="true"><?= lucide_icon('calendar', ['size' => 18, 'stroke' => '1.75']) ?></span>
                                <span>จองรอบคลาส Live + เข้า Zoom</span>
                            </li>
                            <li>
                                <span class="lo-li-icon" aria-hidden="true"><?= lucide_icon('clipboard-check', ['size' => 18, 'stroke' => '1.75']) ?></span>
                                <span>ทำแบบทดสอบ (Quiz)</span>
                            </li>
                            <li>
                                <span class="lo-li-icon" aria-hidden="true"><?= lucide_icon('award', ['size' => 18, 'stroke' => '1.75']) ?></span>
                                <span>รับใบประกาศนียบัตรเมื่อเรียนจบ</span>
                            </li>
                            <li>
                                <span class="lo-li-icon" aria-hidden="true"><?= lucide_icon('users', ['size' => 18, 'stroke' => '1.75']) ?></span>
                                <span>โปรไฟล์: คอร์สของฉัน / การจอง / ใบประกาศ</span>
                            </li>
                            <li>
                                <span class="lo-li-icon" aria-hidden="true"><?= lucide_icon('message-circle', ['size' => 18, 'stroke' => '1.75']) ?></span>
                                <span>เชื่อม LINE OA เพื่อรับแจ้งเตือน</span>
                            </li>
                        </ul>
                    </article>

                    <article class="lo-panel">
                        <h3>ฝั่งผู้ดูแล (Admin)</h3>
                        <ul>
                            <li>
                                <span class="lo-li-icon" aria-hidden="true"><?= lucide_icon('layout-dashboard', ['size' => 18, 'stroke' => '1.75']) ?></span>
                                <span>แดชบอร์ดภาพรวม</span>
                            </li>
                            <li>
                                <span class="lo-li-icon" aria-hidden="true"><?= lucide_icon('book-open', ['size' => 18, 'stroke' => '1.75']) ?></span>
                                <span>จัดการคอร์ส บทเรียน ปก และเอกสาร</span>
                            </li>
                            <li>
                                <span class="lo-li-icon" aria-hidden="true"><?= lucide_icon('calendar', ['size' => 18, 'stroke' => '1.75']) ?></span>
                                <span>สร้างตารางคลาส Live / Hybrid</span>
                            </li>
                            <li>
                                <span class="lo-li-icon" aria-hidden="true"><?= lucide_icon('clipboard-check', ['size' => 18, 'stroke' => '1.75']) ?></span>
                                <span>จัดการการจองคลาส (ยืนยัน / ยกเลิก)</span>
                            </li>
                            <li>
                                <span class="lo-li-icon" aria-hidden="true"><?= lucide_icon('banknote', ['size' => 18, 'stroke' => '1.75']) ?></span>
                                <span>ตรวจสอบการชำระเงิน (ยืนยัน / ปฏิเสธ)</span>
                            </li>
                            <li>
                                <span class="lo-li-icon" aria-hidden="true"><?= lucide_icon('shield-check', ['size' => 18, 'stroke' => '1.75']) ?></span>
                                <span>เปิดสิทธิ์เรียนหลังอนุมัติชำระเงิน</span>
                            </li>
                            <li>
                                <span class="lo-li-icon" aria-hidden="true"><?= lucide_icon('user-check', ['size' => 18, 'stroke' => '1.75']) ?></span>
                                <span>จัดการนักเรียนและสิทธิ์การเรียน</span>
                            </li>
                            <li>
                                <span class="lo-li-icon" aria-hidden="true"><?= lucide_icon('list-ordered', ['size' => 18, 'stroke' => '1.75']) ?></span>
                                <span>สร้างแบบทดสอบและคำถาม</span>
                            </li>
                            <li>
                                <span class="lo-li-icon" aria-hidden="true"><?= lucide_icon('ticket', ['size' => 18, 'stroke' => '1.75']) ?></span>
                                <span>จัดการคูปองส่วนลด</span>
                            </li>
                            <li>
                                <span class="lo-li-icon" aria-hidden="true"><?= lucide_icon('megaphone', ['size' => 18, 'stroke' => '1.75']) ?></span>
                                <span>จัดการข่าวสาร / เนื้อหาเว็บไซต์</span>
                            </li>
                            <li>
                                <span class="lo-li-icon" aria-hidden="true"><?= lucide_icon('chart-column', ['size' => 18, 'stroke' => '1.75']) ?></span>
                                <span>ดูรายงานยอดขายและการลงทะเบียน</span>
                            </li>
                            <li>
                                <span class="lo-li-icon" aria-hidden="true"><?= lucide_icon('settings', ['size' => 18, 'stroke' => '1.75']) ?></span>
                                <span>ตั้งค่าเว็บ การชำระเงิน อีเมล LINE Omise</span>
                            </li>
                            <li>
                                <span class="lo-li-icon" aria-hidden="true"><?= lucide_icon('database', ['size' => 18, 'stroke' => '1.75']) ?></span>
                                <span>สำรองข้อมูลระบบ</span>
                            </li>
                        </ul>
                    </article>
                </div>
            </div>
        </section>

        <section class="lo-block" id="courses">
            <div class="lo-shell">
                <div class="lo-section-title lo-two">
                    <h2>ประเภทคอร์ส</h2>
                    <p>เลือกใช้ตามรูปแบบการสอนของสถาบัน</p>
                </div>
                <div class="lo-three">
                    <div class="lo-item">
                        <h3>Recorded</h3>
                        <p>เรียนวิดีโอย้อนหลังได้ตามสะดวก พร้อมล็อกบทเรียนและติดตามความคืบหน้า</p>
                    </div>
                    <div class="lo-item">
                        <h3>Live</h3>
                        <p>จองรอบเรียน จำกัดจำนวนที่นั่ง และเข้าเรียนผ่านลิงก์ Zoom เมื่อยืนยันแล้ว</p>
                    </div>
                    <div class="lo-item">
                        <h3>Hybrid</h3>
                        <p>รวมทั้งวิดีโอและคลาสสดในคอร์สเดียว ตามโครงสร้างที่กำหนด</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="lo-block lo-block--border" id="payments">
            <div class="lo-shell lo-two">
                <div class="lo-col">
                    <h2>การชำระเงินและการเชื่อมต่อ</h2>
                    <p>รองรับการขายจริง พร้อมแจ้งทีมงานและผู้เรียนอัตโนมัติ</p>
                </div>
                <div class="lo-col">
                    <ul class="lo-plain">
                        <li>
                            <span class="lo-li-icon" aria-hidden="true"><?= lucide_icon('landmark', ['size' => 18, 'stroke' => '1.75']) ?></span>
                            <span>โอนธนาคาร + อัปโหลดสลิป</span>
                        </li>
                        <li>
                            <span class="lo-li-icon" aria-hidden="true"><?= lucide_icon('smartphone', ['size' => 18, 'stroke' => '1.75']) ?></span>
                            <span>PromptPay QR บนหน้าชำระเงิน</span>
                        </li>
                        <li>
                            <span class="lo-li-icon" aria-hidden="true"><?= lucide_icon('credit-card', ['size' => 18, 'stroke' => '1.75']) ?></span>
                            <span>บัตรเครดิต/เดบิต ผ่าน Omise</span>
                        </li>
                        <li>
                            <span class="lo-li-icon" aria-hidden="true"><?= lucide_icon('mail', ['size' => 18, 'stroke' => '1.75']) ?></span>
                            <span>แจ้งเตือนอีเมล และ LINE</span>
                        </li>
                        <li>
                            <span class="lo-li-icon" aria-hidden="true"><?= lucide_icon('video', ['size' => 18, 'stroke' => '1.75']) ?></span>
                            <span>ลิงก์ Zoom ในรอบคลาส Live</span>
                        </li>
                        <li>
                            <span class="lo-li-icon" aria-hidden="true"><?= lucide_icon('ticket', ['size' => 18, 'stroke' => '1.75']) ?></span>
                            <span>คูปองส่วนลด</span>
                        </li>
                    </ul>
                </div>
            </div>
        </section>

        <section class="lo-block lo-block--border" id="pricing">
            <div class="lo-shell">
                <div class="lo-section-title lo-two">
                    <h2>ราคา</h2>
                    <p>แพ็กเกจเริ่มต้นสำหรับเปิดใช้งานระบบ</p>
                </div>
                <div class="lo-price-grid">
                    <div class="lo-price-main">
                        <span class="lo-price-badge">ราคาปิดท้าย</span>
                        <p class="lo-price-label">ระบบ LMS ครบวงจร</p>
                        <p class="lo-price-amount">3,990 <span>บาท</span></p>
                        <p class="lo-price-note">ค่าพัฒนาระบบ / ติดตั้งแพลตฟอร์ม</p>
                    </div>
                    <div class="lo-price-side">
                        <p class="lo-price-side-title">ค่าบริการเพิ่มเติม</p>
                        <div class="lo-price-row">
                            <span class="lo-price-name">
                                <?= lucide_icon('link', ['size' => 18, 'stroke' => '1.75', 'class' => 'lo-price-icon']) ?>
                                โดเมน
                            </span>
                            <strong>450 บาท</strong>
                        </div>
                        <div class="lo-price-row">
                            <span class="lo-price-name">
                                <?= lucide_icon('database', ['size' => 18, 'stroke' => '1.75', 'class' => 'lo-price-icon']) ?>
                                โฮสติ้ง
                            </span>
                            <strong>690 บาท</strong>
                        </div>
                        <p class="lo-price-hint">โดเมนและโฮสติ้งคิดแยกตามรอบบริการจริง</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="lo-block" id="contact">
            <div class="lo-shell lo-two lo-contact">
                <div class="lo-col">
                    <h2>สนใจใช้งานระบบ</h2>
                </div>
                <div class="lo-col lo-contact-actions">
                    <a class="lo-btn" href="<?= e($lineUrl) ?>" target="_blank" rel="noopener">LINE · <?= e($lineId) ?></a>
                    <a class="lo-btn lo-btn--line" href="tel:<?= e($phoneTel) ?>">โทร <?= e($phone) ?></a>
                </div>
            </div>
        </section>
    </main>
    <script>
    (() => {
      const root = document.querySelector('[data-lo-slider]');
      if (!root) return;

      const viewport = root.querySelector('.lo-slide-viewport');
      const track = root.querySelector('.lo-slide-track');
      const items = Array.from(root.querySelectorAll('.lo-slide-item'));
      const dots = Array.from(root.querySelectorAll('[data-lo-dot]'));
      if (!viewport || !track || !items.length) return;

      let index = 0;
      let timer = null;

      const visibleCount = () => (window.matchMedia('(max-width: 800px)').matches ? 2 : 3);
      const maxIndex = () => Math.max(0, items.length - visibleCount());
      const slideWidth = () => viewport.clientWidth / visibleCount();

      const layout = () => {
        const width = slideWidth();
        items.forEach((el) => {
          el.style.flex = `0 0 ${width}px`;
          el.style.maxWidth = `${width}px`;
        });
      };

      const goTo = (next) => {
        layout();
        index = Math.max(0, Math.min(next, maxIndex()));
        track.style.transform = `translateX(-${index * slideWidth()}px)`;
        items.forEach((el, i) => el.classList.toggle('is-active', i === index));
        dots.forEach((dot, i) => {
          const on = i === index;
          dot.classList.toggle('is-active', on);
          dot.setAttribute('aria-selected', on ? 'true' : 'false');
        });
      };

      const next = () => goTo(index >= maxIndex() ? 0 : index + 1);
      const prev = () => goTo(index <= 0 ? maxIndex() : index - 1);

      const start = () => {
        stop();
        timer = window.setInterval(next, 4200);
      };
      const stop = () => {
        if (timer) window.clearInterval(timer);
        timer = null;
      };

      dots.forEach((dot) => {
        dot.addEventListener('click', () => {
          goTo(Number(dot.dataset.loDot || 0));
          start();
        });
      });

      root.addEventListener('mouseenter', stop);
      root.addEventListener('mouseleave', start);
      window.addEventListener('resize', () => goTo(index));

      goTo(0);
      start();
    })();

    (() => {
      const wrap = document.querySelector('[data-lo-video]');
      if (!wrap) return;
      const video = wrap.querySelector('video');
      const playBtn = wrap.querySelector('[data-lo-play]');
      if (!video || !playBtn) return;

      const sync = () => {
        wrap.classList.toggle('is-playing', !video.paused && !video.ended);
      };

      playBtn.addEventListener('click', () => {
        video.play().catch(() => {});
      });

      video.addEventListener('play', sync);
      video.addEventListener('playing', sync);
      video.addEventListener('pause', sync);
      video.addEventListener('ended', sync);
      sync();
    })();
    </script>
</body>
</html>
