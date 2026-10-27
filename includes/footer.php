<?php

declare(strict_types=1);

if (!function_exists('lineContactUrl')) {

    require_once __DIR__ . '/homepage.php';

}

require_once __DIR__ . '/site_content.php';

$footerLineUrl = lineContactUrl();
$footerContent = getFooterContent();

$footerHskCourses = getFooterHskCourseLinks();

$footerCheckoutUrl = $checkoutNavUrl ?? APP_URL . '/public/cart.php';

$footerFacebook = trim(getSetting('facebook_url', ''));

$footerLineId = trim(getSetting('line_id', ''));

$footerPhone = trim(getSetting('phone', ''));

$footerEmail = publicContactEmail();

$footerYoutube = trim(getSetting('youtube_url', ''));

$footerTiktok = trim(getSetting('tiktok_url', ''));

$footerTagline = getSetting('site_tagline', 'ติวสังคมศึกษา ประวัติศาสตร์ · สอบเข้า ม.1 ม.4 และ A-Level');

require __DIR__ . '/views/contact_fab.php';

?>

</main>

<footer class="site-footer" id="contact">

    <div class="container footer-grid">

        <div class="footer-brand">
            <a href="<?= APP_URL ?>/public/index.php" class="footer-brand-link">
                <span class="footer-brand-name">THE LEONA TUTORS</span>
            </a>
            <p class="footer-desc"><?= e($footerTagline) ?></p>
            <div class="footer-social">
                <?php if ($footerFacebook !== ''): ?>
                <a href="<?= e($footerFacebook) ?>" class="footer-social-link footer-social-fb" target="_blank" rel="noopener" aria-label="Facebook">
                    <?= contactChannelIcon('facebook', 20, true) ?>
                </a>
                <?php endif; ?>
                <?php if ($footerLineId !== ''): ?>
                <a href="<?= e($footerLineUrl) ?>" class="footer-social-link footer-social-line" target="_blank" rel="noopener" aria-label="Line">
                    <?= contactChannelIcon('line', 20, true) ?>
                </a>
                <?php endif; ?>
                <?php if ($footerPhone !== ''): ?>
                <a href="tel:<?= e(preg_replace('/\s+/', '', $footerPhone)) ?>" class="footer-social-link footer-social-phone" aria-label="โทรศัพท์">
                    <?= contactChannelIcon('phone', 20, true) ?>
                </a>
                <?php endif; ?>
                <?php if ($footerEmail !== ''): ?>
                <a href="mailto:<?= e($footerEmail) ?>" class="footer-social-link footer-social-email" aria-label="อีเมล">
                    <?= contactChannelIcon('email', 20, true) ?>
                </a>
                <?php endif; ?>
                <?php if ($footerTiktok !== ''): ?>
                <a href="<?= e($footerTiktok) ?>" class="footer-social-link footer-social-tiktok" target="_blank" rel="noopener" aria-label="TikTok">
                    <?= contactChannelIcon('tiktok', 20, true) ?>
                </a>
                <?php endif; ?>
                <?php if ($footerYoutube !== ''): ?>
                <a href="<?= e($footerYoutube) ?>" class="footer-social-link footer-social-youtube" target="_blank" rel="noopener" aria-label="YouTube">
                    <?= contactChannelIcon('youtube', 20, true) ?>
                </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="footer-col">

            <h4><?= e($footerContent['col_courses'] ?? 'คอร์สเรียน') ?></h4>

            <ul>

                <?php foreach ($footerHskCourses as $fc): ?>

                <li><a href="<?= e($fc['url']) ?>"><?= e($fc['title']) ?></a></li>

                <?php endforeach; ?>

                <?php if (!$footerHskCourses): ?>

                <li><a href="<?= APP_URL ?>/public/courses.php">ดูคอร์สทั้งหมด</a></li>

                <?php endif; ?>

            </ul>

        </div>

        <div class="footer-col">

            <h4><?= e($footerContent['col_about'] ?? 'เกี่ยวกับเรา') ?></h4>

            <ul>

                <?php foreach (($footerContent['about_links'] ?? []) as $link): ?>
                <li><a href="<?= e(str_starts_with($link['url'], 'http') ? $link['url'] : APP_URL . $link['url']) ?>"><?= e($link['label'] ?? '') ?></a></li>
                <?php endforeach; ?>

            </ul>

        </div>

        <div class="footer-col">

            <h4><?= e($footerContent['col_help'] ?? 'ช่วยเหลือ') ?></h4>

            <ul>

                <?php foreach (($footerContent['help_links'] ?? []) as $link): ?>
                <?php
                    $href = $link['url'] ?? '#';
                    if ($href === '__checkout__') {
                        $href = $footerCheckoutUrl;
                    } elseif (!str_starts_with($href, 'http')) {
                        $href = APP_URL . $href;
                    }
                ?>
                <li><a href="<?= e($href) ?>"><?= e($link['label'] ?? '') ?></a></li>
                <?php endforeach; ?>

            </ul>

        </div>

        <div class="footer-col footer-contact-col">

            <h4><?= e($footerContent['col_contact'] ?? 'ติดต่อเรา') ?></h4>

            <ul class="footer-contact-list">

                <?php if ($footerLineId !== ''): ?>

                <li>
                    <a href="<?= e($footerLineUrl) ?>" class="footer-contact-chip footer-contact-chip--line" target="_blank" rel="noopener">
                        <span class="footer-contact-chip-icon" aria-hidden="true">
                            <?= contactChannelIcon('line', 18, true) ?>
                        </span>
                        <span class="footer-contact-chip-text">
                            <small>LINE</small>
                            <strong><?= e($footerLineId) ?></strong>
                        </span>
                    </a>
                </li>

                <?php endif; ?>

                <?php if ($footerPhone !== ''): ?>

                <li>
                    <a href="tel:<?= e(preg_replace('/\s+/', '', $footerPhone)) ?>" class="footer-contact-chip footer-contact-chip--phone">
                        <span class="footer-contact-chip-icon" aria-hidden="true">
                            <?= contactChannelIcon('phone', 18, true) ?>
                        </span>
                        <span class="footer-contact-chip-text">
                            <small>โทร</small>
                            <strong><?= e($footerPhone) ?></strong>
                        </span>
                    </a>
                </li>

                <?php endif; ?>

                <?php if ($footerEmail !== ''): ?>

                <li>
                    <a href="mailto:<?= e($footerEmail) ?>" class="footer-contact-chip footer-contact-chip--email">
                        <span class="footer-contact-chip-icon" aria-hidden="true">
                            <?= contactChannelIcon('email', 18, true) ?>
                        </span>
                        <span class="footer-contact-chip-text">
                            <small>อีเมล</small>
                            <strong><?= e($footerEmail) ?></strong>
                        </span>
                    </a>
                </li>

                <?php endif; ?>

            </ul>

        </div>

    </div>

    <div class="footer-bottom">

        <div class="container footer-bottom-inner">

            <p>&copy; <?= date('Y') ?> <?= e($footerContent['copyright'] ?? 'กวดวิชาเดอะลีโอน่า. All Rights Reserved.') ?></p>

            <div class="footer-bottom-links">

                <a href="<?= APP_URL ?>/public/privacy.php">นโยบายความเป็นส่วนตัว</a>

                <span class="footer-bottom-sep" aria-hidden="true">|</span>

                <a href="<?= APP_URL ?>/public/terms.php">เงื่อนไขการให้บริการ</a>

            </div>

        </div>

    </div>

</footer>

<?php

$toastMessage = $cartSuccess ?? null;

if (!empty($toastMessage)):

?>

<div class="toast-stack" id="toastStack" aria-live="polite" aria-atomic="true">

    <div class="toast toast-success" data-toast role="status">

        <span class="toast-icon" aria-hidden="true">

            <?= lucide_icon('check', ['size' => 22, 'stroke' => '2.5', 'attrs' => 'stroke="#fff"']) ?>

        </span>

        <span class="toast-text"><?= e($toastMessage) ?></span>

    </div>

</div>

<?php endif; ?>

<script src="<?= asset('js/main.js') ?>?v=149"></script>

</body>

</html>


