<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/includes/cart.php';
require_once dirname(__DIR__) . '/includes/homepage.php';
require_once dirname(__DIR__) . '/includes/instructor.php';
require_once dirname(__DIR__) . '/includes/site_content.php';

$courses = [];
$cartSuccess = flash('cart_success');

try {
    $courses = getCourses();
} catch (Throwable $e) {
    $courses = [];
}

$featuredList = array_values(array_filter($courses, fn($c) => !empty($c['is_featured'])));
$otherList = array_values(array_filter($courses, fn($c) => empty($c['is_featured'])));
$homeCourses = array_slice(array_merge($featuredList, $otherList), 0, 9);
$homeStats = getHomepageStats();
$homeContent = getHomepageContent();
$reviews = getHomeReviews();
$homeFaqs = getHomepageFaqItems();
$homeFaqLeft = array_slice($homeFaqs, 0, 5);
$homeFaqRight = array_slice($homeFaqs, 5, 5);
$instructor = getInstructorProfile($homeStats);
$instructorPhotoUrl = instructorPhotoUrl($instructor);
$phone = trim(getSetting('phone', '082-8672627'));
$registerUrl = APP_URL . '/public/register.php';
$coursesUrl = APP_URL . '/public/courses.php';
$contactUrl = APP_URL . '/public/contact.php';

$subjects = [
    ['icon' => 'landmark', 'title' => 'สังคมศึกษา', 'level' => 'ประถม – ม.ปลาย'],
    ['icon' => 'scroll-text', 'title' => 'ประวัติศาสตร์', 'level' => 'ม.ต้น – ม.ปลาย'],
    ['icon' => 'scale', 'title' => 'หน้าที่พลเมือง', 'level' => 'ประถม – ม.ปลาย'],
    ['icon' => 'coins', 'title' => 'เศรษฐศาสตร์', 'level' => 'ม.ต้น – ม.ปลาย'],
    ['icon' => 'globe-2', 'title' => 'ภูมิศาสตร์', 'level' => 'ประถม – ม.ปลาย'],
    ['icon' => 'gavel', 'title' => 'กฎหมาย', 'level' => 'ม.ปลาย / A-Level'],
];

$results = [
    ['icon' => 'medal', 'value' => 'ติวเข้ม', 'label' => 'สอบเข้า ม.1 / ม.4 และ A-Level'],
    ['icon' => 'users', 'value' => 'สด + ออนไลน์', 'label' => 'เลือกได้ตามไลฟ์สไตล์'],
    ['icon' => 'building-2', 'value' => '1 ปี', 'label' => 'อายุการเข้าถึงคอร์สหลังสมัคร'],
    ['icon' => 'badge-check', 'value' => 'FIRST25', 'label' => 'เรียนครั้งแรกลด 25%'],
];

$tutors = [
    [
        'name' => $instructor['name'] ?: 'ครูบอล',
        'role' => 'ผู้สอนหลัก',
        'subject' => 'สังคมศึกษา · ประวัติศาสตร์',
        'focus' => 'ติวสอบเข้า ม.1 / ม.4 และ A-Level',
        'photo' => asset('images/leona/tutors/tutor-01.jpg'),
    ],
    [
        'name' => 'ครูเอ',
        'role' => 'ติวเตอร์',
        'subject' => 'หน้าที่พลเมือง · กฎหมาย',
        'focus' => 'วิเคราะห์โจทย์และเทคนิคตัดตัวเลือก',
        'photo' => asset('images/leona/tutors/tutor-02.jpg'),
    ],
    [
        'name' => 'ครูบี',
        'role' => 'ติวเตอร์',
        'subject' => 'เศรษฐศาสตร์ · ภูมิศาสตร์',
        'focus' => 'ปรับพื้นฐานถึงระดับสอบแข่งขัน',
        'photo' => asset('images/leona/tutors/tutor-03.jpg'),
    ],
    [
        'name' => 'ครูซี',
        'role' => 'ติวเตอร์',
        'subject' => 'สังคมศึกษา · ปรับพื้นฐาน',
        'focus' => 'ดูแลนักเรียนประถม–ม.ต้นอย่างใกล้ชิด',
        'photo' => asset('images/leona/tutors/tutor-04.jpg'),
    ],
];
?>

