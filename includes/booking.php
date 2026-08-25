<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

function courseTypeLabel(string $type): string
{
    return match ($type) {
        'live' => 'คลาสออนไลน์ (Live)',
        'hybrid' => 'เรียนสด + วิดีโอ',
        default => 'เรียนวิดีโอ',
    };
}

function isLiveCourse(array $course): bool
{
    $type = $course['course_type'] ?? 'recorded';
    return in_array($type, ['live', 'hybrid'], true);
}

function getCourseZoomUrl(array $course): ?string
{
    $url = trim((string) ($course['zoom_url'] ?? ''));
    return $url !== '' ? $url : null;
}

function courseLiveStartUrl(array $course, ?array $booking = null): string
{
    if ($booking && ($booking['status'] ?? '') === 'confirmed') {
        $zoom = getSessionZoomUrl($booking, $course);
        if ($zoom) {
            return $zoom;
        }
    }

    if ($booking) {
        return APP_URL . '/public/profile.php?tab=bookings';
    }

    return APP_URL . '/public/book.php?course=' . urlencode((string) ($course['slug'] ?? ''));
}

function courseLiveStartLabel(array $course, ?array $booking = null): string
{
    if ($booking && ($booking['status'] ?? '') === 'confirmed') {
        if (getSessionZoomUrl($booking, $course)) {
            return 'เข้า Zoom';
        }

        return 'ดูการจอง';
    }

    if ($booking) {
        return 'ดูการจอง';
    }

    return 'จองรอบเรียน';
}

function courseLiveStartOpensInNewTab(array $course, ?array $booking = null): bool
{
    if (!$booking || ($booking['status'] ?? '') !== 'confirmed') {
        return false;
    }

    $url = courseLiveStartUrl($course, $booking);

    return str_starts_with($url, 'http://') || str_starts_with($url, 'https://');
}

function getSessionZoomUrl(array $session, ?array $course = null): ?string
{
    $sessionUrl = trim((string) ($session['zoom_url'] ?? $session['session_zoom_url'] ?? ''));
    if ($sessionUrl !== '') {
        return $sessionUrl;
    }
    if ($course) {
        $courseUrl = trim((string) ($course['zoom_url'] ?? $course['course_zoom_url'] ?? ''));
        if ($courseUrl !== '') {
            return $courseUrl;
        }
        return getCourseZoomUrl($course);
    }
    return null;
}

function formatSessionRange(array $session): string
{
    $start = strtotime((string) ($session['starts_at'] ?? ''));
    $end = strtotime((string) ($session['ends_at'] ?? ''));
    if (!$start) {
        return '-';
    }
    $date = date('d/m/Y', $start);
    $time = date('H:i', $start);
    if ($end) {
        $time .= ' – ' . date('H:i', $end);
    }
    return "{$date} {$time} น.";
}

function formatSessionDateLabel(array $session): string
{
    $start = strtotime((string) ($session['starts_at'] ?? ''));
    if (!$start) {
        return '-';
    }
    $days = ['อา.', 'จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.', 'ส.'];
    return $days[(int) date('w', $start)] . ' ' . date('j/m/Y', $start);
}

function formatSessionTimeLabel(array $session): string
{
    $start = strtotime((string) ($session['starts_at'] ?? ''));
    $end = strtotime((string) ($session['ends_at'] ?? ''));
    if (!$start) {
        return '-';
    }
    $time = date('H:i', $start);
    if ($end) {
        $time .= ' – ' . date('H:i', $end);
    }
    return $time . ' น.';
}

function sessionSeatsStatus(int $remaining, int $capacity): string
{
    if ($capacity <= 0) {
        return 'ok';
    }
    if ($remaining <= 2) {
        return 'low';
    }
    if ($remaining / $capacity <= 0.2) {
        return 'low';
    }
    return 'ok';
}

function sessionImageUrl(array $session): ?string
{
    $url = trim((string) ($session['image_url'] ?? ''));
    if ($url === '') {
        return null;
    }
    if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
        return $url;
    }
    if (str_starts_with($url, 'uploads/sessions/')) {
        return APP_URL . '/public/download.php?file=' . urlencode(basename($url));
    }
    if (str_starts_with($url, 'uploads/courses/')) {
        return APP_URL . '/public/download.php?file=' . urlencode(basename($url));
    }
    return asset(ltrim($url, '/'));
}

