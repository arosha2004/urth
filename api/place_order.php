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

try {
    $contentType = strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0]));
    if ($contentType !== 'application/json') {
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

    $rawInput = file_get_contents('php://input');
    if ($rawInput === false || strlen($rawInput) > 65536) {
        http_response_code(413);
        echo json_encode(['status' => 'error', 'message' => 'The order request is too large. Please review your cart and try again.']);
        exit;
    }
    $data = json_decode($rawInput, true);

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

    foreach (['customer_name', 'customer_phone', 'customer_email', 'delivery_address', 'order_notes', 'shipping_method'] as $field) {
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
    $shipping_options = [
        'Studio Collection' => 0.00,
        'White Glove Courier' => 15.00
    ];
    if (!isset($shipping_options[$shipping_method])) {
        http_response_code(422);
        echo json_encode(['status' => 'error', 'message' => 'Please choose a valid delivery option.']);
        exit;
    }
    $shipping_cost = $shipping_options[$shipping_method];
    $items = isset($data['items']) && is_array($data['items']) ? $data['items'] : [];

    if ($customer_name === '' || $customer_phone === '' || strlen($customer_name) > 255 || strlen($customer_phone) > 50) {
        http_response_code(422);
        echo json_encode(['status' => 'error', 'message' => 'Customer name and phone number are required.']);
        exit;
    }

    if ($customer_email !== '' && !filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
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
    $productStmt = $conn->prepare("SELECT id, name, sku, price, sale_price, currency, image, availability, is_active, price_on_request FROM products WHERE id = ? LIMIT 1");
    if (!$productStmt) {
        throw new RuntimeException('Could not prepare product lookup.');
    }
    $verifiedItems = [];
    $subtotal = 0;
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
            'currency' => $product['currency'] ?? '£'
        ];
    }
    $productStmt->close();
    $items = $verifiedItems;

    $total = $subtotal + $shipping_cost;
    $currency = $items[0]['currency'] ?? '£';

    // Generate unique order number
    $order_number = 'URTH-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));

    // Begin database transaction
    $conn->begin_transaction();

    $orderStmt = $conn->prepare("INSERT INTO orders (order_number, customer_name, customer_email, customer_phone, delivery_address, order_notes, shipping_method, subtotal, shipping_cost, total, currency, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')");
    if (!$orderStmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $orderStmt->bind_param("sssssssddds", $order_number, $customer_name, $customer_email, $customer_phone, $delivery_address, $order_notes, $shipping_method, $subtotal, $shipping_cost, $total, $currency);
    $orderStmt->execute();
    $order_id = $conn->insert_id;
    $orderStmt->close();

    // Insert order items
    $itemStmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, product_name, product_sku, price, quantity, total, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$itemStmt) {
        throw new Exception("Prepare items failed: " . $conn->error);
    }

    foreach ($items as $it) {
        $prod_id = isset($it['id']) && is_numeric($it['id']) ? (int)$it['id'] : null;
        $prod_name = trim($it['name'] ?? 'Product');
        $prod_sku = trim($it['sku'] ?? '');
        $it_price = (float)($it['price'] ?? 0);
        $it_qty = max(1, (int)($it['qty'] ?? 1));
        $it_total = $it_price * $it_qty;
        $it_image = trim($it['image'] ?? '');

        $itemStmt->bind_param("iissdids", $order_id, $prod_id, $prod_name, $prod_sku, $it_price, $it_qty, $it_total, $it_image);
        $itemStmt->execute();
    }
    $itemStmt->close();

    $conn->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'Order placed successfully.',
        'order_number' => $order_number,
        'order_id' => $order_id,
        'customer_name' => $customer_name,
        'subtotal' => number_format($subtotal, 2),
        'total' => number_format($total, 2),
        'currency' => $currency,
        'items_count' => count($items),
        'items' => $items
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    if (isset($conn) && $conn instanceof mysqli) {
        @$conn->rollback();
    }
    http_response_code(500);
    error_log('URTH order submission failed: ' . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'We could not submit your order just now. Please try again or contact the studio.'
    ]);
}
