<?php
/**
 * Setup Shop Table and Initial Products
 */
require_once __DIR__ . '/config.php';

// Create product_categories table if not exists
$catTableSql = "CREATE TABLE IF NOT EXISTS product_categories (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT DEFAULT NULL,
    display_order INT(11) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($conn->query($catTableSql) === TRUE) {
    echo "Table 'product_categories' created or verified successfully.\n";
    $defaultCategories = [
        ['Digital Tools', 'digital-tools', 'Procreate brushes and stamps, presentation templates, mood-board templates, CAD blocks, Revit families, 3D assets and material textures', 1],
        ['Books & Planners', 'books-planners', 'Design booklets, home planning workbooks, renovation planners, construction trackers and printed publications', 2],
        ['Furniture & Objects', 'furniture-objects', 'Signature furniture, custom furniture, home accessories, architectural art and prints', 3],
        ['Learn', 'learn', 'Recorded courses, live workshops, webinars and classes', 4],
        ['Design Sessions', 'design-sessions', 'Space reviews, room refresh consultations, home planning sessions and other fixed-scope design packages', 5]
    ];
    $cStmt = $conn->prepare("INSERT INTO product_categories (name, slug, description, display_order) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE slug=VALUES(slug), description=VALUES(description), display_order=VALUES(display_order)");
    foreach ($defaultCategories as $cRow) {
        $cStmt->bind_param("sssi", $cRow[0], $cRow[1], $cRow[2], $cRow[3]);
        $cStmt->execute();
    }
    $cStmt->close();
}

// Create products table if not exists
$tableSql = "CREATE TABLE IF NOT EXISTS products (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    sku VARCHAR(100) DEFAULT NULL,
    price DECIMAL(10, 2) NOT NULL,
    sale_price DECIMAL(10, 2) DEFAULT NULL,
    currency VARCHAR(10) DEFAULT '£',
    category VARCHAR(100) DEFAULT 'Furniture & Objects',
    description TEXT DEFAULT NULL,
    image VARCHAR(255) NOT NULL,
    popularity INT(11) DEFAULT 0,
    display_order INT(11) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    is_featured TINYINT(1) DEFAULT 0,
    availability VARCHAR(50) DEFAULT 'available',
    product_label VARCHAR(100) DEFAULT NULL,
    price_on_request TINYINT(1) DEFAULT 0,
    product_type VARCHAR(50) DEFAULT 'furniture_object',
    what_included TEXT DEFAULT NULL,
    options_label VARCHAR(100) DEFAULT 'Option',
    options_list TEXT DEFAULT NULL,
    delivery_time VARCHAR(255) DEFAULT NULL,
    delivery_info TEXT DEFAULT NULL,
    cancellation_refund TEXT DEFAULT NULL,
    gallery_images TEXT DEFAULT NULL,
    file_formats VARCHAR(255) DEFAULT NULL,
    software_compatibility VARCHAR(255) DEFAULT NULL,
    permitted_use TEXT DEFAULT NULL,
    preview_images TEXT DEFAULT NULL,
    book_format VARCHAR(255) DEFAULT NULL,
    sample_pages TEXT DEFAULT NULL,
    book_dimensions VARCHAR(255) DEFAULT NULL,
    dimensions VARCHAR(255) DEFAULT NULL,
    materials VARCHAR(255) DEFAULT NULL,
    finishes VARCHAR(255) DEFAULT NULL,
    customisation TEXT DEFAULT NULL,
    production_time VARCHAR(255) DEFAULT NULL,
    delivery_area VARCHAR(255) DEFAULT NULL,
    course_topics TEXT DEFAULT NULL,
    course_format VARCHAR(255) DEFAULT NULL,
    course_duration VARCHAR(255) DEFAULT NULL,
    access_period VARCHAR(255) DEFAULT NULL,
    live_dates VARCHAR(255) DEFAULT NULL,
    session_scope TEXT DEFAULT NULL,
    session_duration VARCHAR(255) DEFAULT NULL,
    customer_info_needed TEXT DEFAULT NULL,
    deliverables TEXT DEFAULT NULL,
    included_revisions VARCHAR(255) DEFAULT NULL,
    type_details LONGTEXT DEFAULT NULL,
    stock_quantity INT(11) DEFAULT 10,
    low_stock_threshold INT(11) DEFAULT 2,
    booking_capacity INT(11) DEFAULT 0,
    booked_count INT(11) DEFAULT 0,
    allow_backorder TINYINT(1) DEFAULT 0,
    lead_time VARCHAR(100) DEFAULT NULL,
    digital_deliverable_file VARCHAR(255) DEFAULT NULL,
    digital_access_link TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($conn->query($tableSql) === TRUE) {
    echo "Table 'products' created or verified successfully.\n";
} else {
    die("Error creating products table: " . $conn->error . "\n");
}

