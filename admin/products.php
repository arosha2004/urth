<?php
/**
 * URTH Admin Panel - Manage Shop Products
 */

require_once __DIR__ . '/auth.php';
require_admin_auth();
require_once '../config.php';

// Auto-check table existence
$tblCheck = $conn->query("SHOW TABLES LIKE 'products'");
if (!$tblCheck || $tblCheck->num_rows === 0) {
    require_once __DIR__ . '/../setup_shop.php';
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
$catRes = $conn->query("SELECT name FROM product_categories ORDER BY display_order ASC, name ASC");
$standardCategories = [];
if ($catRes) {
    while ($cRow = $catRes->fetch_assoc()) {
        $standardCategories[] = $cRow['name'];
    }
}
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
        $alertMessage = 'Product details updated successfully.';
        break;
    case 'deleted':
        $alertMessage = 'Product has been removed from the shop catalog.';
        $alertType = 'danger';
        break;
    case 'status_updated':
        $alertMessage = 'Product display status has been toggled successfully.';
        break;
    case 'error_missing':
        $alertMessage = 'Please fill in all required fields (Name and Price are required).';
        $alertType = 'danger';
        break;
    case 'error_invalid':
        $alertMessage = 'Invalid product record specified.';
        $alertType = 'danger';
        break;
    case 'error_filetype':
        $alertMessage = 'Invalid file type. Supported formats: JPG, PNG, WEBP, AVIF, SVG.';
        $alertType = 'danger';
        break;
    case 'error_size':
        $alertMessage = 'Uploaded file is too large. Please upload an image under 5MB.';
        $alertType = 'danger';
        break;
    case 'error_upload':
        $alertMessage = 'Failed to upload product image. Please try again.';
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
    <title>Manage Shop Products — URTH Admin Panel</title>
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
                Orders
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
                View Shop Page
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
                <h1>Manage Shop Products</h1>
                <p>Add, edit, organize prices, images, and descriptions for items in the URTH shop collection</p>
            </div>

            <button type="button" id="openAddProductModal" class="btn btn-gold">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Add New Product
            </button>
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
                    <div class="stat-label">Active in Shop</div>
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
                    <div class="stat-label">Average Price</div>
                    <div class="stat-value">£<?= number_format($avgPrice, 2) ?></div>
                </div>
                <div class="stat-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"/><path d="M12 18V6"/></svg>
                </div>
            </div>
        </div>

        <!-- Toolbar & Filter Card -->
        <div class="toolbar-card">
            <div class="search-filter-group" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center; flex: 1;">
                <div class="search-box" style="flex: 1; min-width: 240px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="text" id="productSearchInput" class="form-control" placeholder="Search products by title, SKU, or category...">
                </div>

                <div style="min-width: 150px;">
                    <select id="categoryFilter" class="form-control" style="cursor: pointer;">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="min-width: 130px;">
                    <select id="statusFilter" class="form-control" style="cursor: pointer;">
                        <option value="">All Statuses</option>
                        <option value="1">Active Only</option>
                        <option value="0">Hidden Only</option>
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
                            <th style="width: 50px;">Order</th>
                            <th style="width: 70px;">Image</th>
                            <th>Product Name, SKU &amp; Format</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th style="width: 140px;">Availability &amp; Status</th>
                            <th style="width: 80px; text-align: center;">Featured</th>
                            <th style="width: 160px; text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($products)): ?>
                            <tr id="emptyRow">
                                <td colspan="8">
                                    <div class="empty-state">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                                        <h3>No Shop Products Found</h3>
                                        <p>Click "Add New Product" to create your first shop catalog item.</p>
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
                                ?>
                                <tr class="product-row"
                                    data-id="<?= $prod['id'] ?>"
                                    data-name="<?= htmlspecialchars(strtolower($prod['name'])) ?>"
                                    data-sku="<?= htmlspecialchars(strtolower($prod['sku'] ?? '')) ?>"
                                    data-category="<?= htmlspecialchars($prod['category'] ?? '') ?>"
                                    data-active="<?= (int)$prod['is_active'] ?>"
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
                                        <span class="product-sku">SKU: <?= htmlspecialchars($prod['sku'] ?? 'N/A') ?></span>
                                    </td>
                                    <td>
                                        <span class="category-pill"><?= htmlspecialchars($prod['category'] ?? 'General') ?></span>
                                    </td>
                                    <td>
                                        <?php if (!empty($prod['price_on_request'])): ?>
                                            <div style="font-size: 12px; font-weight: 700; color: var(--gold-primary); text-transform: uppercase; letter-spacing: 0.5px;">Price on request</div>
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
                                        <?php
                                            $avail = strtolower($prod['availability'] ?? 'available');
                                            $availClass = 'available';
                                            $availText = 'Available';
                                            $availColor = '#22c55e';
                                            $availBg = 'rgba(34,197,94,0.15)';
                                            if ($avail === 'made_to_order') {
                                                $availText = 'Made to Order';
                                                $availColor = '#eab308';
                                                $availBg = 'rgba(234,179,8,0.15)';
                                            } elseif ($avail === 'coming_soon') {
                                                $availText = 'Coming Soon';
                                                $availColor = '#38bdf8';
                                                $availBg = 'rgba(56,189,248,0.15)';
                                            } elseif ($avail === 'unavailable') {
                                                $availText = 'Unavailable';
                                                $availColor = '#ef4444';
                                                $availBg = 'rgba(239,68,68,0.15)';
                                            }
                                        ?>
                                        <div style="display: flex; flex-direction: column; gap: 4px; align-items: flex-start;">
                                            <span style="display: inline-block; padding: 2px 7px; border-radius: 4px; font-size: 11px; font-weight: 600; color: <?= $availColor ?>; background: <?= $availBg ?>;">
                                                <?= $availText ?>
                                            </span>
                                            <a href="product_actions.php?action=toggle_status&id=<?= $prod['id'] ?>"
                                               class="status-badge <?= (int)$prod['is_active'] === 1 ? 'active' : 'inactive' ?>"
                                               title="Click to toggle visibility in shop"
                                               style="text-decoration: none; font-size: 10px; padding: 1px 6px;">
                                                <?= (int)$prod['is_active'] === 1 ? 'Catalog Active' : 'Catalog Hidden' ?>
                                            </a>
                                        </div>
                                    </td>
                                    <td style="text-align: center;">
                                        <a href="product_actions.php?action=toggle_featured&id=<?= $prod['id'] ?>"
                                           title="Click to toggle Featured on Shop Homepage"
                                           style="text-decoration: none; font-size: 18px; line-height: 1; transition: transform 0.2s; display: inline-block;"
                                           class="toggle-featured-btn">
                                            <?= (int)($prod['is_featured'] ?? 0) === 1 ? '★ <span style="font-size: 11px; color: var(--gold-primary); font-weight:600; display:block;">Featured</span>' : '<span style="color: #666;">☆</span>' ?>
                                        </a>
                                    </td>
                                    <td>
                                            <a href="../product.php?id=<?= $prod['id'] ?>"
                                               target="_blank"
                                               class="btn btn-outline btn-sm"
                                               title="View shareable public product page">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                                                View Page
                                            </a>
                                            <button type="button"
                                                    class="btn btn-outline btn-sm edit-product-btn"
                                                    title="Edit product details">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                                Edit
                                            </button>
                                            <button type="button"
                                                    class="btn btn-danger btn-sm delete-product-btn"
                                                    title="Remove product from catalog">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                                                Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                            <tr id="noResultsRow" style="display: none;">
                                <td colspan="8">
                                    <div class="empty-state">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                                        <h3>No matching products</h3>
                                        <p>Try searching for a different keyword, SKU, or category.</p>
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

<!-- Modal: Add New Product -->
<div id="addProductModal" class="modal-overlay">
    <div class="modal-dialog" style="max-width: 840px; max-height: 90vh; display: flex; flex-direction: column;">
        <div class="modal-header">
            <div>
                <h3 class="modal-title">Add New Shop Product</h3>
                <p style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">Create catalog product and configure shareable individual product page details</p>
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
                            <input type="text" id="add_product_sku" name="sku" class="form-control" placeholder="e.g. PR104">
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
                                <option value="" disabled>-- Select Category --</option>
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
                        <div id="add_image_preview_wrap" style="display: none; margin-top: 10px;">
                            <span style="font-size: 11px; color: var(--text-muted); display: block; margin-bottom: 4px;">Selected Cover Preview:</span>
                            <div class="product-admin-thumb" style="width: 80px; height: 80px;">
                                <img id="add_image_preview" src="" alt="Preview">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Product Type & Specialized Details -->
                <div style="border-bottom: 1px solid var(--border-color); padding-bottom: 16px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                        <h4 style="font-size: 13px; text-transform: uppercase; letter-spacing: 1px; color: var(--gold-primary); margin: 0; font-weight: 700;">2. Product Type &amp; Specific Attributes</h4>
                        <span style="font-size: 11px; color: var(--text-muted);">Adapts the page layout to the selected product category</span>
                    </div>

                    <div class="form-group">
                        <label for="add_product_type" class="form-label" style="font-weight: 600;">Product Type *</label>
                        <select id="add_product_type" name="product_type" class="form-control" style="border-color: var(--gold-primary); background-color: var(--gold-subtle); font-weight: 600;">
                            <option value="furniture_object" selected>Furniture or Object (Dimensions, Materials, Finishes, Customisation, Production Time, Delivery Area)</option>
                            <option value="digital_asset">Digital Asset (File Formats, Software Compatibility, Preview Images, Permitted Use)</option>
                            <option value="book_planner">Book or Planner (Digital/Printed Format, Sample Pages, Dimensions)</option>
                            <option value="course_workshop">Course or Workshop (Topics, Format, Duration, Access Period, Live Dates / Time Zone)</option>
                            <option value="design_session">Design Session (Scope, Duration, Required Customer Info, Deliverables, Included Revisions)</option>
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
                                <input type="text" name="production_time" class="form-control" placeholder="e.g. 3–4 weeks handcrafted in our workshop">
                            </div>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 10px;">
                            <div class="form-group">
                                <label class="form-label">Customisation Details</label>
                                <input type="text" name="customisation" class="form-control" placeholder="e.g. Bespoke heights, alternate timber species, and stain matching available">
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
                                <input type="text" name="permitted_use" class="form-control" placeholder="e.g. Commercial and personal architectural presentations. Reselling prohibited.">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Preview Images &amp; Demos</label>
                                <input type="text" name="preview_images" class="form-control" placeholder="e.g. 12 high-resolution render samples included in download">
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
                                <input type="text" name="sample_pages" class="form-control" placeholder="e.g. 42 sample pages preview available in table of contents">
                            </div>
                        </div>
                        <div class="form-group" style="margin-top: 10px;">
                            <label class="form-label">Dimensions &amp; Page Details</label>
                            <input type="text" name="book_dimensions" class="form-control" placeholder="e.g. 240 x 170 mm (Standard Royal), 210 pages, 140gsm uncoated FSC paper">
                        </div>
                    </div>

                    <!-- Type Subgroup: Course or Workshop -->
                    <div id="add_type_group_course_workshop" class="type-fields-group" style="display: none; margin-top: 14px; background: rgba(168,85,247,0.03); border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 14px;">
                        <span style="font-size: 12px; font-weight: 700; color: #7c3aed; display: block; margin-bottom: 10px;">Course &amp; Workshop Specifications</span>
                        <div class="form-group">
                            <label class="form-label">Topics / Curriculum Highlights</label>
                            <textarea name="course_topics" class="form-control" rows="2" placeholder="e.g. 1. Spatial Flow &amp; Sightlines, 2. Light Choreography, 3. Architectural Joinery Detailing"></textarea>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 10px;">
                            <div class="form-group">
                                <label class="form-label">Course Format</label>
                                <input type="text" name="course_format" class="form-control" placeholder="e.g. Live Zoom Interactive Webinar + 4K Recorded Archive">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Duration</label>
                                <input type="text" name="course_duration" class="form-control" placeholder="e.g. 4 Weeks (4x 90-minute live masterclasses + weekly critiques)">
                            </div>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 10px;">
                            <div class="form-group">
                                <label class="form-label">Access Period</label>
                                <input type="text" name="access_period" class="form-control" placeholder="e.g. Lifetime unrestricted access to recording portal &amp; templates">
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
                            <textarea name="session_scope" class="form-control" rows="2" placeholder="e.g. Full review of residential spatial layouts, daylighting, joinery placement, and furniture planning"></textarea>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 10px;">
                            <div class="form-group">
                                <label class="form-label">Session Duration</label>
                                <input type="text" name="session_duration" class="form-control" placeholder="e.g. 90-minute live 1-on-1 video call + 3 hours of architect study">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Included Revisions</label>
                                <input type="text" name="included_revisions" class="form-control" placeholder="e.g. Up to 2 iterative design revisions within 14 days of dossier">
                            </div>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 10px;">
                            <div class="form-group">
                                <label class="form-label">Required Customer Information</label>
                                <textarea name="customer_info_needed" class="form-control" rows="2" placeholder="e.g. Existing room dimensions/floor plan, daylight photos, target budget, inspiration boards"></textarea>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Final Deliverables</label>
                                <textarea name="deliverables" class="form-control" rows="2" placeholder="e.g. Scaled 2D plan (PDF), Material &amp; color scheme palette, Clickable shopping list, Video replay"></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Delivery, Inclusions & Shareable Page Details -->
                <div>
                    <h4 style="font-size: 13px; text-transform: uppercase; letter-spacing: 1px; color: var(--gold-primary); margin-bottom: 12px; font-weight: 700;">3. Options, Inclusions &amp; Policies</h4>
                    
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

                <!-- Section 4: Catalog Visibility & Sorting -->
                <div style="background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 14px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 12px;">
                        <div class="form-group">
                            <label for="add_product_availability" class="form-label">Availability *</label>
                            <select id="add_product_availability" name="availability" class="form-control" required>
                                <option value="available" selected>Available</option>
                                <option value="made_to_order">Made to order</option>
                                <option value="coming_soon">Coming soon (cannot accept payment)</option>
                                <option value="unavailable">Unavailable (sold out)</option>
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
                            <label for="add_product_popularity" class="form-label">Popularity</label>
                            <input type="number" id="add_product_popularity" name="popularity" class="form-control" value="5" min="1" max="10">
                        </div>
                    </div>

                    <div style="display: flex; gap: 16px; flex-wrap: wrap; margin-top: 12px;">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 13px; font-weight: 500;">
                            <input type="checkbox" id="add_product_featured" name="is_featured" value="1" style="width: 16px; height: 16px; accent-color: var(--gold-primary);">
                            ★ Featured in Shop Hero Carousel
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 13px; font-weight: 500;">
                            <input type="checkbox" id="add_product_price_on_request" name="price_on_request" value="1" style="width: 16px; height: 16px; accent-color: var(--gold-primary);">
                            Price on request (hide numeric price)
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 13px; font-weight: 500;">
                            <input type="checkbox" name="is_active" value="1" checked style="width: 16px; height: 16px; accent-color: var(--gold-primary);">
                            Display actively in shop catalog
                        </label>
                    </div>
                </div>

            </div>
            
            <div class="modal-footer" style="padding: 14px 24px; border-top: 1px solid var(--border-color); background: var(--bg-card-solid);">
                <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-gold">Publish Product</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit Product -->
<div id="editProductModal" class="modal-overlay">
    <div class="modal-dialog" style="max-width: 840px; max-height: 90vh; display: flex; flex-direction: column;">
        <div class="modal-header">
            <div>
                <h3 class="modal-title">Edit Shop Product</h3>
                <p style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">Modify product attributes and shareable individual page details</p>
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
                                <option value="" disabled>-- Select Category --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat) ?>">
                                        <?= htmlspecialchars($cat) ?>
                                    </option>
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
                        <label for="edit_product_image" class="form-label">Primary Cover Image (Optional to update)</label>
                        <input type="file" id="edit_product_image" name="product_image" class="form-control" accept=".jpg,.jpeg,.png,.webp,.avif,.svg">
                        <div style="margin-top: 6px;">
                            <input type="text" id="edit_custom_image_path" name="custom_image_path" class="form-control" placeholder="Or edit image path/URL" style="font-size: 12px;">
                        </div>
                        <div style="margin-top: 10px;">
                            <span style="font-size: 11px; color: var(--text-muted); display: block; margin-bottom: 4px;">Current / Selected Image Preview:</span>
                            <div class="product-admin-thumb" style="width: 80px; height: 80px;">
                                <img id="edit_image_preview" src="" alt="Preview">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Product Type & Specialized Details -->
                <div style="border-bottom: 1px solid var(--border-color); padding-bottom: 16px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                        <h4 style="font-size: 13px; text-transform: uppercase; letter-spacing: 1px; color: var(--gold-primary); margin: 0; font-weight: 700;">2. Product Type &amp; Specific Attributes</h4>
                        <span style="font-size: 11px; color: var(--text-muted);">Adapts the page layout to the selected product category</span>
                    </div>

                    <div class="form-group">
                        <label for="edit_product_type" class="form-label" style="font-weight: 600;">Product Type *</label>
                        <select id="edit_product_type" name="product_type" class="form-control" style="border-color: var(--gold-primary); background-color: var(--gold-subtle); font-weight: 600;">
                            <option value="furniture_object">Furniture or Object (Dimensions, Materials, Finishes, Customisation, Production Time, Delivery Area)</option>
                            <option value="digital_asset">Digital Asset (File Formats, Software Compatibility, Preview Images, Permitted Use)</option>
                            <option value="book_planner">Book or Planner (Digital/Printed Format, Sample Pages, Dimensions)</option>
                            <option value="course_workshop">Course or Workshop (Topics, Format, Duration, Access Period, Live Dates / Time Zone)</option>
                            <option value="design_session">Design Session (Scope, Duration, Required Customer Info, Deliverables, Included Revisions)</option>
                        </select>
                    </div>

                    <!-- Type Subgroup: Furniture or Object -->
                    <div id="edit_type_group_furniture_object" class="type-fields-group" style="margin-top: 14px; background: rgba(166,132,73,0.03); border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 14px;">
                        <span style="font-size: 12px; font-weight: 700; color: var(--gold-primary); display: block; margin-bottom: 10px;">Furniture &amp; Objects Specifications</span>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                            <div class="form-group">
                                <label class="form-label">Dimensions</label>
                                <input type="text" id="edit_dimensions" name="dimensions" class="form-control" placeholder="e.g. Diameter: 420mm | Height: 520mm">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Materials</label>
                                <input type="text" id="edit_materials" name="materials" class="form-control" placeholder="e.g. Solid European White Oak, Brushed Spun Brass">
                            </div>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 10px;">
                            <div class="form-group">
                                <label class="form-label">Finishes Available</label>
                                <input type="text" id="edit_finishes" name="finishes" class="form-control" placeholder="e.g. Natural Matte Oak, Smoked Walnut">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Production Time</label>
                                <input type="text" id="edit_production_time" name="production_time" class="form-control" placeholder="e.g. 3–4 weeks handcrafted in our workshop">
                            </div>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 10px;">
                            <div class="form-group">
                                <label class="form-label">Customisation Details</label>
                                <input type="text" id="edit_customisation" name="customisation" class="form-control" placeholder="e.g. Bespoke heights, alternate timber species">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Delivery Area</label>
                                <input type="text" id="edit_delivery_area" name="delivery_area" class="form-control" placeholder="e.g. UK Nationwide White-Glove delivery &amp; Worldwide crate freight">
                            </div>
                        </div>
                    </div>

                    <!-- Type Subgroup: Digital Asset -->
                    <div id="edit_type_group_digital_asset" class="type-fields-group" style="display: none; margin-top: 14px; background: rgba(59,130,246,0.03); border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 14px;">
                        <span style="font-size: 12px; font-weight: 700; color: #2563eb; display: block; margin-bottom: 10px;">Digital Asset Specifications</span>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                            <div class="form-group">
                                <label class="form-label">File Formats</label>
                                <input type="text" id="edit_file_formats" name="file_formats" class="form-control" placeholder="e.g. .BRUSHSET, .DWG, .DXF, .RFA, .PNG">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Software Compatibility</label>
                                <input type="text" id="edit_software_compatibility" name="software_compatibility" class="form-control" placeholder="e.g. Procreate 5.2+, AutoCAD 2020+, Revit 2022+">
                            </div>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 10px;">
                            <div class="form-group">
                                <label class="form-label">Permitted Use / License Scope</label>
                                <input type="text" id="edit_permitted_use" name="permitted_use" class="form-control" placeholder="e.g. Commercial and personal architectural presentations">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Preview Images &amp; Demos</label>
                                <input type="text" id="edit_preview_images" name="preview_images" class="form-control" placeholder="e.g. 12 high-resolution render samples included">
                            </div>
                        </div>
                    </div>

                    <!-- Type Subgroup: Book or Planner -->
                    <div id="edit_type_group_book_planner" class="type-fields-group" style="display: none; margin-top: 14px; background: rgba(16,185,129,0.03); border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 14px;">
                        <span style="font-size: 12px; font-weight: 700; color: #059669; display: block; margin-bottom: 10px;">Book &amp; Planner Specifications</span>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                            <div class="form-group">
                                <label class="form-label">Digital / Printed Format</label>
                                <input type="text" id="edit_book_format" name="book_format" class="form-control" placeholder="e.g. Casebound Hardcover Linen &amp; Interactive PDF">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Sample Pages Preview</label>
                                <input type="text" id="edit_sample_pages" name="sample_pages" class="form-control" placeholder="e.g. 42 sample pages preview available">
                            </div>
                        </div>
                        <div class="form-group" style="margin-top: 10px;">
                            <label class="form-label">Dimensions &amp; Page Details</label>
                            <input type="text" id="edit_book_dimensions" name="book_dimensions" class="form-control" placeholder="e.g. 240 x 170 mm, 210 pages, 140gsm paper">
                        </div>
                    </div>

                    <!-- Type Subgroup: Course or Workshop -->
                    <div id="edit_type_group_course_workshop" class="type-fields-group" style="display: none; margin-top: 14px; background: rgba(168,85,247,0.03); border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 14px;">
                        <span style="font-size: 12px; font-weight: 700; color: #7c3aed; display: block; margin-bottom: 10px;">Course &amp; Workshop Specifications</span>
                        <div class="form-group">
                            <label class="form-label">Topics / Curriculum Highlights</label>
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

                    <!-- Type Subgroup: Design Session -->
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

                <!-- Section 3: Delivery, Inclusions & Shareable Page Details -->
                <div>
                    <h4 style="font-size: 13px; text-transform: uppercase; letter-spacing: 1px; color: var(--gold-primary); margin-bottom: 12px; font-weight: 700;">3. Options, Inclusions &amp; Policies</h4>
                    
                    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 12px;">
                        <div class="form-group">
                            <label for="edit_options_label" class="form-label">Available Option Label</label>
                            <input type="text" id="edit_options_label" name="options_label" class="form-control" placeholder="e.g. Finish, Size, Format, License">
                        </div>
                        <div class="form-group">
                            <label for="edit_options_list" class="form-label">Available Options (Comma-separated)</label>
                            <input type="text" id="edit_options_list" name="options_list" class="form-control" placeholder="e.g. Natural White Oak, Smoked Oiled Walnut, Ebonized Ash">
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
                            <textarea id="edit_gallery_images_text" name="gallery_images_text" class="form-control" rows="2" placeholder="Image URLs or paths separated by commas: img/shop/prod-2.jpg, img/shop/prod-3.jpg" style="font-size: 12px;"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Section 4: Catalog Visibility & Sorting -->
                <div style="background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 14px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 12px;">
                        <div class="form-group">
                            <label for="edit_product_availability" class="form-label">Availability *</label>
                            <select id="edit_product_availability" name="availability" class="form-control" required>
                                <option value="available">Available</option>
                                <option value="made_to_order">Made to order</option>
                                <option value="coming_soon">Coming soon (cannot accept payment)</option>
                                <option value="unavailable">Unavailable (sold out)</option>
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
                            <label for="edit_product_popularity" class="form-label">Popularity</label>
                            <input type="number" id="edit_product_popularity" name="popularity" class="form-control" min="1" max="10">
                        </div>
                    </div>

                    <div style="display: flex; gap: 16px; flex-wrap: wrap; margin-top: 12px;">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 13px; font-weight: 500;">
                            <input type="checkbox" id="edit_product_featured" name="is_featured" value="1" style="width: 16px; height: 16px; accent-color: var(--gold-primary);">
                            ★ Featured in Shop Hero Carousel
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 13px; font-weight: 500;">
                            <input type="checkbox" id="edit_product_price_on_request" name="price_on_request" value="1" style="width: 16px; height: 16px; accent-color: var(--gold-primary);">
                            Price on request (hide numeric price)
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 13px; font-weight: 500;">
                            <input type="checkbox" id="edit_product_active" name="is_active" value="1" style="width: 16px; height: 16px; accent-color: var(--gold-primary);">
                            Display actively in shop catalog
                        </label>
                    </div>
                </div>

            </div>
            
            <div class="modal-footer" style="padding: 14px 24px; border-top: 1px solid var(--border-color); background: var(--bg-card-solid);">
                <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-gold">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Delete Product -->
