<?php
/**
 * URTH Architecture Studio - Public API: Product Categories
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
    // Auto-create table if needed
    $conn->query("CREATE TABLE IF NOT EXISTS product_categories (
        id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        slug VARCHAR(100) NOT NULL UNIQUE,
        description TEXT DEFAULT NULL,
        display_order INT(11) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $sql = "
        SELECT c.id, c.name, c.slug, c.description, c.display_order,
               COUNT(p.id) AS product_count
        FROM product_categories c
        LEFT JOIN products p ON (p.category = c.name AND p.is_active = 1)
        GROUP BY c.id, c.name, c.slug, c.description, c.display_order
        ORDER BY c.display_order ASC, c.id ASC
    ";

    $res = $conn->query($sql);
    $categories = [];

    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $categories[] = [
                'id' => (int)$row['id'],
                'name' => $row['name'],
                'slug' => $row['slug'],
                'description' => $row['description'],
                'product_count' => (int)$row['product_count']
            ];
        }
    }

    echo json_encode([
        'status' => 'success',
        'count' => count($categories),
        'categories' => $categories
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to load categories: ' . $e->getMessage()
    ]);
}
