<?php
require_once __DIR__ . '/config.php';

header('Content-Type: text/plain; charset=utf-8');

// 1. Create product_categories table
$sqlCats = "CREATE TABLE IF NOT EXISTS product_categories (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT DEFAULT NULL,
    display_order INT(11) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($conn->query($sqlCats) === TRUE) {
    echo "Table 'product_categories' verified successfully.\n";
} else {
    die("Error creating product_categories table: " . $conn->error . "\n");
}

// 2. Insert or update the 5 required categories
$categories = [
    [
        'name' => 'Digital Tools',
        'slug' => 'digital-tools',
        'description' => 'Procreate brushes and stamps, presentation templates, mood-board templates, CAD blocks, Revit families, 3D assets and material textures',
        'display_order' => 1
    ],
    [
        'name' => 'Books & Planners',
        'slug' => 'books-planners',
        'description' => 'Design booklets, home planning workbooks, renovation planners, construction trackers and printed publications',
        'display_order' => 2
    ],
    [
        'name' => 'Furniture & Objects',
        'slug' => 'furniture-objects',
        'description' => 'Signature furniture, custom furniture, home accessories, architectural art and prints',
        'display_order' => 3
    ],
    [
        'name' => 'Learn',
        'slug' => 'learn',
        'description' => 'Recorded courses, live workshops, webinars and classes',
        'display_order' => 4
    ],
    [
        'name' => 'Design Sessions',
        'slug' => 'design-sessions',
        'description' => 'Space reviews, room refresh consultations, home planning sessions and other fixed-scope design packages',
        'display_order' => 5
    ]
];

$stmt = $conn->prepare("INSERT INTO product_categories (name, slug, description, display_order) 
    VALUES (?, ?, ?, ?) 
    ON DUPLICATE KEY UPDATE slug = VALUES(slug), description = VALUES(description), display_order = VALUES(display_order)");

foreach ($categories as $cat) {
    $stmt->bind_param("sssi", $cat['name'], $cat['slug'], $cat['description'], $cat['display_order']);
    $stmt->execute();
    echo "Category ready: {$cat['name']} ({$cat['slug']})\n";
}
$stmt->close();

// 3. Update existing products in `products` table
$updateProductsSql = "UPDATE products SET category = 'Furniture & Objects' WHERE category IN ('Accessories', 'Chairs', 'Tables') OR category IS NULL OR category = ''";
if ($conn->query($updateProductsSql) === TRUE) {
    echo "Updated existing products to category 'Furniture & Objects'. Affected: " . $conn->affected_rows . "\n";
}

// 4. Alter products table category column default
$conn->query("ALTER TABLE products ALTER COLUMN category SET DEFAULT 'Furniture & Objects'");

echo "\nCategories successfully updated!\n";
