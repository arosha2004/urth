<?php
/**
 * URTH Admin Panel - Product Actions Handler (Add, Edit, Delete, Toggle Status)
 */

ob_start();

require_once __DIR__ . '/auth.php';
require_admin_auth();
require_once '../config.php';

// Ensure products table exists and seed if empty
$tableCheck = $conn->query("SHOW TABLES LIKE 'products'");
if (!$tableCheck || $tableCheck->num_rows === 0) {
    require_once __DIR__ . '/../setup_shop.php';
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$id = (int)($_POST['product_id'] ?? $_POST['id'] ?? $_GET['product_id'] ?? $_GET['id'] ?? 0);

// Check if POST data was dropped due to post_max_size exhaustion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST) && isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {
    header('Location: products.php?msg=error_size');
    exit;
}

$upload_dir = '../img/shop/uploads/';
$allowed_exts = ['svg', 'png', 'jpg', 'jpeg', 'webp', 'avif'];

// Utility to create slug from product name
function slugify(string $text): string {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return empty($text) ? 'product-' . time() : $text;
}

// Ensure category exists in product_categories and return sanitized category name
function ensure_category_exists($conn, string $rawCategory, string $customCategory = ''): string {
    $category = trim($rawCategory);
    if ($category === '__custom__' && !empty($customCategory)) {
        $category = trim($customCategory);
    }
    if (empty($category) || $category === '__custom__') {
        $category = 'Furniture & Objects';
    }

    // Ensure product_categories table exists
    $conn->query("CREATE TABLE IF NOT EXISTS product_categories (
        id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        slug VARCHAR(100) NOT NULL UNIQUE,
        description TEXT DEFAULT NULL,
        display_order INT(11) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $chk = $conn->prepare("SELECT id FROM product_categories WHERE LOWER(name) = LOWER(?)");
    if ($chk) {
        $chk->bind_param("s", $category);
        $chk->execute();
        $res = $chk->get_result();
        if (!$res || $res->num_rows === 0) {
            $cSlug = slugify($category);
            $orderRes = $conn->query("SELECT COALESCE(MAX(display_order), 0) + 1 AS next_order FROM product_categories");
            $nextOrder = $orderRes ? (int)$orderRes->fetch_assoc()['next_order'] : 1;
            $ins = $conn->prepare("INSERT INTO product_categories (name, slug, display_order) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name)");
            if ($ins) {
                $ins->bind_param("ssi", $category, $cSlug, $nextOrder);
                $ins->execute();
                $ins->close();
            }
        }
        $chk->close();
    }

    return $category;
}


switch ($action) {
    case 'add':
        if (!validate_csrf_token()) {
            header('Location: products.php?msg=error_csrf');
            exit;
        }

        $name = trim($_POST['name'] ?? '');
        $price = filter_var($_POST['price'] ?? 0, FILTER_VALIDATE_FLOAT);
        $sale_price_raw = trim($_POST['sale_price'] ?? '');
        $sale_price = ($sale_price_raw !== '' && is_numeric($sale_price_raw)) ? (float)$sale_price_raw : null;
        $currency = trim($_POST['currency'] ?? '£');
        if (empty($currency)) $currency = '£';

        $category = ensure_category_exists($conn, $_POST['category'] ?? '', $_POST['custom_category'] ?? '');

        $sku = trim($_POST['sku'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $popularity = (int)($_POST['popularity'] ?? 5);
        $display_order = (int)($_POST['display_order'] ?? 0);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $availability = trim($_POST['availability'] ?? 'available');
        if (!in_array($availability, ['available', 'made_to_order', 'coming_soon', 'unavailable'])) {
            $availability = 'available';
        }
        $product_label = trim($_POST['product_label'] ?? '');
        $price_on_request = isset($_POST['price_on_request']) ? 1 : 0;

        if ($price_on_request) {
            if ($price === false || $price < 0) $price = 0.00;
        }

        if (empty($name) || (!$price_on_request && ($price === false || $price <= 0))) {
            header('Location: products.php?msg=error_missing');
            exit;
        }

        $slug = slugify($name);

        // Auto-generate SKU if blank
        if (empty($sku)) {
            $sku = 'PR' . strtoupper(substr(md5(uniqid('', true)), 0, 4));
        }

        // Process image
        $image_url = 'img/shop/prod-1.jpg'; // fallback
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
            $file_info = pathinfo($_FILES['product_image']['name']);
            $ext = strtolower($file_info['extension'] ?? '');

            if (!in_array($ext, $allowed_exts)) {
                header('Location: products.php?msg=error_filetype');
                exit;
            }

            if (!is_dir($upload_dir)) {
                @mkdir($upload_dir, 0755, true);
            }

            $safe_name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $file_info['filename']);
            $filename = 'prod_' . time() . '_' . substr($safe_name, 0, 25) . '.' . $ext;
            $target_path = $upload_dir . $filename;

            if (move_uploaded_file($_FILES['product_image']['tmp_name'], $target_path)) {
                $image_url = 'img/shop/uploads/' . $filename;
            } else {
                header('Location: products.php?msg=error_upload');
                exit;
            }
        } elseif (!empty($_POST['custom_image_path'])) {
            $image_url = trim($_POST['custom_image_path']);
        }

        $stmt = $conn->prepare("INSERT INTO products (name, slug, sku, price, sale_price, currency, category, description, image, popularity, display_order, is_active, is_featured, availability, product_label, price_on_request) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("sssddssssiiisssi", $name, $slug, $sku, $price, $sale_price, $currency, $category, $description, $image_url, $popularity, $display_order, $is_active, $is_featured, $availability, $product_label, $price_on_request);
            if ($stmt->execute()) {
                header('Location: products.php?msg=added');
                exit;
            }
        }

        header('Location: products.php?msg=error_db');
        exit;

    case 'edit':
        if (!validate_csrf_token()) {
            header('Location: products.php?msg=error_csrf');
            exit;
        }

        if ($id <= 0) {
            header('Location: products.php?msg=error_invalid');
            exit;
        }

        $name = trim($_POST['name'] ?? '');
        $price = filter_var($_POST['price'] ?? 0, FILTER_VALIDATE_FLOAT);
        $sale_price_raw = trim($_POST['sale_price'] ?? '');
        $sale_price = ($sale_price_raw !== '' && is_numeric($sale_price_raw)) ? (float)$sale_price_raw : null;
        $currency = trim($_POST['currency'] ?? '£');
        if (empty($currency)) $currency = '£';

        $category = ensure_category_exists($conn, $_POST['category'] ?? '', $_POST['custom_category'] ?? '');

        $sku = trim($_POST['sku'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $popularity = (int)($_POST['popularity'] ?? 5);
        $display_order = (int)($_POST['display_order'] ?? 0);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $availability = trim($_POST['availability'] ?? 'available');
        if (!in_array($availability, ['available', 'made_to_order', 'coming_soon', 'unavailable'])) {
            $availability = 'available';
        }
        $product_label = trim($_POST['product_label'] ?? '');
        $price_on_request = isset($_POST['price_on_request']) ? 1 : 0;

        if ($price_on_request) {
            if ($price === false || $price < 0) $price = 0.00;
        }

        if (empty($name) || (!$price_on_request && ($price === false || $price <= 0))) {
            header('Location: products.php?msg=error_missing');
            exit;
        }

        $slug = slugify($name);

        // Fetch current product to retain image if not updated
        $curStmt = $conn->prepare("SELECT image FROM products WHERE id = ?");
        $curStmt->bind_param("i", $id);
        $curStmt->execute();
        $curRes = $curStmt->get_result();
        $curProduct = $curRes ? $curRes->fetch_assoc() : null;
        $curStmt->close();

        if (!$curProduct) {
            header('Location: products.php?msg=error_invalid');
            exit;
        }

        $image_url = $curProduct['image'];

        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
            $file_info = pathinfo($_FILES['product_image']['name']);
            $ext = strtolower($file_info['extension'] ?? '');

            if (!in_array($ext, $allowed_exts)) {
                header('Location: products.php?msg=error_filetype');
                exit;
            }

            if (!is_dir($upload_dir)) {
                @mkdir($upload_dir, 0755, true);
            }

            $safe_name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $file_info['filename']);
            $filename = 'prod_' . time() . '_' . substr($safe_name, 0, 25) . '.' . $ext;
            $target_path = $upload_dir . $filename;

            if (move_uploaded_file($_FILES['product_image']['tmp_name'], $target_path)) {
                // Remove old uploaded file if it was in uploads/
                if (!empty($curProduct['image']) && strpos($curProduct['image'], 'img/shop/uploads/') !== false) {
                    $old_file = '../' . ltrim($curProduct['image'], '/');
                    if (file_exists($old_file)) {
                        @unlink($old_file);
                    }
                }
                $image_url = 'img/shop/uploads/' . $filename;
            } else {
                header('Location: products.php?msg=error_upload');
                exit;
            }
        } elseif (!empty($_POST['custom_image_path'])) {
            $image_url = trim($_POST['custom_image_path']);
        }

        $stmt = $conn->prepare("UPDATE products SET name = ?, slug = ?, sku = ?, price = ?, sale_price = ?, currency = ?, category = ?, description = ?, image = ?, popularity = ?, display_order = ?, is_active = ?, is_featured = ?, availability = ?, product_label = ?, price_on_request = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("sssddssssiiisssii", $name, $slug, $sku, $price, $sale_price, $currency, $category, $description, $image_url, $popularity, $display_order, $is_active, $is_featured, $availability, $product_label, $price_on_request, $id);
            if ($stmt->execute()) {
                header('Location: products.php?msg=updated');
                exit;
            }
        }

        header('Location: products.php?msg=error_db');
        exit;

    case 'delete':
        if (!validate_csrf_token()) {
            header('Location: products.php?msg=error_csrf');
            exit;
        }

        if ($id <= 0) {
            header('Location: products.php?msg=error_invalid');
            exit;
        }

        // Fetch image to delete if it's in uploads
        $delStmt = $conn->prepare("SELECT image FROM products WHERE id = ?");
        $delStmt->bind_param("i", $id);
        $delStmt->execute();
        $delRes = $delStmt->get_result();
        $item = $delRes ? $delRes->fetch_assoc() : null;
        $delStmt->close();

        if ($item && !empty($item['image']) && strpos($item['image'], 'img/shop/uploads/') !== false) {
            $filePath = '../' . ltrim($item['image'], '/');
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
        }

        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                header('Location: products.php?msg=deleted');
                exit;
            }
        }

        header('Location: products.php?msg=error_db');
        exit;

    case 'toggle_status':
        if ($id <= 0) {
            header('Location: products.php?msg=error_invalid');
            exit;
        }

        $stmt = $conn->prepare("UPDATE products SET is_active = (1 - is_active) WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                header('Location: products.php?msg=status_updated');
                exit;
            }
        }

        header('Location: products.php?msg=error_db');
        exit;

    case 'toggle_featured':
        if ($id <= 0) {
            header('Location: products.php?msg=error_invalid');
            exit;
        }

        $stmt = $conn->prepare("UPDATE products SET is_featured = (1 - is_featured) WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                header('Location: products.php?msg=featured_updated');
                exit;
            }
        }

        header('Location: products.php?msg=error_db');
        exit;

    default:
        header('Location: products.php');
        exit;
}
