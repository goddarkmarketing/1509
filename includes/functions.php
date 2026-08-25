<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/icons.php';

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . APP_URL . $path);
    exit;
}

function isSafeLocalReturn(string $path): bool
{
    if ($path === '' || str_contains($path, '://') || str_contains($path, '..')) {
        return false;
    }
    if (!str_starts_with($path, '/public/')) {
        return false;
    }
    if (str_contains($path, '/public/cart_add.php') || str_contains($path, '/public/cart_remove.php')) {
        return false;
    }
    return true;
}

function currentReturnPath(): string
{
    $uri = $_SERVER['REQUEST_URI'] ?? '/public/index.php';
    $uri = preg_replace('#^/LMS#', '', $uri) ?: '/public/index.php';
    $path = parse_url($uri, PHP_URL_PATH) ?: '/public/index.php';
    $query = parse_url($uri, PHP_URL_QUERY);
    $target = $path . ($query ? '?' . $query : '');
    return isSafeLocalReturn($target) ? $target : '/public/index.php';
}

function redirectBack(string $defaultPath = '/public/courses.php'): void
{
    $return = trim($_GET['return'] ?? '');
    if ($return !== '' && isSafeLocalReturn($return)) {
        $return = strtok($return, '#') ?: $return;
        redirect($return);
    }

    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    if ($ref !== '') {
        $path = parse_url($ref, PHP_URL_PATH) ?: '';
        if ($path !== '' && (str_contains($path, '/LMS/') || str_ends_with($path, '/LMS'))) {
            $local = preg_replace('#^/LMS#', '', $path) ?: '/';
            $query = parse_url($ref, PHP_URL_QUERY);
            $target = $local . ($query ? '?' . $query : '');
            if (isSafeLocalReturn($target)) {
                redirect($target);
            }
        }
    }
    redirect($defaultPath);
}

function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return null;
    }
    if (!empty($_SESSION['flash'][$key])) {
        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
    return null;
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

function verifyCsrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if ($token === '' || !hash_equals(csrfToken(), $token)) {
        http_response_code(403);
        exit('คำขอไม่ถูกต้อง กรุณารีเฟรชหน้าแล้วลองใหม่');
    }
}

const MAX_SLIP_UPLOAD_BYTES = 5 * 1024 * 1024;

function validateSlipUpload(array $file): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return 'อัปโหลดไฟล์ไม่สำเร็จ';
    }
    if (($file['size'] ?? 0) > MAX_SLIP_UPLOAD_BYTES) {
        return 'ไฟล์ใหญ่เกิน 5MB';
    }
    $ext = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
    $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'];
    if (!in_array($ext, $allowedExt, true)) {
        return 'รองรับเฉพาะ JPG, PNG, GIF, WEBP, PDF';
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowedMime = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf',
    ];
    if (!in_array($mime, $allowedMime, true)) {
        return 'ชนิดไฟล์ไม่รองรับ';
    }
    return null;
}

function storeSlipUpload(array $file): string|false|null
{
    $error = validateSlipUpload($file);
    if ($error !== null) {
        flash('payment_error', $error);
        return false;
    }
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
    if (!is_dir(UPLOAD_PATH)) {
        mkdir(UPLOAD_PATH, 0755, true);
    }
    $filename = 'slip_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = UPLOAD_PATH . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        flash('payment_error', 'บันทึกไฟล์สลิปไม่สำเร็จ');
        return false;
    }
    return $filename;
}

function asset(string $path): string
{
    return APP_URL . '/assets/' . ltrim($path, '/');
}

function adminAsset(string $path = 'css/admin.css'): string
{
    $relative = ltrim($path, '/');
    $full = BASE_PATH . '/assets/' . $relative;
    $version = is_file($full) ? (string) filemtime($full) : '1';

    return APP_URL . '/assets/' . $relative . '?v=' . $version;
}

function imageAsset(string $relativePath, string $fallbackPath = ''): string
{
    $full = BASE_PATH . '/assets/' . ltrim($relativePath, '/');
    if (is_file($full)) {
        return asset($relativePath);
    }
    if ($fallbackPath !== '') {
        return asset($fallbackPath);
    }
    return asset($relativePath);
}

function brandLogoAsset(): string
{
    return imageAsset('images/logo.png', 'images/logo.svg');
}

