<?php

declare(strict_types=1);

function exportDatabaseSql(): string
{
    $pdo = db();
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    $sql = "-- Wenxin LMS backup\n-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
    $sql .= "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n";

    foreach ($tables as $table) {
        $create = $pdo->query('SHOW CREATE TABLE `' . str_replace('`', '``', $table) . '`')->fetch();
        $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
        $sql .= ($create['Create Table'] ?? '') . ";\n\n";

        $rows = $pdo->query('SELECT * FROM `' . str_replace('`', '``', $table) . '`');
        while ($row = $rows->fetch(PDO::FETCH_ASSOC)) {
            $cols = array_map(static fn ($c) => '`' . str_replace('`', '``', $c) . '`', array_keys($row));
            $vals = array_map(static function ($v) use ($pdo) {
                if ($v === null) {
                    return 'NULL';
                }
                return $pdo->quote((string) $v);
            }, array_values($row));
            $sql .= 'INSERT INTO `' . $table . '` (' . implode(', ', $cols) . ') VALUES (' . implode(', ', $vals) . ");\n";
        }
        $sql .= "\n";
    }

    $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
    return $sql;
}

function backupStorageDir(): string
{
    $dir = BASE_PATH . '/storage/backups';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    return $dir;
}

function backupFilename(): string
{
    return 'wenxin_lms_' . date('Y-m-d_His') . '.sql';
}

function isValidBackupFilename(string $filename): bool
{
    return (bool) preg_match('/^wenxin_lms_\d{4}-\d{2}-\d{2}_\d{6}\.sql$/', $filename);
}

function createBackupFile(): array
{
    $filename = backupFilename();
    $path = backupStorageDir() . '/' . $filename;
    file_put_contents($path, exportDatabaseSql());

    return [
        'filename' => $filename,
        'path' => $path,
        'size' => (int) filesize($path),
        'created_at' => (int) filemtime($path),
    ];
}

function listBackupFiles(): array
{
    $files = glob(backupStorageDir() . '/wenxin_lms_*.sql') ?: [];
    usort($files, static fn ($a, $b) => filemtime($b) <=> filemtime($a));

    $list = [];
    foreach ($files as $path) {
        $list[] = [
            'filename' => basename($path),
            'size' => (int) filesize($path),
            'created_at' => (int) filemtime($path),
        ];
    }

    return $list;
}

function getBackupFilePath(string $filename): ?string
{
    if (!isValidBackupFilename($filename)) {
        return null;
    }

    $path = backupStorageDir() . '/' . $filename;

    return is_file($path) ? $path : null;
}

function deleteBackupFile(string $filename): bool
{
    $path = getBackupFilePath($filename);
    if ($path === null) {
        return false;
    }

    return unlink($path);
}

function formatBackupSize(int $bytes): string
{
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    }
    if ($bytes >= 1024) {
        return number_format($bytes / 1024, 1) . ' KB';
    }

    return $bytes . ' B';
}

function pruneOldBackups(int $keep = 30): void
{
    $files = listBackupFiles();
    foreach (array_slice($files, $keep) as $file) {
        deleteBackupFile($file['filename']);
    }
}
