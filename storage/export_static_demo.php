<?php
declare(strict_types=1);

/**
 * Export public pages to docs/ for GitHub Pages client preview.
 * Run: C:\xampp\php\php.exe storage/export_static_demo.php
 */

$base = 'http://localhost/LMS';
$root = dirname(__DIR__);
$docs = $root . '/docs';

$pages = [
    'index.php' => 'index.html',
    'courses.php' => 'courses.html',
    'contact.php' => 'contact.html',
    'faq.php' => 'faq.html',
    'instructor.php' => 'instructor.html',
    'register.php' => 'register.html',
    'login.php' => 'login.html',
    'announcements.php' => 'announcements.html',
    'privacy.php' => 'privacy.html',
    'terms.php' => 'terms.html',
    'lms-overview.php' => 'lms-overview.html',
    'exams.php' => 'exams.html',
];

$courseSlugs = [
    'm1-intensive-live',
    'm1-intensive-online',
    'm4-intensive-live',
    'm4-intensive-online',
    'a-level-live',
    'a-level-online',
];

function fetchUrl(string $url): string
{
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 30,
            'header' => "Accept-Language: th\r\n",
        ],
    ]);
    $html = @file_get_contents($url, false, $ctx);
    if ($html === false) {
        throw new RuntimeException('Failed to fetch: ' . $url);
    }
    return $html;
}

