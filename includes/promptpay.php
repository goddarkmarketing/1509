<?php
declare(strict_types=1);

function promptPayTlv(string $id, string $value): string
{
    return $id . str_pad((string) strlen($value), 2, '0', STR_PAD_LEFT) . $value;
}

function promptPayCrc16(string $payload): string
{
    $crc = 0xFFFF;
    $len = strlen($payload);
    for ($i = 0; $i < $len; $i++) {
        $crc ^= ord($payload[$i]) << 8;
        for ($j = 0; $j < 8; $j++) {
            if ($crc & 0x8000) {
                $crc = ($crc << 1) ^ 0x1021;
            } else {
                $crc <<= 1;
            }
            $crc &= 0xFFFF;
        }
    }
    return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
}

function normalizePromptPayTarget(string $id, string $type = 'phone'): string
{
    $id = preg_replace('/\D+/', '', $id);
    if ($type === 'phone') {
        if (strlen($id) === 10 && str_starts_with($id, '0')) {
            return '0066' . substr($id, 1);
        }
        if (str_starts_with($id, '66')) {
            return '00' . $id;
        }
    }
    return $id;
}

function buildPromptPayPayload(string $targetId, float $amount, string $type = 'phone'): string
{
    $target = normalizePromptPayTarget($targetId, $type);
    $tag = $type === 'national_id' ? '02' : '01';
    $targetField = $tag . str_pad((string) strlen($target), 2, '0', STR_PAD_LEFT) . $target;
    $aid = 'A000000677010111';
    $merchant = promptPayTlv('00', $aid) . promptPayTlv('01', $targetField);
    $merchantBlock = promptPayTlv('29', $merchant);

    $amountStr = number_format(max(0, $amount), 2, '.', '');
    $payload = promptPayTlv('00', '01')
        . promptPayTlv('01', '12')
        . $merchantBlock
        . promptPayTlv('53', '764')
        . promptPayTlv('54', $amountStr)
        . promptPayTlv('58', 'TH')
        . '6304';

    return $payload . promptPayCrc16($payload);
}

function promptPayQrImageUrl(string $payload, int $size = 280): string
{
    return 'promptpay_qr.php?size=' . $size . '&data=' . rawurlencode($payload);
}

function resolvePromptPayTargetId(): string
{
    $id = trim(getSetting('promptpay_id', ''));
    if ($id === '') {
        $id = trim(getSetting('phone', ''));
    }
    return $id;
}

function isPromptPayEnabled(): bool
{
    return getSetting('promptpay_enabled', '1') === '1';
}

function formatPromptPayTargetDisplay(string $id, string $type = 'phone'): string
{
    $digits = preg_replace('/\D+/', '', $id);
    if ($type === 'phone' && strlen($digits) === 10) {
        return substr($digits, 0, 3) . '-' . substr($digits, 3, 3) . '-' . substr($digits, 6);
    }
    return $id;
}

function getCheckoutPromptPayData(float $amount): ?array
{
    if (!isPromptPayEnabled() || $amount <= 0) {
        return null;
    }
    $id = resolvePromptPayTargetId();
    if ($id === '') {
        return null;
    }
    $type = getSetting('promptpay_id_type', 'phone');
    $payload = buildPromptPayPayload($id, $amount, $type);
    return [
        'payload' => $payload,
        'qr_url' => promptPayQrImageUrl($payload),
        'amount' => $amount,
        'target' => $id,
        'target_display' => formatPromptPayTargetDisplay($id, $type),
        'target_type' => $type,
    ];
}
