<?php
/**
 * URTH Admin Panel - Manage Shop Products & Catalog
 * Advanced product lifecycle management, categories, inventory, booking capacity, deliverables & options
 */

require_once __DIR__ . '/auth.php';
require_admin_auth();
require_once '../config.php';

// Auto-check tables
$tblCheck = $conn->query("SHOW TABLES LIKE 'products'");
if (!$tblCheck || $tblCheck->num_rows === 0) {
    require_once __DIR__ . '/../setup_shop.php';
}

// Fetch all categories from product_categories
$allCategories = [];
$catRes = $conn->query("SELECT * FROM product_categories ORDER BY display_order ASC, name ASC");
if ($catRes) {
    while ($cRow = $catRes->fetch_assoc()) {
        $allCategories[] = $cRow;
    }
}

// Fetch product counts per category
$catCounts = [];
$cntRes = $conn->query("SELECT category, COUNT(*) as cnt FROM products GROUP BY category");
if ($cntRes) {
    while ($r = $cntRes->fetch_assoc()) {
        $catCounts[$r['category']] = (int)$r['cnt'];
    }
}

// Fetch all products ordered by display_order ASC, id DESC
$result = $conn->query("SELECT * FROM products ORDER BY display_order ASC, id DESC");
$products = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}

// Statistics
$totalProducts = count($products);
$activeProducts = count(array_filter($products, function($p) { return (int)$p['is_active'] === 1; }));
$standardCategories = array_column($allCategories, 'name');
if (empty($standardCategories)) {
    $standardCategories = ['Digital Tools', 'Books & Planners', 'Furniture & Objects', 'Learn', 'Design Sessions'];
}
$usedCategories = array_values(array_unique(array_filter(array_column($products, 'category'))));
$categories = array_values(array_unique(array_merge($standardCategories, $usedCategories)));
$totalCategories = count($categories);

$avgPrice = 0;
if ($totalProducts > 0) {
    $sumPrice = array_sum(array_column($products, 'price'));
    $avgPrice = $sumPrice / $totalProducts;
}

// Inventory & Low Stock Counter
$lowStockCount = 0;
foreach ($products as $p) {
    $qty = isset($p['stock_quantity']) ? (int)$p['stock_quantity'] : 10;
    $thresh = isset($p['low_stock_threshold']) ? (int)$p['low_stock_threshold'] : 2;
    $avail = strtolower($p['availability'] ?? 'available');
    if ($avail === 'available' && $qty <= $thresh) {
        $lowStockCount++;
    }
}

// CSRF Token
$csrf_token = generate_csrf_token();

// Status notification messages
$msg = $_GET['msg'] ?? '';
$alertMessage = '';
$alertType = 'success';

