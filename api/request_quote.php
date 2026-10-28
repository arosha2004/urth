<?php
header('Content-Type: application/json; charset=utf-8');

$allowedOrigins = ['https://urthspaces.com', 'https://www.urthspaces.com', 'http://localhost', 'http://127.0.0.1'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins, true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Vary: Origin');
}
header('Cache-Control: no-store');

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax'
    ]);
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    echo json_encode(['status' => 'error', 'message' => 'Use POST to submit a quote request.']);
    exit;
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app/Core/private_storage.php';
require_once __DIR__ . '/../app/Core/shop_mail.php';

$storedFiles = [];
try {
    if ($origin !== '') {
        $scheme = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? '';
        if (!in_array($origin, $allowedOrigins, true) && ($host === '' || $origin !== $scheme . '://' . $host)) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'This quote request is not allowed.']);
            exit;
        }
    }

    if (!empty($_POST['website'])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'The quote request could not be submitted.']);
        exit;
    }

    $requestTimes = $_SESSION['quote_request_times'] ?? [];
    $requestTimes = array_values(array_filter($requestTimes, static function ($time) {
        return is_int($time) && $time > time() - 600;
    }));
    if (count($requestTimes) >= 5) {
        http_response_code(429);
        echo json_encode(['status' => 'error', 'message' => 'Too many quote requests. Please wait a few minutes and try again.']);
        exit;
    }
    $requestTimes[] = time();
    $_SESSION['quote_request_times'] = $requestTimes;

    foreach (['customer_name', 'customer_email', 'customer_phone', 'project_brief', 'selected_option', 'terms_accepted', 'product_id'] as $field) {
        if (isset($_POST[$field]) && !is_string($_POST[$field])) {
            http_response_code(422);
            echo json_encode(['status' => 'error', 'message' => 'Some quote request details are invalid. Please check the form and try again.']);
            exit;
        }
    }

    $customerName = trim($_POST['customer_name'] ?? '');
    $customerEmail = trim($_POST['customer_email'] ?? '');
    $customerPhone = trim($_POST['customer_phone'] ?? '');
    $brief = trim($_POST['project_brief'] ?? '');
    $selectedOption = trim($_POST['selected_option'] ?? '');
    $productId = filter_var($_POST['product_id'] ?? null, FILTER_VALIDATE_INT);
    if (($_POST['terms_accepted'] ?? '') !== '1'
        || $customerName === '' || strlen($customerName) > 255
        || !filter_var($customerEmail, FILTER_VALIDATE_EMAIL) || strlen($customerEmail) > 255
        || $customerPhone === '' || strlen($customerPhone) > 50
        || $brief === '' || strlen($brief) > 10000
        || $productId === false || $productId === null || $productId <= 0) {
        http_response_code(422);
        echo json_encode(['status' => 'error', 'message' => 'Check the required contact, project and consent details.']);
        exit;
    }

    $ipHash = hash_hmac('sha256', $_SERVER['REMOTE_ADDR'] ?? 'unknown', $db_pass . $db_name);
    if (!$conn->query("CREATE TABLE IF NOT EXISTS shop_order_rate_limits (
        ip_hash CHAR(64) NOT NULL PRIMARY KEY,
        window_started INT NOT NULL,
        request_count INT NOT NULL DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4")) {
        throw new RuntimeException('Could not initialize quote request rate limit.');
    }
    $rateStmt = $conn->prepare("INSERT INTO shop_order_rate_limits (ip_hash, window_started, request_count)
        VALUES (?, UNIX_TIMESTAMP(), 1)
        ON DUPLICATE KEY UPDATE
            request_count = IF(window_started < UNIX_TIMESTAMP() - 600, 1, request_count + 1),
            window_started = IF(window_started < UNIX_TIMESTAMP() - 600, UNIX_TIMESTAMP(), window_started)");
    if (!$rateStmt) {
        throw new RuntimeException('Could not prepare quote request rate limit.');
    }
    $rateStmt->bind_param('s', $ipHash);
    $rateStmt->execute();
    $rateStmt->close();
    $countStmt = $conn->prepare('SELECT request_count FROM shop_order_rate_limits WHERE ip_hash = ?');
    $countStmt->bind_param('s', $ipHash);
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $requestCount = $countResult ? (int)$countResult->fetch_assoc()['request_count'] : 0;
    $countStmt->close();
    if ($requestCount > 10) {
        http_response_code(429);
        echo json_encode(['status' => 'error', 'message' => 'Too many quote requests from this network. Please wait before trying again.']);
        exit;
    }

    $productStmt = $conn->prepare('SELECT id, name, sku, image, currency, product_type, options_list, price_on_request, availability, is_active FROM products WHERE id = ? LIMIT 1');
    if (!$productStmt) {
        throw new RuntimeException('Could not prepare quote product lookup.');
    }
    $productStmt->bind_param('i', $productId);
    $productStmt->execute();
    $productResult = $productStmt->get_result();
    $product = $productResult ? $productResult->fetch_assoc() : null;
    $productStmt->close();

    $quoteAllowed = $product && (int)$product['is_active'] === 1
        && !in_array($product['availability'] ?? '', ['coming_soon', 'unavailable', 'fully_booked'], true) && (
        (int)($product['price_on_request'] ?? 0) === 1
        || in_array($product['availability'] ?? '', ['quote_only', 'made_to_order'], true)
        || in_array($product['product_type'] ?? '', ['furniture_object', 'design_session'], true)
    );
    if (!$quoteAllowed) {
        http_response_code(422);
        echo json_encode(['status' => 'error', 'message' => 'This product is no longer available for a quote request.']);
        exit;
    }

    $options = array_filter(array_map('trim', explode(',', (string)($product['options_list'] ?? ''))));
    if ($selectedOption !== '' && !in_array($selectedOption, $options, true)) {
        http_response_code(422);
        echo json_encode(['status' => 'error', 'message' => 'The selected product option is no longer available.']);
        exit;
    }

    $customerFiles = $_FILES['customer_files'] ?? null;
    if ($customerFiles !== null && !is_array($customerFiles['name'] ?? null)) {
        http_response_code(422);
        echo json_encode(['status' => 'error', 'message' => 'The reference file upload is invalid.']);
        exit;
    }
    if ($customerFiles !== null && !empty($customerFiles['name'][0])) {
        if (!in_array($product['product_type'], ['furniture_object', 'design_session'], true)) {
            http_response_code(422);
            echo json_encode(['status' => 'error', 'message' => 'Reference files are not accepted for this product type.']);
            exit;
        }
        if (count($customerFiles['name']) > 5) {
            http_response_code(422);
            echo json_encode(['status' => 'error', 'message' => 'Upload no more than five reference files.']);
            exit;
        }
        for ($index = 0; $index < count($customerFiles['name']); $index++) {
            if (($customerFiles['error'][$index] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $upload = [
                'name' => $customerFiles['name'][$index] ?? '',
                'tmp_name' => $customerFiles['tmp_name'][$index] ?? '',
                'error' => $customerFiles['error'][$index] ?? UPLOAD_ERR_NO_FILE,
                'size' => $customerFiles['size'][$index] ?? 0
            ];
            $uploadError = null;
            $storedFile = store_private_upload($upload, 'customer-uploads', ['jpg', 'jpeg', 'png', 'webp', 'pdf'], 10 * 1024 * 1024, $uploadError);
            if ($storedFile === null) {
                foreach ($storedFiles as $previousFile) {
                    $previousPath = private_storage_path($previousFile);
                    if ($previousPath !== null && is_file($previousPath)) {
                        unlink($previousPath);
                    }
                }
                $storedFiles = [];
                http_response_code(422);
                echo json_encode(['status' => 'error', 'message' => 'A reference file could not be uploaded. Check file types and the 10 MB per-file limit.']);
                exit;
            }
            $storedFiles[] = $storedFile;
        }
    }

    $orderNumber = 'URTH-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
    $statusToken = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    $statusTokenHash = hash('sha256', $statusToken);
    $expiresAt = date('Y-m-d H:i:s', time() + (30 * 86400));
    $uploadsJson = json_encode($storedFiles, JSON_UNESCAPED_SLASHES);
    $currency = $product['currency'] ?: '£';
    $paymentMethod = null;

    $conn->begin_transaction();
    $orderStmt = $conn->prepare("INSERT INTO orders (order_number, order_type, customer_name, customer_email, customer_phone, order_notes, shipping_method, subtotal, shipping_cost, total, currency, status, payment_status, fulfilment_status, payment_method, terms_accepted_at, customer_uploads, customer_status_token_hash, customer_status_token_expires_at) VALUES (?, 'quote_request', ?, ?, ?, ?, 'Confirm after order review', 0, 0, 0, ?, 'Pending', 'pending_payment', 'enquiry', ?, NOW(), ?, ?, ?)");
    if (!$orderStmt) {
        throw new RuntimeException('Could not prepare quote request record.');
    }
    $orderStmt->bind_param('ssssssssss', $orderNumber, $customerName, $customerEmail, $customerPhone, $brief, $currency, $paymentMethod, $uploadsJson, $statusTokenHash, $expiresAt);
    if (!$orderStmt->execute()) {
        throw new RuntimeException('Could not save quote request.');
    }
    $orderId = (int)$conn->insert_id;
    $orderStmt->close();

    $itemStmt = $conn->prepare('INSERT INTO order_items (order_id, product_id, product_name, product_sku, selected_option, price, quantity, total, image) VALUES (?, ?, ?, ?, ?, 0, 1, 0, ?)');
    if (!$itemStmt) {
        throw new RuntimeException('Could not prepare quote request product.');
    }
    $itemStmt->bind_param('iissss', $orderId, $productId, $product['name'], $product['sku'], $selectedOption, $product['image']);
    if (!$itemStmt->execute()) {
        throw new RuntimeException('Could not save quote request product.');
    }
    $itemStmt->close();
    $conn->commit();

    $baseUrl = rtrim((string)(getenv('URTH_PUBLIC_BASE_URL') ?: 'https://urthspaces.com'), '/');
    $statusUrl = $baseUrl . '/order-status.php?token=' . rawurlencode($statusToken);
    $body = "Hello {$customerName},\n\nURTH received your quote request {$orderNumber} for {$product['name']}.\n\nProject brief:\n{$brief}\n\nNo payment is due until URTH reviews your request and confirms a written quote.\n\nSecure status link (expires in 30 days): {$statusUrl}\n\nURTH Architecture Studio";
    $customerEmailSent = false;
    $studioEmailSent = false;
    try {
        $customerEmailSent = send_shop_email($conn, $customerEmail, "URTH quote request received — {$orderNumber}", $body);
    } catch (Throwable $e) {
        error_log('URTH quote acknowledgement could not be queued: ' . $e->getMessage());
    }
    try {
        $studioRecipient = trim((string)(getenv('URTH_ORDER_EMAIL') ?: 'info@urthspaces.com'));
        $studioEmailSent = send_shop_email($conn, $studioRecipient, "New URTH quote request — {$orderNumber}", "Review quote request {$orderNumber} for {$product['name']} from {$customerName} ({$customerEmail}, {$customerPhone}).\n\nProject brief:\n{$brief}\n\nManage requests: {$baseUrl}/admin/orders.php");
    } catch (Throwable $e) {
        error_log('URTH quote notification could not be queued: ' . $e->getMessage());
    }

    echo json_encode([
        'status' => 'success',
        'order_number' => $orderNumber,
        'email_status' => ($customerEmailSent && $studioEmailSent) ? 'sent' : 'pending'
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->rollback();
    }
    foreach ($storedFiles as $storedFile) {
        $storedPath = private_storage_path($storedFile);
        if ($storedPath !== null && is_file($storedPath)) {
            unlink($storedPath);
        }
    }
    http_response_code(500);
    error_log('URTH quote request submission failed: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'We could not submit your quote request. Please try again or contact URTH.']);
}
