<?php
/**
 * URTH Architecture Studio - Public API Endpoint: Active Shop Products
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

header('Cache-Control: no-cache, must-revalidate');

require_once __DIR__ . '/../config.php';

try {
    $category = isset($_GET['category']) ? trim($_GET['category']) : '';
    $sort = isset($_GET['sort']) ? trim($_GET['sort']) : 'default';

    $sql = "SELECT id, name, slug, sku, price, sale_price, currency, category, description, image, popularity, display_order, is_featured, availability, product_label, price_on_request, created_at FROM products WHERE is_active = 1";
    
    if (!empty($category) && $category !== 'all') {
        $categorySafe = $conn->real_escape_string($category);
        $sql .= " AND (category = '{$categorySafe}' OR category IN (SELECT name FROM product_categories WHERE slug = '{$categorySafe}'))";
    }

    switch ($sort) {
        case 'popularity':
            $sql .= " ORDER BY popularity DESC, display_order ASC";
            break;
        case 'date':
            $sql .= " ORDER BY created_at DESC, id DESC";
            break;
        case 'price-asc':
            $sql .= " ORDER BY price ASC";
            break;
        case 'price-desc':
            $sql .= " ORDER BY price DESC";
            break;
        case 'menu_order':
        default:
            $sql .= " ORDER BY display_order ASC, id ASC";
            break;
    }

    $result = $conn->query($sql);
    
    $products = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $priceFloat = (float)$row['price'];
            $curr = !empty($row['currency']) ? $row['currency'] : '£';
            $salePriceFloat = $row['sale_price'] !== null ? (float)$row['sale_price'] : null;
            $priceOnRequest = (int)($row['price_on_request'] ?? 0) === 1 || ($priceFloat <= 0 && $row['price_on_request'] !== null);
            $availability = !empty($row['availability']) ? strtolower($row['availability']) : 'available';

            $products[] = [
                'id' => (string)$row['id'],
                'slug' => $row['slug'],
                'name' => $row['name'],
                'sku' => $row['sku'] ?? ('PR' . str_pad($row['id'], 3, '0', STR_PAD_LEFT)),
                'price' => $priceFloat,
                'sale_price' => $salePriceFloat,
                'formattedPrice' => $priceOnRequest ? 'Price on request' : ($curr . number_format($priceFloat, 2)),
                'formattedSalePrice' => ($priceOnRequest || $salePriceFloat === null) ? null : ($curr . number_format($salePriceFloat, 2)),
                'currency' => $curr,
                'category' => $row['category'] ?? 'Furniture & Objects',
                'description' => $row['description'] ?? '',
                'image' => $row['image'],
                'popularity' => (int)$row['popularity'],
                'display_order' => (int)$row['display_order'],
                'is_featured' => (int)($row['is_featured'] ?? 0),
                'availability' => $availability,
                'product_label' => $row['product_label'] ?? '',
                'price_on_request' => $priceOnRequest ? 1 : 0,
                'date' => substr($row['created_at'], 0, 10),
            ];
        }
    }
    
    echo json_encode([
        'status' => 'success',
        'count' => count($products),
        'products' => $products
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to fetch shop products: ' . $e->getMessage()
    ]);
}
