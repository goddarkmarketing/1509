<?php
declare(strict_types=1);

require_once __DIR__ . '/access.php';

function getGamesByCourse(int $courseId, bool $publishedOnly = true): array
{
    try {
        $sql = 'SELECT * FROM course_games WHERE course_id = ?';
        if ($publishedOnly) {
            $sql .= ' AND is_published = 1';
        }
        $sql .= ' ORDER BY sort_order ASC, id ASC';
        $stmt = db()->prepare($sql);
        $stmt->execute([$courseId]);
        return $stmt->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
}

function getGameById(int $gameId): ?array
{
    try {
        $stmt = db()->prepare('SELECT * FROM course_games WHERE id = ? LIMIT 1');
        $stmt->execute([$gameId]);
        $row = $stmt->fetch();
        return $row ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

function studentCanPlayGame(int $studentId, array $game): bool
{
    return studentHasCourseAccess($studentId, (int) ($game['course_id'] ?? 0));
}

function normalizeGameUrl(string $url): ?string
{
    $url = trim($url);
    if ($url === '' || !preg_match('~^https?://~i', $url)) {
        return null;
    }
    return $url;
}

function gamePlayUrl(int $gameId): string
{
    return APP_URL . '/public/game.php?game_id=' . $gameId;
}
