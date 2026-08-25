<?php
declare(strict_types=1);

require_once __DIR__ . '/checkout_flow.php';

function isOmiseEnabled(): bool
{
    return getSetting('omise_enabled', '0') === '1'
        && omisePublicKey() !== ''
        && omiseSecretKey() !== '';
}

/** Show Omise block on checkout when a public key is configured. */
function isOmiseCheckoutVisible(): bool
{
    return omisePublicKey() !== '';
}

function omisePublicKey(): string
{
    return trim(getSetting('omise_public_key', ''));
}

function omiseSecretKey(): string
{
    $fromEnv = env('OMISE_SECRET_KEY', '');
    if ($fromEnv !== null && $fromEnv !== '') {
        return $fromEnv;
    }
    return trim(getSetting('omise_secret_key', ''));
}

function omiseApiRequest(string $method, string $path, array $data = []): array
{
    $url = 'https://api.omise.co' . $path;
    $ch = curl_init($url);
    $headers = ['Content-Type: application/x-www-form-urlencoded'];

    curl_setopt_array($ch, [
        CURLOPT_USERPWD => omiseSecretKey() . ':',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => $headers,
    ]);

    $method = strtoupper($method);
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    } elseif ($method === 'GET') {
        if ($data) {
            $url .= '?' . http_build_query($data);
            curl_setopt($ch, CURLOPT_URL, $url);
        }
    }

    $body = curl_exec($ch);
    $errno = curl_errno($ch);
    $error = curl_error($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($errno !== 0) {
        throw new RuntimeException('Omise connection error: ' . $error);
    }

    $json = json_decode((string) $body, true);
    if (!is_array($json)) {
        throw new RuntimeException('Omise invalid response (HTTP ' . $code . ')');
    }
    if ($code >= 400) {
        $msg = $json['message'] ?? ('HTTP ' . $code);
        throw new RuntimeException('Omise API: ' . $msg);
    }

    return $json;
}

function amountToSatang(float $amount): int
{
    return max(1, (int) round($amount * 100));
}

