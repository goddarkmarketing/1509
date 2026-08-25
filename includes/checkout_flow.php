<?php
declare(strict_types=1);

require_once __DIR__ . '/cart.php';

function checkoutStepUrl(int $step): string
{
    return match ($step) {
        1 => APP_URL . '/public/courses.php',
        2 => APP_URL . '/public/cart.php',
        3 => APP_URL . '/public/checkout.php',
        4 => APP_URL . '/public/my-courses.php',
        default => APP_URL . '/public/courses.php',
    };
}

function renderCheckoutSteps(int $activeStep): void
{
    $steps = [
        1 => 'เลือกคอร์ส',
        2 => 'ใส่ตะกร้า',
        3 => 'ชำระเงิน',
        4 => 'เริ่มเรียน',
    ];
    ?>
    <nav class="checkout-steps" aria-label="ขั้นตอนการสมัครเรียน">
        <?php foreach ($steps as $num => $label): ?>
            <?php if ($num > 1): ?>
            <span class="checkout-step-line<?= $num <= $activeStep ? ' is-done' : '' ?>" aria-hidden="true"></span>
            <?php endif; ?>
            <?php
            $classes = ['checkout-step'];
            if ($num < $activeStep) {
                $classes[] = 'is-done';
            }
            if ($num === $activeStep) {
                $classes[] = 'is-active';
            }
            $canLink = $num <= $activeStep || ($num === 2 && cartCount() > 0) || ($num === 3 && cartCount() > 0);
            $href = checkoutStepUrl($num);
            if ($num === 3 && cartCount() === 0 && $activeStep < 3) {
                $canLink = false;
            }
            ?>
            <?php if ($canLink && $num !== $activeStep): ?>
            <a href="<?= e($href) ?>" class="<?= e(implode(' ', $classes)) ?>">
                <span class="checkout-step-num"><?= $num ?></span>
                <span class="checkout-step-label"><?= e($label) ?></span>
            </a>
            <?php else: ?>
            <span class="<?= e(implode(' ', $classes)) ?>"<?= $num === $activeStep ? ' aria-current="step"' : '' ?>>
                <span class="checkout-step-num"><?= $num ?></span>
                <span class="checkout-step-label"><?= e($label) ?></span>
            </span>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>
    <?php
}

function requireCartForCheckout(): void
{
    if (cartCount() > 0) {
        return;
    }
    flash('payment_error', 'กรุณาเลือกคอร์สและใส่ตะกร้าก่อนชำระเงิน');
    redirect('/public/courses.php');
}

function appendCartIdsToNote(string $note): string
{
    $ids = getCartCourseIds();
    if (!$ids) {
        return $note;
    }
    $meta = 'cart_ids:' . implode(',', $ids);
    if ($note !== '' && str_contains($note, 'cart_ids:')) {
        return $note;
    }
    return $note !== '' ? $note . "\n" . $meta : $meta;
}

function parseCartIdsFromNote(?string $note): array
{
    if (!$note || !preg_match('/cart_ids:([\d,]+)/', $note, $m)) {
        return [];
    }
    return array_values(array_filter(array_map('intval', explode(',', $m[1]))));
}

function savePaymentItems(int $paymentId, array $cartItems): void
{
    if ($paymentId <= 0 || !$cartItems) {
        return;
    }
    try {
        require_once __DIR__ . '/booking.php';
        $sessionMap = getCartSessionMap();

        $stmt = db()->prepare('INSERT INTO payment_items (payment_id, course_id, session_id, amount) VALUES (?, ?, ?, ?)');
        foreach ($cartItems as $item) {
            $courseId = (int) ($item['id'] ?? 0);
            if ($courseId <= 0) {
                continue;
            }
            $sessionId = $sessionMap[$courseId] ?? null;
            try {
                $stmt->execute([$paymentId, $courseId, $sessionId ?: null, (float) ($item['price'] ?? 0)]);
            } catch (Throwable $e) {
                $fallback = db()->prepare('INSERT INTO payment_items (payment_id, course_id, amount) VALUES (?, ?, ?)');
                $fallback->execute([$paymentId, $courseId, (float) ($item['price'] ?? 0)]);
            }
        }
    } catch (Throwable $e) {
        // payment_items table may be missing on older databases; cart_ids in note still identify courses.
    }
}