function headingFontAsset(): string
{
    return APP_URL . '/assets/fonts/BetterTogether/BetterTogether-Regular.woff2';
}

function fontAsset(): string
{
    return headingFontAsset();
}

function iconAsset(string $filename): string
{
    return APP_URL . '/assets/icon/' . rawurlencode($filename);
}

function versionedCourseAsset(string $relativePath): string
{
    $relativePath = ltrim($relativePath, '/');
    $full = BASE_PATH . '/assets/' . $relativePath;
    $url = asset($relativePath);
    if (is_file($full)) {
        return $url . '?v=' . filemtime($full);
    }
    return $url;
}

function courseCoverUrl(array $course): string
{
    $slugCovers = [
        'hsk1-pinyin' => 'images/courses/hsk1.png',
        'hsk2' => 'images/courses/hsk2.png',
        'hsk3' => 'images/courses/hsk3.png',
        'hsk4' => 'images/courses/hsk4.png',
        'hsk5' => 'images/courses/hsk5.png',
        'exam-prep-hsk3' => 'images/courses/exam-hsk3.png',
        'exam-prep-hsk4' => 'images/courses/exam-hsk4.png',
        'exam-prep-hsk5' => 'images/courses/exam-hsk5.png',
        'm1-intensive-live' => 'images/courses/leona-m1.jpg',
        'm1-intensive-online' => 'images/courses/leona-m1.jpg',
        'm4-intensive-live' => 'images/courses/leona-m4.jpg',
        'm4-intensive-online' => 'images/courses/leona-m4.jpg',
        'alevel-social-live' => 'images/courses/leona-alevel.jpg',
        'alevel-social-online' => 'images/courses/leona-alevel.jpg',
        'foundation-primary' => 'images/courses/leona-primary.jpg',
        'foundation-lower-secondary' => 'images/courses/leona-lower-secondary.jpg',
        'foundation-upper-secondary' => 'images/courses/leona-upper-secondary.jpg',
    ];

    $slug = $course['slug'] ?? '';
    if (isset($slugCovers[$slug]) && is_file(BASE_PATH . '/assets/' . $slugCovers[$slug])) {
        return versionedCourseAsset($slugCovers[$slug]);
    }

    if (!empty($course['image_url'])) {
        $url = $course['image_url'];
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }
        if (str_starts_with($url, 'uploads/courses/')) {
            return APP_URL . '/public/download.php?file=' . urlencode(basename($url));
        }
        return versionedCourseAsset(ltrim($url, '/'));
    }

    $categoryCovers = [
        'foundation' => 'images/courses/cover-foundation.svg',
        'hsk' => 'images/courses/cover-hsk.svg',
        'exam_prep' => 'images/courses/cover-exam.svg',
    ];

    return versionedCourseAsset($categoryCovers[$course['category'] ?? 'hsk'] ?? 'images/courses/cover-hsk.svg');
}

function courseEnrollUrl(array $course): string
{
    $return = urlencode(currentReturnPath());
    return APP_URL . '/public/cart_add.php?course_id=' . urlencode((string) ($course['id'] ?? '')) . '&return=' . $return;
}

function courseBuyUrl(array $course): string
{
    return APP_URL . '/public/cart_buy.php?course_id=' . (int) ($course['id'] ?? 0);
}

function courseBookOrBuyUrl(array $course): string
{
    if (function_exists('isLiveCourse') && isLiveCourse($course)) {
        return APP_URL . '/public/book.php?course=' . urlencode((string) ($course['slug'] ?? ''));
    }

    return courseBuyUrl($course);
}

function courseBookOrBuyLabel(array $course): string
{
    if (function_exists('isLiveCourse') && isLiveCourse($course)) {
        return 'จองคลาส Live';
    }

    return 'ซื้อคอร์สนี้';
}

function courseStartLessonUrl(array $course, array $lessons): string
{
    foreach ($lessons as $lesson) {
        if (!empty($lesson['is_free_preview'])) {
            return APP_URL . '/public/lesson.php?lesson_id=' . (int) ($lesson['id'] ?? 0);
        }
    }
    if (!empty($lessons[0]['id'])) {
        return APP_URL . '/public/lesson.php?lesson_id=' . (int) $lessons[0]['id'];
    }
    return APP_URL . '/public/course.php?slug=' . urlencode((string) ($course['slug'] ?? ''));
}

