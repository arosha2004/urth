<?php
/**
 * URTH Admin Panel - Product & Category Actions Handler
 * Handles Product CRUD, Inventory, Booking, Categories CRUD, Duplication & Publish/Hide
 */

ob_start();

require_once __DIR__ . '/auth.php';
require_admin_auth();
require_once '../config.php';
require_once __DIR__ . '/../app/Core/private_storage.php';

// Ensure tables exist and have complete schema
$tableCheck = $conn->query("SHOW TABLES LIKE 'products'");
if (!$tableCheck || $tableCheck->num_rows === 0) {
    require_once __DIR__ . '/../setup_shop.php';
} else {
    $colCheck = $conn->query("SHOW COLUMNS FROM products LIKE 'stock_quantity'");
    if (!$colCheck || $colCheck->num_rows === 0) {
        require_once __DIR__ . '/../setup_shop.php';
    }
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
$allowed_deliverable_exts = ['zip', 'rar', 'pdf', 'brushset', 'dwg', 'dxf', 'rfa', 'blend', 'epub'];

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

    // ==========================================
    // 1. ADD PRODUCT
    // ==========================================
    case 'add':
        if (!validate_csrf_token()) {
            header('Location: products.php?msg=error_csrf');
            exit;
        }

        $name = trim($_POST['name'] ?? '');
        $price = filter_var($_POST['price'] ?? 0, FILTER_VALIDATE_FLOAT);
        $sale_price_raw = trim($_POST['sale_price'] ?? '');
        $sale_price = ($sale_price_raw !== '' && is_numeric($sale_price_raw)) ? (float)$sale_price_raw : null;
        $currency = trim($_POST['currency'] ?? 'LKR');
        if (empty($currency) || $currency === '£' || $currency === 'GBP') $currency = 'LKR';

        $category = ensure_category_exists($conn, $_POST['category'] ?? '', $_POST['custom_category'] ?? '');

        $sku = trim($_POST['sku'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $popularity = (int)($_POST['popularity'] ?? 5);
        $display_order = (int)($_POST['display_order'] ?? 0);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $availability = trim($_POST['availability'] ?? 'available');
        if (!in_array($availability, ['available', 'made_to_order', 'coming_soon', 'unavailable', 'quote_only', 'booking_open', 'fully_booked'])) {
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

        if (empty($sku)) {
            $sku = 'PR' . strtoupper(substr(md5(uniqid('', true)), 0, 4));
        }

        // Process primary image
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

        // Inventory, Stock & Booking Capacity
        $stock_quantity = isset($_POST['stock_quantity']) && $_POST['stock_quantity'] !== '' ? (int)$_POST['stock_quantity'] : 10;
        $low_stock_threshold = isset($_POST['low_stock_threshold']) && $_POST['low_stock_threshold'] !== '' ? (int)$_POST['low_stock_threshold'] : 2;
        $booking_capacity = isset($_POST['booking_capacity']) ? (int)$_POST['booking_capacity'] : 0;
        $booked_count = isset($_POST['booked_count']) ? (int)$_POST['booked_count'] : 0;
        $allow_backorder = isset($_POST['allow_backorder']) ? 1 : 0;
        $lead_time = trim($_POST['lead_time'] ?? '');

        // Digital Deliverable file or protected link
        $digital_access_link = trim($_POST['digital_access_link'] ?? '');
        $digital_deliverable_file = '';

        if (isset($_FILES['deliverable_upload']) && $_FILES['deliverable_upload']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadError = null;
            $digital_deliverable_file = store_private_upload($_FILES['deliverable_upload'], 'products', array_values(array_unique(array_merge($allowed_exts, $allowed_deliverable_exts))), 100 * 1024 * 1024, $uploadError);
            if ($digital_deliverable_file === null) {
                header('Location: products.php?msg=' . urlencode($uploadError ?? 'error_upload'));
                exit;
            }
        }

        // Product type and detailed metadata fields
        $product_type = trim($_POST['product_type'] ?? 'furniture_object');
        if (!in_array($product_type, ['furniture_object', 'digital_asset', 'book_planner', 'course_workshop', 'design_session'])) {
            $product_type = 'furniture_object';
        }

        $what_included = trim($_POST['what_included'] ?? '');
        $options_label = trim($_POST['options_label'] ?? 'Option');
        $options_list = trim($_POST['options_list'] ?? '');
        $delivery_time = trim($_POST['delivery_time'] ?? '');
        $delivery_info = trim($_POST['delivery_info'] ?? '');
        $cancellation_refund = trim($_POST['cancellation_refund'] ?? '');

        // Type specific fields
        $dimensions = trim($_POST['dimensions'] ?? '');
        $materials = trim($_POST['materials'] ?? '');
        $finishes = trim($_POST['finishes'] ?? '');
        $customisation = trim($_POST['customisation'] ?? '');
        $production_time = trim($_POST['production_time'] ?? '');
        $delivery_area = trim($_POST['delivery_area'] ?? '');

        $file_formats = trim($_POST['file_formats'] ?? '');
        $software_compatibility = trim($_POST['software_compatibility'] ?? '');
        $permitted_use = trim($_POST['permitted_use'] ?? '');
        $preview_images = trim($_POST['preview_images'] ?? '');

        $book_format = trim($_POST['book_format'] ?? '');
        $sample_pages = trim($_POST['sample_pages'] ?? '');
        $book_dimensions = trim($_POST['book_dimensions'] ?? '');

        $course_topics = trim($_POST['course_topics'] ?? '');
        $course_format = trim($_POST['course_format'] ?? '');
        $course_duration = trim($_POST['course_duration'] ?? '');
        $access_period = trim($_POST['access_period'] ?? '');
        $live_dates = trim($_POST['live_dates'] ?? '');

        $session_scope = trim($_POST['session_scope'] ?? '');
        $session_duration = trim($_POST['session_duration'] ?? '');
        $customer_info_needed = trim($_POST['customer_info_needed'] ?? '');
        $deliverables = trim($_POST['deliverables'] ?? '');
        $included_revisions = trim($_POST['included_revisions'] ?? '');

        // Gallery images
        $galleryList = [];
        if (!empty($image_url)) {
            $galleryList[] = $image_url;
        }
        $rawGalleryText = trim($_POST['gallery_images_text'] ?? '');
        if (!empty($rawGalleryText)) {
            $lines = preg_split('/[\r\n,]+/', $rawGalleryText);
            foreach ($lines as $line) {
                $cleanLine = trim($line);
                if (!empty($cleanLine) && !in_array($cleanLine, $galleryList, true)) {
                    $galleryList[] = $cleanLine;
                }
            }
        }
        if (isset($_FILES['gallery_files']) && is_array($_FILES['gallery_files']['name'])) {
            $fileCount = count($_FILES['gallery_files']['name']);
            for ($fi = 0; $fi < $fileCount; $fi++) {
                if ($_FILES['gallery_files']['error'][$fi] === UPLOAD_ERR_OK) {
                    $gExt = strtolower(pathinfo($_FILES['gallery_files']['name'][$fi], PATHINFO_EXTENSION));
                    if (in_array($gExt, $allowed_exts)) {
                        if (!is_dir($upload_dir)) @mkdir($upload_dir, 0755, true);
                        $gFileName = 'gal_' . time() . '_' . $fi . '_' . rand(100, 999) . '.' . $gExt;
                        $gTarget = $upload_dir . $gFileName;
                        if (move_uploaded_file($_FILES['gallery_files']['tmp_name'][$fi], $gTarget)) {
                            $galleryList[] = 'img/shop/uploads/' . $gFileName;
                        }
                    }
                }
            }
        }
        $gallery_images = json_encode(array_values(array_unique($galleryList)));

        $insertSql = "INSERT INTO products (
            name, slug, sku, price, sale_price, currency, category, description, image,
            popularity, display_order, is_active, is_featured, availability, product_label, price_on_request,
            product_type, what_included, options_label, options_list, delivery_time, delivery_info, cancellation_refund, gallery_images,
            dimensions, materials, finishes, customisation, production_time, delivery_area,
            file_formats, software_compatibility, permitted_use, preview_images,
            book_format, sample_pages, book_dimensions,
            course_topics, course_format, course_duration, access_period, live_dates,
            session_scope, session_duration, customer_info_needed, deliverables, included_revisions,
            stock_quantity, low_stock_threshold, booking_capacity, booked_count, allow_backorder, lead_time,
            digital_deliverable_file, digital_access_link
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?,
            ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?,
            ?, ?
        )";

        $stmt = $conn->prepare($insertSql);
        if ($stmt) {
            // Types:
            // 1-9: sssddssss
            // 10-16: iiisssi
            // 17-47: 31 strings (s)
            // 48-53: iiiiis (stock_quantity, low_stock_threshold, booking_capacity, booked_count, allow_backorder, lead_time)
            // 54-55: ss (digital_deliverable_file, digital_access_link)
            $types = "sssddssssiiisssi" . str_repeat("s", 31) . "iiiiis" . "ss";
            $stmt->bind_param(
                $types,
                $name, $slug, $sku, $price, $sale_price, $currency, $category, $description, $image_url,
                $popularity, $display_order, $is_active, $is_featured, $availability, $product_label, $price_on_request,
                $product_type, $what_included, $options_label, $options_list, $delivery_time, $delivery_info, $cancellation_refund, $gallery_images,
                $dimensions, $materials, $finishes, $customisation, $production_time, $delivery_area,
                $file_formats, $software_compatibility, $permitted_use, $preview_images,
                $book_format, $sample_pages, $book_dimensions,
                $course_topics, $course_format, $course_duration, $access_period, $live_dates,
                $session_scope, $session_duration, $customer_info_needed, $deliverables, $included_revisions,
                $stock_quantity, $low_stock_threshold, $booking_capacity, $booked_count, $allow_backorder, $lead_time,
                $digital_deliverable_file, $digital_access_link
            );
            if ($stmt->execute()) {
                header('Location: products.php?msg=added');
                exit;
            }
        }

        header('Location: products.php?msg=error_db');
        exit;

    // ==========================================
    // 2. EDIT PRODUCT
    // ==========================================
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
        $currency = trim($_POST['currency'] ?? 'LKR');
        if (empty($currency) || $currency === '£' || $currency === 'GBP') $currency = 'LKR';

        $category = ensure_category_exists($conn, $_POST['category'] ?? '', $_POST['custom_category'] ?? '');

        $sku = trim($_POST['sku'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $popularity = (int)($_POST['popularity'] ?? 5);
        $display_order = (int)($_POST['display_order'] ?? 0);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $availability = trim($_POST['availability'] ?? 'available');
        if (!in_array($availability, ['available', 'made_to_order', 'coming_soon', 'unavailable', 'quote_only', 'booking_open', 'fully_booked'])) {
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

        $curStmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
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

        // Inventory & Booking
        $stock_quantity = isset($_POST['stock_quantity']) && $_POST['stock_quantity'] !== '' ? (int)$_POST['stock_quantity'] : (int)($curProduct['stock_quantity'] ?? 10);
        $low_stock_threshold = isset($_POST['low_stock_threshold']) && $_POST['low_stock_threshold'] !== '' ? (int)$_POST['low_stock_threshold'] : (int)($curProduct['low_stock_threshold'] ?? 2);
        $booking_capacity = isset($_POST['booking_capacity']) ? (int)$_POST['booking_capacity'] : (int)($curProduct['booking_capacity'] ?? 0);
        $booked_count = isset($_POST['booked_count']) ? (int)$_POST['booked_count'] : (int)($curProduct['booked_count'] ?? 0);
        $allow_backorder = isset($_POST['allow_backorder']) ? 1 : 0;
        $lead_time = trim($_POST['lead_time'] ?? ($curProduct['lead_time'] ?? ''));

        // Deliverables
        $digital_access_link = trim($_POST['digital_access_link'] ?? ($curProduct['digital_access_link'] ?? ''));
        $digital_deliverable_file = $curProduct['digital_deliverable_file'] ?? '';

        if (isset($_FILES['deliverable_upload']) && $_FILES['deliverable_upload']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadError = null;
            $digital_deliverable_file = store_private_upload($_FILES['deliverable_upload'], 'products', array_values(array_unique(array_merge($allowed_exts, $allowed_deliverable_exts))), 100 * 1024 * 1024, $uploadError);
            if ($digital_deliverable_file === null) {
                header('Location: products.php?msg=' . urlencode($uploadError ?? 'error_upload'));
                exit;
            }
        }

        // Product type and detailed metadata fields
        $product_type = trim($_POST['product_type'] ?? ($curProduct['product_type'] ?? 'furniture_object'));
        if (!in_array($product_type, ['furniture_object', 'digital_asset', 'book_planner', 'course_workshop', 'design_session'])) {
            $product_type = 'furniture_object';
        }

        $what_included = trim($_POST['what_included'] ?? '');
        $options_label = trim($_POST['options_label'] ?? 'Option');
        $options_list = trim($_POST['options_list'] ?? '');
        $delivery_time = trim($_POST['delivery_time'] ?? '');
        $delivery_info = trim($_POST['delivery_info'] ?? '');
        $cancellation_refund = trim($_POST['cancellation_refund'] ?? '');

        // Type specific fields
        $dimensions = trim($_POST['dimensions'] ?? '');
        $materials = trim($_POST['materials'] ?? '');
        $finishes = trim($_POST['finishes'] ?? '');
        $customisation = trim($_POST['customisation'] ?? '');
        $production_time = trim($_POST['production_time'] ?? '');
        $delivery_area = trim($_POST['delivery_area'] ?? '');

        $file_formats = trim($_POST['file_formats'] ?? '');
        $software_compatibility = trim($_POST['software_compatibility'] ?? '');
        $permitted_use = trim($_POST['permitted_use'] ?? '');
        $preview_images = trim($_POST['preview_images'] ?? '');

        $book_format = trim($_POST['book_format'] ?? '');
        $sample_pages = trim($_POST['sample_pages'] ?? '');
        $book_dimensions = trim($_POST['book_dimensions'] ?? '');

        $course_topics = trim($_POST['course_topics'] ?? '');
        $course_format = trim($_POST['course_format'] ?? '');
        $course_duration = trim($_POST['course_duration'] ?? '');
        $access_period = trim($_POST['access_period'] ?? '');
        $live_dates = trim($_POST['live_dates'] ?? '');

        $session_scope = trim($_POST['session_scope'] ?? '');
        $session_duration = trim($_POST['session_duration'] ?? '');
        $customer_info_needed = trim($_POST['customer_info_needed'] ?? '');
        $deliverables = trim($_POST['deliverables'] ?? '');
        $included_revisions = trim($_POST['included_revisions'] ?? '');

        // Gallery images
        $galleryList = [];
        if (!empty($image_url)) {
            $galleryList[] = $image_url;
        }
        $rawGalleryText = trim($_POST['gallery_images_text'] ?? '');
        if (!empty($rawGalleryText)) {
            $lines = preg_split('/[\r\n,]+/', $rawGalleryText);
            foreach ($lines as $line) {
                $cleanLine = trim($line);
                if (!empty($cleanLine) && !in_array($cleanLine, $galleryList, true)) {
                    $galleryList[] = $cleanLine;
                }
            }
        } elseif (!empty($curProduct['gallery_images'])) {
            $existingGal = json_decode($curProduct['gallery_images'], true);
            if (is_array($existingGal)) {
                $galleryList = array_values(array_unique(array_merge($galleryList, $existingGal)));
            }
        }
        if (isset($_FILES['gallery_files']) && is_array($_FILES['gallery_files']['name'])) {
            $fileCount = count($_FILES['gallery_files']['name']);
            for ($fi = 0; $fi < $fileCount; $fi++) {
                if ($_FILES['gallery_files']['error'][$fi] === UPLOAD_ERR_OK) {
                    $gExt = strtolower(pathinfo($_FILES['gallery_files']['name'][$fi], PATHINFO_EXTENSION));
                    if (in_array($gExt, $allowed_exts)) {
                        if (!is_dir($upload_dir)) @mkdir($upload_dir, 0755, true);
                        $gFileName = 'gal_' . time() . '_' . $fi . '_' . rand(100, 999) . '.' . $gExt;
                        $gTarget = $upload_dir . $gFileName;
                        if (move_uploaded_file($_FILES['gallery_files']['tmp_name'][$fi], $gTarget)) {
                            $galleryList[] = 'img/shop/uploads/' . $gFileName;
                        }
                    }
                }
            }
        }
        $gallery_images = json_encode(array_values(array_unique($galleryList)));

        $updateSql = "UPDATE products SET 
            name = ?, slug = ?, sku = ?, price = ?, sale_price = ?, currency = ?, category = ?, description = ?, image = ?,
            popularity = ?, display_order = ?, is_active = ?, is_featured = ?, availability = ?, product_label = ?, price_on_request = ?,
            product_type = ?, what_included = ?, options_label = ?, options_list = ?, delivery_time = ?, delivery_info = ?, cancellation_refund = ?, gallery_images = ?,
            dimensions = ?, materials = ?, finishes = ?, customisation = ?, production_time = ?, delivery_area = ?,
            file_formats = ?, software_compatibility = ?, permitted_use = ?, preview_images = ?,
            book_format = ?, sample_pages = ?, book_dimensions = ?,
            course_topics = ?, course_format = ?, course_duration = ?, access_period = ?, live_dates = ?,
            session_scope = ?, session_duration = ?, customer_info_needed = ?, deliverables = ?, included_revisions = ?,
            stock_quantity = ?, low_stock_threshold = ?, booking_capacity = ?, booked_count = ?, allow_backorder = ?, lead_time = ?,
            digital_deliverable_file = ?, digital_access_link = ?
            WHERE id = ?";

        $stmt = $conn->prepare($updateSql);
        if ($stmt) {
            // Types:
            // 1-9: sssddssss
            // 10-16: iiisssi
            // 17-47: 31 strings (s)
            // 48-53: iiiiis
            // 54-55: ss
            // 56: id (i)
            $types = "sssddssssiiisssi" . str_repeat("s", 31) . "iiiiis" . "ss" . "i";
            $stmt->bind_param(
                $types,
                $name, $slug, $sku, $price, $sale_price, $currency, $category, $description, $image_url,
                $popularity, $display_order, $is_active, $is_featured, $availability, $product_label, $price_on_request,
                $product_type, $what_included, $options_label, $options_list, $delivery_time, $delivery_info, $cancellation_refund, $gallery_images,
                $dimensions, $materials, $finishes, $customisation, $production_time, $delivery_area,
                $file_formats, $software_compatibility, $permitted_use, $preview_images,
                $book_format, $sample_pages, $book_dimensions,
                $course_topics, $course_format, $course_duration, $access_period, $live_dates,
                $session_scope, $session_duration, $customer_info_needed, $deliverables, $included_revisions,
                $stock_quantity, $low_stock_threshold, $booking_capacity, $booked_count, $allow_backorder, $lead_time,
                $digital_deliverable_file, $digital_access_link,
                $id
            );
            if ($stmt->execute()) {
                header('Location: products.php?msg=updated');
                exit;
            }
        }

        header('Location: products.php?msg=error_db');
        exit;

    // ==========================================
    // 3. DUPLICATE PRODUCT
    // ==========================================
    case 'duplicate':
        if (!validate_csrf_token()) {
            header('Location: products.php?msg=error_csrf');
            exit;
        }

        if ($id <= 0) {
            header('Location: products.php?msg=error_invalid');
            exit;
        }

        $res = $conn->query("SELECT * FROM products WHERE id = " . $id);
        $prod = $res ? $res->fetch_assoc() : null;
        if (!$prod) {
            header('Location: products.php?msg=error_invalid');
            exit;
        }

        unset($prod['id'], $prod['created_at'], $prod['updated_at']);
        $prod['name'] = $prod['name'] . ' (Copy)';
        $prod['slug'] = slugify($prod['name']) . '-' . time();
        $prod['sku'] = 'PR' . strtoupper(substr(md5(uniqid('', true)), 0, 4));
        $prod['display_order'] = ((int)$prod['display_order']) + 1;
        $prod['is_active'] = 0; // default to hidden so staff can review

        $cols = array_keys($prod);
        $fieldsList = implode(", ", $cols);
        $placeholders = implode(", ", array_fill(0, count($cols), "?"));

        $dupStmt = $conn->prepare("INSERT INTO products ({$fieldsList}) VALUES ({$placeholders})");
        if ($dupStmt) {
            $types = str_repeat("s", count($cols));
            $values = array_values($prod);
            $dupStmt->bind_param($types, ...$values);
            if ($dupStmt->execute()) {
                header('Location: products.php?msg=duplicated');
                exit;
            }
        }

        header('Location: products.php?msg=error_db');
        exit;

    // ==========================================
    // 4. TOGGLE ACTIVE (PUBLISH / HIDE)
    // ==========================================
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

    // ==========================================
    // 5. TOGGLE FEATURED
    // ==========================================
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

    // ==========================================
    // 6. DELETE PRODUCT
    // ==========================================
    case 'delete':
        if (!validate_csrf_token()) {
            header('Location: products.php?msg=error_csrf');
            exit;
        }

        if ($id <= 0) {
            header('Location: products.php?msg=error_invalid');
            exit;
        }

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

    // ==========================================
    // 7. CATEGORY MANAGEMENT: ADD
    // ==========================================
    case 'category_add':
        if (!validate_csrf_token()) {
            header('Location: products.php?msg=error_csrf');
            exit;
        }

        $catName = trim($_POST['cat_name'] ?? '');
        $catDesc = trim($_POST['cat_description'] ?? '');
        $catOrder = (int)($_POST['cat_display_order'] ?? 0);

        if (empty($catName)) {
            header('Location: products.php?msg=cat_empty');
            exit;
        }

        $catSlug = slugify($catName);

        $catStmt = $conn->prepare("INSERT INTO product_categories (name, slug, description, display_order) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE description = VALUES(description), display_order = VALUES(display_order)");
        if ($catStmt) {
            $catStmt->bind_param("sssi", $catName, $catSlug, $catDesc, $catOrder);
            if ($catStmt->execute()) {
                header('Location: products.php?msg=cat_added');
                exit;
            }
        }

        header('Location: products.php?msg=error_db');
        exit;

    // ==========================================
    // 8. CATEGORY MANAGEMENT: EDIT
    // ==========================================
    case 'category_edit':
        if (!validate_csrf_token()) {
            header('Location: products.php?msg=error_csrf');
            exit;
        }

        $catId = (int)($_POST['cat_id'] ?? 0);
        $catName = trim($_POST['cat_name'] ?? '');
        $catDesc = trim($_POST['cat_description'] ?? '');
        $catOrder = (int)($_POST['cat_display_order'] ?? 0);

        if ($catId <= 0 || empty($catName)) {
            header('Location: products.php?msg=cat_empty');
            exit;
        }

        $chkOld = $conn->prepare("SELECT name FROM product_categories WHERE id = ?");
        $chkOld->bind_param("i", $catId);
        $chkOld->execute();
        $oldRes = $chkOld->get_result();
        $oldRow = $oldRes ? $oldRes->fetch_assoc() : null;
        $chkOld->close();

        $catSlug = slugify($catName);

        $catStmt = $conn->prepare("UPDATE product_categories SET name = ?, slug = ?, description = ?, display_order = ? WHERE id = ?");
        if ($catStmt) {
            $catStmt->bind_param("sssii", $catName, $catSlug, $catDesc, $catOrder, $catId);
            if ($catStmt->execute()) {
                if ($oldRow && $oldRow['name'] !== $catName) {
                    $uProd = $conn->prepare("UPDATE products SET category = ? WHERE category = ?");
                    $uProd->bind_param("ss", $catName, $oldRow['name']);
                    $uProd->execute();
                    $uProd->close();
                }
                header('Location: products.php?msg=cat_updated');
                exit;
            }
        }

        header('Location: products.php?msg=error_db');
        exit;

    // ==========================================
    // 9. CATEGORY MANAGEMENT: DELETE
    // ==========================================
    case 'category_delete':
        if (!validate_csrf_token()) {
            header('Location: products.php?msg=error_csrf');
            exit;
        }

        $catId = (int)($_POST['cat_id'] ?? 0);
        if ($catId <= 0) {
            header('Location: products.php?msg=error_invalid');
            exit;
        }

        $chkOld = $conn->prepare("SELECT name FROM product_categories WHERE id = ?");
        $chkOld->bind_param("i", $catId);
        $chkOld->execute();
        $oldRes = $chkOld->get_result();
        $oldRow = $oldRes ? $oldRes->fetch_assoc() : null;
        $chkOld->close();

        if ($oldRow) {
            $fallbackCat = 'Furniture & Objects';
            $uProd = $conn->prepare("UPDATE products SET category = ? WHERE category = ?");
            $uProd->bind_param("ss", $fallbackCat, $oldRow['name']);
            $uProd->execute();
            $uProd->close();
        }

        $delStmt = $conn->prepare("DELETE FROM product_categories WHERE id = ?");
        if ($delStmt) {
            $delStmt->bind_param("i", $catId);
            if ($delStmt->execute()) {
                header('Location: products.php?msg=cat_deleted');
                exit;
            }
        }

        header('Location: products.php?msg=error_db');
        exit;

    default:
        header('Location: products.php');
        exit;
}
