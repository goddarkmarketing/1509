<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

const FEEDBACK_DIR = BASE_PATH . '/feedback';
const FEEDBACK_IMAGES_DIR = FEEDBACK_DIR . '/images';
const FEEDBACK_JSON_PATH = FEEDBACK_DIR . '/feedback.json';
const FEEDBACK_MD_PATH = FEEDBACK_DIR . '/FEEDBACK.md';
const FEEDBACK_CONFIG_PATH = FEEDBACK_DIR . '/config.json';

function feedbackEnsureStorage(): void
{
    foreach ([FEEDBACK_DIR, FEEDBACK_IMAGES_DIR] as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
    $deny = FEEDBACK_DIR . '/.htaccess';
    if (!is_file($deny)) {
        @file_put_contents($deny, "Options -Indexes\nRequire all denied\n");
    }
    $imgDeny = FEEDBACK_IMAGES_DIR . '/.htaccess';
    if (!is_file($imgDeny)) {
        @file_put_contents($imgDeny, "Options -Indexes\nRequire all denied\n");
    }
    if (!is_file(FEEDBACK_JSON_PATH)) {
        file_put_contents(FEEDBACK_JSON_PATH, json_encode(['items' => []], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    if (!is_file(FEEDBACK_CONFIG_PATH)) {
        $bootstrap = [
            'enabled' => true,
            'token' => bin2hex(random_bytes(16)),
            'password_hash' => '',
            'updated_at' => date('c'),
        ];
        file_put_contents(
            FEEDBACK_CONFIG_PATH,
            json_encode($bootstrap, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }
}

function feedbackLoadConfig(): array
{
    feedbackEnsureStorage();
    $raw = @file_get_contents(FEEDBACK_CONFIG_PATH);
    $data = $raw ? json_decode($raw, true) : null;
    if (!is_array($data)) {
        $data = ['enabled' => true, 'token' => '', 'password_hash' => ''];
    }
    $data['enabled'] = (bool) ($data['enabled'] ?? true);
    $data['token'] = (string) ($data['token'] ?? '');
    $data['password_hash'] = (string) ($data['password_hash'] ?? '');
    if ($data['token'] === '') {
        $data['token'] = bin2hex(random_bytes(16));
        feedbackSaveConfig($data);
    }
    return $data;
}

function feedbackSaveConfig(array $config): void
{
    if (!is_dir(FEEDBACK_DIR)) {
        mkdir(FEEDBACK_DIR, 0755, true);
    }
    $config['updated_at'] = date('c');
    file_put_contents(
        FEEDBACK_CONFIG_PATH,
        json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    );
}

function feedbackCategories(): array
{
    return [
        'text' => 'ข้อความ',
        'image' => 'รูปภาพ',
        'layout' => 'Layout',
        'color' => 'สี',
        'function' => 'ฟังก์ชัน',
        'mobile' => 'Mobile',
        'other' => 'อื่น ๆ',
    ];
}

function feedbackPriorities(): array
{
    return [
        'low' => 'ต่ำ',
        'medium' => 'ปานกลาง',
        'high' => 'สูง',
    ];
}

function feedbackStatuses(): array
{
    return [
        'pending' => 'รอดำเนินการ',
        'in-progress' => 'กำลังแก้',
        'completed' => 'เสร็จแล้ว',
        'rejected' => 'ปฏิเสธ',
    ];
}

function feedbackIsPreviewAuthorized(): bool
{
    if (isAdminLoggedIn()) {
        return true;
    }
    $config = feedbackLoadConfig();
    if (empty($config['enabled'])) {
        return false;
    }
    $sessionToken = (string) ($_SESSION['feedback_preview_token'] ?? '');
    $configToken = (string) ($config['token'] ?? '');
    if ($sessionToken !== '' && $configToken !== '' && hash_equals($configToken, $sessionToken)) {
        return true;
    }
    return false;
}

function feedbackActivatePreviewFromRequest(): bool
{
    $config = feedbackLoadConfig();
    if (empty($config['enabled'])) {
        return false;
    }
    $token = trim((string) ($_GET['review'] ?? $_GET['feedback_token'] ?? ''));
    if ($token === '' || $config['token'] === '' || !hash_equals($config['token'], $token)) {
        return false;
    }
    if (!empty($config['password_hash']) && empty($_SESSION['feedback_preview_unlocked'])) {
        return false;
    }
    $_SESSION['feedback_preview_token'] = $config['token'];
    return true;
}

function feedbackIsAdminArea(): bool
{
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    return str_contains($script, '/admin/');
}

/** Feedback tool is admin-backend only (never on public site). */
function feedbackShouldInjectClientTool(): bool
{
    if (!feedbackIsAdminArea()) {
        return false;
    }

    feedbackEnsureStorage();
    $config = feedbackLoadConfig();
    if (empty($config['enabled'])) {
        return false;
    }

    if (!isAdminLoggedIn()) {
        return false;
    }

    if (!empty($_GET['review']) || !empty($_GET['feedback_token'])) {
        feedbackActivatePreviewFromRequest();
    }

    if (isset($_GET['feedback'])) {
        $_SESSION['feedback_admin_preview'] = ((string) $_GET['feedback'] !== '0');
    }

    if (!empty($_SESSION['feedback_admin_preview'])) {
        return true;
    }

    // Review link unlock also enables admin feedback after login
    return feedbackIsPreviewAuthorized() && !empty($_SESSION['feedback_preview_token']);
}

function feedbackAdminStartUrl(string $token = ''): string
{
    $config = feedbackLoadConfig();
    $t = $token !== '' ? $token : (string) ($config['token'] ?? '');
    $url = APP_URL . '/admin/dashboard.php?feedback=1';
    if ($t !== '') {
        $url .= '&review=' . rawurlencode($t);
    }
    return $url;
}

function feedbackClientBootstrap(): array
{
    return [
        'enabled' => true,
        'submitUrl' => APP_URL . '/public/api/feedback_submit.php',
        'csrf' => csrfToken(),
        'appUrl' => APP_URL,
        'categories' => feedbackCategories(),
        'priorities' => feedbackPriorities(),
        'html2canvasUrl' => 'https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js',
    ];
}

function feedbackLoadItems(): array
{
    feedbackEnsureStorage();
    $raw = @file_get_contents(FEEDBACK_JSON_PATH);
    $data = $raw ? json_decode($raw, true) : null;
    if (!is_array($data)) {
        return [];
    }
    $items = $data['items'] ?? $data;
    return is_array($items) ? array_values($items) : [];
}

function feedbackSaveItems(array $items): void
{
    feedbackEnsureStorage();
    $payload = [
        'updatedAt' => date('c'),
        'items' => array_values($items),
    ];
    file_put_contents(
        FEEDBACK_JSON_PATH,
        json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    );
    feedbackWriteMarkdown($items);
}

function feedbackNextId(array $items): string
{
    $max = 0;
    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }
        $id = (string) ($item['id'] ?? '');
        if (preg_match('/^FB-(\d+)$/i', $id, $m)) {
            $max = max($max, (int) $m[1]);
        }
    }
    return sprintf('FB-%03d', $max + 1);
}

function feedbackSanitizeText(string $text): string
{
    $text = preg_replace('/\b(?:password|passwd|pwd|token|api[_-]?key|secret|authorization)\b\s*[:=]\s*\S+/iu', '[redacted]', $text) ?? $text;
    $text = preg_replace('/\b[A-Za-z0-9_-]{24,}\b/', '[redacted]', $text) ?? $text;
    return trim($text);
}

function feedbackDecodeScreenshotDataUrl(string $dataUrl): ?string
{
    if (!preg_match('#^data:image/png;base64,(.+)$#s', $dataUrl, $m)) {
        return null;
    }
    $bin = base64_decode($m[1], true);
    if ($bin === false || strlen($bin) < 100) {
        return null;
    }
    if (strlen($bin) > 8 * 1024 * 1024) {
        return null;
    }
    return $bin;
}

function feedbackCreateItem(array $input, ?string $screenshotBinary = null): array
{
    feedbackEnsureStorage();
    $items = feedbackLoadItems();
    $id = feedbackNextId($items);

    $screenshotRel = '';
    if ($screenshotBinary !== null) {
        $filename = $id . '.png';
        $path = FEEDBACK_IMAGES_DIR . '/' . $filename;
        if (file_put_contents($path, $screenshotBinary) === false) {
            throw new RuntimeException('บันทึกภาพไม่สำเร็จ');
        }
        $screenshotRel = 'images/' . $filename;
    }

    $item = [
        'id' => $id,
        'page' => feedbackSanitizeText((string) ($input['page'] ?? '')),
        'url' => feedbackSanitizeText((string) ($input['url'] ?? '')),
        'route' => feedbackSanitizeText((string) ($input['route'] ?? '')),
        'section' => feedbackSanitizeText((string) ($input['section'] ?? '')),
        'feedbackId' => feedbackSanitizeText((string) ($input['feedbackId'] ?? '')),
        'selector' => feedbackSanitizeText((string) ($input['selector'] ?? '')),
        'elementTag' => feedbackSanitizeText((string) ($input['elementTag'] ?? '')),
        'elementText' => feedbackSanitizeText(mb_substr((string) ($input['elementText'] ?? ''), 0, 500)),
        'componentName' => feedbackSanitizeText((string) ($input['componentName'] ?? '')),
        'boundingRect' => [
            'x' => (float) ($input['boundingRect']['x'] ?? 0),
            'y' => (float) ($input['boundingRect']['y'] ?? 0),
            'width' => (float) ($input['boundingRect']['width'] ?? 0),
            'height' => (float) ($input['boundingRect']['height'] ?? 0),
        ],
        'comment' => feedbackSanitizeText((string) ($input['comment'] ?? '')),
        'category' => (string) ($input['category'] ?? 'other'),
        'priority' => (string) ($input['priority'] ?? 'medium'),
        'clientName' => feedbackSanitizeText((string) ($input['clientName'] ?? '')),
        'status' => 'pending',
        'screenshot' => $screenshotRel,
        'viewport' => [
            'width' => (int) ($input['viewport']['width'] ?? 0),
            'height' => (int) ($input['viewport']['height'] ?? 0),
        ],
        'scrollPosition' => [
            'x' => (int) ($input['scrollPosition']['x'] ?? 0),
            'y' => (int) ($input['scrollPosition']['y'] ?? 0),
        ],
        'createdAt' => date('c'),
    ];

    if (!isset(feedbackCategories()[$item['category']])) {
        $item['category'] = 'other';
    }
    if (!isset(feedbackPriorities()[$item['priority']])) {
        $item['priority'] = 'medium';
    }
    if ($item['comment'] === '') {
        throw new InvalidArgumentException('กรุณากรอกรายละเอียดที่ต้องการแก้ไข');
    }

    $items[] = $item;
    feedbackSaveItems($items);
    return $item;
}

function feedbackUpdateStatus(string $id, string $status): bool
{
    if (!isset(feedbackStatuses()[$status])) {
        return false;
    }
    $items = feedbackLoadItems();
    $found = false;
    foreach ($items as &$item) {
        if (($item['id'] ?? '') === $id) {
            $item['status'] = $status;
            $item['updatedAt'] = date('c');
            $found = true;
            break;
        }
    }
    unset($item);
    if ($found) {
        feedbackSaveItems($items);
    }
    return $found;
}

function feedbackDeleteItem(string $id): bool
{
    $items = feedbackLoadItems();
    $kept = [];
    $deleted = null;
    foreach ($items as $item) {
        if (($item['id'] ?? '') === $id) {
            $deleted = $item;
            continue;
        }
        $kept[] = $item;
    }
    if ($deleted === null) {
        return false;
    }
    $shot = (string) ($deleted['screenshot'] ?? '');
    if ($shot !== '' && str_starts_with($shot, 'images/')) {
        $path = FEEDBACK_DIR . '/' . $shot;
        if (is_file($path)) {
            @unlink($path);
        }
    }
    feedbackSaveItems($kept);
    return true;
}

function feedbackWriteMarkdown(array $items): void
{
    $cats = feedbackCategories();
    $pris = feedbackPriorities();
    $stats = feedbackStatuses();
    $lines = ["# รายการแก้ไขจากลูกค้า", ''];
    if (!$items) {
        $lines[] = '_ยังไม่มีรายการ_';
        $lines[] = '';
    }
    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }
        $id = (string) ($item['id'] ?? '');
        $lines[] = '## ' . $id;
        $lines[] = '- หน้า: ' . (string) ($item['page'] ?? '');
        $lines[] = '- URL: ' . (string) ($item['url'] ?? '');
        $lines[] = '- ส่วน: ' . ((string) ($item['section'] ?? '') !== '' ? $item['section'] : ((string) ($item['feedbackId'] ?? '') ?: '-'));
        $lines[] = '- Selector: `' . (string) ($item['selector'] ?? '') . '`';
        $lines[] = '- ประเภท: ' . ($cats[$item['category'] ?? ''] ?? (string) ($item['category'] ?? ''));
        $lines[] = '- ความสำคัญ: ' . ($pris[$item['priority'] ?? ''] ?? (string) ($item['priority'] ?? ''));
        $lines[] = '- สถานะ: ' . ($stats[$item['status'] ?? ''] ?? (string) ($item['status'] ?? ''));
        if (!empty($item['clientName'])) {
            $lines[] = '- ผู้แจ้ง: ' . (string) $item['clientName'];
        }
        $lines[] = '';
        $lines[] = '### รายละเอียด';
        $lines[] = (string) ($item['comment'] ?? '');
        $lines[] = '';
        $shot = (string) ($item['screenshot'] ?? '');
        if ($shot !== '') {
            $lines[] = '### รูปภาพ';
            $lines[] = '![' . $id . '](./' . $shot . ')';
            $lines[] = '';
        }
    }
    file_put_contents(FEEDBACK_MD_PATH, implode("\n", $lines) . "\n");
}

function feedbackFilterItems(array $items, array $filters): array
{
    $page = trim((string) ($filters['page'] ?? ''));
    $category = trim((string) ($filters['category'] ?? ''));
    $priority = trim((string) ($filters['priority'] ?? ''));
    $status = trim((string) ($filters['status'] ?? ''));

    return array_values(array_filter($items, static function ($item) use ($page, $category, $priority, $status) {
        if (!is_array($item)) {
            return false;
        }
        if ($page !== '' && stripos((string) ($item['page'] ?? ''), $page) === false && stripos((string) ($item['route'] ?? ''), $page) === false) {
            return false;
        }
        if ($category !== '' && ($item['category'] ?? '') !== $category) {
            return false;
        }
        if ($priority !== '' && ($item['priority'] ?? '') !== $priority) {
            return false;
        }
        if ($status !== '' && ($item['status'] ?? '') !== $status) {
            return false;
        }
        return true;
    }));
}

function feedbackScreenshotPath(string $id): ?string
{
    $path = FEEDBACK_IMAGES_DIR . '/' . $id . '.png';
    return is_file($path) ? $path : null;
}

function feedbackBuildZipPackage(): string
{
    feedbackEnsureStorage();
    $items = feedbackLoadItems();
    feedbackWriteMarkdown($items);

    if (!class_exists('ZipArchive')) {
        throw new RuntimeException('เซิร์ฟเวอร์ไม่รองรับ ZipArchive');
    }

    $tmp = sys_get_temp_dir() . '/feedback_package_' . bin2hex(random_bytes(4)) . '.zip';
    $zip = new ZipArchive();
    if ($zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new RuntimeException('สร้าง ZIP ไม่สำเร็จ');
    }
    $zip->addFile(FEEDBACK_JSON_PATH, 'feedback/feedback.json');
    if (is_file(FEEDBACK_MD_PATH)) {
        $zip->addFile(FEEDBACK_MD_PATH, 'feedback/FEEDBACK.md');
    }
    foreach ($items as $item) {
        $id = (string) ($item['id'] ?? '');
        $img = feedbackScreenshotPath($id);
        if ($img) {
            $zip->addFile($img, 'feedback/images/' . $id . '.png');
        }
    }
    $zip->close();
    return $tmp;
}

function feedbackRenderClientAssets(): void
{
    if (!feedbackShouldInjectClientTool()) {
        return;
    }
    $boot = feedbackClientBootstrap();
    $css = asset('css/feedback-tool.css') . '?v=1';
    $js = asset('js/feedback-tool.js') . '?v=1';
    echo '<link rel="stylesheet" href="' . e($css) . '">' . "\n";
    echo '<script>window.__FEEDBACK_BOOT__=' . json_encode($boot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ';</script>' . "\n";
    echo '<script src="' . e($js) . '" defer></script>' . "\n";
}

function feedbackReviewUrl(): string
{
    $config = feedbackLoadConfig();
    $token = (string) ($config['token'] ?? '');
    return APP_URL . '/public/review.php?review=' . rawurlencode($token);
}

function feedbackSafeAdminRedirect(string $target): string
{
    $target = trim($target);
    if ($target === '' || !str_starts_with($target, '/admin/')) {
        return '/admin/dashboard.php?feedback=1';
    }
    if (str_contains($target, '://') || str_contains($target, '..')) {
        return '/admin/dashboard.php?feedback=1';
    }
    return $target;
}