// Ensure all newer columns exist even if products table was created in an older schema
$productColsToAdd = [
    'stock_quantity' => "INT(11) DEFAULT 10",
    'low_stock_threshold' => "INT(11) DEFAULT 2",
    'booking_capacity' => "INT(11) DEFAULT 0",
    'booked_count' => "INT(11) DEFAULT 0",
    'allow_backorder' => "TINYINT(1) DEFAULT 0",
    'lead_time' => "VARCHAR(100) DEFAULT NULL",
    'digital_deliverable_file' => "VARCHAR(255) DEFAULT NULL",
    'digital_access_link' => "TEXT DEFAULT NULL"
];
$existingProductCols = [];
$cRes = $conn->query("SHOW COLUMNS FROM products");
if ($cRes) {
    while ($crow = $cRes->fetch_assoc()) {
        $existingProductCols[] = $crow['Field'];
    }
}
foreach ($productColsToAdd as $col => $def) {
    if (!in_array($col, $existingProductCols, true)) {
        $conn->query("ALTER TABLE products ADD COLUMN {$col} {$def}");
    }
}

// Ensure upload directory exists
$uploadDir = __DIR__ . '/img/shop/uploads';
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0755, true);
}

// Check if products already exist
$checkRes = $conn->query("SELECT COUNT(*) AS total FROM products");
$count = $checkRes ? (int)$checkRes->fetch_assoc()['total'] : 0;