<?php if ($cartSuccess): ?>
<div class="container" style="padding-top:1rem"><div class="alert alert-success"><?= e($cartSuccess) ?></div></div>
<?php endif; ?>

<section class="leona-hero" id="about" aria-label="แบนเนอร์หลัก">
    <div class="leona-hero-media" aria-hidden="true">
        <img
            src="<?= e(asset('images/leona/hero.jpg')) ?>"
            alt=""
            width="1600"
            height="900"
            fetchpriority="high"
            decoding="async"
        >
    </div>
    <div class="leona-hero-shade" aria-hidden="true"></div>
    <div class="container leona-hero-inner">
        <div class="leona-hero-copy leona-reveal">
            <p class="leona-hero-eyebrow">กวดวิชาเดอะลีโอน่า</p>
            <h1>THE LEONA<br><span>TUTORS</span></h1>
            <p class="leona-hero-lead">ติวสังคมศึกษาเข้าใจง่าย เห็นผลจริง<br>ครบทุกวิชา ทุกระดับชั้น</p>
            <p class="leona-hero-levels">ประถม · มัธยม · เตรียมสอบ / A-Level</p>
            <div class="leona-hero-actions">
                <a href="<?= e($registerUrl) ?>" class="btn btn-primary leona-hero-cta">สมัครเรียน</a>
                <a href="<?= e($coursesUrl) ?>" class="btn btn-outline leona-hero-cta-ghost">ดูคอร์ส</a>
            </div>
        </div>
    </div>
</section>

<section class="leona-section" id="subjects">
    <div class="container">
        <header class="leona-section-head leona-reveal">
            <h2>วิชาที่เปิดสอน</h2>
        </header>
        <div class="leona-subjects-grid">
            <?php foreach ($subjects as $subject): ?>
            <article class="leona-subject-card leona-reveal">
                <span class="leona-subject-icon" aria-hidden="true">
                    <?= lucide_icon($subject['icon'], ['size' => 32, 'stroke' => '1.6']) ?>
                </span>
                <h3><?= e($subject['title']) ?></h3>
                <p><?= e($subject['level']) ?></p>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="leona-section" id="results">
    <div class="container leona-results-layout">
        <div class="leona-results-copy">
            <header class="leona-section-head leona-section-head-left leona-reveal">
                <h2>ผลการเรียนของเรา</h2>
                <p class="leona-results-lead">ออกแบบมาเพื่อให้เห็นผลจริง ทั้งติวเข้มและปรับพื้นฐาน</p>
            </header>
            <ul class="leona-results-list">
                <?php foreach ($results as $item): ?>
                <li class="leona-reveal">
                    <span class="leona-results-icon" aria-hidden="true">
                        <?= lucide_icon($item['icon'], ['size' => 22, 'stroke' => '1.75']) ?>
                    </span>
                    <div>
                        <strong><?= e($item['value']) ?></strong>
                        <span><?= e($item['label']) ?></span>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <figure class="leona-results-media leona-reveal">
            <img
                src="<?= e(asset('images/leona/results-students.jpg')) ?>"
                alt="นักเรียนกวดวิชาเดอะลีโอน่า"
                width="900"
                height="675"
                loading="lazy"
                decoding="async"
            >
        </figure>
    </div>
</section>

