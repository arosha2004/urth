<?php

function ensure_shop_email_outbox(mysqli $conn): void
{
    $sql = "CREATE TABLE IF NOT EXISTS shop_email_outbox (
        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        recipient VARCHAR(255) NOT NULL,
        subject VARCHAR(255) NOT NULL,
        body TEXT NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'pending',
        attempts INT NOT NULL DEFAULT 0,
        last_error TEXT DEFAULT NULL,
        last_attempt_at DATETIME DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (status),
        INDEX (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    if (!$conn->query($sql)) {
        throw new RuntimeException('Could not initialize email outbox.');
    }
}

function send_shop_email(mysqli $conn, string $recipient, string $subject, string $body, ?int $retryId = null, ?string $replyTo = null): bool
{
    ensure_shop_email_outbox($conn);
    if ($retryId === null && (!filter_var($recipient, FILTER_VALIDATE_EMAIL) || trim($subject) === '' || trim($body) === '')) {
        throw new InvalidArgumentException('A valid recipient, subject and message are required.');
    }

    $subject = trim(str_replace(["\r", "\n"], ' ', $subject));
    if ($retryId === null) {
        $insert = $conn->prepare("INSERT INTO shop_email_outbox (recipient, subject, body, status) VALUES (?, ?, ?, 'pending')");
        if (!$insert) {
            throw new RuntimeException('Could not queue the email.');
        }
        $insert->bind_param('sss', $recipient, $subject, $body);
        if (!$insert->execute()) {
            throw new RuntimeException('Could not queue the email.');
        }
        $retryId = (int)$conn->insert_id;
        $insert->close();
    } else {
        $fetch = $conn->prepare("SELECT recipient, subject, body FROM shop_email_outbox WHERE id = ? AND status = 'failed' LIMIT 1");
        if (!$fetch) {
            throw new RuntimeException('Could not load the failed email.');
        }
        $fetch->bind_param('i', $retryId);
        $fetch->execute();
        $result = $fetch->get_result();
        $email = $result ? $result->fetch_assoc() : null;
        $fetch->close();
        if (!$email) {
            throw new RuntimeException('The failed email is no longer available for retry.');
        }
        $recipient = $email['recipient'];
        $subject = $email['subject'];
        $body = $email['body'];
    }
    if (!filter_var($recipient, FILTER_VALIDATE_EMAIL) || trim($subject) === '' || trim($body) === '') {
        throw new InvalidArgumentException('The saved email record is incomplete.');
    }

    $from = trim((string)getenv('URTH_MAIL_FROM'));
    $transportError = '';
    $sent = false;
    $replyTo = $replyTo !== null ? trim($replyTo) : '';
    if ($replyTo !== '' && (!filter_var($replyTo, FILTER_VALIDATE_EMAIL) || preg_match('/[\r\n]/', $replyTo))) {
        $replyTo = '';
    }
    $smtpHost = trim((string)getenv('URTH_SMTP_HOST'));
    if ($from === '' || !filter_var($from, FILTER_VALIDATE_EMAIL)) {
        $transportError = 'URTH_MAIL_FROM is not configured with a valid sender address.';
    } elseif ($smtpHost !== '') {
        try {
            shop_smtp_send($smtpHost, $from, $recipient, $subject, $body, $replyTo);
            $sent = true;
        } catch (Throwable $e) {
            $transportError = 'SMTP: ' . $e->getMessage();
        }
    } elseif (!function_exists('mail')) {
        $transportError = 'PHP mail() is unavailable on this server.';
    } else {
        $headers = "From: {$from}\r\nContent-Type: text/plain; charset=UTF-8";
        if ($replyTo !== '') {
            $headers .= "\r\nReply-To: {$replyTo}";
        }
        set_error_handler(static function ($severity, $message) use (&$transportError) {
            $transportError = $message;
            return true;
        });
        try {
            $sent = mail($recipient, $subject, $body, $headers);
        } catch (Throwable $e) {
            $transportError = $e->getMessage();
        } finally {
            restore_error_handler();
        }
        if (!$sent && $transportError === '') {
            $transportError = 'The configured mail transport did not accept the message.';
        }
    }

    $status = $sent ? 'sent' : 'failed';
    $attemptedAt = date('Y-m-d H:i:s');
    $lastError = $sent ? null : substr($transportError, 0, 2000);
    $update = $conn->prepare("UPDATE shop_email_outbox SET status = ?, attempts = attempts + 1, last_error = ?, last_attempt_at = ? WHERE id = ?");
    if (!$update) {
        throw new RuntimeException('Could not update the email delivery record.');
    }
    $update->bind_param('sssi', $status, $lastError, $attemptedAt, $retryId);
    if (!$update->execute()) {
        throw new RuntimeException('Could not update the email delivery record.');
    }
    $update->close();

    return $sent;
}

/**
 * Minimal authenticated SMTP sender (implicit TLS on 465, or STARTTLS on 587).
 * Configured with URTH_SMTP_HOST, URTH_SMTP_PORT, URTH_SMTP_USER, URTH_SMTP_PASS.
 */
function shop_smtp_send(string $host, string $from, string $recipient, string $subject, string $body, string $replyTo = ''): void
{
    $port = (int)(getenv('URTH_SMTP_PORT') ?: 465);
    $user = (string)getenv('URTH_SMTP_USER');
    $pass = (string)getenv('URTH_SMTP_PASS');
    if ($user === '' || $pass === '') {
        throw new RuntimeException('URTH_SMTP_USER and URTH_SMTP_PASS must be set.');
    }

    $remote = ($port === 465 ? 'ssl://' : 'tcp://') . $host . ':' . $port;
    $errno = 0;
    $errstr = '';
    $socket = @stream_socket_client($remote, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, stream_context_create([
        'ssl' => ['verify_peer' => true, 'verify_peer_name' => true, 'peer_name' => $host],
    ]));
    if (!$socket) {
        throw new RuntimeException("Could not connect to {$host}:{$port} ({$errstr}).");
    }
    stream_set_timeout($socket, 20);

    $read = static function () use ($socket): string {
        $response = '';
        while (($line = fgets($socket, 1024)) !== false) {
            $response .= $line;
            if (strlen($line) < 4 || $line[3] === ' ') {
                break;
            }
        }
        return $response;
    };
    $command = static function (?string $line, array $expect, bool $secret = false) use ($socket, $read): string {
        if ($line !== null) {
            fwrite($socket, $line . "\r\n");
        }
        $response = $read();
        $code = (int)substr($response, 0, 3);
        if (!in_array($code, $expect, true)) {
            $shown = $secret ? '[credentials]' : substr((string)$line, 0, 120);
            throw new RuntimeException(trim("Server rejected {$shown}: {$response}"));
        }
        return $response;
    };

    try {
        $heloHost = preg_replace('/[^A-Za-z0-9.-]/', '', (string)($_SERVER['SERVER_NAME'] ?? '')) ?: 'localhost';
        $command(null, [220]);
        $command("EHLO {$heloHost}", [250]);
        if ($port !== 465) {
            $command('STARTTLS', [220]);
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException('STARTTLS negotiation failed.');
            }
            $command("EHLO {$heloHost}", [250]);
        }
        $command('AUTH LOGIN', [334]);
        $command(base64_encode($user), [334], true);
        $command(base64_encode($pass), [235], true);
        $command("MAIL FROM:<{$from}>", [250]);
        $command("RCPT TO:<{$recipient}>", [250, 251]);
        $command('DATA', [354]);

        $domain = substr(strrchr($from, '@'), 1);
        $headers = [
            'Date: ' . date('r'),
            'From: URTH <' . $from . '>',
            'To: <' . $recipient . '>',
            'Subject: =?UTF-8?B?' . base64_encode($subject) . '?=',
            'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . $domain . '>',
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: base64',
        ];
        if ($replyTo !== '') {
            $headers[] = 'Reply-To: <' . $replyTo . '>';
        }
        $message = implode("\r\n", $headers) . "\r\n\r\n"
            . rtrim(chunk_split(base64_encode(str_replace(["\r\n", "\n"], ["\n", "\r\n"], $body)), 76, "\r\n"))
            . "\r\n.";
        $command($message, [250]);
        $command('QUIT', [221]);
    } finally {
        fclose($socket);
    }
}