function formatDurationMinutes(int $minutes): string
{
    if ($minutes <= 0) {
        return '';
    }
    if ($minutes < 60) {
        return $minutes . ' นาที';
    }
    $hours = intdiv($minutes, 60);
    $mins = $minutes % 60;
    return $mins > 0 ? $hours . ' ชม. ' . $mins . ' นาที' : $hours . ' ชม.';
}

/** @return array{video:int,doc:int,preview:int,totalMinutes:int} */
function courseLessonStats(array $lessons): array
{
    $video = 0;
    $doc = 0;
    $preview = 0;
    $totalMinutes = 0;
    foreach ($lessons as $lesson) {
        if (!empty($lesson['video_url'])) {
            $video++;
        }
        if (!empty($lesson['document_url'])) {
            $doc++;
        }
        if (!empty($lesson['is_free_preview'])) {
            $preview++;
        }
        $totalMinutes += (int) ($lesson['duration_minutes'] ?? 0);
    }
    return [
        'video' => $video,
        'doc' => $doc,
        'preview' => $preview,
        'totalMinutes' => $totalMinutes,
    ];
}

function courseAudienceBullets(array $course): array
{
    $category = $course['category'] ?? 'hsk';
    $level = $course['level'] ?? 'beginner';

    if ($category === 'foundation') {
        return [
            'ผู้เริ่มเรียนภาษาจีน ไม่ต้องมีพื้นฐานมาก่อน',
            'ผู้ที่ต้องการเรียนพินอินและเตรียมสอบ HSK 1',
            'นักเรียน นักศึกษา หรือผู้ทำงานที่อยากเริ่มภาษาจีนอย่างถูกต้อง',
        ];
    }
    if ($category === 'exam_prep') {
        return [
            'ผู้ที่เรียนครบระดับแล้วและกำลังเตรียมสอบ HSK',
            'ผู้ที่ต้องการฝึกทำข้อสอบและทบทวนจุดอ่อนก่อนสอบจริง',
            'ผู้ที่ต้องการเทคนิคการทำข้อสอบและจัดการเวลาในห้องสอบ',
        ];
    }

    return match ($level) {
        'intermediate' => [
            'ผู้ที่มีพื้นฐานภาษาจีนระดับต้นแล้ว',
            'ผู้ที่ต้องการพัฒนาทักษะสื่อสารและอ่านเข้าใจระดับกลาง',
            'ผู้ที่เตรียมสอบ HSK ระดับกลาง',
        ],
        'advanced' => [
            'ผู้ที่มีพื้นฐานภาษาจีนระดับกลางขึ้นไป',
            'ผู้ที่ต้องการพัฒนาทักษะระดับสูงเพื่อการเรียน การทำงาน หรือสอบ HSK',
            'ผู้ที่ต้องการอ่านและเขียนภาษาจีนในบริบทที่ซับซ้อนขึ้น',
        ],
        default => [
            'ผู้ที่เรียนภาษาจีนมาบ้างแล้วและต้องการต่อยอด',
            'ผู้ที่ต้องการเสริมคำศัพท์ ไวยากรณ์ และทักษะสื่อสาร',
            'ผู้ที่เตรียมสอบ HSK ในระดับถัดไป',
        ],
    };
}

function courseIncludedItems(): array
{
    return [
        ['icon' => 'video', 'title' => 'วิดีโอบทเรียนออนไลน์', 'desc' => 'เรียนทีละบท ดูซ้ำได้ตามต้องการ'],
        ['icon' => 'doc', 'title' => 'เอกสารประกอบ', 'desc' => 'ทบทวนและดาวน์โหลดตามที่อาจารย์แนบในแต่ละบท'],
        ['icon' => 'device', 'title' => 'เรียนได้ทุกอุปกรณ์', 'desc' => 'เข้าเรียนผ่านมือถือ แท็บเล็ต หรือคอมพิวเตอร์'],
        ['icon' => 'support', 'title' => 'สอบถามทีมงาน', 'desc' => 'ติดต่อผ่าน Line / Facebook หลังแจ้งชำระเงิน'],
    ];
}

