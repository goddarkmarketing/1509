<?php
/**
 * แก้ข้อความภาษาไทยเพี้ยน (import ผิด charset)
 * รันครั้งเดียว: http://localhost/LMS/database/fix_encoding.php
 * หรือ: php database/fix_encoding.php
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/database.php';

header('Content-Type: text/html; charset=UTF-8');

$tables = [
    'site_settings' => ['setting_value'],
    'courses' => ['title', 'subtitle', 'description', 'highlights'],
    'lessons' => ['title', 'description'],
    'admin_users' => ['full_name'],
    'payments' => ['student_name', 'note'],
];

function fixMojibake(string $value): string
{
    if ($value === '' || mb_check_encoding($value, 'UTF-8') === false) {
        return $value;
    }
    // UTF-8 ที่ถูกเก็บผ่าน latin1 connection
    $fixed = @mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1');
    if ($fixed !== false && mb_strlen($fixed) > 0 && preg_match('/[\x{0E00}-\x{0E7F}]/u', $fixed)) {
        return $fixed;
    }
    return $value;
}

$pdo = db();
$updated = 0;

foreach ($tables as $table => $columns) {
    $rows = $pdo->query("SELECT * FROM {$table}")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
        $pk = array_key_first($row);
        $id = $row[$pk];
        $sets = [];
        $params = [];
        foreach ($columns as $col) {
            if (!isset($row[$col]) || $row[$col] === null) {
                continue;
            }
            $fixed = fixMojibake((string) $row[$col]);
            if ($fixed !== $row[$col]) {
                $sets[] = "{$col} = ?";
                $params[] = $fixed;
            }
        }
        if ($sets) {
            $params[] = $id;
            $sql = "UPDATE {$table} SET " . implode(', ', $sets) . " WHERE {$pk} = ?";
            $pdo->prepare($sql)->execute($params);
            $updated++;
        }
    }
}

echo '<!DOCTYPE html><html lang="th"><meta charset="UTF-8">';
echo '<body style="font-family:sans-serif;padding:2rem">';
echo '<h1>แก้ encoding เรียบร้อย</h1>';
echo '<p>อัปเดต ' . (int) $updated . ' แถว</p>';
require_once dirname(__DIR__) . '/includes/functions.php';
$tagline = getSetting('site_tagline');
echo '<p>ทดสอบ: ' . htmlspecialchars($tagline, ENT_QUOTES, 'UTF-8') . '</p>';
echo '<p><a href="../public/index.php">กลับหน้าแรก</a></p>';
echo '</body></html>';
