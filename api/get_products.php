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

    $sql = "SELECT * FROM products WHERE is_active = 1";
    
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
            $curr = (!empty($row['currency']) && $row['currency'] !== '£' && $row['currency'] !== 'GBP') ? $row['currency'] : 'LKR';
            $salePriceFloat = $row['sale_price'] !== null ? (float)$row['sale_price'] : null;
            $priceOnRequest = (int)($row['price_on_request'] ?? 0) === 1 || ($priceFloat <= 0 && $row['price_on_request'] !== null);
            $availability = !empty($row['availability']) ? strtolower($row['availability']) : 'available';

            // Decode gallery images
            $gallery = [];
            if (!empty($row['gallery_images'])) {
                $decoded = json_decode($row['gallery_images'], true);
                if (is_array($decoded)) {
                    $gallery = $decoded;
                }
            }
            if (empty($gallery) && !empty($row['image'])) {
                $gallery = [$row['image']];
            }

            $prefix = (in_array($curr, ['£', '$', '€'], true) ? $curr : $curr . ' ');
            $products[] = [
                'id' => (string)$row['id'],
                'slug' => $row['slug'],
                'name' => $row['name'],
                'sku' => $row['sku'] ?? ('PR' . str_pad($row['id'], 3, '0', STR_PAD_LEFT)),
                'price' => $priceFloat,
                'sale_price' => $salePriceFloat,
                'formattedPrice' => $priceOnRequest ? 'Price on request' : ($prefix . number_format($priceFloat, 2)),
                'formattedSalePrice' => ($priceOnRequest || $salePriceFloat === null) ? null : ($prefix . number_format($salePriceFloat, 2)),
                'currency' => $curr,
                'category' => $row['category'] ?? 'Furniture & Objects',
                'description' => $row['description'] ?? '',
                'image' => $row['image'],
                'gallery_images' => $gallery,
                'popularity' => (int)$row['popularity'],
                'display_order' => (int)$row['display_order'],
                'is_featured' => (int)($row['is_featured'] ?? 0),
                'availability' => $availability,
                'product_label' => $row['product_label'] ?? '',
                'price_on_request' => $priceOnRequest ? 1 : 0,
                'product_type' => $row['product_type'] ?? 'furniture_object',
                'options_label' => $row['options_label'] ?? 'Option',
                'options_list' => $row['options_list'] ?? '',
                'what_included' => $row['what_included'] ?? '',
                'delivery_time' => $row['delivery_time'] ?? '',
                'delivery_info' => $row['delivery_info'] ?? '',
                'cancellation_refund' => $row['cancellation_refund'] ?? '',
                'dimensions' => $row['dimensions'] ?? '',
                'materials' => $row['materials'] ?? '',
                'finishes' => $row['finishes'] ?? '',
                'customisation' => $row['customisation'] ?? '',
                'production_time' => $row['production_time'] ?? '',
                'delivery_area' => $row['delivery_area'] ?? '',
                'file_formats' => $row['file_formats'] ?? '',
                'software_compatibility' => $row['software_compatibility'] ?? '',
                'permitted_use' => $row['permitted_use'] ?? '',
                'preview_images' => $row['preview_images'] ?? '',
                'book_format' => $row['book_format'] ?? '',
                'sample_pages' => $row['sample_pages'] ?? '',
                'book_dimensions' => $row['book_dimensions'] ?? '',
                'course_topics' => $row['course_topics'] ?? '',
                'course_format' => $row['course_format'] ?? '',
                'course_duration' => $row['course_duration'] ?? '',
                'access_period' => $row['access_period'] ?? '',
                'live_dates' => $row['live_dates'] ?? '',
                'session_scope' => $row['session_scope'] ?? '',
                'session_duration' => $row['session_duration'] ?? '',
                'customer_info_needed' => $row['customer_info_needed'] ?? '',
                'deliverables' => $row['deliverables'] ?? '',
                'included_revisions' => $row['included_revisions'] ?? '',
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