function courseFaqItems(): array
{
    return [
        [
            'q' => 'หลังชำระเงินแล้ว เปิดสิทธิ์เรียนเมื่อไหร่?',
            'a' => 'ทีมงานจะตรวจสอบสลิปและเปิดสิทธิ์ให้โดยเร็วที่สุด โดยปกติภายใน 1–2 วันทำการหลังแจ้งชำระเงิน',
        ],
        [
            'q' => 'เรียนซ้ำหรือย้อนดูบทเรียนได้ไหม?',
            'a' => 'ได้ครับ สามารถกลับมาดูวิดีโอและทบทวนเนื้อหาในบทที่เรียนแล้วได้ตลอด',
        ],
        [
            'q' => 'มีบททดลองเรียนฟรีไหม?',
            'a' => 'บทที่ระบุป้าย "ทดลองเรียนฟรี" สามารถเข้าเรียนได้ก่อนสมัครคอร์ส (ถ้ามีในคอร์สนั้น)',
        ],
        [
            'q' => 'ชำระเงินผ่านช่องทางไหนได้บ้าง?',
            'a' => 'โอนเงินตามบัญชีที่ระบบแสดง แล้วแจ้งหลักฐานการโอนผ่านฟอร์มชำระเงินบนเว็บไซต์',
        ],
        [
            'q' => 'สมัครหลายคอร์สพร้อมกันได้ไหม?',
            'a' => 'ได้ครับ เพิ่มคอร์สลงตะกร้าแล้วชำระรวมในครั้งเดียวได้',
        ],
    ];
}

function getRelatedCourses(array $course, int $limit = 3): array
{
    $courseId = (int) ($course['id'] ?? 0);
    $category = $course['category'] ?? 'hsk';
    $related = [];

    $stmt = db()->prepare('
        SELECT * FROM courses
        WHERE is_active = 1 AND id != ? AND category = ?
        ORDER BY sort_order ASC, id ASC
        LIMIT ' . (int) $limit
    );
    $stmt->execute([$courseId, $category]);
    $related = $stmt->fetchAll();

    if (count($related) >= $limit) {
        return $related;
    }

    $excludeIds = array_merge([$courseId], array_map(fn($c) => (int) $c['id'], $related));
    $placeholders = implode(',', array_fill(0, count($excludeIds), '?'));
    $stmt = db()->prepare("
        SELECT * FROM courses
        WHERE is_active = 1 AND id NOT IN ({$placeholders})
        ORDER BY sort_order ASC, id ASC
        LIMIT " . (int) ($limit - count($related))
    );
    $stmt->execute($excludeIds);
    return array_merge($related, $stmt->fetchAll());
}

function formatPrice(?float $price): string
{
    if ($price === null || $price <= 0) {
        return 'ติดต่อสอบถาม';
    }
    return number_format($price, 0) . ' บาท';
}