<section class="leona-section" id="courses">
    <div class="container">
        <div class="leona-section-head-row">
            <header class="leona-section-head leona-section-head-left leona-reveal">
                <h2><?= e($homeContent['courses']['title'] ?? 'คอร์สเรียนยอดนิยม') ?></h2>
            </header>
            <a href="<?= e($coursesUrl) ?>" class="section-link-more">ดูคอร์สทั้งหมด<?= lucide_text_link_suffix(16) ?></a>
        </div>
        <?php if ($homeCourses): ?>
        <div class="reviews-slider courses-home-slider" id="coursesSlider" aria-roledescription="carousel" aria-label="คอร์สเรียนยอดนิยม">
            <button type="button" class="reviews-slider-btn reviews-slider-prev" aria-label="คอร์สก่อนหน้า">
                <?= lucide_icon('chevron-left', ['size' => 20, 'stroke' => '2']) ?>
            </button>
            <div class="reviews-slider-viewport">
                <div class="reviews-slider-track courses-home-slider-track">
                    <?php foreach ($homeCourses as $course): ?>
                        <?php include dirname(__DIR__) . '/includes/course_card.php'; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            <button type="button" class="reviews-slider-btn reviews-slider-next" aria-label="คอร์สถัดไป">
                <?= lucide_icon('chevron-right', ['size' => 20, 'stroke' => '2']) ?>
            </button>
            <div class="reviews-slider-dots" role="tablist" aria-label="เลือกชุดคอร์ส"></div>
        </div>
        <?php else: ?>
        <p class="lesson-empty">กำลังเตรียมคอร์ส... กรุณาลองใหม่ภายหลัง</p>
        <?php endif; ?>
    </div>
</section>

<section class="leona-section leona-section-soft" id="tutors">
    <div class="container">
        <header class="leona-section-head leona-reveal">
            <h2>ทีมติวเตอร์</h2>
            <p>ผู้สอนที่ดูแลการติวเข้มและปรับพื้นฐานอย่างใกล้ชิด</p>
        </header>
        <div class="leona-tutors-grid">
            <?php foreach ($tutors as $tutor): ?>
            <article class="leona-tutor-person leona-reveal">
                <div class="leona-tutor-person-photo">
                    <?php if (($tutor['photo'] ?? '') !== ''): ?>
                    <img src="<?= e($tutor['photo']) ?>" alt="<?= e($tutor['name']) ?>" loading="lazy" width="400" height="400">
                    <?php else: ?>
                    <span class="leona-tutor-fallback" aria-hidden="true"><?= e(mb_substr($tutor['name'], 0, 1)) ?></span>
                    <?php endif; ?>
                </div>
                <div class="leona-tutor-person-body">
                    <p class="leona-tutor-person-role"><?= e($tutor['role']) ?></p>
                    <h3><?= e($tutor['name']) ?></h3>
                    <p class="leona-tutor-person-subject"><?= e($tutor['subject']) ?></p>
                    <p class="leona-tutor-person-focus"><?= e($tutor['focus']) ?></p>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="leona-section" id="reviews">
    <div class="container">
        <header class="leona-section-head leona-reveal">
            <h2><?= e($homeContent['reviews']['title'] ?? 'รีวิวจากผู้ปกครอง') ?></h2>
            <p><?= e($homeContent['reviews']['subtitle'] ?? 'เสียงจากผู้ปกครองที่ไว้วางใจกวดวิชาเดอะลีโอน่า') ?></p>
        </header>
        <?php if ($reviews): ?>
        <div class="reviews-slider leona-reviews-slider" id="reviewsSlider" aria-roledescription="carousel" aria-label="รีวิวจากผู้ปกครอง">
            <button type="button" class="reviews-slider-btn reviews-slider-prev" aria-label="รีวิวก่อนหน้า">
                <?= lucide_icon('chevron-left', ['size' => 20, 'stroke' => '2']) ?>
            </button>
            <div class="reviews-slider-viewport">
                <div class="reviews-slider-track">
                    <?php foreach (array_slice($reviews, 0, 10) as $review): ?>
                    <article class="review-card leona-review-slide">
                        <div class="review-stars" aria-label="5 จาก 5 ดาว">
                            <?php for ($s = 0; $s < 5; $s++): ?>
                            <?= lucide_icon('star', ['size' => 14, 'class' => 'review-star']) ?>
                            <?php endfor; ?>
                        </div>
                        <blockquote class="review-quote">&ldquo;<?= e($review['quote'] ?? '') ?>&rdquo;</blockquote>
                        <div class="review-author">
                            <span class="review-avatar" aria-hidden="true">
                                <?= lucide_icon('user', ['size' => 18, 'stroke' => '1.85']) ?>
                            </span>
                            <div class="review-author-meta">
                                <strong><?= e($review['name'] ?? 'ผู้ปกครอง') ?></strong>
                                <span><?= e($review['course'] ?? '') ?></span>
                            </div>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
            </div>
            <button type="button" class="reviews-slider-btn reviews-slider-next" aria-label="รีวิวถัดไป">
                <?= lucide_icon('chevron-right', ['size' => 20, 'stroke' => '2']) ?>
            </button>
            <div class="reviews-slider-dots" role="tablist" aria-label="เลือกหน้ารีวิว"></div>
        </div>
        <?php endif; ?>
    </div>