function rewriteHtml(string $html, string $siteBase): string
{
    // Absolute localhost assets/public -> relative docs paths
    $html = str_replace('http://localhost/LMS/assets/', 'assets/', $html);
    $html = str_replace('https://localhost/LMS/assets/', 'assets/', $html);

    $map = [
        'http://localhost/LMS/public/index.php' => 'index.html',
        'http://localhost/LMS/public/courses.php' => 'courses.html',
        'http://localhost/LMS/public/contact.php' => 'contact.html',
        'http://localhost/LMS/public/faq.php' => 'faq.html',
        'http://localhost/LMS/public/instructor.php' => 'instructor.html',
        'http://localhost/LMS/public/register.php' => 'register.html',
        'http://localhost/LMS/public/login.php' => 'login.html',
        'http://localhost/LMS/public/announcements.php' => 'announcements.html',
        'http://localhost/LMS/public/privacy.php' => 'privacy.html',
        'http://localhost/LMS/public/terms.php' => 'terms.html',
        'http://localhost/LMS/public/lms-overview.php' => 'lms-overview.html',
        'http://localhost/LMS/public/exams.php' => 'exams.html',
        'http://localhost/LMS/public/cart.php' => 'courses.html',
        'http://localhost/LMS/public/checkout.php' => 'courses.html',
        'http://localhost/LMS/public/my-courses.php' => 'login.html',
        'http://localhost/LMS/public/profile.php' => 'login.html',
        'http://localhost/LMS/public/forgot-password.php' => 'login.html',
    ];

    foreach ($map as $from => $to) {
        $html = str_replace($from, $to, $html);
    }

    // course detail links
    $html = preg_replace_callback(
        '#http://localhost/LMS/public/course\.php\?slug=([a-z0-9\-]+)#i',
        static fn(array $m): string => 'course-' . $m[1] . '.html',
        $html
    );

    // exam pack detail links
    $html = preg_replace_callback(
        '#http://localhost/LMS/public/exam\.php\?slug=([a-z0-9\-]+)#i',
        static fn(array $m): string => 'exam-' . $m[1] . '.html',
        $html
    );

    // login-gated exam actions -> login page in demo
    $html = preg_replace('#http://localhost/LMS/public/exam_(take|submit)\.php[^"\']*#i', 'login.html', $html);

    // cart/book/add actions -> noop for demo
    $html = preg_replace('#http://localhost/LMS/public/(cart_add|cart_remove|cart_buy|book|apply_coupon|exam_cart_add)\.php[^"\']*#i', '#', $html);

    // leftover localhost
    $html = str_replace('http://localhost/LMS/public/', '', $html);
    $html = str_replace('http://localhost/LMS/', '', $html);

    // Demo banner + base for GH Pages project site
    $banner = <<<HTML
<style>
.demo-preview-banner{position:sticky;top:0;z-index:9999;background:#1e3f3a;color:#fff;text-align:center;padding:.55rem 1rem;font-size:.88rem;font-family:BetterTogether,sans-serif}
.demo-preview-banner a{color:#e2cc8a;margin-left:.5rem}
.demo-preview-banner strong{color:#e2cc8a}
</style>
<div class="demo-preview-banner">
  <strong>เดโมหน้าตาเท่านั้น</strong> — ยังไม่ได้เชื่อมระบบจริง (สมัคร/ล็อกอิน/ชำระเงินใช้ดูเลย์เอาต์ได้)
  <a href="index.html">หน้าแรก</a>
  <a href="courses.html">คอร์ส</a>
  <a href="exams.html">จำลองสนามสอบ</a>
  <a href="faq.html">FAQ</a>
  <a href="contact.html">ติดต่อ</a>
</div>
HTML;

    if (str_contains($html, '<body')) {
        $html = preg_replace('/<body([^>]*)>/', '<body$1>' . "\n" . $banner, $html, 1);
    }

    // Help relative asset paths when opened under /1509/
    if (!str_contains($html, '<base ')) {
        $html = str_replace('<head>', '<head>' . "\n" . '    <base href="' . $siteBase . '">', $html);
    }

    return $html;
}

function copyDir(string $src, string $dst): void
{
    if (!is_dir($src)) {
        return;
    }
    if (!is_dir($dst)) {
        mkdir($dst, 0777, true);
    }
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($it as $item) {
        $target = $dst . DIRECTORY_SEPARATOR . $it->getSubPathName();
        if ($item->isDir()) {
            if (!is_dir($target)) {
                mkdir($target, 0777, true);
            }
        } else {
            $parent = dirname($target);
            if (!is_dir($parent)) {
                mkdir($parent, 0777, true);
            }
            copy($item->getPathname(), $target);
        }
    }
}

echo "Exporting static demo to docs/\n";

if (is_dir($docs)) {
    // clean html only, keep if needed
    foreach (glob($docs . '/*.html') ?: [] as $old) {
        unlink($old);
    }
} else {
    mkdir($docs, 0777, true);
}

$siteBase = '/1509/';

// Discover exam pack slugs from the catalog page so new packs are picked up automatically.
$examSlugs = [];
try {
    if (preg_match_all('#/public/exam\.php\?slug=([a-z0-9\-]+)#i', fetchUrl($base . '/public/exams.php'), $m)) {
        $examSlugs = array_values(array_unique($m[1]));
    }
} catch (Throwable $e) {
    echo "- skip exam slug discovery: {$e->getMessage()}\n";
}

foreach ($pages as $php => $htmlName) {
    $url = $base . '/public/' . $php;
    echo "- {$php} -> {$htmlName}\n";
    $html = rewriteHtml(fetchUrl($url), $siteBase);
    file_put_contents($docs . '/' . $htmlName, $html);
}

foreach ($courseSlugs as $slug) {
    $url = $base . '/public/course.php?slug=' . rawurlencode($slug);
    $htmlName = 'course-' . $slug . '.html';
    echo "- course {$slug} -> {$htmlName}\n";
    try {
        $html = rewriteHtml(fetchUrl($url), $siteBase);
        file_put_contents($docs . '/' . $htmlName, $html);
    } catch (Throwable $e) {
        echo "  skip: {$e->getMessage()}\n";
    }
}

foreach ($examSlugs as $slug) {
    $url = $base . '/public/exam.php?slug=' . rawurlencode($slug);
    $htmlName = 'exam-' . $slug . '.html';
    echo "- exam {$slug} -> {$htmlName}\n";
    try {
        $html = rewriteHtml(fetchUrl($url), $siteBase);
        file_put_contents($docs . '/' . $htmlName, $html);
    } catch (Throwable $e) {
        echo "  skip: {$e->getMessage()}\n";
    }
}

echo "Copying assets...\n";
$assetPairs = [
    'css' => ['style.css', 'fonts-bettertogether.css'],
    'js' => ['main.js'],
    'fonts/BetterTogether' => null, // whole dir
    'images/leona' => null,
    'images/courses' => null,
    'images/instructor' => null,
    'images/hero' => null,
    'vendor/lucide' => null,
];

$assetsDst = $docs . '/assets';
if (!is_dir($assetsDst)) {
    mkdir($assetsDst, 0777, true);
}

copyDir($root . '/assets/css', $assetsDst . '/css');
copyDir($root . '/assets/js', $assetsDst . '/js');
copyDir($root . '/assets/fonts', $assetsDst . '/fonts');
copyDir($root . '/assets/images', $assetsDst . '/images');
copyDir($root . '/assets/vendor/lucide', $assetsDst . '/vendor/lucide');

// nojekyll for GH Pages
file_put_contents($docs . '/.nojekyll', '');

echo "Done. Open docs/index.html or deploy GitHub Pages from /docs\n";