function getSessionById(int $sessionId): ?array
{
    if ($sessionId <= 0) {
        return null;
    }
    try {
        $stmt = db()->prepare('
            SELECT cs.*, c.title AS course_title, c.slug AS course_slug, c.zoom_url AS course_zoom_url, c.course_type
            FROM course_sessions cs
            JOIN courses c ON c.id = cs.course_id
            WHERE cs.id = ?
            LIMIT 1
        ');
        $stmt->execute([$sessionId]);
        $row = $stmt->fetch();
        return $row ?: null;
    } catch (Throwable $e) {
        // Production may not have migrated yet; avoid blank page.
        return null;
    }
}

function getAvailableSessions(int $courseId, int $limit = 30): array
{
    try {
        $stmt = db()->prepare('
            SELECT cs.*, c.title AS course_title, c.zoom_url AS course_zoom_url
            FROM course_sessions cs
            JOIN courses c ON c.id = cs.course_id
            WHERE cs.course_id = ?
              AND cs.status = "scheduled"
              AND cs.starts_at > NOW()
              AND cs.booked_count < cs.capacity
            ORDER BY cs.starts_at ASC
            LIMIT ?
        ');
        $stmt->bindValue(1, $courseId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
}

function sessionHasCapacity(int $sessionId): bool
{
    try {
        $stmt = db()->prepare('
            SELECT id FROM course_sessions
            WHERE id = ? AND status = "scheduled" AND starts_at > NOW() AND booked_count < capacity
            LIMIT 1
        ');
        $stmt->execute([$sessionId]);
        return (bool) $stmt->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
}

function getCartSessionMap(): array
{
    if (empty($_SESSION['cart_session_map']) || !is_array($_SESSION['cart_session_map'])) {
        return [];
    }
    $map = [];
    foreach ($_SESSION['cart_session_map'] as $courseId => $sessionId) {
        $cid = (int) $courseId;
        $sid = (int) $sessionId;
        if ($cid > 0 && $sid > 0) {
            $map[$cid] = $sid;
        }
    }
    return $map;
}

function setCartSessionForCourse(int $courseId, int $sessionId): void
{
    if ($courseId <= 0 || $sessionId <= 0) {
        return;
    }
    $map = getCartSessionMap();
    $map[$courseId] = $sessionId;
    $_SESSION['cart_session_map'] = $map;
}

function removeCartSessionForCourse(int $courseId): void
{
    $map = getCartSessionMap();
    unset($map[$courseId]);
    $_SESSION['cart_session_map'] = $map;
}

function clearCartSessions(): void
{
    unset($_SESSION['cart_session_map']);
}

function getCartSessionDetails(): array
{
    $map = getCartSessionMap();
    $details = [];
    foreach ($map as $courseId => $sessionId) {
        $session = getSessionById((int) $sessionId);
        if ($session) {
            $details[(int) $courseId] = $session;
        }
    }
    return $details;
}

function appendSessionMapToNote(string $note, array $sessionMap): string
{
    if (!$sessionMap) {
        return $note;
    }
    $pairs = [];
    foreach ($sessionMap as $courseId => $sessionId) {
        $pairs[] = (int) $courseId . ':' . (int) $sessionId;
    }
    $meta = 'session_map:' . implode(',', $pairs);
    if ($note !== '' && str_contains($note, 'session_map:')) {
        return $note;
    }
    return $note !== '' ? $note . "\n" . $meta : $meta;
}

function parseSessionMapFromNote(?string $note): array
{
    if (!$note || !preg_match('/session_map:([\d:,]+)/', $note, $m)) {
        return [];
    }
    $map = [];
    foreach (explode(',', $m[1]) as $pair) {
        if (!str_contains($pair, ':')) {
            continue;
        }
        [$courseId, $sessionId] = array_map('intval', explode(':', $pair, 2));
        if ($courseId > 0 && $sessionId > 0) {
            $map[$courseId] = $sessionId;
        }
    }
    return $map;
}

function bookingLog(string $message): void
{
    $logDir = BASE_PATH . '/storage/logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    file_put_contents($logDir . '/booking.log', date('Y-m-d H:i:s') . ' ' . $message . "\n", FILE_APPEND);
}

function resolveSessionIdForBooking(int $courseId, int $sessionId): ?int
{
    if ($sessionId > 0) {
        $session = getSessionById($sessionId);
        if ($session) {
            return $sessionId;
        }
    }
    if ($courseId > 0) {
        $sessions = getAvailableSessions($courseId, 1);
        if ($sessions) {
            return (int) $sessions[0]['id'];
        }
        $stmt = db()->prepare('
            SELECT id FROM course_sessions
            WHERE course_id = ? AND status = "scheduled"
            ORDER BY starts_at ASC
            LIMIT 1
        ');
        $stmt->execute([$courseId]);
        $fallback = (int) ($stmt->fetchColumn() ?: 0);

        return $fallback > 0 ? $fallback : null;
    }

    return null;
}

function syncSessionBookingsFromPayment(int $paymentId): int
{
    if ($paymentId <= 0) {
        return 0;
    }

    try {
        if (getBookingsByPaymentId($paymentId)) {
            return 0;
        }

        $stmt = db()->prepare('SELECT * FROM payments WHERE id = ? LIMIT 1');
        $stmt->execute([$paymentId]);
        $payment = $stmt->fetch();
        if (!$payment) {
            return 0;
        }

        $sessionMap = parseSessionMapFromNote((string) ($payment['note'] ?? ''));
        if (!$sessionMap) {
            return 0;
        }

        require_once __DIR__ . '/checkout_flow.php';
        $studentId = findOrCreateStudent(
            (string) ($payment['student_name'] ?? ''),
            $payment['student_email'] ?? null,
            (string) ($payment['student_phone'] ?? '')
        );
        $status = ($payment['status'] ?? '') === 'verified' ? 'confirmed' : 'pending';
        createBookingsForPayment($paymentId, $studentId, $sessionMap, $status);

        return count(getBookingsByPaymentId($paymentId));
    } catch (Throwable $e) {
        bookingLog('sync payment #' . $paymentId . ': ' . $e->getMessage());

        return 0;
    }
}

function syncAllMissingSessionBookings(int $limit = 150): int
{
    try {
        $stmt = db()->query('
            SELECT id FROM payments
            WHERE status IN ("pending", "verified")
              AND note LIKE "%session_map:%"
            ORDER BY id DESC
            LIMIT ' . (int) $limit
        );
        $synced = 0;
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $paymentId) {
            $synced += syncSessionBookingsFromPayment((int) $paymentId);
        }

        return $synced;
    } catch (Throwable $e) {
        bookingLog('sync all: ' . $e->getMessage());

        return 0;
    }
}

function createSessionBooking(int $sessionId, int $studentId, ?int $paymentId, string $status = 'pending'): ?int
{
    if ($sessionId <= 0 || $studentId <= 0) {
        return null;
    }
    if (!in_array($status, ['pending', 'confirmed', 'cancelled'], true)) {
        $status = 'pending';
    }

    $check = db()->prepare('
        SELECT id, status FROM session_bookings
        WHERE session_id = ? AND student_id = ? AND status IN ("pending","confirmed")
        LIMIT 1
    ');
    $check->execute([$sessionId, $studentId]);
    $existing = $check->fetch();
    if ($existing) {
        if ($paymentId) {
            $upd = db()->prepare('UPDATE session_bookings SET payment_id = ?, status = ? WHERE id = ?');
            $upd->execute([$paymentId, $status, (int) $existing['id']]);
        }
        return (int) $existing['id'];
    }

    if ($status !== 'cancelled' && !sessionHasCapacity($sessionId)) {
        return null;
    }

    $ins = db()->prepare('
        INSERT INTO session_bookings (session_id, student_id, payment_id, status)
        VALUES (?, ?, ?, ?)
    ');
    $ins->execute([$sessionId, $studentId, $paymentId, $status]);

    if ($status === 'confirmed') {
        incrementSessionBookedCount($sessionId);
    }

    return (int) db()->lastInsertId();
}

function incrementSessionBookedCount(int $sessionId): void
{
    db()->prepare('
        UPDATE course_sessions SET booked_count = LEAST(booked_count + 1, capacity)
        WHERE id = ?
    ')->execute([$sessionId]);
}

function decrementSessionBookedCount(int $sessionId): void
{
    db()->prepare('
        UPDATE course_sessions
        SET booked_count = CASE WHEN booked_count > 0 THEN booked_count - 1 ELSE 0 END
        WHERE id = ?
    ')->execute([$sessionId]);
}

function confirmSessionBooking(int $bookingId): void
{
    $stmt = db()->prepare('SELECT session_id, status FROM session_bookings WHERE id = ? LIMIT 1');
    $stmt->execute([$bookingId]);
    $row = $stmt->fetch();
    if (!$row || ($row['status'] ?? '') === 'confirmed') {
        return;
    }
    db()->prepare('UPDATE session_bookings SET status = "confirmed" WHERE id = ?')->execute([$bookingId]);
    incrementSessionBookedCount((int) $row['session_id']);
}

function cancelSessionBooking(int $bookingId): void
{
    $stmt = db()->prepare('SELECT session_id, status FROM session_bookings WHERE id = ? LIMIT 1');
    $stmt->execute([$bookingId]);
    $row = $stmt->fetch();
    if (!$row) {
        return;
    }
    db()->prepare('UPDATE session_bookings SET status = "cancelled" WHERE id = ?')->execute([$bookingId]);
    if (($row['status'] ?? '') === 'confirmed') {
        decrementSessionBookedCount((int) $row['session_id']);
    }
}

function createBookingsForPayment(int $paymentId, int $studentId, array $sessionMap, string $status = 'pending'): void
{
    if (!$sessionMap || $paymentId <= 0 || $studentId <= 0) {
        return;
    }
    try {
        foreach ($sessionMap as $courseId => $sessionId) {
            $courseId = (int) $courseId;
            $sessionId = (int) $sessionId;
            if ($courseId <= 0 && $sessionId > 0) {
                $session = getSessionById($sessionId);
                $courseId = $session ? (int) $session['course_id'] : 0;
            }
            $resolvedSessionId = resolveSessionIdForBooking($courseId, $sessionId);
            if (!$resolvedSessionId) {
                bookingLog("payment #{$paymentId}: no session for course #{$courseId} (wanted session #{$sessionId})");
                continue;
            }
            $bookingId = createSessionBooking($resolvedSessionId, $studentId, $paymentId, $status);
            if (!$bookingId) {
                bookingLog("payment #{$paymentId}: createSessionBooking failed for session #{$resolvedSessionId}");
            }
        }
    } catch (Throwable $e) {
        bookingLog('payment #' . $paymentId . ': ' . $e->getMessage());
    }
}

function confirmBookingsForPayment(int $paymentId, int $studentId): void
{
    require_once __DIR__ . '/line_messaging.php';

    syncSessionBookingsFromPayment($paymentId);

    $stmt = db()->prepare('
        SELECT sb.id, sb.session_id, cs.starts_at, cs.title AS session_title, c.title AS course_title
        FROM session_bookings sb
        JOIN course_sessions cs ON cs.id = sb.session_id
        JOIN courses c ON c.id = cs.course_id
        WHERE sb.payment_id = ? AND sb.student_id = ? AND sb.status = "pending"
    ');
    $stmt->execute([$paymentId, $studentId]);
    $bookings = $stmt->fetchAll();

    if (!$bookings) {
        $pay = db()->prepare('SELECT note FROM payments WHERE id = ? LIMIT 1');
        $pay->execute([$paymentId]);
        $note = (string) ($pay->fetchColumn() ?: '');
        $sessionMap = parseSessionMapFromNote($note);
        createBookingsForPayment($paymentId, $studentId, $sessionMap, 'confirmed');
        $stmt->execute([$paymentId, $studentId]);
        $bookings = $stmt->fetchAll();
    }

    foreach ($bookings as $booking) {
        confirmSessionBooking((int) $booking['id']);
        linePushBookingConfirmed($studentId, $booking);
        notifyBookingConfirmed($studentId, $booking);
    }
}

function getStudentBookings(int $studentId): array
{
    try {
        $stmt = db()->prepare('
            SELECT sb.*, cs.starts_at, cs.ends_at, cs.title AS session_title, cs.zoom_url AS session_zoom_url,
                   c.id AS course_id, c.title AS course_title, c.slug AS course_slug, c.zoom_url AS course_zoom_url, c.course_type
            FROM session_bookings sb
            JOIN course_sessions cs ON cs.id = sb.session_id
            JOIN courses c ON c.id = cs.course_id
            WHERE sb.student_id = ? AND sb.status IN ("pending","confirmed")
            ORDER BY cs.starts_at ASC
        ');
        $stmt->execute([$studentId]);
        return $stmt->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
}

function getAdminBookings(?string $status = null, int $limit = 200): array
{
    $sql = '
        SELECT sb.*, cs.starts_at, cs.ends_at, cs.title AS session_title, cs.zoom_url AS session_zoom_url,
               c.title AS course_title, c.zoom_url AS course_zoom_url, s.full_name, s.phone, s.email
        FROM session_bookings sb
        JOIN course_sessions cs ON cs.id = sb.session_id
        JOIN courses c ON c.id = cs.course_id
        JOIN students s ON s.id = sb.student_id
    ';
    $params = [];
    if ($status && in_array($status, ['pending', 'confirmed', 'cancelled'], true)) {
        $sql .= ' WHERE sb.status = ?';
        $params[] = $status;
    }
    $sql .= ' ORDER BY cs.starts_at DESC LIMIT ' . (int) $limit;
    try {
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
}

function getUpcomingSessions(int $limit = 20): array
{
    $stmt = db()->prepare('
        SELECT cs.*, c.title AS course_title, c.slug AS course_slug
        FROM course_sessions cs
        JOIN courses c ON c.id = cs.course_id
        WHERE cs.status = "scheduled" AND cs.starts_at > NOW()
        ORDER BY cs.starts_at ASC
        LIMIT ?
    ');
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function getBookingReportStats(): array
{
    $stats = [
        'bookings_pending' => 0,
        'bookings_confirmed' => 0,
        'sessions_upcoming' => 0,
        'revenue_month' => 0.0,
        'revenue_total' => 0.0,
    ];

    try {
        $stats['bookings_pending'] = (int) db()->query('SELECT COUNT(*) FROM session_bookings WHERE status = "pending"')->fetchColumn();
        $stats['bookings_confirmed'] = (int) db()->query('SELECT COUNT(*) FROM session_bookings WHERE status = "confirmed"')->fetchColumn();
        $stats['sessions_upcoming'] = (int) db()->query('SELECT COUNT(*) FROM course_sessions WHERE status = "scheduled" AND starts_at > NOW()')->fetchColumn();
        $stats['revenue_total'] = (float) db()->query('SELECT COALESCE(SUM(amount),0) FROM payments WHERE status = "verified"')->fetchColumn();
        $stats['revenue_month'] = (float) db()->query('
            SELECT COALESCE(SUM(amount),0) FROM payments
            WHERE status = "verified" AND created_at >= DATE_FORMAT(NOW(), "%Y-%m-01")
        ')->fetchColumn();
    } catch (Throwable $e) {
        // tables may not exist yet
    }

    return $stats;
}

function getCourseRevenueReport(int $limit = 20): array
{
    try {
        $stmt = db()->prepare('
            SELECT c.id, c.title, COUNT(DISTINCT e.student_id) AS enrollments,
                   COALESCE(SUM(pi.amount), 0) AS revenue
            FROM courses c
            LEFT JOIN enrollments e ON e.course_id = c.id AND e.status IN ("active","completed")
            LEFT JOIN payment_items pi ON pi.course_id = c.id
            LEFT JOIN payments p ON p.id = pi.payment_id AND p.status = "verified"
            GROUP BY c.id, c.title
            ORDER BY revenue DESC, enrollments DESC
            LIMIT ?
        ');
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
}

function sessionStatusLabel(string $status): string
{
    return match ($status) {
        'scheduled' => 'เปิดจอง',
        'cancelled' => 'ยกเลิก',
        'completed' => 'จบแล้ว',
        default => $status,
    };
}

function sessionStatusBadgeClass(string $status): string
{
    return match ($status) {
        'scheduled' => 'badge-active',
        'cancelled' => 'badge-cancelled',
        default => 'badge-completed',
    };
}

/** เหตุผลที่นักเรียนยังมองไม่เห็นรอบนี้ — null = แสดงได้ */
function getSessionStudentVisibilityReason(array $session, ?array $course = null): ?string
{
    $courseId = (int) ($session['course_id'] ?? $course['id'] ?? 0);
    if (!$course && $courseId > 0) {
        $course = getCourseById($courseId);
    }
    if (!$course) {
        return 'ไม่พบคอร์ส';
    }
    if (!isCourseActive($course)) {
        return 'คอร์สปิดใช้งาน';
    }
    if (!isLiveCourse($course)) {
        return 'คอร์สไม่ใช่ประเภท Live/Hybrid';
    }
    if (($session['status'] ?? '') !== 'scheduled') {
        return 'สถานะรอบไม่เปิดจอง';
    }
    $starts = strtotime((string) ($session['starts_at'] ?? ''));
    if (!$starts || $starts <= time()) {
        return 'เลยเวลาเริ่มแล้ว';
    }
    if ((int) ($session['booked_count'] ?? 0) >= (int) ($session['capacity'] ?? 0)) {
        return 'ที่นั่งเต็ม';
    }

    return null;
}

function isSessionVisibleToStudents(array $session, ?array $course = null): bool
{
    return getSessionStudentVisibilityReason($session, $course) === null;
}

function bookingStatusLabel(string $status): string
{
    return match ($status) {
        'confirmed' => 'ยืนยันแล้ว',
        'cancelled' => 'ยกเลิก',
        default => 'รอยืนยัน',
    };
}

function bookingStatusBadgeClass(string $status): string
{
    return match ($status) {
        'confirmed' => 'badge-verified',
        'cancelled' => 'badge-cancelled',
        default => 'badge-pending',
    };
}

function formatPaymentSessionSummary(?string $note, ?int $paymentId = null): string
{
    return formatPaymentSessionTimeLabel($note, $paymentId);
}

function formatPaymentSessionTimeLabel(?string $note, ?int $paymentId = null): string
{
    $lines = [];
    $map = parseSessionMapFromNote($note);
    foreach ($map as $sessionId) {
        $session = getSessionById((int) $sessionId);
        if ($session) {
            $lines[] = formatSessionRange($session);
        }
    }
    if ($lines) {
        return implode(' · ', $lines);
    }
    if ($paymentId) {
        foreach (getBookingsByPaymentId($paymentId) as $booking) {
            $lines[] = formatSessionRange($booking);
        }
    }
    return $lines ? implode(' · ', $lines) : '';
}

function getBookingsByPaymentId(int $paymentId): array
{
    if ($paymentId <= 0) {
        return [];
    }
    try {
        $stmt = db()->prepare('
            SELECT sb.*, cs.starts_at, cs.ends_at, cs.title AS session_title, cs.zoom_url AS session_zoom_url,
                   c.id AS course_id, c.title AS course_title, c.zoom_url AS course_zoom_url
            FROM session_bookings sb
            JOIN course_sessions cs ON cs.id = sb.session_id
            JOIN courses c ON c.id = cs.course_id
            WHERE sb.payment_id = ?
            ORDER BY cs.starts_at ASC
        ');
        $stmt->execute([$paymentId]);
        return $stmt->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
}

function getStudentBookingForCourse(int $studentId, int $courseId): ?array
{
    $stmt = db()->prepare('
        SELECT sb.*, cs.starts_at, cs.ends_at, cs.title AS session_title, cs.zoom_url AS session_zoom_url,
               c.title AS course_title, c.slug AS course_slug, c.zoom_url AS course_zoom_url, c.course_type
        FROM session_bookings sb
        JOIN course_sessions cs ON cs.id = sb.session_id
        JOIN courses c ON c.id = cs.course_id
        WHERE sb.student_id = ? AND c.id = ? AND sb.status IN ("pending","confirmed")
        ORDER BY cs.starts_at ASC
        LIMIT 1
    ');
    $stmt->execute([$studentId, $courseId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function notifyBookingConfirmed(int $studentId, array $booking): void
{
    require_once __DIR__ . '/mailer.php';
    $stmt = db()->prepare('SELECT full_name, email FROM students WHERE id = ? LIMIT 1');
    $stmt->execute([$studentId]);
    $student = $stmt->fetch();
    if (!$student || empty($student['email'])) {
        return;
    }
    $session = getSessionById((int) ($booking['session_id'] ?? 0));
    $zoom = $session ? getSessionZoomUrl($session) : null;
    $when = $session ? formatSessionRange($session) : '';
    notifyBookingConfirmedEmail(
        (string) $student['email'],
        (string) $student['full_name'],
        (string) ($booking['course_title'] ?? ''),
        $when,
        $zoom
    );
}
