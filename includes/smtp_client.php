<?php
declare(strict_types=1);

/**
 * Minimal SMTP client (AUTH LOGIN, STARTTLS / SSL).
 */
function smtpSendMail(
    string $host,
    int $port,
    string $encryption,
    string $user,
    string $pass,
    string $from,
    string $fromName,
    string $to,
    string $subject,
    string $htmlBody
): bool {
    if ($host === '' || $to === '') {
        return false;
    }

    $encryption = strtolower($encryption);
    $remote = $encryption === 'ssl' ? "ssl://{$host}:{$port}" : "{$host}:{$port}";
    $errno = 0;
    $errstr = '';
    $socket = @stream_socket_client($remote, $errno, $errstr, 15, STREAM_CLIENT_CONNECT);
    if (!$socket) {
        smtpLog("connect failed: {$errstr} ({$errno})");
        return false;
    }

    stream_set_timeout($socket, 15);

    try {
        smtpExpect($socket, [220]);
        smtpCmd($socket, 'EHLO localhost', [250]);

        if ($encryption === 'tls') {
            smtpCmd($socket, 'STARTTLS', [220]);
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException('STARTTLS failed');
            }
            smtpCmd($socket, 'EHLO localhost', [250]);
        }

        if ($user !== '') {
            smtpCmd($socket, 'AUTH LOGIN', [334]);
            smtpCmd($socket, base64_encode($user), [334]);
            smtpCmd($socket, base64_encode($pass), [235]);
        }

        smtpCmd($socket, 'MAIL FROM:<' . $from . '>', [250]);
        smtpCmd($socket, 'RCPT TO:<' . $to . '>', [250, 251]);
        smtpCmd($socket, 'DATA', [354]);

        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $encodedFromName = mb_encode_mimeheader($fromName, 'UTF-8');
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
            "From: {$encodedFromName} <{$from}>",
            "To: {$to}",
            "Subject: {$encodedSubject}",
            'Date: ' . date('r'),
        ];
        $message = implode("\r\n", $headers) . "\r\n\r\n" . $htmlBody;
        $message = preg_replace('/^\./m', '..', $message) ?? $message;
        fwrite($socket, $message . "\r\n.\r\n");
        smtpExpect($socket, [250]);
        smtpCmd($socket, 'QUIT', [221]);
    } catch (Throwable $e) {
        smtpLog($e->getMessage());
        @fclose($socket);
        return false;
    }

    fclose($socket);
    return true;
}

function smtpCmd($socket, string $cmd, array $okCodes): string
{
    fwrite($socket, $cmd . "\r\n");
    return smtpExpect($socket, $okCodes);
}

function smtpExpect($socket, array $okCodes): string
{
    $response = '';
    while (($line = fgets($socket, 515)) !== false) {
        $response .= $line;
        if (isset($line[3]) && $line[3] === ' ') {
            break;
        }
    }
    $code = (int) substr(trim($response), 0, 3);
    if (!in_array($code, $okCodes, true)) {
        throw new RuntimeException('SMTP error: ' . trim($response));
    }
    return $response;
}

function smtpLog(string $message): void
{
    $logDir = BASE_PATH . '/storage/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    file_put_contents(
        $logDir . '/email.log',
        date('Y-m-d H:i:s') . ' SMTP ' . $message . "\n",
        FILE_APPEND
    );
}