function getSettings(): array
{
    static $settings = null;
    if ($settings !== null && empty($GLOBALS['__lms_settings_force_reload'])) {
        return $settings;
    }
    unset($GLOBALS['__lms_settings_force_reload']);

    try {
        $stmt = db()->query('SELECT setting_key, setting_value FROM site_settings');
        $settings = [];
        foreach ($stmt->fetchAll() as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return $settings;
    } catch (Throwable) {
        return [
            'site_title' => 'กวดวิชาเดอะลีโอน่า',
            'site_tagline' => 'ติวสังคมศึกษา ประวัติศาสตร์ · สอบเข้า ม.1 ม.4 และ A-Level',
            'bank_account_name' => 'นางสาวสินีนาฎ สุริยวาลย์',
            'bank_name' => 'กรุงไทย',
            'bank_account_number' => '',
            'payment_note' => 'กรุณาแจ้งหลักฐานการโอน เพื่อความรวดเร็วในการดำเนินการ ขอบคุณค่ะ',
            'facebook_url' => '',
            'line_id' => '',
            'phone' => '082-8672627',
        ];
    }
}

function getSetting(string $key, string $default = ''): string
{
    $settings = getSettings();
    return $settings[$key] ?? $default;
}

function resetSettingsCache(): void
{
    $GLOBALS['__lms_settings_force_reload'] = true;
}

function saveSetting(string $key, string $value): void
{
    $stmt = db()->prepare('
        INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?)
        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
    ');
    $stmt->execute([$key, $value]);
    resetSettingsCache();
}

function saveSettings(array $pairs): void
{
    $stmt = db()->prepare('
        INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?)
        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
    ');
    foreach ($pairs as $key => $value) {
        $stmt->execute([$key, (string) $value]);
    }
    resetSettingsCache();
}

function getCourses(?string $category = null, bool $activeOnly = true, ?string $search = null, ?string $courseType = null): array
{
    $sql = 'SELECT * FROM courses WHERE 1=1';
    $params = [];

    if ($activeOnly) {
        $sql .= ' AND is_active = 1';
    }
    if ($category) {
        $sql .= ' AND category = ?';
        $params[] = $category;
    }
    if ($courseType && in_array($courseType, ['recorded', 'live', 'hybrid'], true)) {
        $sql .= ' AND course_type = ?';
        $params[] = $courseType;
    }
    if ($search !== null && $search !== '') {
        $sql .= ' AND (title LIKE ? OR subtitle LIKE ? OR description LIKE ?)';
        $like = '%' . $search . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    $sql .= ' ORDER BY sort_order ASC, id ASC';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getCourseBySlug(string $slug): ?array
{
    $stmt = db()->prepare('SELECT * FROM courses WHERE slug = ? LIMIT 1');
    $stmt->execute([$slug]);
    $course = $stmt->fetch();
    return $course ?: null;
}

function isCourseActive(array $course): bool
{
    return (int) ($course['is_active'] ?? 0) === 1;
}

function getActiveCourseBySlug(string $slug): ?array
{
    $course = getCourseBySlug($slug);
    return ($course && isCourseActive($course)) ? $course : null;
}

function getActiveCourseById(int $id): ?array
{
    $course = getCourseById($id);
    return ($course && isCourseActive($course)) ? $course : null;
}

function parseCheckboxFlag(mixed $value): int
{
    if (is_array($value)) {
        $value = end($value);
    }

    return ($value === '1' || $value === 1 || $value === true || $value === 'on') ? 1 : 0;
}

function getCourseById(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM courses WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $course = $stmt->fetch();
    return $course ?: null;
}

function getLessonsByCourse(int $courseId): array
{
    $stmt = db()->prepare('SELECT * FROM lessons WHERE course_id = ? AND is_published = 1 ORDER BY sort_order ASC, id ASC');
    $stmt->execute([$courseId]);
    return $stmt->fetchAll();
}

function categoryLabel(string $category): string
{
    return match ($category) {
        'foundation' => 'พื้นฐาน',
        'hsk' => 'HSK',
        'exam_prep' => 'ติวสอบ',
        default => 'ทั่วไป',
    };
}

function levelBadge(string $level): string
{
    return match ($level) {
        'beginner' => 'เริ่มต้น',
        'intermediate' => 'ปานกลาง',
        'advanced' => 'ขั้นสูง',
        default => 'ทุกระดับ',
    };
}

function adminPagination(int $totalItems, int $perPage, int $page): array
{
    $perPage = max(1, $perPage);
    $totalPages = max(1, (int) ceil($totalItems / $perPage));
    $page = min(max(1, $page), $totalPages);

    return [
        'total' => max(0, $totalItems),
        'per_page' => $perPage,
        'page' => $page,
        'total_pages' => $totalPages,
        'offset' => ($page - 1) * $perPage,
    ];
}

function renderAdminPagination(array $pager, string $basePath, array $query = []): void
{
    if (($pager['total_pages'] ?? 1) <= 1) {
        return;
    }

    $current = (int) ($pager['page'] ?? 1);
    $totalPages = (int) ($pager['total_pages'] ?? 1);
    $buildUrl = static function (int $page) use ($basePath, $query): string {
        $params = array_merge($query, ['page' => $page]);
        $qs = http_build_query($params);

        return APP_URL . $basePath . ($qs !== '' ? '?' . $qs : '');
    };
    ?>
    <nav class="admin-pagination" aria-label="เปลี่ยนหน้า">
        <?php if ($current > 1): ?>
        <a href="<?= e($buildUrl($current - 1)) ?>" class="admin-pagination-link" aria-label="หน้าก่อนหน้า">‹</a>
        <?php endif; ?>
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a
            href="<?= e($buildUrl($i)) ?>"
            class="admin-pagination-link<?= $i === $current ? ' is-active' : '' ?>"
            <?= $i === $current ? 'aria-current="page"' : '' ?>
        ><?= $i ?></a>
        <?php endfor; ?>
        <?php if ($current < $totalPages): ?>
        <a href="<?= e($buildUrl($current + 1)) ?>" class="admin-pagination-link" aria-label="หน้าถัดไป">›</a>
        <?php endif; ?>
    </nav>
    <?php
}
