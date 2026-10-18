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

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config.php';

try {
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);

    if (!$data && !empty($_POST)) {
        $data = $_POST;
        if (isset($data['items']) && is_string($data['items'])) {
            $data['items'] = json_decode($data['items'], true);
        }
    }

    if (!$data) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid or empty request payload']);
        exit;
    }

    $customer_name = trim($data['customer_name'] ?? '');
    $customer_phone = trim($data['customer_phone'] ?? '');
    $customer_email = trim($data['customer_email'] ?? '');
    $delivery_address = trim($data['delivery_address'] ?? '');
    $order_notes = trim($data['order_notes'] ?? '');
    $shipping_method = trim($data['shipping_method'] ?? 'Standard Delivery');
    $shipping_cost = (float)($data['shipping_cost'] ?? 0);
    $items = isset($data['items']) && is_array($data['items']) ? $data['items'] : [];

    if (empty($customer_name) || empty($customer_phone)) {
        http_response_code(422);
        echo json_encode(['status' => 'error', 'message' => 'Customer name and phone number are required.']);
        exit;
    }

    if (empty($items)) {
        http_response_code(422);
        echo json_encode(['status' => 'error', 'message' => 'Shopping cart is empty.']);
        exit;
    }

    // Calculate subtotal
    $subtotal = 0;
    foreach ($items as $item) {
        $price = (float)($item['price'] ?? 0);
        $qty = max(1, (int)($item['qty'] ?? 1));
        $subtotal += ($price * $qty);
    }

    $total = $subtotal + $shipping_cost;
    $currency = '£';

    // Generate unique order number
    $order_number = 'URTH-' . date('ymd') . '-' . strtoupper(substr(md5(uniqid((string)mt_rand(), true)), 0, 4));

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
        'items_count' => count($items)
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    if (isset($conn) && $conn instanceof mysqli) {
        @$conn->rollback();
    }
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to process order: ' . $e->getMessage()
    ]);
}