function insertBankTransferPayment(
    ?int $courseId,
    string $name,
    ?string $email,
    string $phone,
    float $amount,
    ?string $transferDate,
    ?string $transferTime,
    ?string $slipPath,
    ?string $note,
    ?string $couponCode
): int {
    $baseParams = [
        $courseId ?: null,
        $name,
        $email ?: null,
        $phone,
        $amount,
        $transferDate ?: null,
        $transferTime ?: null,
        $slipPath,
        $note ?: null,
    ];

    $attempts = [
        static function () use ($baseParams, $couponCode): int {
            $stmt = db()->prepare('
                INSERT INTO payments (course_id, student_name, student_email, student_phone, amount, transfer_date, transfer_time, slip_image, note, coupon_code, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "pending")
            ');
            $stmt->execute([...$baseParams, $couponCode]);
            return (int) db()->lastInsertId();
        },
        static function () use ($baseParams): int {
            $stmt = db()->prepare('
                INSERT INTO payments (course_id, student_name, student_email, student_phone, amount, transfer_date, transfer_time, slip_image, note, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, "pending")
            ');
            $stmt->execute($baseParams);
            return (int) db()->lastInsertId();
        },
        static function () use ($courseId, $name, $phone, $amount, $note): int {
            $stmt = db()->prepare('
                INSERT INTO payments (course_id, student_name, student_phone, amount, note, status, payment_method)
                VALUES (?, ?, ?, ?, ?, "pending", "transfer")
            ');
            $stmt->execute([$courseId ?: null, $name, $phone, $amount, $note ?: null]);
            return (int) db()->lastInsertId();
        },
        static function () use ($name, $phone, $amount, $note): int {
            $stmt = db()->prepare('
                INSERT INTO payments (student_name, student_phone, amount, note, status)
                VALUES (?, ?, ?, ?, "pending")
            ');
            $stmt->execute([$name, $phone, $amount, $note ?: null]);
            return (int) db()->lastInsertId();
        },
    ];

    $errors = [];
    foreach ($attempts as $attempt) {
        try {
            $paymentId = $attempt();
            if ($paymentId > 0) {
                return $paymentId;
            }
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }

    $logDir = BASE_PATH . '/storage/logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    file_put_contents(
        $logDir . '/payment.log',
        date('Y-m-d H:i:s') . ' insert failed: ' . implode(' | ', $errors) . "\n",
        FILE_APPEND
    );

    throw new RuntimeException($errors[0] ?? 'Unable to save payment');
}

function getPaymentCourseIds(int $paymentId): array
{
    $stmt = db()->prepare('SELECT course_id FROM payment_items WHERE payment_id = ? ORDER BY id ASC');
    $stmt->execute([$paymentId]);
    $ids = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    return array_values(array_filter($ids));
}

function getPaymentItemsWithTitles(int $paymentId): array
{
    try {
        $stmt = db()->prepare('
            SELECT pi.course_id, pi.amount, c.title
            FROM payment_items pi
            JOIN courses c ON c.id = pi.course_id
            WHERE pi.payment_id = ?
            ORDER BY pi.id ASC
        ');
        $stmt->execute([$paymentId]);
        return $stmt->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
}

function getPendingEnrollmentsForAdmin(): array
{
    try {
        $stmt = db()->query('
            SELECT e.id AS enrollment_id, e.student_id, e.course_id, e.enrolled_at,
                   s.full_name, s.phone, s.email, c.title AS course_title
            FROM enrollments e
            JOIN students s ON s.id = e.student_id
            JOIN courses c ON c.id = e.course_id
            WHERE e.status = "pending"
            ORDER BY e.enrolled_at DESC
            LIMIT 100
        ');
        return $stmt->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
}

function formatPaymentCustomerLine(array $payment): string
{
    $name = trim((string) ($payment['student_name'] ?? ''));
    $phone = trim((string) ($payment['student_phone'] ?? ''));
    if ($name !== '' && $phone !== '') {
        return $name . ' · ' . $phone;
    }
    return $name !== '' ? $name : $phone;
}

/** ชื่อผู้ใช้สำหรับแสดงในตารางแอดมิน — อีเมลหรือเบอร์โทร */
function formatPaymentCustomerUser(array $payment): string
{
    $email = trim((string) ($payment['student_email'] ?? ''));
    if ($email !== '') {
        return $email;
    }
    $phone = trim((string) ($payment['student_phone'] ?? ''));
    if ($phone !== '') {
        return $phone;
    }
    $name = trim((string) ($payment['student_name'] ?? ''));
    return $name !== '' ? $name : '-';
}

function formatPaymentCustomerTitle(array $payment): string
{
    $parts = array_filter([
        trim((string) ($payment['student_name'] ?? '')),
        trim((string) ($payment['student_phone'] ?? '')),
        trim((string) ($payment['student_email'] ?? '')),
    ]);
    return implode(' · ', $parts);
}

function formatPaymentCourseLine(array $payment, array $items = []): string
{
    $titles = [];
    foreach ($items as $item) {
        $title = trim((string) ($item['title'] ?? ''));
        if ($title !== '') {
            $titles[] = $title;
        }
    }
    if (!$titles && !empty($payment['course_title'])) {
        $titles[] = (string) $payment['course_title'];
    }
    return $titles ? implode(', ', $titles) : '-';
}

function rejectDuplicatePendingPayments(array $verifiedPayment, int $verifiedPaymentId): void
{
    $phone = trim((string) ($verifiedPayment['student_phone'] ?? ''));
    if ($phone === '' || $verifiedPaymentId <= 0) {
        return;
    }

    $courseIds = getPaymentCourseIds($verifiedPaymentId);
    if (!$courseIds) {
        $courseIds = parseCartIdsFromNote($verifiedPayment['note'] ?? '');
    }
    if (!$courseIds && !empty($verifiedPayment['course_id'])) {
        $courseIds = [(int) $verifiedPayment['course_id']];
    }
    if (!$courseIds) {
        return;
    }

    $stmt = db()->prepare('
        SELECT id, note, course_id FROM payments
        WHERE student_phone = ? AND status = "pending" AND id != ?
    ');
    $stmt->execute([$phone, $verifiedPaymentId]);
    $reject = db()->prepare('UPDATE payments SET status = "rejected" WHERE id = ?');

    foreach ($stmt->fetchAll() as $row) {
        $pendingId = (int) ($row['id'] ?? 0);
        if ($pendingId <= 0) {
            continue;
        }
        $pendingCourseIds = getPaymentCourseIds($pendingId);
        if (!$pendingCourseIds) {
            $pendingCourseIds = parseCartIdsFromNote($row['note'] ?? '');
        }
        if (!$pendingCourseIds && !empty($row['course_id'])) {
            $pendingCourseIds = [(int) $row['course_id']];
        }
        if (array_intersect($courseIds, $pendingCourseIds)) {
            $reject->execute([$pendingId]);
        }
    }
}

function resolveCheckoutStudentId(string $name, ?string $email, string $phone): int
{
    require_once __DIR__ . '/student_auth.php';
    if (isStudentLoggedIn()) {
        return (int) $_SESSION['student_id'];
    }
    return findOrCreateStudent($name, $email, $phone);
}

function getCourseIdsFromCartItems(array $items): array
{
    return filterValidCourseIds(array_values(array_filter(array_map(static fn ($item) => (int) ($item['id'] ?? 0), $items))));
}

function filterValidCourseIds(array $courseIds): array
{
    $courseIds = array_values(array_unique(array_filter(array_map('intval', $courseIds))));
    if (!$courseIds) {
        return [];
    }
    try {
        $placeholders = implode(',', array_fill(0, count($courseIds), '?'));
        $stmt = db()->prepare("SELECT id FROM courses WHERE id IN ($placeholders)");
        $stmt->execute($courseIds);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    } catch (Throwable $e) {
        return [];
    }
}

function enrollmentStatusRank(string $status): int
{
    return match ($status) {
        'completed' => 4,
        'active' => 3,
        'pending' => 2,
        'cancelled' => 1,
        default => 0,
    };
}

function syncEnrollmentsFromPaymentsForStudent(int $studentId): void
{
    if ($studentId <= 0) {
        return;
    }
    $stmt = db()->prepare('SELECT phone FROM students WHERE id = ? LIMIT 1');
    $stmt->execute([$studentId]);
    $phone = trim((string) ($stmt->fetchColumn() ?: ''));
    if ($phone === '') {
        return;
    }

    $payStmt = db()->prepare('
        SELECT * FROM payments
        WHERE student_phone = ? AND status IN ("pending", "verified")
        ORDER BY created_at ASC
    ');
    $payStmt->execute([$phone]);

    $desiredByCourse = [];
    foreach ($payStmt->fetchAll() as $payment) {
        $paymentId = (int) ($payment['id'] ?? 0);
        $courseIds = $paymentId > 0 ? getPaymentCourseIds($paymentId) : [];
        if (!$courseIds) {
            $courseIds = parseCartIdsFromNote($payment['note'] ?? '');
        }
        if (!$courseIds && !empty($payment['course_id'])) {
            $courseIds = [(int) $payment['course_id']];
        }
        if (!$courseIds) {
            continue;
        }
        $courseIds = filterValidCourseIds($courseIds);
        if (!$courseIds) {
            continue;
        }

        $paymentActive = ($payment['status'] ?? '') === 'verified';
        foreach ($courseIds as $courseId) {
            $courseId = (int) $courseId;
            if ($courseId <= 0) {
                continue;
            }
            if ($paymentActive) {
                $desiredByCourse[$courseId] = 'active';
            } elseif (!isset($desiredByCourse[$courseId])) {
                $desiredByCourse[$courseId] = 'pending';
            }
        }
    }

    foreach ($desiredByCourse as $courseId => $status) {
        enrollStudentInCourses($studentId, [(int) $courseId], $status);
    }
}

function findOrCreateStudent(string $name, ?string $email, string $phone): int
{
    $stmt = db()->prepare('SELECT id FROM students WHERE phone = ? LIMIT 1');
    $stmt->execute([$phone]);
    $existing = $stmt->fetchColumn();
    if ($existing) {
        $studentId = (int) $existing;
        $upd = db()->prepare('UPDATE students SET full_name = ?, email = COALESCE(?, email) WHERE id = ?');
        $upd->execute([$name, $email ?: null, $studentId]);
        return $studentId;
    }

    $ins = db()->prepare('INSERT INTO students (full_name, email, phone) VALUES (?, ?, ?)');
    $ins->execute([$name, $email ?: null, $phone]);
    return (int) db()->lastInsertId();
}

function enrollStudentInCourses(int $studentId, array $courseIds, string $status = 'active'): void
{
    $allowed = ['pending', 'active', 'completed', 'cancelled'];
    if (!in_array($status, $allowed, true)) {
        $status = 'active';
    }

    $courseIds = filterValidCourseIds($courseIds);
    if (!$courseIds) {
        return;
    }

    $check = db()->prepare('SELECT id, status FROM enrollments WHERE student_id = ? AND course_id = ? LIMIT 1');
    $insert = db()->prepare('INSERT INTO enrollments (student_id, course_id, status) VALUES (?, ?, ?)');
    $upd = db()->prepare('UPDATE enrollments SET status = ? WHERE student_id = ? AND course_id = ?');

    foreach (array_unique(array_map('intval', $courseIds)) as $courseId) {
        if ($courseId <= 0) {
            continue;
        }
        $check->execute([$studentId, $courseId]);
        $existing = $check->fetch();
        if ($existing) {
            $current = (string) ($existing['status'] ?? 'pending');
            if (
                $status === 'pending'
                && enrollmentStatusRank($current) > enrollmentStatusRank('pending')
            ) {
                continue;
            }
            $upd->execute([$status, $studentId, $courseId]);
            continue;
        }
        $insert->execute([$studentId, $courseId, $status]);
    }
}

function getEnrolledCoursesByPhone(string $phone): array
{
    $phone = trim($phone);
    if ($phone === '') {
        return [];
    }

    try {
        $stmt = db()->prepare('
            SELECT c.id, c.slug, c.title, c.subtitle, c.category, c.level, c.price, c.course_type, e.status, e.enrolled_at
            FROM enrollments e
            JOIN students s ON s.id = e.student_id
            JOIN courses c ON c.id = e.course_id
            WHERE s.phone = ? AND e.status IN ("active", "completed")
            ORDER BY e.enrolled_at DESC
        ');
        $stmt->execute([$phone]);
        return $stmt->fetchAll();
    } catch (Throwable $e) {
        try {
            $stmt = db()->prepare('
                SELECT c.id, c.slug, c.title, c.subtitle, c.category, c.level, c.price, e.status, e.enrolled_at
                FROM enrollments e
                JOIN students s ON s.id = e.student_id
                JOIN courses c ON c.id = e.course_id
                WHERE s.phone = ? AND e.status IN ("active", "completed")
                ORDER BY e.enrolled_at DESC
            ');
            $stmt->execute([$phone]);
            $rows = $stmt->fetchAll();
            foreach ($rows as &$row) {
                $row['course_type'] = 'recorded';
            }
            unset($row);
            return $rows;
        } catch (Throwable $e2) {
            return [];
        }
    }
}

function getEnrolledCoursesByStudentId(int $studentId): array
{
    if ($studentId <= 0) {
        return [];
    }
    try {
        $stmt = db()->prepare('
            SELECT c.id, c.slug, c.title, c.subtitle, c.category, c.level, c.price, c.course_type, e.status, e.enrolled_at
            FROM enrollments e
            JOIN courses c ON c.id = e.course_id
            WHERE e.student_id = ? AND e.status IN ("pending", "active", "completed")
            ORDER BY e.enrolled_at DESC
        ');
        $stmt->execute([$studentId]);
        return $stmt->fetchAll();
    } catch (Throwable $e) {
        try {
            $stmt = db()->prepare('
                SELECT c.id, c.slug, c.title, c.subtitle, c.category, c.level, c.price, e.status, e.enrolled_at
                FROM enrollments e
                JOIN courses c ON c.id = e.course_id
                WHERE e.student_id = ? AND e.status IN ("pending", "active", "completed")
                ORDER BY e.enrolled_at DESC
            ');
            $stmt->execute([$studentId]);
            $rows = $stmt->fetchAll();
            foreach ($rows as &$row) {
                $row['course_type'] = 'recorded';
            }
            unset($row);
            return $rows;
        } catch (Throwable $e2) {
            return [];
        }
    }
}

function getFirstLessonIdForCourse(int $courseId): ?int
{
    $stmt = db()->prepare('
        SELECT id FROM lessons
        WHERE course_id = ? AND is_published = 1
        ORDER BY sort_order ASC, id ASC
        LIMIT 1
    ');
    $stmt->execute([$courseId]);
    $id = $stmt->fetchColumn();
    return $id ? (int) $id : null;
}

function addCourseSlugToCart(string $slug): bool
{
    $course = getActiveCourseBySlug($slug);
    if (!$course) {
        return false;
    }
    addToCartCourse((int) $course['id']);
    return true;
}

function enrollFromPayment(array $payment): void
{
    require_once __DIR__ . '/mailer.php';
    require_once __DIR__ . '/line_notify.php';
    require_once __DIR__ . '/booking.php';

    $paymentId = (int) ($payment['id'] ?? 0);
    $courseIds = $paymentId > 0 ? getPaymentCourseIds($paymentId) : [];
    if (!$courseIds) {
        $courseIds = parseCartIdsFromNote($payment['note'] ?? '');
    }
    if (!$courseIds && !empty($payment['course_id'])) {
        $courseIds = [(int) $payment['course_id']];
    }
    if (!$courseIds) {
        return;
    }
    $courseIds = filterValidCourseIds($courseIds);
    if (!$courseIds) {
        return;
    }

    $studentId = findOrCreateStudent(
        (string) ($payment['student_name'] ?? ''),
        $payment['student_email'] ?? null,
        (string) ($payment['student_phone'] ?? '')
    );
    enrollStudentInCourses($studentId, $courseIds, 'active');

    $titles = [];
    foreach ($courseIds as $cid) {
        $c = getCourseById((int) $cid);
        if ($c) {
            $titles[] = $c['title'];
        }
    }
    $email = $payment['student_email'] ?? null;
    if (!$email) {
        $stmt = db()->prepare('SELECT email FROM students WHERE id = ? LIMIT 1');
        $stmt->execute([$studentId]);
        $email = $stmt->fetchColumn() ?: null;
    }
    if ($email && $titles) {
        notifyEnrollmentOpened((string) $email, (string) ($payment['student_name'] ?? ''), $titles);
    }
    if ($titles) {
        lineNotifyEnrollment(
            (string) ($payment['student_name'] ?? ''),
            (string) ($payment['student_phone'] ?? ''),
            $titles
        );
    }

    syncSessionBookingsFromPayment($paymentId);
    confirmBookingsForPayment($paymentId, $studentId);
}
