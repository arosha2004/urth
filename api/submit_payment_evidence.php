<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app/Core/private_storage.php';
require_once __DIR__ . '/../app/Core/shop_mail.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Use POST to submit payment evidence.');
}

$token = (string)($_POST['token'] ?? '');
if (!preg_match('/^[A-Za-z0-9_-]{43}$/D', $token)) {
    http_response_code(404);
    exit('Order link is invalid or has expired.');
}

$tokenHash = hash('sha256', $token);
$stmt = $conn->prepare("SELECT id, order_number, order_type, total, fulfilment_status, payment_status, receipt_url FROM orders WHERE customer_status_token_hash = ? AND customer_status_token_expires_at > NOW() LIMIT 1");
if (!$stmt) {
    throw new RuntimeException('Could not prepare payment-evidence order lookup.');
}
$stmt->bind_param('s', $tokenHash);
$stmt->execute();
$result = $stmt->get_result();
$order = $result ? $result->fetch_assoc() : null;
$stmt->close();

if (!$order) {
    http_response_code(404);
    exit('Order link is invalid or has expired.');
}

$statusUrl = '../order-status.php?token=' . rawurlencode($token);
if (($order['order_type'] ?? 'order') === 'quote_request'
    && ((float)$order['total'] <= 0 || $order['fulfilment_status'] !== 'quote_accepted')) {
    header('Location: ' . $statusUrl);
    exit;
}
if (!in_array($order['payment_status'], ['pending_payment', 'deposit_paid', 'balance_pending'], true)) {
    header('Location: ' . $statusUrl);
    exit;
}
if (!empty($order['receipt_url'])) {
    header('Location: ' . $statusUrl);
    exit;
}

$uploadError = null;
$storedReceipt = store_private_upload(
    $_FILES['payment_evidence'] ?? [],
    'receipts',
    ['pdf', 'jpg', 'jpeg', 'png', 'webp'],
    10 * 1024 * 1024,
    $uploadError
);
if ($storedReceipt === null) {
    header('Location: ' . $statusUrl . '&evidence=error');
    exit;
}

$update = $conn->prepare("UPDATE orders SET receipt_url = ? WHERE id = ? AND receipt_url IS NULL AND payment_status IN ('pending_payment', 'deposit_paid', 'balance_pending')");
if (!$update) {
    $filePath = private_storage_path($storedReceipt);
    if ($filePath !== null && is_file($filePath)) {
        unlink($filePath);
    }
    throw new RuntimeException('Could not prepare payment-evidence update.');
}
$orderId = (int)$order['id'];
$update->bind_param('si', $storedReceipt, $orderId);
$updated = $update->execute() && $update->affected_rows === 1;
$update->close();

if (!$updated) {
    $filePath = private_storage_path($storedReceipt);
    if ($filePath !== null && is_file($filePath)) {
        unlink($filePath);
    }
    header('Location: ' . $statusUrl);
    exit;
}

try {
    $studioRecipient = trim((string)(getenv('URTH_ORDER_EMAIL') ?: 'info@urthspaces.com'));
    send_shop_email(
        $conn,
        $studioRecipient,
        'Payment evidence submitted — ' . $order['order_number'],
        "Payment evidence was submitted for order {$order['order_number']}.\nReview the receipt in the authenticated admin orders dashboard."
    );
} catch (Throwable $e) {
    error_log('URTH payment evidence notification could not be queued: ' . $e->getMessage());
}

header('Location: ' . $statusUrl . '&evidence=received');
exit;