</section>

<section class="leona-section" id="faq">
    <div class="container">
        <header class="leona-section-head leona-reveal">
            <h2><?= e($homeContent['faq']['title'] ?? 'คำถามที่พบบ่อย') ?></h2>
            <p><?= e($homeContent['faq']['subtitle'] ?? 'คำตอบสำหรับคำถามที่ผู้ปกครองและนักเรียนถามบ่อย') ?></p>
        </header>
        <?php if ($homeFaqs): ?>
        <div class="leona-faq-columns leona-reveal">
            <div class="leona-faq-col">
                <?php foreach ($homeFaqLeft as $faq): ?>
                <details class="faq-item">
                    <summary><?= e($faq['q'] ?? '') ?></summary>
                    <p><?= e($faq['a'] ?? '') ?></p>
                </details>
                <?php endforeach; ?>
            </div>
            <div class="leona-faq-col">
                <?php foreach ($homeFaqRight as $faq): ?>
                <details class="faq-item">
                    <summary><?= e($faq['q'] ?? '') ?></summary>
                    <p><?= e($faq['a'] ?? '') ?></p>
                </details>
                <?php endforeach; ?>
            </div>
        </div>
        <p class="leona-faq-more">
            <a href="<?= APP_URL ?>/public/faq.php" class="section-link-more">ดูคำถามทั้งหมด<?= lucide_text_link_suffix(16) ?></a>
        </p>
        <?php endif; ?>
    </div>
</section>

<section class="leona-promo" aria-label="โปรโมชั่น">
    <div class="container leona-promo-inner leona-reveal">
        <div class="leona-promo-offer" aria-hidden="true">
            <span class="leona-promo-percent">25%</span>
            <span class="leona-promo-percent-label">ส่วนลดครั้งแรก</span>
        </div>
        <div class="leona-promo-copy">
            <p class="leona-promo-kicker">ข้อเสนอพิเศษ</p>
            <h2>สมัครคอร์สครั้งแรก รับส่วนลดทันที</h2>
            <div class="leona-promo-codes">
                <span class="leona-promo-code">
                    <small>สมาชิกใหม่</small>
                    <strong>FIRST25</strong>
                </span>
                <span class="leona-promo-code">
                    <small>นักเรียนเก่า ลด 10%</small>
                    <strong>ALUMNI10</strong>
                </span>
            </div>
        </div>
        <a href="<?= e($registerUrl) ?>" class="btn leona-promo-btn">สมัครเลย<?= lucide_text_link_suffix(16) ?></a>
    </div>
</section>

<section class="leona-contact-strip">
    <div class="container leona-contact-strip-inner">
        <p>สอบถามรอบเรียนกับครูบอลได้เลย</p>
        <div class="leona-contact-strip-actions">
            <?php if ($phone !== ''): ?>
            <a href="tel:<?= e(preg_replace('/\s+/', '', $phone)) ?>" class="btn btn-outline"><?= e($phone) ?></a>
            <?php endif; ?>
            <a href="<?= e($contactUrl) ?>" class="btn btn-primary">ติดต่อเรา</a>
        </div>
    </div>
</section>

<script>
(() => {
  const nodes = document.querySelectorAll('.leona-reveal');
  if (!nodes.length) return;
  if (!('IntersectionObserver' in window)) {
    nodes.forEach((el) => el.classList.add('is-visible'));
    return;
  }
  const io = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (!entry.isIntersecting) return;
      entry.target.classList.add('is-visible');
      io.unobserve(entry.target);
    });
  }, { threshold: 0.16, rootMargin: '0px 0px -8% 0px' });
  nodes.forEach((el) => io.observe(el));
})();
</script>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
