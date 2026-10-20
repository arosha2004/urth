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

function send_shop_email(mysqli $conn, string $recipient, string $subject, string $body, ?int $retryId = null): bool
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
    if ($from === '' || !filter_var($from, FILTER_VALIDATE_EMAIL)) {
        $transportError = 'URTH_MAIL_FROM is not configured with a valid sender address.';
    } elseif (!function_exists('mail')) {
        $transportError = 'PHP mail() is unavailable on this server.';
    } else {
        $headers = "From: {$from}\r\nContent-Type: text/plain; charset=UTF-8";
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
