<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

function examTablesReady(): bool
{
    try {
        db()->query('SELECT 1 FROM exam_packs LIMIT 1');
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

function examLevelLabel(string $level): string
{
    return match ($level) {
        'beginner' => 'เริ่มต้น',
        'advanced' => 'ขั้นสูง',
        default => 'กลาง',
    };
}

/** CSS tone key for subject-colored covers */
function examSubjectTone(string $subject): string
{
    $s = mb_strtolower(trim($subject), 'UTF-8');
    if (str_contains($s, 'คณิต') || str_contains($s, 'math')) {
        return 'math';
    }
    if (str_contains($s, 'อังกฤษ') || str_contains($s, 'english')) {
        return 'english';
    }
    if (str_contains($s, 'ไทย') || str_contains($s, 'thai')) {
        return 'thai';
    }
    if (str_contains($s, 'วิทย์') || str_contains($s, 'science')) {
        return 'science';
    }
    if (str_contains($s, 'จีน') || str_contains($s, 'hsk') || str_contains($s, 'chinese')) {
        return 'chinese';
    }
    return 'default';
}

function examCoverUrl(?array $pack): string
{
    $custom = trim((string) ($pack['cover_image'] ?? ''));
    if ($custom !== '') {
        if (str_starts_with($custom, 'http://') || str_starts_with($custom, 'https://') || str_starts_with($custom, '/')) {
            return $custom;
        }
        return asset($custom);
    }
    return '';
}

/** Modern card media: custom image or subject-toned panel */
function examPackMediaHtml(array $pack, string $href = ''): string
{
    $cover = examCoverUrl($pack);
    $tone = examSubjectTone((string) ($pack['subject'] ?? ''));
    $subject = (string) ($pack['subject'] ?? 'ชุดข้อสอบ');
    $level = examLevelLabel((string) ($pack['level'] ?? 'intermediate'));
    $qCount = (int) ($pack['question_count'] ?? 0);

    if ($cover !== '') {
        $inner = '<img src="' . e($cover) . '" alt="" loading="lazy" width="400" height="225">';
        $media = '<div class="ui-exam-media ui-exam-media--image">' . $inner . '</div>';
    } else {
        $media = '<div class="ui-exam-media ui-exam-media--' . e($tone) . '">'
            . '<div class="ui-exam-media-glow" aria-hidden="true"></div>'
            . '<p class="ui-exam-media-kicker">จำลองสนามสอบ</p>'
            . '<p class="ui-exam-media-subject">' . e($subject) . '</p>'
            . '<p class="ui-exam-media-meta">' . e($level)
            . ($qCount > 0 ? ' · ' . $qCount . ' ข้อ' : '')
            . '</p>'
            . '</div>';
    }

    if ($href !== '') {
        return '<a class="ui-card-media-link" href="' . e($href) . '" aria-label="' . e((string) ($pack['title'] ?? '')) . '">' . $media . '</a>';
    }
    return $media;
}

function getExamPacks(bool $publishedOnly = true, ?string $subject = null): array
{
    if (!examTablesReady()) {
        return [];
    }
    try {
        $sql = 'SELECT ep.*, (SELECT COUNT(*) FROM exam_questions eq WHERE eq.exam_pack_id = ep.id) AS question_count
                FROM exam_packs ep WHERE 1=1';
        $params = [];
        if ($publishedOnly) {
            $sql .= ' AND ep.is_published = 1';
        }
        if ($subject !== null && $subject !== '') {
            $sql .= ' AND ep.subject = ?';
            $params[] = $subject;
        }
        $sql .= ' ORDER BY ep.sort_order ASC, ep.id DESC';
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
}

function getExamSubjects(bool $publishedOnly = true): array
{
    if (!examTablesReady()) {
        return [];
    }
    try {
        $sql = 'SELECT DISTINCT subject FROM exam_packs';
        if ($publishedOnly) {
            $sql .= ' WHERE is_published = 1';
        }
        $sql .= ' ORDER BY subject ASC';
        return db()->query($sql)->fetchAll(PDO::FETCH_COLUMN) ?: [];
    } catch (Throwable $e) {
        return [];
    }
}

function getExamPackById(int $id): ?array
{
    if ($id <= 0 || !examTablesReady()) {
        return null;
    }
    $stmt = db()->prepare('
        SELECT ep.*, (SELECT COUNT(*) FROM exam_questions eq WHERE eq.exam_pack_id = ep.id) AS question_count
        FROM exam_packs ep WHERE ep.id = ? LIMIT 1
    ');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function getExamPackBySlug(string $slug): ?array
{
    $slug = trim($slug);
    if ($slug === '' || !examTablesReady()) {
        return null;
    }
    $stmt = db()->prepare('
        SELECT ep.*, (SELECT COUNT(*) FROM exam_questions eq WHERE eq.exam_pack_id = ep.id) AS question_count
        FROM exam_packs ep WHERE ep.slug = ? LIMIT 1
    ');
    $stmt->execute([$slug]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function getActiveExamPackById(int $id): ?array
{
    $pack = getExamPackById($id);
    if (!$pack || !(int) ($pack['is_published'] ?? 0)) {
        return null;
    }
    return $pack;
}

function getExamQuestions(int $examPackId): array
{
    if ($examPackId <= 0 || !examTablesReady()) {
        return [];
    }
    $stmt = db()->prepare('SELECT * FROM exam_questions WHERE exam_pack_id = ? ORDER BY sort_order ASC, id ASC');
    $stmt->execute([$examPackId]);
    return $stmt->fetchAll();
}

function getExamQuestionById(int $questionId): ?array
{
    if ($questionId <= 0) {
        return null;
    }
    $stmt = db()->prepare('SELECT * FROM exam_questions WHERE id = ? LIMIT 1');
    $stmt->execute([$questionId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function parseExamQuestionOptions(array $question): array
{
    $opts = json_decode($question['options_json'] ?? '{}', true);
    return is_array($opts) ? $opts : [];
}

function studentExamPurchaseStatus(int $studentId, int $examPackId): ?string
{
    if ($studentId <= 0 || $examPackId <= 0 || !examTablesReady()) {
        return null;
    }
    $stmt = db()->prepare('SELECT status FROM exam_purchases WHERE student_id = ? AND exam_pack_id = ? LIMIT 1');
    $stmt->execute([$studentId, $examPackId]);
    $status = $stmt->fetchColumn();
    return $status !== false ? (string) $status : null;
}

function studentHasExamAccess(int $studentId, int $examPackId): bool
{
    $status = studentExamPurchaseStatus($studentId, $examPackId);
    return $status === 'active';
}

function grantExamPurchases(int $studentId, array $examPackIds, string $status = 'active', ?int $paymentId = null): void
{
    if ($studentId <= 0 || !examTablesReady()) {
        return;
    }
    $allowed = ['pending', 'active', 'cancelled'];
    if (!in_array($status, $allowed, true)) {
        $status = 'active';
    }
    $examPackIds = array_values(array_unique(array_filter(array_map('intval', $examPackIds))));
    if (!$examPackIds) {
        return;
    }

    $check = db()->prepare('SELECT id, status FROM exam_purchases WHERE student_id = ? AND exam_pack_id = ? LIMIT 1');
    $insert = db()->prepare('
        INSERT INTO exam_purchases (student_id, exam_pack_id, payment_id, status)
        VALUES (?, ?, ?, ?)
    ');
    $update = db()->prepare('UPDATE exam_purchases SET status = ?, payment_id = COALESCE(?, payment_id) WHERE id = ?');

    $rank = static fn(string $s): int => match ($s) {
        'active' => 3,
        'pending' => 2,
        'cancelled' => 1,
        default => 0,
    };

    foreach ($examPackIds as $packId) {
        if ($packId <= 0) {
            continue;
        }
        $check->execute([$studentId, $packId]);
        $existing = $check->fetch();
        if ($existing) {
            $current = (string) ($existing['status'] ?? 'pending');
            if ($status === 'pending' && $rank($current) > $rank('pending')) {
                continue;
            }
            $update->execute([$status, $paymentId, (int) $existing['id']]);
            continue;
        }
        $insert->execute([$studentId, $packId, $paymentId, $status]);
    }
}

function getStudentExamPurchases(int $studentId): array
{
    if ($studentId <= 0 || !examTablesReady()) {
        return [];
    }
    try {
        $stmt = db()->prepare('
            SELECT ep.*, p.status AS purchase_status, p.purchased_at,
                   (SELECT COUNT(*) FROM exam_questions eq WHERE eq.exam_pack_id = ep.id) AS question_count
            FROM exam_purchases p
            JOIN exam_packs ep ON ep.id = p.exam_pack_id
            WHERE p.student_id = ? AND p.status IN ("pending", "active")
            ORDER BY p.purchased_at DESC
        ');
        $stmt->execute([$studentId]);
        return $stmt->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
}

function getBestExamAttempt(int $studentId, int $examPackId): ?array
{
    if ($studentId <= 0 || $examPackId <= 0) {
        return null;
    }
    $stmt = db()->prepare('
        SELECT * FROM exam_attempts
        WHERE student_id = ? AND exam_pack_id = ?
        ORDER BY score DESC, completed_at DESC
        LIMIT 1
    ');
    $stmt->execute([$studentId, $examPackId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function gradeExamAttempt(array $questions, array $answers): array
{
    $correct = 0;
    $total = count($questions);
    $detail = [];
    foreach ($questions as $q) {
        $qid = (int) $q['id'];
        $chosen = $answers[$qid] ?? '';
        $isCorrect = $chosen !== '' && $chosen === ($q['correct_key'] ?? '');
        if ($isCorrect) {
            $correct++;
        }
        $detail[$qid] = [
            'chosen' => $chosen,
            'correct_key' => $q['correct_key'] ?? '',
            'is_correct' => $isCorrect,
            'explanation' => $q['explanation'] ?? null,
        ];
    }
    $score = $total > 0 ? (int) round(($correct / $total) * 100) : 0;
    return ['score' => $score, 'correct' => $correct, 'total' => $total, 'detail' => $detail];
}

function saveExamAttempt(
    int $studentId,
    int $examPackId,
    int $score,
    int $correct,
    int $total,
    bool $passed,
    array $answers,
    int $timeSpentSeconds = 0,
    ?string $startedAt = null
): int {
    $stmt = db()->prepare('
        INSERT INTO exam_attempts
            (student_id, exam_pack_id, score, correct_count, total_questions, passed, answers_json, time_spent_seconds, started_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ');
    $stmt->execute([
        $studentId,
        $examPackId,
        $score,
        $correct,
        $total,
        $passed ? 1 : 0,
        json_encode($answers, JSON_UNESCAPED_UNICODE),
        max(0, $timeSpentSeconds),
        $startedAt,
    ]);
    return (int) db()->lastInsertId();
}

function filterValidExamPackIds(array $ids): array
{
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
    if (!$ids || !examTablesReady()) {
        return [];
    }
    try {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = db()->prepare("SELECT id FROM exam_packs WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    } catch (Throwable $e) {
        return [];
    }
}

function parseExamPackIdsFromNote(?string $note): array
{
    if (!$note || !preg_match('/exam_pack_ids:([\d,]+)/', $note, $m)) {
        return [];
    }
    return array_values(array_filter(array_map('intval', explode(',', $m[1]))));
}

function getPaymentExamPackIds(int $paymentId): array
{
    if ($paymentId <= 0) {
        return [];
    }
    try {
        $stmt = db()->prepare('SELECT exam_pack_id FROM payment_items WHERE payment_id = ? AND exam_pack_id IS NOT NULL ORDER BY id ASC');
        $stmt->execute([$paymentId]);
        return array_values(array_filter(array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN))));
    } catch (Throwable $e) {
        return [];
    }
}
