<?php
/**
 * URTH Architecture Studio - API: Place Order & Store in MySQL
 */

header('Content-Type: application/json; charset=utf-8');

$allowed_origins = [
    'https://urthspaces.com',
    'https://www.urthspaces.com',
    'http://localhost',
    'http://127.0.0.1',
];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins, true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Vary: Origin');
}
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax'
    ]);
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST, OPTIONS');
    echo json_encode(['status' => 'error', 'message' => 'Use POST to submit an order.']);
    exit;
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app/Core/private_storage.php';
require_once __DIR__ . '/../app/Core/shop_mail.php';

$storedCustomerUploads = [];

try {
    $contentType = strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0]));
    if ($contentType === 'application/json') {
        $rawInput = file_get_contents('php://input');
        if ($rawInput === false || strlen($rawInput) > 65536) {
            http_response_code(413);
            echo json_encode(['status' => 'error', 'message' => 'The order request is too large. Please review your cart and try again.']);
            exit;
        }
        $data = json_decode($rawInput, true);
    } elseif ($contentType === 'multipart/form-data') {
        $data = $_POST;
        $data['items'] = json_decode((string)($_POST['items'] ?? ''), true);
    } else {
        http_response_code(415);
        echo json_encode(['status' => 'error', 'message' => 'Please submit your order using the checkout form.']);
        exit;
    }

    if ($origin !== '' && !in_array($origin, $allowed_origins, true)) {
        $requestScheme = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
        $requestHost = $_SERVER['HTTP_HOST'] ?? '';
        if ($requestHost === '' || $origin !== $requestScheme . '://' . $requestHost) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'This order request is not allowed.']);
            exit;
        }
    }

    $rateLimitTableSql = "CREATE TABLE IF NOT EXISTS shop_order_rate_limits (
        ip_hash CHAR(64) NOT NULL PRIMARY KEY,
        window_started INT NOT NULL,
        request_count INT NOT NULL DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    if (!$conn->query($rateLimitTableSql)) {
        throw new RuntimeException('Could not initialize checkout rate limit.');
    }
    if (random_int(1, 100) === 1) {
        $conn->query("DELETE FROM shop_order_rate_limits WHERE window_started < UNIX_TIMESTAMP() - 86400");
    }
    $ipHash = hash_hmac('sha256', $_SERVER['REMOTE_ADDR'] ?? 'unknown', $db_pass . $db_name);
    $rateStmt = $conn->prepare("INSERT INTO shop_order_rate_limits (ip_hash, window_started, request_count)
        VALUES (?, UNIX_TIMESTAMP(), 1)
        ON DUPLICATE KEY UPDATE
            request_count = IF(window_started < UNIX_TIMESTAMP() - 600, 1, request_count + 1),
            window_started = IF(window_started < UNIX_TIMESTAMP() - 600, UNIX_TIMESTAMP(), window_started)");
    if (!$rateStmt) {
        throw new RuntimeException('Could not prepare checkout rate limit.');
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
        echo json_encode(['status' => 'error', 'message' => 'Too many order attempts from this network. Please wait a few minutes and try again.']);
        exit;
    }

    $requestTimes = $_SESSION['order_request_times'] ?? [];
    $requestTimes = array_values(array_filter($requestTimes, static function ($time) {
        return is_int($time) && $time > time() - 600;
    }));
    if (count($requestTimes) >= 5) {
        http_response_code(429);
        echo json_encode(['status' => 'error', 'message' => 'Too many order attempts. Please wait a few minutes and try again.']);
        exit;
    }
    $requestTimes[] = time();
    $_SESSION['order_request_times'] = $requestTimes;

    if (!is_array($data)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid or empty request payload']);
        exit;
    }

    if (!empty($data['website'])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'The order could not be submitted. Please try again.']);
        exit;
    }

    foreach (['customer_name', 'customer_phone', 'customer_email', 'delivery_address', 'order_notes', 'shipping_method', 'payment_method', 'terms_accepted'] as $field) {
        if (isset($data[$field]) && !is_string($data[$field])) {
            http_response_code(422);
            echo json_encode(['status' => 'error', 'message' => 'Some order details are invalid. Please check the form and try again.']);
            exit;
        }
    }

    $customer_name = trim($data['customer_name'] ?? '');
    $customer_phone = trim($data['customer_phone'] ?? '');
    $customer_email = trim($data['customer_email'] ?? '');
    $delivery_address = trim($data['delivery_address'] ?? '');
    $order_notes = trim($data['order_notes'] ?? '');
    $shipping_method = trim($data['shipping_method'] ?? 'Standard Delivery');
    if ($shipping_method !== 'Confirm after order review') {
        http_response_code(422);
        echo json_encode(['status' => 'error', 'message' => 'Please choose a valid delivery option.']);
        exit;
    }
    $shipping_cost = 0.00;
    $payment_method = trim($data['payment_method'] ?? '');
    if ($payment_method !== 'Bank transfer' || ($data['terms_accepted'] ?? '') !== '1') {
        http_response_code(422);
        echo json_encode(['status' => 'error', 'message' => 'Choose an available payment method and accept the shop terms and privacy notice.']);
        exit;
    }
    $items = isset($data['items']) && is_array($data['items']) ? $data['items'] : [];

    if ($customer_name === '' || $customer_phone === '' || strlen($customer_name) > 255 || strlen($customer_phone) > 50) {
        http_response_code(422);
        echo json_encode(['status' => 'error', 'message' => 'Customer name and phone number are required.']);
        exit;
    }

    if (!filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(422);
        echo json_encode(['status' => 'error', 'message' => 'Please enter a valid email address.']);
        exit;
    }

    if (strlen($customer_email) > 255 || strlen($delivery_address) > 10000 || strlen($order_notes) > 10000) {
        http_response_code(422);
        echo json_encode(['status' => 'error', 'message' => 'Some order details are too long. Please shorten them and try again.']);
        exit;
    }

    if (empty($items)) {
        http_response_code(422);
        echo json_encode(['status' => 'error', 'message' => 'Shopping cart is empty.']);
        exit;
    }

    if (count($items) > 30) {
        http_response_code(422);
        echo json_encode(['status' => 'error', 'message' => 'Your cart has too many different items. Please review it and try again.']);
        exit;
    }

    // Rebuild order lines from the current catalogue; never accept prices or descriptions from the browser.
    $conn->begin_transaction();
    $productStmt = $conn->prepare("SELECT id, name, sku, price, sale_price, currency, image, availability, is_active, price_on_request, product_type, options_list, book_format, stock_quantity, booking_capacity, booked_count, allow_backorder FROM products WHERE id = ? LIMIT 1 FOR UPDATE");
    if (!$productStmt) {
        throw new RuntimeException('Could not prepare product lookup.');
    }
    $verifiedItems = [];
    $subtotal = 0;
    $currency = null;
    $requiresDeliveryAddress = false;
    $acceptsCustomerUploads = false;
    $lockedProducts = [];
    $requestedQuantities = [];
    foreach ($items as $item) {
        if (!is_array($item)) {
            http_response_code(422);
            echo json_encode(['status' => 'error', 'message' => 'One or more cart items are invalid. Please refresh your cart and try again.']);
            exit;
        }
        $productId = filter_var($item['id'] ?? null, FILTER_VALIDATE_INT);
        $qty = filter_var($item['qty'] ?? null, FILTER_VALIDATE_INT);
        if ($productId === false || $productId === null || $qty === false || $qty === null || $qty < 1 || $qty > 99) {
            http_response_code(422);
            echo json_encode(['status' => 'error', 'message' => 'One or more cart items are invalid. Please refresh your cart and try again.']);
            exit;
        }
        $productStmt->bind_param('i', $productId);
        $productStmt->execute();
        $productResult = $productStmt->get_result();
        $product = $productResult ? $productResult->fetch_assoc() : null;
        if (!$product || !(int)$product['is_active'] || (int)$product['price_on_request']
            || in_array(strtolower($product['availability'] ?? ''), ['coming_soon', 'unavailable', 'fully_booked'], true)) {
            http_response_code(422);
            echo json_encode(['status' => 'error', 'message' => 'A cart item is no longer available. Please refresh your cart.']);
            exit;
        }
        $productType = $product['product_type'] ?? 'furniture_object';
        $productCurrency = (!empty($product['currency']) && $product['currency'] !== '£' && $product['currency'] !== 'GBP') ? $product['currency'] : 'LKR';
        $lockedProducts[(int)$product['id']] = $product;
        $requestedQuantities[(int)$product['id']] = ($requestedQuantities[(int)$product['id']] ?? 0) + $qty;
        if ($currency !== null && $currency !== $productCurrency) {
            http_response_code(422);
            echo json_encode(['status' => 'error', 'message' => 'Products with different currencies must be ordered separately.']);
            exit;
        }
        $currency = $productCurrency;
        $productOption = trim((string)($item['option'] ?? ''));
        $availableOptions = array_filter(array_map('trim', explode(',', (string)($product['options_list'] ?? ''))));
        if ($productOption !== '' && !in_array($productOption, $availableOptions, true)) {
            http_response_code(422);
            echo json_encode(['status' => 'error', 'message' => 'A selected product option is no longer available. Please refresh the product page.']);
            exit;
        }
        if ($productType === 'furniture_object'
            || ($productType === 'book_planner' && preg_match('/print|physical/i', (string)($product['book_format'] ?? '')))) {
            $requiresDeliveryAddress = true;
        }
        if (in_array($productType, ['furniture_object', 'design_session'], true)) {
            $acceptsCustomerUploads = true;
        }
        $price = isset($product['sale_price']) && (float)$product['sale_price'] > 0
            ? (float)$product['sale_price']
            : (float)$product['price'];
        if ($price <= 0) {
            http_response_code(422);
            echo json_encode(['status' => 'error', 'message' => 'A cart item has an invalid price. Please contact the studio.']);
            exit;
        }
        $subtotal += ($price * $qty);
        $verifiedItems[] = [
            'id' => (int)$product['id'],
            'name' => $product['name'],
            'sku' => $product['sku'] ?? '',
            'price' => $price,
            'qty' => $qty,
            'image' => $product['image'] ?? '',
            'currency' => $productCurrency,
            'option' => $productOption,
            'product_type' => $productType
        ];
    }
    $productStmt->close();
    $items = $verifiedItems;

    foreach ($requestedQuantities as $reservedProductId => $requestedQuantity) {
        $reservedProduct = $lockedProducts[$reservedProductId];
        $reservedType = $reservedProduct['product_type'] ?? 'furniture_object';
        $reservedAvailability = strtolower($reservedProduct['availability'] ?? 'available');
        $isPhysical = $reservedType === 'furniture_object'
            || ($reservedType === 'book_planner' && preg_match('/print|physical/i', (string)($reservedProduct['book_format'] ?? '')));
        $isBookable = in_array($reservedType, ['course_workshop', 'design_session'], true);

        if ($isPhysical && $reservedAvailability !== 'made_to_order' && !(int)$reservedProduct['allow_backorder']
            && (int)$reservedProduct['stock_quantity'] < $requestedQuantity) {
            $conn->rollback();
            http_response_code(422);
            echo json_encode(['status' => 'error', 'message' => 'There is not enough stock to accept this order request. Please contact URTH to confirm availability.']);
            exit;
        }
        if ($isBookable && (int)$reservedProduct['booking_capacity'] > 0
            && (int)$reservedProduct['booked_count'] + $requestedQuantity > (int)$reservedProduct['booking_capacity']) {
            $conn->rollback();
            http_response_code(422);
            echo json_encode(['status' => 'error', 'message' => 'There are not enough available places or consultation slots for this request. Please contact URTH.']);
            exit;
        }
    }

    if ($requiresDeliveryAddress && $delivery_address === '') {
        http_response_code(422);
        echo json_encode(['status' => 'error', 'message' => 'A delivery address is required for physical products.']);
        exit;
    }
    if (!$requiresDeliveryAddress) {
        $delivery_address = '';
    }

    $customerUploads = $_FILES['customer_files'] ?? null;
    if ($customerUploads !== null && !empty($customerUploads['name'][0])) {
        if (!$acceptsCustomerUploads) {
            http_response_code(422);
            echo json_encode(['status' => 'error', 'message' => 'Reference files are only accepted for custom furniture and design-session requests.']);
            exit;
        }
        if (count($customerUploads['name']) > 5) {
            http_response_code(422);
            echo json_encode(['status' => 'error', 'message' => 'Upload no more than five reference files.']);
            exit;
        }
        $allowedUploadExtensions = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
        for ($fileIndex = 0; $fileIndex < count($customerUploads['name']); $fileIndex++) {
            if (($customerUploads['error'][$fileIndex] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $upload = [
                'name' => $customerUploads['name'][$fileIndex] ?? '',
                'tmp_name' => $customerUploads['tmp_name'][$fileIndex] ?? '',
                'error' => $customerUploads['error'][$fileIndex] ?? UPLOAD_ERR_NO_FILE,
                'size' => $customerUploads['size'][$fileIndex] ?? 0
            ];
            $uploadError = null;
            $storedUpload = store_private_upload($upload, 'customer-uploads', $allowedUploadExtensions, 10 * 1024 * 1024, $uploadError);
            if ($storedUpload === null) {
                foreach ($storedCustomerUploads as $previousUpload) {
                    $previousPath = private_storage_path($previousUpload);
                    if ($previousPath !== null && is_file($previousPath)) {
                        unlink($previousPath);
                    }
                }
                $storedCustomerUploads = [];
                http_response_code(422);
                echo json_encode(['status' => 'error', 'message' => 'A reference file could not be uploaded. Check file types and the 10 MB per-file limit.']);
                exit;
            }
            $storedCustomerUploads[] = $storedUpload;
        }
    }

    $total = $subtotal + $shipping_cost;
    $currency = $currency ?? 'LKR';
    $statusToken = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    $statusTokenHash = hash('sha256', $statusToken);
    $statusTokenExpiresAt = date('Y-m-d H:i:s', time() + (30 * 86400));
    $customerUploadsJson = json_encode($storedCustomerUploads, JSON_UNESCAPED_SLASHES);

    // Generate unique order number
    $order_number = 'URTH-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));

    $orderStmt = $conn->prepare("INSERT INTO orders (order_number, customer_name, customer_email, customer_phone, delivery_address, order_notes, shipping_method, subtotal, shipping_cost, total, currency, status, payment_method, terms_accepted_at, customer_uploads, customer_status_token_hash, customer_status_token_expires_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', ?, NOW(), ?, ?, ?)");
    if (!$orderStmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $orderStmt->bind_param("sssssssdddsssss", $order_number, $customer_name, $customer_email, $customer_phone, $delivery_address, $order_notes, $shipping_method, $subtotal, $shipping_cost, $total, $currency, $payment_method, $customerUploadsJson, $statusTokenHash, $statusTokenExpiresAt);
    $orderStmt->execute();
    $order_id = $conn->insert_id;
    $orderStmt->close();

    // Insert order items
    $itemStmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, product_name, product_sku, selected_option, price, quantity, total, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$itemStmt) {
        throw new Exception("Prepare items failed: " . $conn->error);
    }

    foreach ($items as $it) {
        $prod_id = isset($it['id']) && is_numeric($it['id']) ? (int)$it['id'] : null;
        $prod_name = trim($it['name'] ?? 'Product');
        $prod_sku = trim($it['sku'] ?? '');
        $selected_option = trim($it['option'] ?? '');
        $it_price = (float)($it['price'] ?? 0);
        $it_qty = max(1, (int)($it['qty'] ?? 1));
        $it_total = $it_price * $it_qty;
        $it_image = trim($it['image'] ?? '');

        $itemStmt->bind_param("iisssdids", $order_id, $prod_id, $prod_name, $prod_sku, $selected_option, $it_price, $it_qty, $it_total, $it_image);
        $itemStmt->execute();
    }
    $itemStmt->close();

    $conn->commit();

    $baseUrl = rtrim((string)(getenv('URTH_PUBLIC_BASE_URL') ?: 'https://urthspaces.com'), '/');
    $statusUrl = $baseUrl . '/order-status.php?token=' . rawurlencode($statusToken);
    $itemSummary = implode("\n", array_map(static function ($item) {
        $option = $item['option'] !== '' ? ' (' . $item['option'] . ')' : '';
        $sku = $item['sku'] !== '' ? ' [SKU ' . $item['sku'] . ']' : '';
        return '- ' . $item['qty'] . ' x ' . $item['name'] . $option . $sku . ' — ' . $item['currency'] . ' ' . number_format($item['price'] * $item['qty'], 2);
    }, $items));
    $subtotalFormatted = $currency . ' ' . number_format($subtotal, 2);
    $ordersMailbox = trim((string)(getenv('URTH_SHOP_ORDER_EMAIL') ?: 'sales@shop.urthspaces.com'));
    $whatsAppNumber = '94768008687';
    $whatsAppUrl = 'https://wa.me/' . $whatsAppNumber . '?text=' . rawurlencode("Hello URTH, I'd like to talk about my order {$order_number}.");
    $customerEmailSent = false;
    $studioEmailSent = false;

    $customerBody = "Hello {$customer_name},\n\n"
        . "Thank you for your order. We've received order request {$order_number}.\n\n"
        . "Payment is pending while URTH reviews availability, delivery arrangements and the final total. "
        . "Please wait for us to confirm the final total and payment instructions before paying.\n\n"
        . "ORDER SUMMARY\n"
        . "Order number: {$order_number}\n"
        . "Date: " . date('j M Y, g:i a') . "\n\n"
        . "Items:\n{$itemSummary}\n\n"
        . "Provisional item subtotal: {$subtotalFormatted}\n"
        . "Delivery: {$shipping_method}\n"
        . "Preferred payment method: {$payment_method}\n"
        . ($delivery_address !== '' ? "\nDelivery address:\n{$delivery_address}\n" : '')
        . ($order_notes !== '' ? "\nYour notes:\n{$order_notes}\n" : '')
        . "\nTrack your order securely (link valid for 30 days):\n{$statusUrl}\n\n"
        . "QUESTIONS ABOUT YOUR ORDER?\n"
        . "Reply to this email or write to {$ordersMailbox}, quoting {$order_number}.\n"
        . "WhatsApp: {$whatsAppUrl}\n\n"
        . "URTH Architecture Studio";
    try {
        $customerEmailSent = send_shop_email($conn, $customer_email, "Your URTH order confirmation — {$order_number}", $customerBody, null, $ordersMailbox);
    } catch (Throwable $emailError) {
        error_log('URTH customer order confirmation could not be queued: ' . $emailError->getMessage());
    }

    $studioBody = "A new order request is awaiting review.\n\n"
        . "Order: {$order_number} (ID {$order_id})\n"
        . "Placed: " . date('Y-m-d H:i:s') . "\n\n"
        . "CUSTOMER\n"
        . "Name: {$customer_name}\n"
        . "Email: {$customer_email}\n"
        . "Phone / WhatsApp: {$customer_phone}\n"
        . "Delivery address: " . ($delivery_address !== '' ? "\n{$delivery_address}" : 'Not required') . "\n\n"
        . "ORDER\n"
        . "Items:\n{$itemSummary}\n\n"
        . "Provisional subtotal: {$subtotalFormatted}\n"
        . "Delivery: {$shipping_method}\n"
        . "Payment method: {$payment_method} (pending)\n"
        . (count($storedCustomerUploads) > 0 ? "Reference files uploaded: " . count($storedCustomerUploads) . " (view in admin)\n" : '')
        . ($order_notes !== '' ? "\nCustomer notes:\n{$order_notes}\n" : '')
        . "\nReply to this email to contact the customer directly.\n"
        . "Manage orders: {$baseUrl}/admin/orders.php";
    try {
        $studioEmailSent = send_shop_email($conn, $ordersMailbox, "New URTH order — {$order_number} — {$customer_name}", $studioBody, null, $customer_email);
    } catch (Throwable $emailError) {
        error_log('URTH studio order notification could not be queued: ' . $emailError->getMessage());
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Order request saved. Payment is pending while URTH reviews the order.',
        'order_number' => $order_number,
        'order_id' => $order_id,
        'customer_name' => $customer_name,
        'subtotal' => number_format($subtotal, 2),
        'total' => number_format($total, 2),
        'currency' => $currency,
        'email_status' => $customerEmailSent ? 'sent' : 'pending',
        'studio_email_status' => $studioEmailSent ? 'sent' : 'pending',
        'orders_email' => $ordersMailbox,
        'items_count' => count($items),
        'items' => $items
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    if (isset($conn) && $conn instanceof mysqli) {
        @$conn->rollback();
    }
    foreach ($storedCustomerUploads as $storedUpload) {
        $storedPath = private_storage_path($storedUpload);
        if ($storedPath !== null && is_file($storedPath)) {
            unlink($storedPath);
        }
    }
    http_response_code(500);
    error_log('URTH order submission failed: ' . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'We could not submit your order just now. Please try again or contact the studio.'
    ]);
}