switch ($msg) {
    case 'added':
        $alertMessage = 'New shop product added successfully.';
        break;
    case 'updated':
        $alertMessage = 'Product details, options, and stock settings updated successfully.';
        break;
    case 'duplicated':
        $alertMessage = 'Product cloned successfully as a draft copy.';
        break;
    case 'deleted':
        $alertMessage = 'Product has been removed from the shop catalog.';
        $alertType = 'danger';
        break;
    case 'status_updated':
        $alertMessage = 'Product visibility status has been toggled successfully.';
        break;
    case 'featured_updated':
        $alertMessage = 'Product featured spotlight status updated.';
        break;
    case 'cat_added':
        $alertMessage = 'New product category added successfully.';
        break;
    case 'cat_updated':
        $alertMessage = 'Category details and assigned products updated successfully.';
        break;
    case 'cat_deleted':
        $alertMessage = 'Category deleted successfully. Associated products reassigned.';
        $alertType = 'danger';
        break;
    case 'cat_empty':
        $alertMessage = 'Category name cannot be empty.';
        $alertType = 'danger';
        break;
    case 'error_missing':
        $alertMessage = 'Please fill in all required fields (Product Title and valid Price).';
        $alertType = 'danger';
        break;
    case 'error_invalid':
        $alertMessage = 'Invalid product or category record specified.';
        $alertType = 'danger';
        break;
    case 'error_filetype':
        $alertMessage = 'Invalid file type. Supported image formats: JPG, PNG, WEBP, AVIF, SVG.';
        $alertType = 'danger';
        break;
    case 'error_size':
        $alertMessage = 'Uploaded file exceeds server size limits.';
        $alertType = 'danger';
        break;
    case 'error_upload':
        $alertMessage = 'Failed to upload image. Please check file permissions.';
        $alertType = 'danger';
        break;
    case 'error_csrf':
        $alertMessage = 'Session expired or invalid security token. Please try again.';
        $alertType = 'danger';
        break;
    case 'error_db':
        $alertMessage = 'A database error occurred. Please try again.';
        $alertType = 'danger';
        break;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Shop Catalog &amp; Products — URTH Admin Panel</title>
    <link rel="stylesheet" href="assets/css/admin.css?v=<?= time() ?>">
</head>
<body>

<div class="admin-layout">

    <!-- Header Navigation Bar -->
    <header class="admin-header">
        <a href="index.php" class="header-brand">
            <img src="../urth_clone/images/6a1c3ef3f96cad36ed74d8cd_favicon.jpg" alt="URTH Logo" style="width: 38px; height: 38px; border-radius: var(--radius-sm); object-fit: cover;">
            <div class="brand-text">
                <span class="brand-name">URTH</span>
                <span class="brand-sub">Admin Console</span>
            </div>
        </a>

        <!-- Navigation Tabs -->
        <nav class="admin-nav-tabs">
            <a href="index.php" class="admin-nav-link">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                Projects
            </a>
            <a href="products.php" class="admin-nav-link active">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                Shop Products
            </a>
            <a href="orders.php" class="admin-nav-link">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                Orders &amp; Enquiries
            </a>
            <a href="partners.php" class="admin-nav-link">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m4.93 4.93 4.24 4.24"/><path d="m14.83 9.17 4.24-4.24"/><path d="m14.83 14.83 4.24 4.24"/><path d="m9.17 14.83-4.24 4.24"/></svg>
                Partner Logos
            </a>
        </nav>

        <div class="header-actions">
            <div class="user-badge">
                <span class="user-avatar"><?= strtoupper(substr($_SESSION['admin_username'] ?? 'A', 0, 1)) ?></span>
                <span><?= htmlspecialchars($_SESSION['admin_username'] ?? 'Admin') ?></span>
            </div>

            <a href="../shop.php" target="_blank" class="btn btn-outline btn-sm" title="Preview public shop catalog">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                View Live Shop
            </a>

            <a href="logout.php" class="btn btn-danger btn-sm" title="Log out of admin session">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                Logout
            </a>
        </div>
    </header>

    <!-- Main Content Container -->
    <main class="admin-container">

        <!-- Notification Banner -->
        <?php if (!empty($alertMessage)): ?>
            <div class="alert alert-<?= $alertType ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                <span><?= htmlspecialchars($alertMessage) ?></span>
            </div>
        <?php endif; ?>

        <!-- Page Header -->
        <div class="page-header">
            <div class="page-title-wrap">
                <h1>Shop Products &amp; Catalog Management</h1>
                <p>Manage products, categories, stock levels, booking capacities, bespoke options and digital deliverables</p>
            </div>

            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <button type="button" id="openCategoryModalBtn" class="btn btn-outline">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                    Manage Categories
                </button>
                <button type="button" id="openAddProductModal" class="btn btn-gold">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Add New Product
                </button>
            </div>
        </div>

        <!-- Summary Statistics Widgets -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-info">
                    <div class="stat-label">Total Products</div>
                    <div class="stat-value"><?= $totalProducts ?></div>
                </div>
                <div class="stat-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-info">
                    <div class="stat-label">Active (Published)</div>
                    <div class="stat-value" style="color: var(--success);"><?= $activeProducts ?></div>
                </div>
                <div class="stat-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-info">
                    <div class="stat-label">Categories</div>
                    <div class="stat-value" style="color: var(--gold-primary);"><?= $totalCategories ?></div>
                </div>
                <div class="stat-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-info">
                    <div class="stat-label">Inventory Attention</div>
                    <div class="stat-value" style="color: <?= $lowStockCount > 0 ? '#f59e0b' : 'var(--text-primary)' ?>;"><?= $lowStockCount ?> <span style="font-size: 11px; font-weight: 500; color: var(--text-muted);">low/out</span></div>
                </div>
                <div class="stat-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                </div>
            </div>
        </div>

        <!-- Toolbar & Filter Card -->
        <div class="toolbar-card">
            <div class="search-filter-group" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center; flex: 1;">
                <div class="search-box" style="flex: 1; min-width: 220px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="text" id="productSearchInput" class="form-control" placeholder="Search by product name, SKU, or specs...">
                </div>

                <div style="min-width: 160px;">
                    <select id="categoryFilter" class="form-control" style="cursor: pointer;">
                        <option value="">All Categories (<?= $totalCategories ?>)</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="min-width: 140px;">
                    <select id="statusFilter" class="form-control" style="cursor: pointer;">
                        <option value="">All Visibility</option>
                        <option value="1">Published (Active)</option>
                        <option value="0">Hidden (Draft)</option>
                    </select>
                </div>

                <div style="min-width: 160px;">
                    <select id="availabilityFilter" class="form-control" style="cursor: pointer;">
                        <option value="">All Availability Modes</option>
                        <option value="available">In Stock / Available</option>
                        <option value="low_stock">Low Stock Alerts</option>
                        <option value="made_to_order">Made to Order (Bespoke)</option>
                        <option value="booking_capacity">Booking Capacity</option>
                        <option value="digital_asset">Digital Assets</option>
                        <option value="price_on_request">Price on Request</option>
                    </select>
                </div>
            </div>

            <div style="font-size: 13px; color: var(--text-secondary); white-space: nowrap;">
                Showing <strong id="productCount" style="color: var(--text-primary);"><?= $totalProducts ?></strong> items
            </div>
        </div>

        <!-- Table Card Section -->
        <div class="table-card">
            <div class="table-responsive">
                <table class="custom-table" id="productsTable">
                    <thead>
                        <tr>
                            <th style="width: 45px;">#</th>
                            <th style="width: 65px;">Image</th>
                            <th>Product Name, SKU &amp; Type</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th style="width: 160px;">Stock / Booking Availability</th>
                            <th style="width: 110px;">Catalog Status</th>
                            <th style="width: 60px; text-align: center;">Featured</th>
                            <th style="width: 220px; text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($products)): ?>
                            <tr id="emptyRow">
                                <td colspan="9">
                                    <div class="empty-state">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                                        <h3>No Shop Products Found</h3>
                                        <p>Click "Add New Product" to populate your URTH studio shop collection.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($products as $prod): ?>
                                <?php
                                    $imgSrc = $prod['image'];
                                    if (!preg_match('/^https?:\/\//i', $imgSrc)) {
                                        $imgSrc = '../' . ltrim($imgSrc, '/');
                                    }
                                    $curr = !empty($prod['currency']) ? $prod['currency'] : '£';
                                    $displayPrice = $curr . number_format((float)$prod['price'], 2);

                                    $stockQty = isset($prod['stock_quantity']) ? (int)$prod['stock_quantity'] : 10;
                                    $lowThresh = isset($prod['low_stock_threshold']) ? (int)$prod['low_stock_threshold'] : 2;
                                    $bookCap = isset($prod['booking_capacity']) ? (int)$prod['booking_capacity'] : 0;
                                    $bookedCnt = isset($prod['booked_count']) ? (int)$prod['booked_count'] : 0;
                                    $pType = $prod['product_type'] ?? 'furniture_object';
                                    $avail = strtolower($prod['availability'] ?? 'available');
                                    $isLow = ($avail === 'available' && $stockQty <= $lowThresh);
                                ?>
                                <tr class="product-row"
                                    data-id="<?= $prod['id'] ?>"
                                    data-name="<?= htmlspecialchars(strtolower($prod['name'])) ?>"
                                    data-sku="<?= htmlspecialchars(strtolower($prod['sku'] ?? '')) ?>"
                                    data-category="<?= htmlspecialchars($prod['category'] ?? '') ?>"
                                    data-active="<?= (int)$prod['is_active'] ?>"
                                    data-avail="<?= $avail ?>"
                                    data-type="<?= $pType ?>"
                                    data-is-low="<?= $isLow ? '1' : '0' ?>"
                                    data-por="<?= !empty($prod['price_on_request']) ? '1' : '0' ?>"
                                    data-raw='<?= htmlspecialchars(json_encode($prod), ENT_QUOTES, 'UTF-8') ?>'>
                                    <td>
                                        <span class="order-badge"><?= (int)$prod['display_order'] ?></span>
                                    </td>
                                    <td>
                                        <div class="product-admin-thumb">
                                            <img src="<?= htmlspecialchars($imgSrc) ?>"
                                                 alt="<?= htmlspecialchars($prod['name']) ?>"
                                                 loading="lazy">
                                        </div>
                                    </td>
                                    <td>
                                        <div style="display: flex; align-items: baseline; gap: 6px; flex-wrap: wrap;">
                                            <strong style="color: var(--text-primary); font-size: 14px;"><?= htmlspecialchars($prod['name']) ?></strong>
                                            <?php if (!empty($prod['product_label'])): ?>
                                                <span style="font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; padding: 2px 6px; border-radius: 4px; background: rgba(200,169,110,0.18); color: var(--gold-primary); border: 1px solid rgba(200,169,110,0.3);"><?= htmlspecialchars($prod['product_label']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div style="display: flex; gap: 8px; align-items: center; margin-top: 2px;">
                                            <span class="product-sku">SKU: <?= htmlspecialchars($prod['sku'] ?? 'N/A') ?></span>
                                            <span style="font-size: 10px; color: var(--text-muted);">&bull; <?= ucwords(str_replace('_', ' ', $pType)) ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="category-pill"><?= htmlspecialchars($prod['category'] ?? 'General') ?></span>
                                    </td>
                                    <td>
                                        <?php if (!empty($prod['price_on_request'])): ?>
                                            <div style="font-size: 12px; font-weight: 700; color: var(--gold-primary); text-transform: uppercase; letter-spacing: 0.5px;">Quote On Request</div>
                                        <?php else: ?>
                                            <div class="product-price-val"><?= htmlspecialchars($displayPrice) ?></div>
                                            <?php if (!empty($prod['sale_price'])): ?>
                                                <span style="font-size: 11px; color: var(--danger); text-decoration: line-through;">
                                                    <?= htmlspecialchars($curr . number_format((float)$prod['sale_price'], 2)) ?>
                                                </span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($pType === 'design_session' || $bookCap > 0): ?>
                                            <?php $remaining = max(0, $bookCap - $bookedCnt); ?>
                                            <span class="stock-pill stock-pill-booking" title="Total Capacity: <?= $bookCap ?> | Booked: <?= $bookedCnt ?>">
                                                <?= $remaining ?> / <?= $bookCap ?> Slots Available
                                            </span>
                                            <?php if (!empty($prod['live_dates'])): ?>
                                                <div style="font-size: 10px; color: var(--text-muted); margin-top: 2px;"><?= htmlspecialchars($prod['live_dates']) ?></div>
                                            <?php endif; ?>
                                        <?php elseif ($avail === 'made_to_order'): ?>
                                            <span class="stock-pill stock-pill-mto">
                                                Made to order <?= !empty($prod['lead_time']) ? '(' . htmlspecialchars($prod['lead_time']) . ')' : '' ?>
                                            </span>
                                        <?php elseif ($pType === 'digital_asset'): ?>
                                            <span class="stock-pill stock-pill-digital" title="Deliverable link: <?= htmlspecialchars($prod['digital_access_link'] ?? 'Auto-assigned') ?>">
                                                Digital Asset (Unlimited)
                                            </span>
                                        <?php elseif ($stockQty <= 0): ?>
                                            <span class="stock-pill stock-pill-out">Out of Stock</span>
                                        <?php elseif ($stockQty <= $lowThresh): ?>
                                            <span class="stock-pill stock-pill-low">Low Stock: <?= $stockQty ?> left</span>
                                        <?php else: ?>
                                            <span class="stock-pill stock-pill-in">In Stock (<?= $stockQty ?> pcs)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="product_actions.php?action=toggle_status&id=<?= $prod['id'] ?>"
                                           class="status-badge <?= (int)$prod['is_active'] === 1 ? 'active' : 'inactive' ?>"
                                           title="Click to toggle visibility (Publish/Hide)"
                                           style="text-decoration: none; font-size: 11px; padding: 2px 8px; display: inline-block;">
                                            <?= (int)$prod['is_active'] === 1 ? '● Published' : '○ Hidden (Draft)' ?>
                                        </a>
                                    </td>
                                    <td style="text-align: center;">
                                        <a href="product_actions.php?action=toggle_featured&id=<?= $prod['id'] ?>"
                                           title="Toggle Featured on Shop Showcase"
                                           style="text-decoration: none; font-size: 18px; line-height: 1; transition: transform 0.2s; display: inline-block;">
                                            <?= (int)($prod['is_featured'] ?? 0) === 1 ? '<span style="color: var(--gold-primary);">★</span>' : '<span style="color: #cbd5e1;">☆</span>' ?>
                                        </a>
                                    </td>
                                    <td>
                                        <div class="table-actions" style="justify-content: flex-end; gap: 4px;">
                                            <a href="../product.php?id=<?= $prod['id'] ?>"
                                               target="_blank"
                                               class="btn btn-outline btn-sm"
                                               title="View public product page">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                                            </a>
                                            <button type="button"
                                                    class="btn btn-outline btn-sm edit-product-btn"
                                                    title="Edit product details &amp; inventory">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                                Edit
                                            </button>
                                            <form action="product_actions.php" method="POST" style="display: inline;" onsubmit="return confirm('Clone this product into a new draft item?');">
                                                <input type="hidden" name="action" value="duplicate">
                                                <input type="hidden" name="product_id" value="<?= $prod['id'] ?>">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                                <button type="submit" class="btn btn-outline btn-sm" title="Duplicate product as draft">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                                                    Clone
                                                </button>
                                            </form>
                                            <button type="button"
                                                    class="btn btn-danger btn-sm delete-product-btn"
                                                    title="Remove product from catalog">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                            <tr id="noResultsRow" style="display: none;">
                                <td colspan="9">
                                    <div class="empty-state">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                                        <h3>No matching products</h3>
                                        <p>Adjust your search filters or clear the keyword to display shop items.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <!-- Footer -->
    <footer class="admin-footer">
        &copy; <?= date('Y') ?> URTH Architecture Studio — Admin Management Panel
    </footer>

</div>

<!-- ========================================================================= -->
<!-- MODAL: MANAGE CATEGORIES -->
<!-- ========================================================================= -->
<div id="manageCategoriesModal" class="modal-overlay">
    <div class="modal-dialog" style="max-width: 720px; max-height: 88vh; display: flex; flex-direction: column;">
        <div class="modal-header">
            <div>
                <h3 class="modal-title">Manage Shop Categories</h3>
                <p style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">Organize shop sections, descriptions, and catalog display ordering</p>
            </div>
            <button type="button" class="modal-close" data-modal-close>&times;</button>
        </div>

        <div class="modal-body" style="overflow-y: auto; padding: 20px 24px; display: flex; flex-direction: column; gap: 20px;">
            <!-- Add New Category Form -->
            <form action="product_actions.php" method="POST" style="background: var(--bg-canvas); padding: 16px; border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
                <input type="hidden" name="action" value="category_add">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

                <h4 style="font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; color: var(--gold-primary); font-weight: 700; margin-bottom: 12px;">+ Add New Category</h4>
                <div style="display: grid; grid-template-columns: 2fr 1fr auto; gap: 10px; align-items: flex-end;">
                    <div class="form-group">
                        <label class="form-label" style="font-size: 11px;">Category Name *</label>
                        <input type="text" name="cat_name" class="form-control" placeholder="e.g. Lighting &amp; Lamps" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" style="font-size: 11px;">Display Order</label>
                        <input type="number" name="cat_display_order" class="form-control" value="<?= count($allCategories) + 1 ?>" min="0">
                    </div>
                    <button type="submit" class="btn btn-gold btn-sm" style="height: 38px;">Save Category</button>
                </div>
                <div class="form-group" style="margin-top: 8px;">
                    <label class="form-label" style="font-size: 11px;">Description / Subtitle</label>
                    <input type="text" name="cat_description" class="form-control" placeholder="Short description for shop catalog filter header...">
                </div>
            </form>

            <!-- Existing Categories Table -->
            <div>
                <h4 style="font-size: 13px; font-weight: 700; color: var(--text-primary); margin-bottom: 10px;">Existing Catalog Categories (<?= count($allCategories) ?>)</h4>
                <table class="custom-table" style="font-size: 13px;">
                    <thead>
                        <tr>
                            <th style="width: 40px;">Order</th>
                            <th>Name</th>
                            <th>Products</th>
                            <th>Description</th>
                            <th style="width: 130px; text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allCategories as $catRow): ?>
                            <?php $pCount = $catCounts[$catRow['name']] ?? 0; ?>
                            <tr>
                                <td><strong><?= (int)$catRow['display_order'] ?></strong></td>
                                <td>
                                    <strong style="color: var(--text-primary);"><?= htmlspecialchars($catRow['name']) ?></strong>
                                    <div style="font-size: 11px; color: var(--text-muted); font-family: monospace;"><?= htmlspecialchars($catRow['slug']) ?></div>
                                </td>
                                <td>
                                    <span class="category-pill"><?= $pCount ?> items</span>
                                </td>
                                <td>
                                    <span style="font-size: 11px; color: var(--text-secondary);"><?= htmlspecialchars($catRow['description'] ?? '—') ?></span>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 4px; justify-content: flex-end;">
                                        <button type="button" 
                                                class="btn btn-outline btn-sm edit-category-btn"
                                                data-id="<?= $catRow['id'] ?>"
                                                data-name="<?= htmlspecialchars($catRow['name']) ?>"
                                                data-order="<?= (int)$catRow['display_order'] ?>"
                                                data-desc="<?= htmlspecialchars($catRow['description'] ?? '') ?>">
                                            Edit
                                        </button>
                                        <form action="product_actions.php" method="POST" onsubmit="return confirm('Delete category <?= htmlspecialchars($catRow['name']) ?>? Any existing products will be reassigned to Furniture & Objects.');" style="display: inline;">
                                            <input type="hidden" name="action" value="category_delete">
                                            <input type="hidden" name="cat_id" value="<?= $catRow['id'] ?>">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" title="Delete category">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-outline" data-modal-close>Close</button>
        </div>
    </div>
</div>

<!-- Category Edit Sub-Modal -->
<div id="editCategoryModal" class="modal-overlay">
    <div class="modal-dialog" style="max-width: 480px;">
        <div class="modal-header">
            <h3 class="modal-title">Edit Category</h3>
            <button type="button" class="modal-close" data-modal-close>&times;</button>
        </div>
        <form action="product_actions.php" method="POST">
            <input type="hidden" name="action" value="category_edit">
            <input type="hidden" name="cat_id" id="edit_cat_id">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

            <div class="modal-body" style="display: flex; flex-direction: column; gap: 12px;">
                <div class="form-group">
                    <label class="form-label">Category Name *</label>
                    <input type="text" name="cat_name" id="edit_cat_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Display Order</label>
                    <input type="number" name="cat_display_order" id="edit_cat_order" class="form-control" min="0">
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="cat_description" id="edit_cat_description" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-gold">Update Category</button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: ADD NEW PRODUCT -->
<!-- ========================================================================= -->
<div id="addProductModal" class="modal-overlay">
    <div class="modal-dialog" style="max-width: 860px; max-height: 90vh; display: flex; flex-direction: column;">
        <div class="modal-header">
            <div>
                <h3 class="modal-title">Add New Shop Product</h3>
                <p style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">Configure product details, stock or booking capacity, pricing, and bespoke options</p>
            </div>
            <button type="button" class="modal-close" data-modal-close>&times;</button>
        </div>
        <form action="product_actions.php" method="POST" enctype="multipart/form-data" style="display: flex; flex-direction: column; overflow: hidden; height: 100%;">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            
            <div class="modal-body" style="overflow-y: auto; padding: 20px 24px; display: flex; flex-direction: column; gap: 20px;">
                
                <!-- Section 1: Basic Information -->
                <div style="border-bottom: 1px solid var(--border-color); padding-bottom: 16px;">
                    <h4 style="font-size: 13px; text-transform: uppercase; letter-spacing: 1px; color: var(--gold-primary); margin-bottom: 12px; font-weight: 700;">1. Core Product Details</h4>
                    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 12px;">
                        <div class="form-group">
                            <label for="add_product_name" class="form-label">Product Title *</label>
                            <input type="text" id="add_product_name" name="name" class="form-control" placeholder="e.g. Bertt Side Table" required>
                        </div>
                        <div class="form-group">
                            <label for="add_product_sku" class="form-label">SKU (Catalog Code)</label>
                            <input type="text" id="add_product_sku" name="sku" class="form-control" placeholder="e.g. PR104 (Auto-generated if empty)">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; margin-top: 12px;">
                        <div class="form-group">
                            <label for="add_product_price" class="form-label">Price (£) *</label>
                            <input type="number" step="0.01" min="0" id="add_product_price" name="price" class="form-control" placeholder="83.40" required>
                        </div>
                        <div class="form-group">
                            <label for="add_product_sale_price" class="form-label">Sale Price (£)</label>
                            <input type="number" step="0.01" min="0" id="add_product_sale_price" name="sale_price" class="form-control" placeholder="Optional discount">
                        </div>
                        <div class="form-group">
                            <label for="add_product_category" class="form-label">Catalog Category *</label>
                            <select id="add_product_category" name="category" class="form-control" required>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat) ?>" <?= $cat === 'Furniture & Objects' ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat) ?>
                                    </option>
                                <?php endforeach; ?>
                                <option value="__custom__">+ Add New Category...</option>
                            </select>
                            <div id="add_custom_category_wrap" style="display: none; margin-top: 6px;">
                                <input type="text" id="add_custom_category" name="custom_category" class="form-control" placeholder="Enter new category name...">
                            </div>
                        </div>
                    </div>

                    <div class="form-group" style="margin-top: 12px;">
                        <label for="add_product_desc" class="form-label">Full Product Description</label>
                        <textarea id="add_product_desc" name="description" class="form-control" rows="3" placeholder="Provide design inspiration, architectural context, craftsmanship details..."></textarea>
                    </div>

                    <div class="form-group" style="margin-top: 12px;">
                        <label for="add_product_image" class="form-label">Primary Cover Image *</label>
                        <input type="file" id="add_product_image" name="product_image" class="form-control" accept=".jpg,.jpeg,.png,.webp,.avif,.svg">
                        <div style="margin-top: 6px;">
                            <input type="text" name="custom_image_path" class="form-control" placeholder="Or specify existing image path/URL: img/shop/prod-1.jpg" style="font-size: 12px;">
                        </div>
                    </div>
                </div>

                <!-- Section 2: Inventory, Stock & Booking Capacity -->
                <div style="border-bottom: 1px solid var(--border-color); padding-bottom: 16px; background: rgba(16,185,129,0.02); border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 14px;">
                    <h4 style="font-size: 13px; text-transform: uppercase; letter-spacing: 1px; color: #059669; margin-bottom: 12px; font-weight: 700;">2. Inventory, Stock &amp; Booking Capacity</h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px;">
                        <div class="form-group">
                            <label class="form-label">Physical Stock Quantity</label>
                            <input type="number" name="stock_quantity" class="form-control" value="10" min="0" placeholder="10">
                            <span style="font-size: 11px; color: var(--text-muted);">Current units in studio inventory</span>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Low Stock Alert Threshold</label>
                            <input type="number" name="low_stock_threshold" class="form-control" value="2" min="1" placeholder="2">
                            <span style="font-size: 11px; color: var(--text-muted);">Triggers amber warning tag</span>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Made-to-Order Lead Time</label>
                            <input type="text" name="lead_time" class="form-control" placeholder="e.g. 3–4 weeks handcrafted">
                            <span style="font-size: 11px; color: var(--text-muted);">For bespoke or crafted pieces</span>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 10px;">
                        <div class="form-group">
                            <label class="form-label">Booking Capacity (Max Seats / Slots)</label>
                            <input type="number" name="booking_capacity" class="form-control" value="0" min="0" placeholder="For sessions / workshops (e.g. 12)">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Already Booked Count</label>
                            <input type="number" name="booked_count" class="form-control" value="0" min="0" placeholder="0">
                        </div>
                    </div>

                    <div style="margin-top: 10px;">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 13px;">
                            <input type="checkbox" name="allow_backorder" value="1" style="accent-color: var(--gold-primary);">
                            Allow customers to purchase on backorder when stock reaches 0
                        </label>
                    </div>
                </div>

                <!-- Section 3: Digital Deliverables & Protected Links -->
                <div style="border-bottom: 1px solid var(--border-color); padding-bottom: 16px; background: rgba(99,102,241,0.02); border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 14px;">
                    <h4 style="font-size: 13px; text-transform: uppercase; letter-spacing: 1px; color: #4f46e5; margin-bottom: 12px; font-weight: 700;">3. Digital Deliverables &amp; Protected Links (Optional)</h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div class="form-group">
                            <label class="form-label">Upload Deliverable Asset (.ZIP, .PDF, .RFA, .DWG)</label>
                            <input type="file" name="deliverable_upload" class="form-control" accept=".zip,.rar,.pdf,.brushset,.dwg,.dxf,.rfa,.blend">
                            <span style="font-size: 11px; color: var(--text-muted);">Stored securely in protected deliverables vault</span>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Protected Access URL / Token Link</label>
                            <input type="url" name="digital_access_link" class="form-control" placeholder="https://urthspaces.com/download.php?token=sec_xxx">
                            <span style="font-size: 11px; color: var(--text-muted);">Auto-dispatched to verified paying clients</span>
                        </div>
                    </div>
                </div>

                <!-- Section 4: Product Type & Specialized Details -->
                <div style="border-bottom: 1px solid var(--border-color); padding-bottom: 16px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                        <h4 style="font-size: 13px; text-transform: uppercase; letter-spacing: 1px; color: var(--gold-primary); margin: 0; font-weight: 700;">4. Product Type &amp; Specific Attributes</h4>
                        <span style="font-size: 11px; color: var(--text-muted);">Adapts the layout and technical specifications</span>
                    </div>

                    <div class="form-group">
                        <label for="add_product_type" class="form-label" style="font-weight: 600;">Product Type *</label>
                        <select id="add_product_type" name="product_type" class="form-control" style="border-color: var(--gold-primary); background-color: var(--gold-subtle); font-weight: 600;">
                            <option value="furniture_object" selected>Furniture or Object (Dimensions, Materials, Finishes, Customisation, Lead Time)</option>
                            <option value="digital_asset">Digital Asset (File Formats, Compatibility, Permitted Use)</option>
                            <option value="book_planner">Book or Planner (Format, Sample Pages, Dimensions)</option>
                            <option value="course_workshop">Course or Workshop (Topics, Duration, Access Period, Live Dates)</option>
                            <option value="design_session">Design Session (Scope, Duration, Customer Info Needed, Deliverables)</option>
                        </select>
                    </div>

                    <!-- Type Subgroup: Furniture or Object -->
                    <div id="add_type_group_furniture_object" class="type-fields-group" style="margin-top: 14px; background: rgba(166,132,73,0.03); border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 14px;">
                        <span style="font-size: 12px; font-weight: 700; color: var(--gold-primary); display: block; margin-bottom: 10px;">Furniture &amp; Objects Specifications</span>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                            <div class="form-group">
                                <label class="form-label">Dimensions</label>
                                <input type="text" name="dimensions" class="form-control" placeholder="e.g. Diameter: 420mm | Height: 520mm | Weight: 9.8kg">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Materials</label>
                                <input type="text" name="materials" class="form-control" placeholder="e.g. Solid European White Oak, Brushed Spun Brass, Eco-Bio Wax">
                            </div>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 10px;">
                            <div class="form-group">
                                <label class="form-label">Finishes Available</label>
                                <input type="text" name="finishes" class="form-control" placeholder="e.g. Natural Matte Oak, Smoked Walnut, Ebonized Ash">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Production Time</label>
                                <input type="text" name="production_time" class="form-control" placeholder="e.g. 3–4 weeks handcrafted in workshop">
                            </div>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 10px;">
                            <div class="form-group">
                                <label class="form-label">Customisation Details</label>
                                <input type="text" name="customisation" class="form-control" placeholder="e.g. Custom heights and timber species available on request">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Delivery Area</label>
                                <input type="text" name="delivery_area" class="form-control" placeholder="e.g. UK Nationwide White-Glove delivery &amp; Worldwide crate freight">
                            </div>
                        </div>
                    </div>

                    <!-- Type Subgroup: Digital Asset -->
                    <div id="add_type_group_digital_asset" class="type-fields-group" style="display: none; margin-top: 14px; background: rgba(59,130,246,0.03); border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 14px;">
                        <span style="font-size: 12px; font-weight: 700; color: #2563eb; display: block; margin-bottom: 10px;">Digital Asset Specifications</span>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                            <div class="form-group">
                                <label class="form-label">File Formats</label>
                                <input type="text" name="file_formats" class="form-control" placeholder="e.g. .BRUSHSET, .DWG, .DXF, .RFA, .PNG (4K), .PDF">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Software Compatibility</label>
                                <input type="text" name="software_compatibility" class="form-control" placeholder="e.g. Procreate 5.2+, AutoCAD 2020+, Revit 2022+, Blender">
                            </div>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 10px;">
                            <div class="form-group">
                                <label class="form-label">Permitted Use / License Scope</label>
                                <input type="text" name="permitted_use" class="form-control" placeholder="e.g. Commercial and personal architectural presentations.">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Preview Images &amp; Demos</label>
                                <textarea name="preview_images" class="form-control" rows="3" placeholder="One image URL or preview note per line"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Type Subgroup: Book or Planner -->
                    <div id="add_type_group_book_planner" class="type-fields-group" style="display: none; margin-top: 14px; background: rgba(16,185,129,0.03); border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 14px;">
                        <span style="font-size: 12px; font-weight: 700; color: #059669; display: block; margin-bottom: 10px;">Book &amp; Planner Specifications</span>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                            <div class="form-group">
                                <label class="form-label">Digital / Printed Format</label>
                                <input type="text" name="book_format" class="form-control" placeholder="e.g. Casebound Hardcover Linen &amp; Interactive Digital PDF">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Sample Pages Preview</label>
                                <input type="text" name="sample_pages" class="form-control" placeholder="e.g. 42 sample pages preview available in TOC">
                            </div>
                        </div>
                        <div class="form-group" style="margin-top: 10px;">
                            <label class="form-label">Dimensions &amp; Page Details</label>
                            <input type="text" name="book_dimensions" class="form-control" placeholder="e.g. 240 x 170 mm (Standard Royal), 210 pages, 140gsm FSC paper">
                        </div>
                    </div>

                    <!-- Type Subgroup: Course or Workshop -->
                    <div id="add_type_group_course_workshop" class="type-fields-group" style="display: none; margin-top: 14px; background: rgba(168,85,247,0.03); border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 14px;">
                        <span style="font-size: 12px; font-weight: 700; color: #7c3aed; display: block; margin-bottom: 10px;">Course &amp; Workshop Specifications</span>
                        <div class="form-group">
                            <label class="form-label">Topics / Curriculum Highlights</label>
                            <textarea name="course_topics" class="form-control" rows="2" placeholder="e.g. 1. Spatial Sightlines, 2. Light Choreography, 3. Architectural Joinery"></textarea>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 10px;">
                            <div class="form-group">
                                <label class="form-label">Course Format</label>
                                <input type="text" name="course_format" class="form-control" placeholder="e.g. Live Zoom Webinar + 4K Recorded Archive">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Duration</label>
                                <input type="text" name="course_duration" class="form-control" placeholder="e.g. 4 Weeks (4x 90-minute live masterclasses)">
                            </div>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 10px;">
                            <div class="form-group">
                                <label class="form-label">Access Period</label>
                                <input type="text" name="access_period" class="form-control" placeholder="e.g. Lifetime unrestricted access to recording portal">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Dates &amp; Time Zone for Live Events</label>
                                <input type="text" name="live_dates" class="form-control" placeholder="e.g. Every Saturday 14:00 – 15:30 GMT (London Time)">
                            </div>
                        </div>
                    </div>

                    <!-- Type Subgroup: Design Session -->
                    <div id="add_type_group_design_session" class="type-fields-group" style="display: none; margin-top: 14px; background: rgba(234,88,12,0.03); border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 14px;">
                        <span style="font-size: 12px; font-weight: 700; color: #ea580c; display: block; margin-bottom: 10px;">Design Session Specifications</span>
                        <div class="form-group">
                            <label class="form-label">Consultation Scope</label>
                            <textarea name="session_scope" class="form-control" rows="2" placeholder="e.g. Full review of residential spatial layouts, daylighting, joinery placement..."></textarea>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 10px;">
                            <div class="form-group">
                                <label class="form-label">Session Duration</label>
                                <input type="text" name="session_duration" class="form-control" placeholder="e.g. 90-minute live 1-on-1 video call + dossier">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Included Revisions</label>
                                <input type="text" name="included_revisions" class="form-control" placeholder="e.g. Up to 2 iterative design revisions within 14 days">
                            </div>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 10px;">
                            <div class="form-group">
                                <label class="form-label">Required Customer Information</label>
                                <textarea name="customer_info_needed" class="form-control" rows="2" placeholder="e.g. Existing room dimensions/floor plan, daylight photos, target budget..."></textarea>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Final Deliverables</label>
                                <textarea name="deliverables" class="form-control" rows="2" placeholder="e.g. Scaled 2D plan (PDF), Material palette, Clickable shopping list, Video replay"></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 5: Options, Inclusions & Policies -->
                <div>
                    <h4 style="font-size: 13px; text-transform: uppercase; letter-spacing: 1px; color: var(--gold-primary); margin-bottom: 12px; font-weight: 700;">5. Options, Inclusions &amp; Policies</h4>
                    
                    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 12px;">
                        <div class="form-group">
                            <label for="add_options_label" class="form-label">Available Option Label</label>
                            <input type="text" id="add_options_label" name="options_label" class="form-control" placeholder="e.g. Finish, Size, Format, License">
                        </div>
                        <div class="form-group">
                            <label for="add_options_list" class="form-label">Available Options (Comma-separated)</label>
                            <input type="text" id="add_options_list" name="options_list" class="form-control" placeholder="e.g. Natural White Oak, Smoked Oiled Walnut, Ebonized Ash">
                        </div>
                    </div>

                    <div class="form-group" style="margin-top: 12px;">
                        <label for="add_what_included" class="form-label">What is Included (List with bullets or newlines)</label>
                        <textarea id="add_what_included" name="what_included" class="form-control" rows="3" placeholder="• 1x Bertt Solid Oak Table&#10;• Studio certificate of authenticity&#10;• Protective beeswax care kit"></textarea>
                    </div>

                    <div class="form-group" style="margin-top: 12px;">
                        <label for="add_delivery_time" class="form-label">Expected Delivery or Preparation Time</label>
                        <input type="text" id="add_delivery_time" name="delivery_time" class="form-control" placeholder="e.g. Preparation: 2–3 business days | Made to order: 3–4 weeks handcrafted lead time">
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 12px;">
                        <div class="form-group">
                            <label for="add_delivery_info" class="form-label">Delivery &amp; Shipping Policy</label>
                            <textarea id="add_delivery_info" name="delivery_info" class="form-control" rows="2" placeholder="e.g. White-glove UK courier delivery in reinforced wooden crate. Worldwide freight options available."></textarea>
                        </div>
                        <div class="form-group">
                            <label for="add_cancellation_refund" class="form-label">Cancellation &amp; Refund Information</label>
                            <textarea id="add_cancellation_refund" name="cancellation_refund" class="form-control" rows="2" placeholder="e.g. 14-day return policy for standard models in original packaging. Bespoke finishes subject to restocking fee."></textarea>
                        </div>
                    </div>

                    <div class="form-group" style="margin-top: 12px;">
                        <label class="form-label">Additional Product Image Gallery</label>
                        <input type="file" name="gallery_files[]" multiple class="form-control" accept=".jpg,.jpeg,.png,.webp,.avif,.svg">
                        <div style="margin-top: 6px;">
                            <textarea name="gallery_images_text" class="form-control" rows="2" placeholder="Or enter additional image URLs/paths separated by commas: img/shop/prod-2.jpg, img/shop/prod-3.jpg" style="font-size: 12px;"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Section 6: Catalog Visibility & Sorting -->
                <div style="background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 14px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 12px;">
                        <div class="form-group">
                            <label for="add_product_availability" class="form-label">Availability Mode *</label>
                            <select id="add_product_availability" name="availability" class="form-control" required>
                                <option value="available" selected>Available (In Stock)</option>
                                <option value="made_to_order">Made to order (Bespoke)</option>
                                <option value="coming_soon">Coming soon</option>
                                <option value="unavailable">Unavailable (Sold out)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="add_product_label" class="form-label">Format Badge</label>
                            <select id="add_product_label" name="product_label" class="form-control">
                                <option value="">-- No Label --</option>
                                <option value="Digital">Digital</option>
                                <option value="Printed">Printed</option>
                                <option value="Made to order">Made to order</option>
                                <option value="Online course">Online course</option>
                                <option value="Consultation">Consultation</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="add_product_order" class="form-label">Display Order</label>
                            <input type="number" id="add_product_order" name="display_order" class="form-control" value="<?= $totalProducts + 1 ?>" min="0">
                        </div>
                        <div class="form-group">
                            <label for="add_product_popularity" class="form-label">Popularity (1-10)</label>
                            <input type="number" id="add_product_popularity" name="popularity" class="form-control" value="5" min="1" max="10">
                        </div>
                    </div>

                    <div style="display: flex; gap: 16px; flex-wrap: wrap; margin-top: 12px;">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 13px; font-weight: 500;">
                            <input type="checkbox" id="add_product_featured" name="is_featured" value="1" style="width: 16px; height: 16px; accent-color: var(--gold-primary);">
                            ★ Featured Spotlight on Shop Home
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 13px; font-weight: 500;">
                            <input type="checkbox" id="add_product_price_on_request" name="price_on_request" value="1" style="width: 16px; height: 16px; accent-color: var(--gold-primary);">
                            Price on request (hide numeric price)
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 13px; font-weight: 500;">
                            <input type="checkbox" name="is_active" value="1" checked style="width: 16px; height: 16px; accent-color: var(--gold-primary);">
                            Publish actively in live shop
                        </label>
                    </div>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-gold">Save &amp; Publish Product</button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: EDIT PRODUCT -->
<!-- ========================================================================= -->
<div id="editProductModal" class="modal-overlay">
    <div class="modal-dialog" style="max-width: 860px; max-height: 90vh; display: flex; flex-direction: column;">
        <div class="modal-header">
            <div>
                <h3 class="modal-title">Edit Shop Product</h3>
                <p style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">Modify pricing, catalog visibility, stock levels, booking capacities, and bespoke attributes</p>
            </div>
            <button type="button" class="modal-close" data-modal-close>&times;</button>
        </div>
        <form action="product_actions.php" method="POST" enctype="multipart/form-data" style="display: flex; flex-direction: column; overflow: hidden; height: 100%;">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="product_id" id="edit_product_id">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            
            <div class="modal-body" style="overflow-y: auto; padding: 20px 24px; display: flex; flex-direction: column; gap: 20px;">
                
                <!-- Section 1: Basic Information -->
                <div style="border-bottom: 1px solid var(--border-color); padding-bottom: 16px;">
                    <h4 style="font-size: 13px; text-transform: uppercase; letter-spacing: 1px; color: var(--gold-primary); margin-bottom: 12px; font-weight: 700;">1. Core Product Details</h4>
                    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 12px;">
                        <div class="form-group">
                            <label for="edit_product_name" class="form-label">Product Title *</label>
                            <input type="text" id="edit_product_name" name="name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_product_sku" class="form-label">SKU (Catalog Code)</label>
                            <input type="text" id="edit_product_sku" name="sku" class="form-control">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; margin-top: 12px;">
                        <div class="form-group">
                            <label for="edit_product_price" class="form-label">Price (£) *</label>
                            <input type="number" step="0.01" min="0" id="edit_product_price" name="price" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_product_sale_price" class="form-label">Sale Price (£)</label>
                            <input type="number" step="0.01" min="0" id="edit_product_sale_price" name="sale_price" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="edit_product_category" class="form-label">Catalog Category *</label>
                            <select id="edit_product_category" name="category" class="form-control" required>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                                <?php endforeach; ?>
                                <option value="__custom__">+ Add New Category...</option>
                            </select>
                            <div id="edit_custom_category_wrap" style="display: none; margin-top: 6px;">
                                <input type="text" id="edit_custom_category" name="custom_category" class="form-control" placeholder="Enter new category name...">
                            </div>
                        </div>
                    </div>

                    <div class="form-group" style="margin-top: 12px;">
                        <label for="edit_product_desc" class="form-label">Full Product Description</label>
                        <textarea id="edit_product_desc" name="description" class="form-control" rows="3"></textarea>
                    </div>

                    <div class="form-group" style="margin-top: 12px;">
                        <label class="form-label">Primary Cover Image</label>
                        <div style="display: flex; gap: 12px; align-items: center;">
                            <div class="product-admin-thumb" style="width: 60px; height: 60px;">
                                <img id="edit_image_preview" src="" alt="Cover Preview">
                            </div>
                            <div style="flex: 1;">
                                <input type="file" name="product_image" class="form-control" accept=".jpg,.jpeg,.png,.webp,.avif,.svg">
                                <input type="text" id="edit_custom_image_path" name="custom_image_path" class="form-control" placeholder="Current path or external URL" style="font-size: 12px; margin-top: 4px;">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Inventory, Stock & Booking Capacity -->
                <div style="border-bottom: 1px solid var(--border-color); padding-bottom: 16px; background: rgba(16,185,129,0.02); border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 14px;">
                    <h4 style="font-size: 13px; text-transform: uppercase; letter-spacing: 1px; color: #059669; margin-bottom: 12px; font-weight: 700;">2. Inventory, Stock &amp; Booking Capacity</h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px;">
                        <div class="form-group">
                            <label class="form-label">Physical Stock Quantity</label>
                            <input type="number" id="edit_stock_quantity" name="stock_quantity" class="form-control" min="0">
                            <span style="font-size: 11px; color: var(--text-muted);">Current units in studio inventory</span>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Low Stock Alert Threshold</label>
                            <input type="number" id="edit_low_stock_threshold" name="low_stock_threshold" class="form-control" min="1">
                            <span style="font-size: 11px; color: var(--text-muted);">Triggers amber warning tag</span>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Made-to-Order Lead Time</label>
                            <input type="text" id="edit_lead_time" name="lead_time" class="form-control" placeholder="e.g. 3–4 weeks handcrafted">
                            <span style="font-size: 11px; color: var(--text-muted);">For bespoke or crafted pieces</span>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 10px;">
                        <div class="form-group">
                            <label class="form-label">Booking Capacity (Max Seats / Slots)</label>
                            <input type="number" id="edit_booking_capacity" name="booking_capacity" class="form-control" min="0">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Already Booked Count</label>
                            <input type="number" id="edit_booked_count" name="booked_count" class="form-control" min="0">
                        </div>
                    </div>

                    <div style="margin-top: 10px;">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 13px;">
                            <input type="checkbox" id="edit_allow_backorder" name="allow_backorder" value="1" style="accent-color: var(--gold-primary);">
                            Allow customers to purchase on backorder when stock reaches 0
                        </label>
                    </div>
                </div>

                <!-- Section 3: Digital Deliverables & Protected Links -->
                <div style="border-bottom: 1px solid var(--border-color); padding-bottom: 16px; background: rgba(99,102,241,0.02); border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 14px;">
                    <h4 style="font-size: 13px; text-transform: uppercase; letter-spacing: 1px; color: #4f46e5; margin-bottom: 12px; font-weight: 700;">3. Digital Deliverables &amp; Protected Links (Optional)</h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div class="form-group">
                            <label class="form-label">Upload New Deliverable Asset (.ZIP, .PDF, .RFA, .DWG)</label>
                            <input type="file" name="deliverable_upload" class="form-control" accept=".zip,.rar,.pdf,.brushset,.dwg,.dxf,.rfa,.blend">
                            <input type="text" id="edit_digital_deliverable_file" class="form-control" placeholder="Current private file key" style="font-size: 11px; margin-top: 4px;" readonly>
                            <div id="edit_private_product_download" style="font-size: 11px; margin-top: 4px;"></div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Protected Access URL / Token Link</label>
                            <input type="url" id="edit_digital_access_link" name="digital_access_link" class="form-control" placeholder="https://urthspaces.com/download.php?token=sec_xxx">
                            <span style="font-size: 11px; color: var(--text-muted);">Auto-dispatched to verified paying clients</span>
                        </div>
                    </div>
                </div>

                <!-- Section 4: Product Type & Specialized Details -->
                <div style="border-bottom: 1px solid var(--border-color); padding-bottom: 16px;">
                    <div class="form-group">
                        <label for="edit_product_type" class="form-label" style="font-weight: 600;">Product Type *</label>
                        <select id="edit_product_type" name="product_type" class="form-control" style="border-color: var(--gold-primary); background-color: var(--gold-subtle); font-weight: 600;">
                            <option value="furniture_object">Furniture or Object (Dimensions, Materials, Finishes, Customisation)</option>
                            <option value="digital_asset">Digital Asset (File Formats, Compatibility, Permitted Use)</option>
                            <option value="book_planner">Book or Planner (Format, Sample Pages, Dimensions)</option>
                            <option value="course_workshop">Course or Workshop (Topics, Duration, Access Period, Live Dates)</option>
                            <option value="design_session">Design Session (Scope, Duration, Customer Info Needed, Deliverables)</option>
                        </select>
                    </div>

                    <!-- Type Subgroups for Edit -->
                    <div id="edit_type_group_furniture_object" class="type-fields-group" style="margin-top: 14px; background: rgba(166,132,73,0.03); border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 14px;">
                        <span style="font-size: 12px; font-weight: 700; color: var(--gold-primary); display: block; margin-bottom: 10px;">Furniture &amp; Objects Specifications</span>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                            <div class="form-group">
                                <label class="form-label">Dimensions</label>
                                <input type="text" id="edit_dimensions" name="dimensions" class="form-control">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Materials</label>
                                <input type="text" id="edit_materials" name="materials" class="form-control">
                            </div>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 10px;">
                            <div class="form-group">
                                <label class="form-label">Finishes Available</label>
                                <input type="text" id="edit_finishes" name="finishes" class="form-control">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Production Time</label>
                                <input type="text" id="edit_production_time" name="production_time" class="form-control">
                            </div>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 10px;">
                            <div class="form-group">
                                <label class="form-label">Customisation Details</label>
                                <input type="text" id="edit_customisation" name="customisation" class="form-control">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Delivery Area</label>
                                <input type="text" id="edit_delivery_area" name="delivery_area" class="form-control">
                            </div>
                        </div>
                    </div>

                    <div id="edit_type_group_digital_asset" class="type-fields-group" style="display: none; margin-top: 14px; background: rgba(59,130,246,0.03); border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 14px;">
                        <span style="font-size: 12px; font-weight: 700; color: #2563eb; display: block; margin-bottom: 10px;">Digital Asset Specifications</span>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                            <div class="form-group">
                                <label class="form-label">File Formats</label>
                                <input type="text" id="edit_file_formats" name="file_formats" class="form-control">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Software Compatibility</label>
                                <input type="text" id="edit_software_compatibility" name="software_compatibility" class="form-control">
                            </div>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 10px;">
                            <div class="form-group">
                                <label class="form-label">Permitted Use / License Scope</label>
                                <input type="text" id="edit_permitted_use" name="permitted_use" class="form-control">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Preview Images &amp; Demos</label>
                                <textarea id="edit_preview_images" name="preview_images" class="form-control" rows="3" placeholder="One image URL or preview note per line"></textarea>
                            </div>
                        </div>
                    </div>

                    <div id="edit_type_group_book_planner" class="type-fields-group" style="display: none; margin-top: 14px; background: rgba(16,185,129,0.03); border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 14px;">
                        <span style="font-size: 12px; font-weight: 700; color: #059669; display: block; margin-bottom: 10px;">Book &amp; Planner Specifications</span>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                            <div class="form-group">
                                <label class="form-label">Digital / Printed Format</label>
                                <input type="text" id="edit_book_format" name="book_format" class="form-control">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Sample Pages Preview</label>
                                <input type="text" id="edit_sample_pages" name="sample_pages" class="form-control">
                            </div>
                        </div>
                        <div class="form-group" style="margin-top: 10px;">
                            <label class="form-label">Dimensions &amp; Page Details</label>
                            <input type="text" id="edit_book_dimensions" name="book_dimensions" class="form-control">
                        </div>
                    </div>

                    <div id="edit_type_group_course_workshop" class="type-fields-group" style="display: none; margin-top: 14px; background: rgba(168,85,247,0.03); border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 14px;">
                        <span style="font-size: 12px; font-weight: 700; color: #7c3aed; display: block; margin-bottom: 10px;">Course &amp; Workshop Specifications</span>
                        <div class="form-group">
                            <label class="form-label">Topics / Curriculum</label>
                            <textarea id="edit_course_topics" name="course_topics" class="form-control" rows="2"></textarea>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 10px;">
                            <div class="form-group">
                                <label class="form-label">Course Format</label>
                                <input type="text" id="edit_course_format" name="course_format" class="form-control">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Duration</label>
                                <input type="text" id="edit_course_duration" name="course_duration" class="form-control">
                            </div>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 10px;">
                            <div class="form-group">
                                <label class="form-label">Access Period</label>
                                <input type="text" id="edit_access_period" name="access_period" class="form-control">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Dates &amp; Time Zone for Live Events</label>
                                <input type="text" id="edit_live_dates" name="live_dates" class="form-control">
                            </div>
                        </div>
                    </div>

                    <div id="edit_type_group_design_session" class="type-fields-group" style="display: none; margin-top: 14px; background: rgba(234,88,12,0.03); border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 14px;">
                        <span style="font-size: 12px; font-weight: 700; color: #ea580c; display: block; margin-bottom: 10px;">Design Session Specifications</span>
                        <div class="form-group">
                            <label class="form-label">Consultation Scope</label>
                            <textarea id="edit_session_scope" name="session_scope" class="form-control" rows="2"></textarea>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 10px;">
                            <div class="form-group">
                                <label class="form-label">Session Duration</label>
                                <input type="text" id="edit_session_duration" name="session_duration" class="form-control">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Included Revisions</label>
                                <input type="text" id="edit_included_revisions" name="included_revisions" class="form-control">
                            </div>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 10px;">
                            <div class="form-group">
                                <label class="form-label">Required Customer Information</label>
                                <textarea id="edit_customer_info_needed" name="customer_info_needed" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Final Deliverables</label>
                                <textarea id="edit_deliverables" name="deliverables" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 5: Options, Inclusions & Policies -->
                <div>
                    <h4 style="font-size: 13px; text-transform: uppercase; letter-spacing: 1px; color: var(--gold-primary); margin-bottom: 12px; font-weight: 700;">5. Options, Inclusions &amp; Policies</h4>
                    
                    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 12px;">
                        <div class="form-group">
                            <label for="edit_options_label" class="form-label">Available Option Label</label>
                            <input type="text" id="edit_options_label" name="options_label" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="edit_options_list" class="form-label">Available Options (Comma-separated)</label>
                            <input type="text" id="edit_options_list" name="options_list" class="form-control">
                        </div>
                    </div>

                    <div class="form-group" style="margin-top: 12px;">
                        <label for="edit_what_included" class="form-label">What is Included (List with bullets or newlines)</label>
                        <textarea id="edit_what_included" name="what_included" class="form-control" rows="3"></textarea>
                    </div>

                    <div class="form-group" style="margin-top: 12px;">
                        <label for="edit_delivery_time" class="form-label">Expected Delivery or Preparation Time</label>
                        <input type="text" id="edit_delivery_time" name="delivery_time" class="form-control">
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 12px;">
                        <div class="form-group">
                            <label for="edit_delivery_info" class="form-label">Delivery &amp; Shipping Policy</label>
                            <textarea id="edit_delivery_info" name="delivery_info" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="edit_cancellation_refund" class="form-label">Cancellation &amp; Refund Information</label>
                            <textarea id="edit_cancellation_refund" name="cancellation_refund" class="form-control" rows="2"></textarea>
                        </div>
                    </div>

                    <div class="form-group" style="margin-top: 12px;">
                        <label class="form-label">Additional Product Image Gallery</label>
                        <input type="file" name="gallery_files[]" multiple class="form-control" accept=".jpg,.jpeg,.png,.webp,.avif,.svg">
                        <div style="margin-top: 6px;">
                            <textarea id="edit_gallery_images_text" name="gallery_images_text" class="form-control" rows="2" placeholder="Image URLs/paths separated by commas" style="font-size: 12px;"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Section 6: Catalog Visibility & Sorting -->
                <div style="background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 14px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 12px;">
                        <div class="form-group">
                            <label for="edit_product_availability" class="form-label">Availability Mode *</label>
                            <select id="edit_product_availability" name="availability" class="form-control" required>
                                <option value="available">Available (In Stock)</option>
                                <option value="made_to_order">Made to order (Bespoke)</option>
                                <option value="coming_soon">Coming soon</option>
                                <option value="unavailable">Unavailable (Sold out)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="edit_product_label" class="form-label">Format Badge</label>
                            <select id="edit_product_label" name="product_label" class="form-control">
                                <option value="">-- No Label --</option>
                                <option value="Digital">Digital</option>
                                <option value="Printed">Printed</option>
                                <option value="Made to order">Made to order</option>
                                <option value="Online course">Online course</option>
                                <option value="Consultation">Consultation</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="edit_product_order" class="form-label">Display Order</label>
                            <input type="number" id="edit_product_order" name="display_order" class="form-control" min="0">
                        </div>
                        <div class="form-group">
                            <label for="edit_product_popularity" class="form-label">Popularity (1-10)</label>
                            <input type="number" id="edit_product_popularity" name="popularity" class="form-control" min="1" max="10">
                        </div>
                    </div>

                    <div style="display: flex; gap: 16px; flex-wrap: wrap; margin-top: 12px;">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 13px; font-weight: 500;">
                            <input type="checkbox" id="edit_product_featured" name="is_featured" value="1" style="width: 16px; height: 16px; accent-color: var(--gold-primary);">
                            ★ Featured Spotlight on Shop Home
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 13px; font-weight: 500;">
                            <input type="checkbox" id="edit_product_price_on_request" name="price_on_request" value="1" style="width: 16px; height: 16px; accent-color: var(--gold-primary);">
                            Price on request (hide numeric price)
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 13px; font-weight: 500;">
                            <input type="checkbox" id="edit_product_active" name="is_active" value="1" style="width: 16px; height: 16px; accent-color: var(--gold-primary);">
                            Publish actively in live shop
                        </label>
                    </div>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-gold">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: DELETE PRODUCT CONFIRMATION -->
<!-- ========================================================================= -->
<div id="deleteProductModal" class="modal-overlay">
    <div class="modal-dialog" style="max-width: 440px;">
        <div class="modal-header">
            <h3 class="modal-title">Delete Product</h3>
            <button type="button" class="modal-close" data-modal-close>&times;</button>
        </div>
        <form action="product_actions.php" method="POST">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="product_id" id="delete_product_id">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            
            <div class="modal-body">
                <p style="color: var(--text-secondary); line-height: 1.6;">
                    Are you sure you want to remove <strong id="delete_product_name" style="color: var(--text-primary);"></strong> from the catalog?
                </p>
                <p style="color: var(--danger); font-size: 12px; margin-top: 8px;">
                    This action will permanently delete this product record and remove its cover image.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-danger">Confirm Delete</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Modal Helpers
    const openModal = (m) => { if (m) { m.classList.add('active'); document.body.style.overflow = 'hidden'; } };
    const closeModal = (m) => { if (m) { m.classList.remove('active'); document.body.style.overflow = ''; } };

    document.querySelectorAll('[data-modal-close]').forEach(b => {
        b.addEventListener('click', () => closeModal(b.closest('.modal-overlay')));
    });

    document.querySelectorAll('.modal-overlay').forEach(o => {
        o.addEventListener('click', (e) => { if (e.target === o) closeModal(o); });
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') document.querySelectorAll('.modal-overlay.active').forEach(m => closeModal(m));
    });

    // Category Modal
    const categoryModal = document.querySelector('#manageCategoriesModal');
    const openCatBtn = document.querySelector('#openCategoryModalBtn');
    if (openCatBtn) openCatBtn.addEventListener('click', () => openModal(categoryModal));

    // Category Edit Submodal
    const editCatModal = document.querySelector('#editCategoryModal');
    document.querySelectorAll('.edit-category-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelector('#edit_cat_id').value = btn.getAttribute('data-id');
            document.querySelector('#edit_cat_name').value = btn.getAttribute('data-name');
            document.querySelector('#edit_cat_order').value = btn.getAttribute('data-order');
            document.querySelector('#edit_cat_description').value = btn.getAttribute('data-desc');
            openModal(editCatModal);
        });
    });

    // Add Product Modal
    const addModal = document.querySelector('#addProductModal');
    const openAddBtn = document.querySelector('#openAddProductModal');
    if (openAddBtn) openAddBtn.addEventListener('click', () => openModal(addModal));

    // Custom Category Input Toggle
    const handleCategorySelect = (selectEl, wrapEl) => {
        if (!selectEl || !wrapEl) return;
        selectEl.addEventListener('change', () => {
            wrapEl.style.display = selectEl.value === '__custom__' ? 'block' : 'none';
        });
    };
    handleCategorySelect(document.querySelector('#add_product_category'), document.querySelector('#add_custom_category_wrap'));
    handleCategorySelect(document.querySelector('#edit_product_category'), document.querySelector('#edit_custom_category_wrap'));

    // Dynamic Type-Specific Fields Switcher
    const switchProductType = (prefix, typeVal) => {
        const types = ['furniture_object', 'digital_asset', 'book_planner', 'course_workshop', 'design_session'];
        types.forEach(t => {
            const grp = document.querySelector(`#${prefix}_type_group_${t}`);
            if (grp) grp.style.display = (t === typeVal) ? 'block' : 'none';
        });
    };

    const addTypeSelect = document.querySelector('#add_product_type');
    if (addTypeSelect) {
        addTypeSelect.addEventListener('change', (e) => switchProductType('add', e.target.value));
    }
    const editTypeSelect = document.querySelector('#edit_product_type');
    if (editTypeSelect) {
        editTypeSelect.addEventListener('change', (e) => switchProductType('edit', e.target.value));
    }

    // Edit Product Logic
    const editModal = document.querySelector('#editProductModal');
    const editImgPreview = document.querySelector('#edit_image_preview');

    document.querySelectorAll('.edit-product-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const row = btn.closest('.product-row');
            if (!row) return;

            const rawData = row.getAttribute('data-raw');
            if (!rawData) return;

            const prod = JSON.parse(rawData);
            const setVal = (sel, val) => { const el = document.querySelector(sel); if (el) el.value = val !== null && val !== undefined ? val : ''; };

            setVal('#edit_product_id', prod.id);
            setVal('#edit_product_name', prod.name);
            setVal('#edit_product_sku', prod.sku);
            setVal('#edit_product_price', prod.price);
            setVal('#edit_product_sale_price', prod.sale_price);
            setVal('#edit_product_order', prod.display_order);
            setVal('#edit_product_popularity', prod.popularity);
            setVal('#edit_product_desc', prod.description);

            // Category
            const catSelect = document.querySelector('#edit_product_category');
            if (catSelect) {
                let found = false;
                for (let i = 0; i < catSelect.options.length; i++) {
                    if (catSelect.options[i].value === prod.category) {
                        catSelect.selectedIndex = i;
                        found = true;
                        break;
                    }
                }
                if (!found && prod.category) {
                    const opt = new Option(prod.category, prod.category, true, true);
                    catSelect.add(opt);
                }
            }

            // Inventory & Capacity
            setVal('#edit_stock_quantity', prod.stock_quantity !== undefined ? prod.stock_quantity : 10);
            setVal('#edit_low_stock_threshold', prod.low_stock_threshold !== undefined ? prod.low_stock_threshold : 2);
            setVal('#edit_booking_capacity', prod.booking_capacity !== undefined ? prod.booking_capacity : 0);
            setVal('#edit_booked_count', prod.booked_count !== undefined ? prod.booked_count : 0);
            setVal('#edit_lead_time', prod.lead_time);
            document.querySelector('#edit_allow_backorder').checked = parseInt(prod.allow_backorder, 10) === 1;

            // Deliverables
            setVal('#edit_digital_deliverable_file', prod.digital_deliverable_file);
            const privateProductDownload = document.querySelector('#edit_private_product_download');
            privateProductDownload.textContent = '';
            if (prod.digital_deliverable_file) {
                const productFileLink = document.createElement('a');
                productFileLink.href = 'private_file.php?key=' + encodeURIComponent(prod.digital_deliverable_file);
                productFileLink.textContent = 'Download private product file';
                privateProductDownload.appendChild(productFileLink);
            }
            setVal('#edit_digital_access_link', prod.digital_access_link);

            // Checkboxes & Selects
            document.querySelector('#edit_product_active').checked = parseInt(prod.is_active, 10) === 1;
            const editAvail = document.querySelector('#edit_product_availability');
            if (editAvail) editAvail.value = prod.availability || 'available';
            const editLabel = document.querySelector('#edit_product_label');
            if (editLabel) editLabel.value = prod.product_label || '';
            const editFeat = document.querySelector('#edit_product_featured');
            if (editFeat) editFeat.checked = parseInt(prod.is_featured, 10) === 1;
            const editPor = document.querySelector('#edit_product_price_on_request');
            if (editPor) editPor.checked = parseInt(prod.price_on_request, 10) === 1;

            // Product Type and Dynamic Fields
            const pType = prod.product_type || 'furniture_object';
            if (editTypeSelect) {
                editTypeSelect.value = pType;
                switchProductType('edit', pType);
            }

            setVal('#edit_options_label', prod.options_label || 'Option');
            setVal('#edit_options_list', prod.options_list || '');
            setVal('#edit_what_included', prod.what_included || '');
            setVal('#edit_delivery_time', prod.delivery_time || '');
            setVal('#edit_delivery_info', prod.delivery_info || '');
            setVal('#edit_cancellation_refund', prod.cancellation_refund || '');

            // Gallery images text
            let galleryText = '';
            if (prod.gallery_images) {
                try {
                    const galArr = typeof prod.gallery_images === 'string' ? JSON.parse(prod.gallery_images) : prod.gallery_images;
                    if (Array.isArray(galArr)) galleryText = galArr.join(', ');
                } catch(e) {
                    galleryText = prod.gallery_images;
                }
            }
            setVal('#edit_gallery_images_text', galleryText);

            // Furniture
            setVal('#edit_dimensions', prod.dimensions);
            setVal('#edit_materials', prod.materials);
            setVal('#edit_finishes', prod.finishes);
            setVal('#edit_customisation', prod.customisation);
            setVal('#edit_production_time', prod.production_time);
            setVal('#edit_delivery_area', prod.delivery_area);

            // Digital Asset
            setVal('#edit_file_formats', prod.file_formats);
            setVal('#edit_software_compatibility', prod.software_compatibility);
            setVal('#edit_permitted_use', prod.permitted_use);
            setVal('#edit_preview_images', prod.preview_images);

            // Book or Planner
            setVal('#edit_book_format', prod.book_format);
            setVal('#edit_sample_pages', prod.sample_pages);
            setVal('#edit_book_dimensions', prod.book_dimensions);

            // Course or Workshop
            setVal('#edit_course_topics', prod.course_topics);
            setVal('#edit_course_format', prod.course_format);
            setVal('#edit_course_duration', prod.course_duration);
            setVal('#edit_access_period', prod.access_period);
            setVal('#edit_live_dates', prod.live_dates);

            // Design Session
            setVal('#edit_session_scope', prod.session_scope);
            setVal('#edit_session_duration', prod.session_duration);
            setVal('#edit_customer_info_needed', prod.customer_info_needed);
            setVal('#edit_deliverables', prod.deliverables);
            setVal('#edit_included_revisions', prod.included_revisions);

            const imgPath = prod.image ? (!/^https?:\/\//i.test(prod.image) ? '../' + prod.image.replace(/^\/+/, '') : prod.image) : '';
            if (editImgPreview) editImgPreview.src = imgPath;
            setVal('#edit_custom_image_path', prod.image);

            openModal(editModal);
        });
    });

    // Delete Modal Logic
    const deleteModal = document.querySelector('#deleteProductModal');
    document.querySelectorAll('.delete-product-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const row = btn.closest('.product-row');
            if (!row) return;

            const rawData = row.getAttribute('data-raw');
            if (!rawData) return;
            const prod = JSON.parse(rawData);

            document.querySelector('#delete_product_id').value = prod.id;
            document.querySelector('#delete_product_name').textContent = prod.name;
            openModal(deleteModal);
        });
    });

    // Live Search & Category / Status / Availability Filtering
    const searchInput = document.querySelector('#productSearchInput');
    const catFilter = document.querySelector('#categoryFilter');
    const statFilter = document.querySelector('#statusFilter');
    const availFilter = document.querySelector('#availabilityFilter');
    const countDisplay = document.querySelector('#productCount');
    const rows = document.querySelectorAll('.product-row');
    const noResultsRow = document.querySelector('#noResultsRow');

    function filterTable() {
        const query = (searchInput ? searchInput.value : '').toLowerCase().trim();
        const selectedCat = (catFilter ? catFilter.value : '').toLowerCase().trim();
        const selectedStatus = statFilter ? statFilter.value : '';
        const selectedAvail = availFilter ? availFilter.value : '';

        let visibleCount = 0;

        rows.forEach(row => {
            const name = row.getAttribute('data-name') || '';
            const sku = row.getAttribute('data-sku') || '';
            const cat = (row.getAttribute('data-category') || '').toLowerCase();
            const active = row.getAttribute('data-active') || '1';
            const avail = row.getAttribute('data-avail') || '';
            const type = row.getAttribute('data-type') || '';
            const isLow = row.getAttribute('data-is-low') === '1';
            const isPor = row.getAttribute('data-por') === '1';

            const matchesSearch = !query || name.includes(query) || sku.includes(query) || cat.includes(query);
            const matchesCat = !selectedCat || cat === selectedCat;
            const matchesStatus = selectedStatus === '' || active === selectedStatus;

            let matchesAvail = true;
            if (selectedAvail === 'available') {
                matchesAvail = avail === 'available';
            } else if (selectedAvail === 'low_stock') {
                matchesAvail = isLow;
            } else if (selectedAvail === 'made_to_order') {
                matchesAvail = avail === 'made_to_order';
            } else if (selectedAvail === 'booking_capacity') {
                matchesAvail = type === 'design_session' || type === 'course_workshop';
            } else if (selectedAvail === 'digital_asset') {
                matchesAvail = type === 'digital_asset';
            } else if (selectedAvail === 'price_on_request') {
                matchesAvail = isPor;
            }

            if (matchesSearch && matchesCat && matchesStatus && matchesAvail) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        if (countDisplay) countDisplay.textContent = visibleCount;
        if (noResultsRow) noResultsRow.style.display = visibleCount === 0 && rows.length > 0 ? '' : 'none';
    }

    if (searchInput) searchInput.addEventListener('input', filterTable);
    if (catFilter) catFilter.addEventListener('change', filterTable);
    if (statFilter) statFilter.addEventListener('change', filterTable);
    if (availFilter) availFilter.addEventListener('change', filterTable);
});
</script>

</body>
</html>