function createPendingOmisePayment(array $customer, array $cartItems, float $amount, ?string $couponCode): int
{
    require_once __DIR__ . '/booking.php';
    require_once __DIR__ . '/student_auth.php';

    $name = trim($customer['name'] ?? '');
    $phone = trim($customer['phone'] ?? '');
    $email = trim($customer['email'] ?? '') ?: null;
    $courseId = count($cartItems) === 1 ? (int) ($cartItems[0]['id'] ?? 0) : null;

    $note = 'cart_ids:' . implode(',', array_map(static fn ($i) => (int) ($i['id'] ?? 0), $cartItems));
    if ($couponCode) {
        $note .= "\ncoupon:" . $couponCode;
    }
    $note = appendSessionMapToNote($note, getCartSessionMap());
    $note .= "\npayment_method:omise";

    $stmt = db()->prepare('
        INSERT INTO payments (course_id, student_name, student_email, student_phone, amount, note, coupon_code, status, payment_method)
        VALUES (?, ?, ?, ?, ?, ?, ?, "pending", "omise")
    ');
    $stmt->execute([$courseId ?: null, $name, $email, $phone, $amount, $note, $couponCode]);
    $paymentId = (int) db()->lastInsertId();
    savePaymentItems($paymentId, $cartItems);

    $courseIds = getCourseIdsFromCartItems($cartItems);
    if ($courseIds) {
        $studentId = resolveCheckoutStudentId($name, $email, $phone);
        enrollStudentInCourses($studentId, $courseIds, 'pending');
        $sessionMap = getCartSessionMap();
        if ($sessionMap) {
            createBookingsForPayment($paymentId, $studentId, $sessionMap, 'pending');
        }
    }

    return $paymentId;
}

function createOmisePromptPayCharge(int $paymentId, float $amount): array
{
    require_once __DIR__ . '/mailer.php';
    $returnUri = siteBaseUrl() . '/public/omise_return.php?payment_id=' . $paymentId;

    $source = omiseApiRequest('POST', '/sources', [
        'amount' => amountToSatang($amount),
        'currency' => 'thb',
        'type' => 'promptpay',
        'return_uri' => $returnUri,
    ]);

    $charge = omiseApiRequest('POST', '/charges', [
        'amount' => amountToSatang($amount),
        'currency' => 'thb',
        'source' => $source['id'] ?? '',
        'metadata' => ['payment_id' => (string) $paymentId],
    ]);

    db()->prepare('UPDATE payments SET omise_charge_id = ? WHERE id = ?')
        ->execute([$charge['id'] ?? '', $paymentId]);

    return [
        'charge' => $charge,
        'authorize_uri' => $source['authorize_uri'] ?? ($charge['authorize_uri'] ?? null),
    ];
}

function createOmiseCardCharge(int $paymentId, float $amount, string $omiseToken): array
{
    $charge = omiseApiRequest('POST', '/charges', [
        'amount' => amountToSatang($amount),
        'currency' => 'thb',
        'card' => $omiseToken,
        'metadata' => ['payment_id' => (string) $paymentId],
    ]);

    db()->prepare('UPDATE payments SET omise_charge_id = ? WHERE id = ?')
        ->execute([$charge['id'] ?? '', $paymentId]);

    return $charge;
}

function getOmiseCharge(string $chargeId): ?array
{
    if ($chargeId === '') {
        return null;
    }
    try {
        return omiseApiRequest('GET', '/charges/' . rawurlencode($chargeId));
    } catch (Throwable $e) {
        omiseLog($e->getMessage());
        return null;
    }
}

function omiseChargeIsPaid(array $charge): bool
{
    return ($charge['paid'] ?? false) === true
        || ($charge['status'] ?? '') === 'successful';
}

function completeOmisePayment(int $paymentId): bool
{
    $stmt = db()->prepare('SELECT * FROM payments WHERE id = ? LIMIT 1');
    $stmt->execute([$paymentId]);
    $payment = $stmt->fetch();
    if (!$payment) {
        return false;
    }
    if (($payment['status'] ?? '') === 'verified') {
        return true;
    }

    $chargeId = (string) ($payment['omise_charge_id'] ?? '');
    if ($chargeId === '') {
        return false;
    }

    $charge = getOmiseCharge($chargeId);
    if (!$charge || !omiseChargeIsPaid($charge)) {
        return false;
    }

    require_once __DIR__ . '/checkout_flow.php';
    require_once __DIR__ . '/coupon.php';

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $lock = $pdo->prepare('SELECT status FROM payments WHERE id = ? FOR UPDATE');
        $lock->execute([$paymentId]);
        $current = $lock->fetch();
        if (!$current || ($current['status'] ?? '') === 'verified') {
            $pdo->commit();
            return true;
        }

        $pdo->prepare('UPDATE payments SET status = ? WHERE id = ?')->execute(['verified', $paymentId]);

        $couponCode = $payment['coupon_code'] ?? null;
        if ($couponCode) {
            incrementCouponUsage($couponCode);
        }

        enrollFromPayment($payment);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        omiseLog('complete payment #' . $paymentId . ': ' . $e->getMessage());
        return false;
    }

    require_once __DIR__ . '/cart.php';
    clearCart();
    $_SESSION['checkout_phone'] = $payment['student_phone'] ?? '';

    return true;
}

function handleOmiseWebhookPayload(string $rawBody): bool
{
    $event = json_decode($rawBody, true);
    if (!is_array($event)) {
        return false;
    }

    $key = $event['key'] ?? '';
    if (!in_array($key, ['charge.complete', 'charge.create'], true)) {
        return true;
    }

    $charge = $event['data']['object'] ?? null;
    if (!is_array($charge) || ($charge['object'] ?? '') !== 'charge') {
        return false;
    }

    $paymentId = (int) ($charge['metadata']['payment_id'] ?? 0);
    if ($paymentId <= 0) {
        $chargeId = (string) ($charge['id'] ?? '');
        $stmt = db()->prepare('SELECT id FROM payments WHERE omise_charge_id = ? LIMIT 1');
        $stmt->execute([$chargeId]);
        $paymentId = (int) ($stmt->fetchColumn() ?: 0);
    }

    if ($paymentId <= 0) {
        return false;
    }

    if (!omiseChargeIsPaid($charge)) {
        return true;
    }

    return completeOmisePayment($paymentId);
}

function omiseLog(string $message): void
{
    $logDir = BASE_PATH . '/storage/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    file_put_contents($logDir . '/omise.log', date('Y-m-d H:i:s') . ' ' . $message . "\n", FILE_APPEND);
}