<div id="deleteProductModal" class="modal-overlay">
    <div class="modal-dialog" style="max-width: 440px;">
        <div class="modal-header">
            <h3 class="modal-title">Delete Shop Product</h3>
            <button type="button" class="modal-close" data-modal-close>&times;</button>
        </div>
        <form action="product_actions.php" method="POST">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="product_id" id="delete_product_id">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            
            <div class="modal-body">
                <p style="color: var(--text-secondary); line-height: 1.6;">
                    Are you sure you want to remove <strong id="delete_product_name" style="color: var(--text-primary);"></strong> from the shop catalog?
                </p>
                <p style="color: var(--danger); font-size: 12px; margin-top: 8px;">
                    This action will permanently delete this item from your database and cannot be undone.
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
    const openModal = (modal) => {
        if (modal) modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    };
    const closeModal = (modal) => {
        if (modal) modal.classList.remove('active');
        document.body.style.overflow = '';
    };

    // Close buttons
    document.querySelectorAll('[data-modal-close]').forEach(btn => {
        btn.addEventListener('click', () => {
            const overlay = btn.closest('.modal-overlay');
            closeModal(overlay);
        });
    });

    // Close on backdrop click
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) closeModal(overlay);
        });
    });

    // Close on ESC
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-overlay.active').forEach(m => closeModal(m));
        }
    });

    // Open Add Modal
    const openAddBtn = document.querySelector('#openAddProductModal');
    const addModal = document.querySelector('#addProductModal');
    const addCatSelect = document.querySelector('#add_product_category');
    const addCustomWrap = document.querySelector('#add_custom_category_wrap');
    const addCustomInput = document.querySelector('#add_custom_category');

    if (addCatSelect && addCustomWrap) {
        addCatSelect.addEventListener('change', () => {
            if (addCatSelect.value === '__custom__') {
                addCustomWrap.style.display = 'block';
                if (addCustomInput) {
                    addCustomInput.required = true;
                    addCustomInput.focus();
                }
            } else {
                addCustomWrap.style.display = 'none';
                if (addCustomInput) {
                    addCustomInput.required = false;
                    addCustomInput.value = '';
                }
            }
        });
    }

    // Product Type Switching Logic
    const typeKeys = ['furniture_object', 'digital_asset', 'book_planner', 'course_workshop', 'design_session'];
    function switchProductType(prefix, type) {
        const selected = type || 'furniture_object';
        typeKeys.forEach(k => {
            const el = document.querySelector(`#${prefix}_type_group_${k}`);
            if (el) {
                el.style.display = (k === selected) ? 'block' : 'none';
            }
        });
    }

    const addTypeSelect = document.querySelector('#add_product_type');
    if (addTypeSelect) {
        addTypeSelect.addEventListener('change', () => {
            switchProductType('add', addTypeSelect.value);
        });
    }

    const editTypeSelect = document.querySelector('#edit_product_type');
    if (editTypeSelect) {
        editTypeSelect.addEventListener('change', () => {
            switchProductType('edit', editTypeSelect.value);
        });
    }

    if (openAddBtn && addModal) {
        openAddBtn.addEventListener('click', () => {
            if (addCatSelect) {
                addCatSelect.value = 'Furniture & Objects';
                if (addCustomWrap) addCustomWrap.style.display = 'none';
                if (addCustomInput) {
                    addCustomInput.required = false;
                    addCustomInput.value = '';
                }
            }
            if (addTypeSelect) {
                addTypeSelect.value = 'furniture_object';
                switchProductType('add', 'furniture_object');
            }
            const addAvail = document.querySelector('#add_product_availability');
            if (addAvail) addAvail.value = 'available';
            const addLabel = document.querySelector('#add_product_label');
            if (addLabel) addLabel.value = '';
            const addFeat = document.querySelector('#add_product_featured');
            if (addFeat) addFeat.checked = false;
            const addPor = document.querySelector('#add_product_price_on_request');
            if (addPor) addPor.checked = false;
            
            // Clear inputs inside addModal
            addModal.querySelectorAll('input[type="text"], textarea').forEach(inp => {
                if (!['csrf_token', 'action'].includes(inp.name)) inp.value = '';
            });
            if (addImgPreview) addImgPreview.src = '';
            if (addImgWrap) addImgWrap.style.display = 'none';

            openModal(addModal);
        });
    }

    // Add Image Live Preview
    const addImgInput = document.querySelector('#add_product_image');
    const addImgWrap = document.querySelector('#add_image_preview_wrap');
    const addImgPreview = document.querySelector('#add_image_preview');
    if (addImgInput && addImgPreview) {
        addImgInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (re) => {
                    addImgPreview.src = re.target.result;
                    if (addImgWrap) addImgWrap.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // Edit Modal Logic
    const editModal = document.querySelector('#editProductModal');
    const editImgInput = document.querySelector('#edit_product_image');
    const editImgPreview = document.querySelector('#edit_image_preview');
    const editCatSelect = document.querySelector('#edit_product_category');
    const editCustomWrap = document.querySelector('#edit_custom_category_wrap');
    const editCustomInput = document.querySelector('#edit_custom_category');

    if (editCatSelect && editCustomWrap) {
        editCatSelect.addEventListener('change', () => {
            if (editCatSelect.value === '__custom__') {
                editCustomWrap.style.display = 'block';
                if (editCustomInput) {
                    editCustomInput.required = true;
                    editCustomInput.focus();
                }
            } else {
                editCustomWrap.style.display = 'none';
                if (editCustomInput) {
                    editCustomInput.required = false;
                    editCustomInput.value = '';
                }
            }
        });
    }

    if (editImgInput && editImgPreview) {
        editImgInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (re) => {
                    editImgPreview.src = re.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    }

    document.querySelectorAll('.edit-product-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const row = btn.closest('.product-row');
            if (!row) return;

            const rawData = row.getAttribute('data-raw');
            if (!rawData) return;

            const prod = JSON.parse(rawData);

            document.querySelector('#edit_product_id').value = prod.id;
            document.querySelector('#edit_product_name').value = prod.name;
            document.querySelector('#edit_product_sku').value = prod.sku || '';
            document.querySelector('#edit_product_price').value = prod.price;
            document.querySelector('#edit_product_sale_price').value = prod.sale_price !== null ? prod.sale_price : '';
            
            // Set category in edit modal
            if (editCatSelect) {
                const curCat = (prod.category || '').trim();
                let matched = false;
                for (let i = 0; i < editCatSelect.options.length; i++) {
                    if (editCatSelect.options[i].value === curCat) {
                        editCatSelect.selectedIndex = i;
                        matched = true;
                        break;
                    }
                }
                if (!matched && curCat) {
                    const opt = new Option(curCat, curCat, true, true);
                    const customOpt = editCatSelect.querySelector('option[value="__custom__"]');
                    if (customOpt) {
                        editCatSelect.insertBefore(opt, customOpt);
                    } else {
                        editCatSelect.appendChild(opt);
                    }
                    editCatSelect.value = curCat;
                }
                if (editCustomWrap) editCustomWrap.style.display = 'none';
                if (editCustomInput) {
                    editCustomInput.required = false;
                    editCustomInput.value = '';
                }
            }

            document.querySelector('#edit_product_desc').value = prod.description || '';
            document.querySelector('#edit_product_order').value = prod.display_order;
            document.querySelector('#edit_product_popularity').value = prod.popularity;
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

            // General metadata
            const setVal = (sel, val) => { const el = document.querySelector(sel); if (el) el.value = val || ''; };
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
            const editCustomPath = document.querySelector('#edit_custom_image_path');
            if (editCustomPath) editCustomPath.value = prod.image || '';

            openModal(editModal);
        });
    });

    // Delete Modal Logic
    const deleteModal = document.querySelector('#deleteProductModal');
    const deleteIdInput = document.querySelector('#delete_product_id');
    const deleteNameSpan = document.querySelector('#delete_product_name');

    document.querySelectorAll('.delete-product-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const row = btn.closest('.product-row');
            if (!row) return;

            const rawData = row.getAttribute('data-raw');
            if (!rawData) return;

            const prod = JSON.parse(rawData);

            if (deleteIdInput) deleteIdInput.value = prod.id;
            if (deleteNameSpan) deleteNameSpan.textContent = prod.name;

            openModal(deleteModal);
        });
    });

    // Live Search & Category / Status Filtering
    const searchInput = document.querySelector('#productSearchInput');
    const catFilter = document.querySelector('#categoryFilter');
    const statFilter = document.querySelector('#statusFilter');
    const countDisplay = document.querySelector('#productCount');
    const rows = document.querySelectorAll('.product-row');
    const noResultsRow = document.querySelector('#noResultsRow');

    function filterTable() {
        const query = (searchInput ? searchInput.value : '').toLowerCase().trim();
        const selectedCat = (catFilter ? catFilter.value : '').toLowerCase().trim();
        const selectedStatus = statFilter ? statFilter.value : '';

        let visibleCount = 0;

        rows.forEach(row => {
            const name = row.getAttribute('data-name') || '';
            const sku = row.getAttribute('data-sku') || '';
            const cat = (row.getAttribute('data-category') || '').toLowerCase();
            const active = row.getAttribute('data-active') || '1';

            const matchesSearch = !query || name.includes(query) || sku.includes(query) || cat.includes(query);
            const matchesCat = !selectedCat || cat === selectedCat;
            const matchesStatus = selectedStatus === '' || active === selectedStatus;

            if (matchesSearch && matchesCat && matchesStatus) {
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
});
</script>

</body>
</html>
