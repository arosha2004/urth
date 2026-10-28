<?php

function create_shop_download_link(mysqli $conn, int $orderId, string $fileKey): string
{
    if ($orderId <= 0 || !private_storage_key_is_valid($fileKey) || strpos($fileKey, 'deliverables/') !== 0) {
        throw new InvalidArgumentException('A valid private order deliverable is required.');
    }

    $tableSql = "CREATE TABLE IF NOT EXISTS shop_download_tokens (
        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        token_hash CHAR(64) NOT NULL UNIQUE,
        file_key VARCHAR(255) NOT NULL,
        expires_at DATETIME NOT NULL,
        revoked_at DATETIME DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (order_id),
        INDEX (expires_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    if (!$conn->query($tableSql)) {
        throw new RuntimeException('Could not initialize secure download links.');
    }

    $token = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    $tokenHash = hash('sha256', $token);
    $expiresAt = date('Y-m-d H:i:s', time() + (30 * 86400));
    $conn->begin_transaction();
    try {
        $revoke = $conn->prepare('UPDATE shop_download_tokens SET revoked_at = NOW() WHERE order_id = ? AND revoked_at IS NULL');
        if (!$revoke) {
            throw new RuntimeException('Could not rotate secure download access.');
        }
        $revoke->bind_param('i', $orderId);
        if (!$revoke->execute()) {
            throw new RuntimeException('Could not rotate secure download access.');
        }
        $revoke->close();

        $insert = $conn->prepare('INSERT INTO shop_download_tokens (order_id, token_hash, file_key, expires_at) VALUES (?, ?, ?, ?)');
        if (!$insert) {
            throw new RuntimeException('Could not create secure download access.');
        }
        $insert->bind_param('isss', $orderId, $tokenHash, $fileKey, $expiresAt);
        if (!$insert->execute()) {
            throw new RuntimeException('Could not create secure download access.');
        }
        $insert->close();

        $link = rtrim((string)(getenv('URTH_PUBLIC_BASE_URL') ?: 'https://urthspaces.com'), '/')
            . '/download.php?token=' . rawurlencode($token);
        $updateOrder = $conn->prepare('UPDATE orders SET digital_access_link = ? WHERE id = ?');
        if (!$updateOrder) {
            throw new RuntimeException('Could not save secure download access.');
        }
        $updateOrder->bind_param('si', $link, $orderId);
        if (!$updateOrder->execute()) {
            throw new RuntimeException('Could not save secure download access.');
        }
        $updateOrder->close();
        $conn->commit();
        return $link;
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }
}

function send_shop_digital_delivery(mysqli $conn, array $order, string $downloadLink): bool
{
    if (empty($order['customer_email']) || !filter_var($order['customer_email'], FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('A valid customer email address is required for digital delivery.');
    }
    $subject = 'Your URTH digital product — ' . $order['order_number'];
    $body = "Hello {$order['customer_name']},\n\nPayment for order {$order['order_number']} has been confirmed and your digital deliverable is ready.\n\nDownload your file using this private link (expires in 30 days):\n{$downloadLink}\n\nIf the link expires, contact URTH to request a new one.\n\nURTH Architecture Studio";
    return send_shop_email($conn, $order['customer_email'], $subject, $body);
}