if ($count === 0) {
    echo "Seeding initial 9 shop products...\n";

    $seedProducts = [
        [
            'name' => 'Bertt Side table',
            'slug' => 'bertt-side-table',
            'sku' => 'PR104',
            'price' => 83.40,
            'sale_price' => null,
            'currency' => '£',
            'category' => 'Furniture & Objects',
            'description' => 'Handcrafted with brushed brass accents and refined geometry. The Bertt Side Table pairs understated minimalism with tactile elegance, making it an ideal companion for contemporary armchairs and bedsides.',
            'image' => 'img/shop/prod-1.jpg',
            'popularity' => 5,
            'display_order' => 1,
            'is_active' => 1
        ],
        [
            'name' => 'Coffee Table',
            'slug' => 'coffee-table',
            'sku' => 'PR101',
            'price' => 57.00,
            'sale_price' => null,
            'currency' => '£',
            'category' => 'Furniture & Objects',
            'description' => 'Sculptural wooden coffee table featuring continuous curved veneer grain lines and an organic open pocket for books or magazines. Finished with a natural matte protective wax.',
            'image' => 'img/shop/prod-2.jpg',
            'popularity' => 4,
            'display_order' => 2,
            'is_active' => 1
        ],
        [
            'name' => 'Modern End Table',
            'slug' => 'modern-end-table-2',
            'sku' => 'PR106',
            'price' => 87.00,
            'sale_price' => null,
            'currency' => '£',
            'category' => 'Furniture & Objects',
            'description' => 'Contemporary minimalist accent table with an integrated linen fixture and turned natural timber pedestal base. Casts a warm architectural glow throughout modern living spaces.',
            'image' => 'img/shop/prod-3.jpg',
            'popularity' => 6,
            'display_order' => 3,
            'is_active' => 1
        ],
        [
            'name' => 'Modern End Table',
            'slug' => 'modern-end-table-1',
            'sku' => 'PR102',
            'price' => 87.00,
            'sale_price' => null,
            'currency' => '£',
            'category' => 'Furniture & Objects',
            'description' => 'Iconic mid-century molded bucket chair with geometric wire Eiffel base and solid turned dowel legs. Ergonomically contoured for enduring comfort and timeless aesthetics.',
            'image' => 'img/shop/prod-4.jpg',
            'popularity' => 3,
            'display_order' => 4,
            'is_active' => 1
        ],
        [
            'name' => 'Side Table',
            'slug' => 'side-table',
            'sku' => 'PR103',
            'price' => 48.00,
            'sale_price' => null,
            'currency' => '£',
            'category' => 'Furniture & Objects',
            'description' => 'Minimalist spun brass and brushed bronze accent side table. Features a slender stem and weighted base, designed to complement contemporary seating arrangements.',
            'image' => 'img/shop/prod-5.jpg',
            'popularity' => 2,
            'display_order' => 5,
            'is_active' => 1
        ],
        [
            'name' => 'Soft chair',
            'slug' => 'soft-chair',
            'sku' => 'PR105',
            'price' => 57.00,
            'sale_price' => null,
            'currency' => '£',
            'category' => 'Furniture & Objects',
            'description' => 'Deep-seated lounge chair upholstered in premium textured linen bouclé with sculpted ash wood arms. Generously cushioned for reading nooks and living lounges.',
            'image' => 'img/shop/prod-6.jpg',
            'popularity' => 7,
            'display_order' => 6,
            'is_active' => 1
        ],
        [
            'name' => 'Wooden Chair',
            'slug' => 'wooden-chair',
            'sku' => 'PR108',
            'price' => 83.40,
            'sale_price' => null,
            'currency' => '£',
            'category' => 'Furniture & Objects',
            'description' => 'Clean-lined solid oak dining chair inspired by Nordic architectural joinery. Features a contoured backrest and hand-finished satin protective lacquer.',
            'image' => 'img/shop/prod-7.jpg',
            'popularity' => 8,
            'display_order' => 7,
            'is_active' => 1
        ],
        [
            'name' => 'Wooden Nightstand',
            'slug' => 'wooden-nightstand',
            'sku' => 'PR107',
            'price' => 48.00,
            'sale_price' => null,
            'currency' => '£',
            'category' => 'Furniture & Objects',
            'description' => 'Floating-feel bedside nightstand crafted in solid white oak. Includes a soft-close dovetail drawer and recessed cable management channel.',
            'image' => 'img/shop/prod-8.jpg',
            'popularity' => 1,
            'display_order' => 8,
            'is_active' => 1
        ],
        [
            'name' => 'Wooden Table',
            'slug' => 'wooden-table',
            'sku' => 'PR109',
            'price' => 97.00,
            'sale_price' => null,
            'currency' => '£',
            'category' => 'Furniture & Objects',
            'description' => 'Solid oak dining and studio work table featuring chamfered edges and trestle legs with architectural mortise-and-tenon details. Seats up to six comfortably.',
            'image' => 'img/shop/prod-9.jpg',
            'popularity' => 9,
            'display_order' => 9,
            'is_active' => 1
        ],
    ];

    $stmt = $conn->prepare("INSERT INTO products (name, slug, sku, price, sale_price, currency, category, description, image, popularity, display_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error . "\n");
    }

    foreach ($seedProducts as $p) {
        $stmt->bind_param(
            "sssddssssiii",
            $p['name'],
            $p['slug'],
            $p['sku'],
            $p['price'],
            $p['sale_price'],
            $p['currency'],
            $p['category'],
            $p['description'],
            $p['image'],
            $p['popularity'],
            $p['display_order'],
            $p['is_active']
        );
        $stmt->execute();
    }
    echo "Successfully seeded " . count($seedProducts) . " products.\n";
} else {
    echo "Table 'products' already contains {$count} records. No seed needed.\n";
}
