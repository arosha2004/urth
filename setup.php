<?php
// setup.php
require_once 'config.php';

// Create table
$sql = "CREATE TABLE IF NOT EXISTS projects (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    category VARCHAR(255) DEFAULT 'Architectural Design',
    description TEXT DEFAULT NULL,
    location VARCHAR(255) DEFAULT NULL,
    link_url VARCHAR(255) NOT NULL,
    image1 VARCHAR(255) NOT NULL,
    image2 VARCHAR(255) NOT NULL,
    image3 VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Table projects created successfully or already exists.<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

// Create project_images table
$sql_images = "CREATE TABLE IF NOT EXISTS project_images (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    project_id INT(6) UNSIGNED NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
)";

if ($conn->query($sql_images) === TRUE) {
    echo "Table project_images created successfully or already exists.<br>";
} else {
    echo "Error creating project_images table: " . $conn->error . "<br>";
}

// Create partners table
$sql_partners = "CREATE TABLE IF NOT EXISTS partners (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    logo_url VARCHAR(255) NOT NULL,
    website_url VARCHAR(255) DEFAULT NULL,
    display_order INT(11) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql_partners) === TRUE) {
    echo "Table partners created successfully or already exists.<br>";
} else {
    echo "Error creating partners table: " . $conn->error . "<br>";
}

// Create product_categories table
$sql_categories = "CREATE TABLE IF NOT EXISTS product_categories (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT DEFAULT NULL,
    display_order INT(11) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql_categories) === TRUE) {
    echo "Table product_categories created successfully or already exists.<br>";
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
} else {
    echo "Error creating product_categories table: " . $conn->error . "<br>";
}

// Create products table
$sql_products = "CREATE TABLE IF NOT EXISTS products (
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql_products) === TRUE) {
    echo "Table products created successfully or already exists.<br>";
} else {
    echo "Error creating products table: " . $conn->error . "<br>";
}

// Create orders table
$sql_orders = "CREATE TABLE IF NOT EXISTS orders (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(50) NOT NULL UNIQUE,
    customer_name VARCHAR(255) NOT NULL,
    customer_email VARCHAR(255) DEFAULT NULL,
    customer_phone VARCHAR(50) NOT NULL,
    delivery_address TEXT DEFAULT NULL,
    order_notes TEXT DEFAULT NULL,
    shipping_method VARCHAR(100) DEFAULT 'Standard Delivery',
    subtotal DECIMAL(10, 2) NOT NULL,
    shipping_cost DECIMAL(10, 2) DEFAULT 0.00,
    total DECIMAL(10, 2) NOT NULL,
    currency VARCHAR(10) DEFAULT '£',
    status VARCHAR(50) DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql_orders) === TRUE) {
    echo "Table orders created successfully or already exists.<br>";
} else {
    echo "Error creating orders table: " . $conn->error . "<br>";
}

// Create order_items table
$sql_order_items = "CREATE TABLE IF NOT EXISTS order_items (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    order_id INT(11) NOT NULL,
    product_id INT(11) DEFAULT NULL,
    product_name VARCHAR(255) NOT NULL,
    product_sku VARCHAR(100) DEFAULT NULL,
    price DECIMAL(10, 2) NOT NULL,
    quantity INT(11) NOT NULL,
    total DECIMAL(10, 2) NOT NULL,
    image VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql_order_items) === TRUE) {
    echo "Table order_items created successfully or already exists.<br>";
} else {
    echo "Error creating order_items table: " . $conn->error . "<br>";
}

$conn->close();
echo "<a href='index.php'>Return to Home</a>";
?>
