<?php
/**
 * URTH Admin Panel - Customer Orders Management
 */

require_once __DIR__ . '/auth.php';
require_admin_auth();
require_once '../config.php';

// Auto-check tables
$tblCheck = $conn->query("SHOW TABLES LIKE 'orders'");
if (!$tblCheck || $tblCheck->num_rows === 0) {
    require_once __DIR__ . '/../setup.php';
}

// Fetch all orders
$result = $conn->query("SELECT * FROM orders ORDER BY id DESC");
$orders = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
}

// Fetch order items grouped by order_id
$itemsResult = $conn->query("SELECT * FROM order_items ORDER BY id ASC");
$orderItems = [];
if ($itemsResult) {
    while ($it = $itemsResult->fetch_assoc()) {
        $orderItems[$it['order_id']][] = $it;
    }
}

foreach ($orders as &$ord) {
    $ord['items'] = $orderItems[$ord['id']] ?? [];
}
unset($ord);

// Stats
$totalOrders = count($orders);
$pendingOrders = count(array_filter($orders, function($o) { return $o['status'] === 'Pending'; }));
$completedOrders = count(array_filter($orders, function($o) { return $o['status'] === 'Completed'; }));
$totalRevenue = array_sum(array_column($orders, 'total'));

$csrf_token = generate_csrf_token();

// Alerts
$msg = $_GET['msg'] ?? '';
$alertMessage = '';
$alertType = 'success';

switch ($msg) {
    case 'status_updated':
        $alertMessage = 'Order status updated successfully.';
        break;
    case 'deleted':
        $alertMessage = 'Order record removed from database.';
        $alertType = 'danger';
        break;
    case 'error_invalid':
        $alertMessage = 'Invalid order record specified.';
        $alertType = 'danger';
        break;
    case 'error_csrf':
        $alertMessage = 'Security token invalid or expired. Please try again.';
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
    <title>Customer Orders — URTH Admin Panel</title>
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
            <a href="products.php" class="admin-nav-link">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                Shop Products
            </a>
            <a href="orders.php" class="admin-nav-link active">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                Orders <?php if ($pendingOrders > 0): ?><span style="background: var(--gold-primary); color: #fff; font-size: 10px; padding: 1px 6px; border-radius: 10px; margin-left: 4px;"><?= $pendingOrders ?></span><?php endif; ?>
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

            <a href="../cart.php" target="_blank" class="btn btn-outline btn-sm" title="Preview public cart">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                View Cart Page
            </a>

            <a href="logout.php" class="btn btn-danger btn-sm" title="Log out">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                Logout
            </a>
        </div>
    </header>

    <!-- Main Container -->
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
                <h1>Customer Orders &amp; Inquiries</h1>
                <p>View, review, and manage customer furniture orders placed from the shop cart</p>
            </div>
        </div>

        <!-- Summary Statistics Widgets -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-info">
                    <div class="stat-label">Total Orders</div>
                    <div class="stat-value"><?= $totalOrders ?></div>
                </div>
                <div class="stat-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-info">
                    <div class="stat-label">Pending Review</div>
                    <div class="stat-value" style="color: var(--gold-primary);"><?= $pendingOrders ?></div>
                </div>
                <div class="stat-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-info">
                    <div class="stat-label">Completed Orders</div>
                    <div class="stat-value" style="color: var(--success);"><?= $completedOrders ?></div>
                </div>
                <div class="stat-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-info">
                    <div class="stat-label">Total Order Value</div>
                    <div class="stat-value">£<?= number_format($totalRevenue, 2) ?></div>
                </div>
                <div class="stat-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"/><path d="M12 18V6"/></svg>
                </div>
            </div>
        </div>

        <!-- Toolbar & Filter Card -->
        <div class="toolbar-card">
            <div class="search-filter-group" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center; flex: 1;">
                <div class="search-box" style="flex: 1; min-width: 250px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="text" id="orderSearchInput" class="form-control" placeholder="Search orders by number, customer name, phone, or email...">
                </div>

                <div style="min-width: 160px;">
                    <select id="statusFilter" class="form-control" style="cursor: pointer;">
                        <option value="">All Statuses</option>
                        <option value="Pending">Pending</option>
                        <option value="Processing">Processing</option>
                        <option value="Completed">Completed</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                </div>
            </div>

            <div style="font-size: 13px; color: var(--text-secondary); white-space: nowrap;">
                Showing <strong id="orderCount" style="color: var(--text-primary);"><?= $totalOrders ?></strong> orders
            </div>
        </div>

        <!-- Table Card Section -->
        <div class="table-card">
            <div class="table-responsive">
                <table class="custom-table" id="ordersTable">
                    <thead>
                        <tr>
                            <th style="width: 140px;">Order #</th>
                            <th style="width: 150px;">Date &amp; Time</th>
                            <th>Customer</th>
                            <th>Contact</th>
                            <th style="width: 90px;">Items</th>
                            <th>Total</th>
                            <th style="width: 120px;">Status</th>
                            <th style="width: 150px; text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                            <tr id="emptyRow">
                                <td colspan="8">
                                    <div class="empty-state">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                        <h3>No Orders Received Yet</h3>
                                        <p>When customers submit their shopping cart from the shop or cart page, orders will appear here automatically.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($orders as $ord): ?>
                                <?php
                                    $statusClass = 'inactive';
                                    if ($ord['status'] === 'Completed') $statusClass = 'active';
                                    elseif ($ord['status'] === 'Processing') $statusClass = 'info';
                                    elseif ($ord['status'] === 'Pending') $statusClass = 'pending';
                                ?>
                                <tr class="order-row"
                                    data-id="<?= $ord['id'] ?>"
                                    data-number="<?= htmlspecialchars(strtolower($ord['order_number'])) ?>"
                                    data-customer="<?= htmlspecialchars(strtolower($ord['customer_name'])) ?>"
                                    data-phone="<?= htmlspecialchars(strtolower($ord['customer_phone'])) ?>"
                                    data-email="<?= htmlspecialchars(strtolower($ord['customer_email'] ?? '')) ?>"
                                    data-status="<?= htmlspecialchars($ord['status']) ?>"
                                    data-raw='<?= htmlspecialchars(json_encode($ord), ENT_QUOTES, 'UTF-8') ?>'>
                                    <td>
                                        <strong style="color: var(--text-primary); font-family: monospace; font-size: 13px;"><?= htmlspecialchars($ord['order_number']) ?></strong>
                                    </td>
                                    <td>
                                        <span style="font-size: 12px; color: var(--text-secondary);"><?= date('M j, Y - H:i', strtotime($ord['created_at'])) ?></span>
                                    </td>
                                    <td>
                                        <strong style="color: var(--text-primary); font-size: 14px;"><?= htmlspecialchars($ord['customer_name']) ?></strong>
                                    </td>
                                    <td>
                                        <div style="font-size: 12px;">
                                            <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $ord['customer_phone']) ?>" target="_blank" style="color: var(--gold-primary); text-decoration: none; font-weight: 500;">
                                                <?= htmlspecialchars($ord['customer_phone']) ?>
                                            </a>
                                            <?php if (!empty($ord['customer_email'])): ?>
                                                <div style="color: var(--text-muted); font-size: 11px;"><?= htmlspecialchars($ord['customer_email']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="category-pill"><?= count($ord['items']) ?> pcs</span>
                                    </td>
                                    <td>
                                        <strong style="color: var(--text-primary); font-size: 14px;">£<?= number_format((float)$ord['total'], 2) ?></strong>
                                    </td>
                                    <td>
                                        <span class="status-badge <?= $statusClass ?>">
                                            <?= htmlspecialchars($ord['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="table-actions" style="justify-content: flex-end; gap: 6px;">
                                            <button type="button" class="btn btn-outline btn-sm view-order-btn" title="View order details">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                                Details
                                            </button>
                                            <button type="button" class="btn btn-danger btn-sm delete-order-btn" title="Delete order">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                            <tr id="noResultsRow" style="display: none;">
                                <td colspan="8">
                                    <div class="empty-state">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                                        <h3>No matching orders</h3>
                                        <p>Try searching for a different keyword or customer name.</p>
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

<!-- Modal: View Order Details -->
<div id="viewOrderModal" class="modal-overlay">
    <div class="modal-dialog" style="max-width: 680px;">
        <div class="modal-header">
            <div>
                <h3 class="modal-title" id="detailOrderNumber">Order Details</h3>
                <span id="detailOrderDate" style="font-size: 12px; color: var(--text-muted);"></span>
            </div>
            <button type="button" class="modal-close" data-modal-close>&times;</button>
        </div>
        
        <div class="modal-body" style="display: flex; flex-direction: column; gap: 16px;">
            <!-- Customer Summary Card -->
            <div style="background: var(--bg-canvas); padding: 16px; border-radius: var(--radius-sm); border: 1px solid var(--border-color); display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div>
                    <span style="font-size: 11px; text-transform: uppercase; color: var(--text-muted); font-weight: 600; display: block;">Customer</span>
                    <strong id="detailCustomerName" style="font-size: 14px; color: var(--text-primary);"></strong>
                    <div id="detailCustomerPhone" style="font-size: 12px; margin-top: 2px;"></div>
                    <div id="detailCustomerEmail" style="font-size: 12px; color: var(--text-muted);"></div>
                </div>
                <div>
                    <span style="font-size: 11px; text-transform: uppercase; color: var(--text-muted); font-weight: 600; display: block;">Delivery Address</span>
                    <div id="detailDeliveryAddress" style="font-size: 13px; color: var(--text-secondary); line-height: 1.4;">None provided</div>
                    <div id="detailShippingMethod" style="font-size: 11px; color: var(--gold-primary); font-weight: 600; margin-top: 4px;"></div>
                </div>
            </div>

            <div id="detailNotesWrap" style="display: none; background: #fffbeb; padding: 12px 14px; border-radius: var(--radius-sm); border: 1px solid #fde68a; font-size: 13px;">
                <strong style="color: #92400e; display: block; margin-bottom: 2px;">Customer Notes:</strong>
                <span id="detailOrderNotes" style="color: #78350f;"></span>
            </div>

            <!-- Ordered Items List -->
            <div>
                <h4 style="font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px; color: var(--text-primary);">Ordered Pieces</h4>
                <div id="detailItemsContainer" style="display: flex; flex-direction: column; gap: 8px;">
                    <!-- Populated dynamically -->
                </div>
            </div>

            <!-- Totals Breakout -->
            <div style="border-top: 1px solid var(--border-color); padding-top: 12px; display: flex; flex-direction: column; gap: 4px; align-items: flex-end;">
                <div style="font-size: 13px; color: var(--text-secondary);">Subtotal: <strong id="detailSubtotal">£0.00</strong></div>
                <div style="font-size: 13px; color: var(--text-secondary);">Shipping: <strong id="detailShippingCost">£0.00</strong></div>
                <div style="font-size: 17px; font-weight: 800; color: var(--text-primary); margin-top: 4px;">Grand Total: <span id="detailGrandTotal" style="color: var(--gold-primary);">£0.00</span></div>
            </div>

            <!-- Status Changer Form -->
            <form action="order_actions.php" method="POST" style="border-top: 1px solid var(--border-color); padding-top: 14px; display: flex; align-items: center; justify-content: space-between; gap: 12px;">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="order_id" id="statusOrderId">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

                <div style="display: flex; align-items: center; gap: 8px;">
                    <label for="orderStatusSelect" style="font-size: 12px; font-weight: 600; text-transform: uppercase; color: var(--text-secondary);">Update Status:</label>
                    <select name="status" id="orderStatusSelect" class="form-control" style="width: 140px; font-size: 13px;">
                        <option value="Pending">Pending</option>
                        <option value="Processing">Processing</option>
                        <option value="Completed">Completed</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                    <button type="submit" class="btn btn-gold btn-sm">Update</button>
                </div>

                <a href="#" target="_blank" id="modalWhatsAppContactBtn" class="btn btn-outline btn-sm" style="display: inline-flex; align-items: center; gap: 6px;">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="#25d366"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                    WhatsApp Customer
                </a>
            </form>
        </div>
        
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" data-modal-close>Close</button>
        </div>
    </div>
</div>

<!-- Modal: Delete Order -->
<div id="deleteOrderModal" class="modal-overlay">
    <div class="modal-dialog" style="max-width: 440px;">
        <div class="modal-header">
            <h3 class="modal-title">Delete Order Record</h3>
            <button type="button" class="modal-close" data-modal-close>&times;</button>
        </div>
        <form action="order_actions.php" method="POST">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="order_id" id="delete_order_id">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            
            <div class="modal-body">
                <p style="color: var(--text-secondary); line-height: 1.6;">
                    Are you sure you want to remove order <strong id="delete_order_num" style="color: var(--text-primary);"></strong>?
                </p>
                <p style="color: var(--danger); font-size: 12px; margin-top: 8px;">
                    This will delete the customer order and item records permanently.
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

    // View Details Logic
    const viewModal = document.querySelector('#viewOrderModal');
    document.querySelectorAll('.view-order-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const row = btn.closest('.order-row');
            if (!row) return;

            const ord = JSON.parse(row.getAttribute('data-raw'));

            document.querySelector('#detailOrderNumber').textContent = 'Order #' + ord.order_number;
            document.querySelector('#detailOrderDate').textContent = 'Placed on ' + ord.created_at;
            document.querySelector('#detailCustomerName').textContent = ord.customer_name;
            document.querySelector('#detailCustomerPhone').textContent = ord.customer_phone;
            document.querySelector('#detailCustomerEmail').textContent = ord.customer_email || 'No email provided';
            document.querySelector('#detailDeliveryAddress').textContent = ord.delivery_address || 'No address provided';
            document.querySelector('#detailShippingMethod').textContent = ord.shipping_method || 'Standard';

            const notesWrap = document.querySelector('#detailNotesWrap');
            if (ord.order_notes) {
                notesWrap.style.display = 'block';
                document.querySelector('#detailOrderNotes').textContent = ord.order_notes;
            } else {
                notesWrap.style.display = 'none';
            }

            // Items List
            const itemsContainer = document.querySelector('#detailItemsContainer');
            itemsContainer.innerHTML = (ord.items || []).map(it => {
                const img = it.image ? (!/^https?:\/\//i.test(it.image) ? '../' + it.image.replace(/^\/+/, '') : it.image) : '../img/shop/prod-1.jpg';
                return `
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 8px 12px; background: var(--bg-canvas); border-radius: 4px; border: 1px solid var(--border-color);">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <img src="${img}" style="width: 36px; height: 36px; object-fit: contain; background: #fff; border-radius: 2px;" alt="${it.product_name}">
                            <div>
                                <strong style="font-size: 13px; color: var(--text-primary); display: block;">${it.product_name}</strong>
                                <span style="font-size: 11px; color: var(--text-muted);">${it.product_sku || ''}</span>
                            </div>
                        </div>
                        <div style="text-align: right; font-size: 13px;">
                            <span style="color: var(--text-secondary);">${it.quantity} &times; £${parseFloat(it.price).toFixed(2)}</span>
                            <strong style="color: var(--text-primary); margin-left: 10px;">£${parseFloat(it.total).toFixed(2)}</strong>
                        </div>
                    </div>
                `;
            }).join('');

            document.querySelector('#detailSubtotal').textContent = '£' + parseFloat(ord.subtotal).toFixed(2);
            document.querySelector('#detailShippingCost').textContent = '£' + parseFloat(ord.shipping_cost || 0).toFixed(2);
            document.querySelector('#detailGrandTotal').textContent = '£' + parseFloat(ord.total).toFixed(2);

            document.querySelector('#statusOrderId').value = ord.id;
            document.querySelector('#orderStatusSelect').value = ord.status;

            const waBtn = document.querySelector('#modalWhatsAppContactBtn');
            const cleanPhone = (ord.customer_phone || '').replace(/[^0-9]/g, '');
            const waMsg = encodeURIComponent(`Hello ${ord.customer_name}, this is URTH Architecture Studio regarding your order #${ord.order_number}.`);
            waBtn.href = `https://wa.me/${cleanPhone}?text=${waMsg}`;

            openModal(viewModal);
        });
    });

    // Delete Modal Logic
    const deleteModal = document.querySelector('#deleteOrderModal');
    document.querySelectorAll('.delete-order-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const row = btn.closest('.order-row');
            if (!row) return;
            const ord = JSON.parse(row.getAttribute('data-raw'));

            document.querySelector('#delete_order_id').value = ord.id;
            document.querySelector('#delete_order_num').textContent = '#' + ord.order_number;

            openModal(deleteModal);
        });
    });

    // Search and Status Filter
    const searchInput = document.querySelector('#orderSearchInput');
    const statusFilter = document.querySelector('#statusFilter');
    const countDisplay = document.querySelector('#orderCount');
    const rows = document.querySelectorAll('.order-row');
    const noResultsRow = document.querySelector('#noResultsRow');

    function filterOrders() {
        const query = (searchInput ? searchInput.value : '').toLowerCase().trim();
        const stat = statusFilter ? statusFilter.value : '';

        let visible = 0;
        rows.forEach(r => {
            const num = r.getAttribute('data-number') || '';
            const cust = r.getAttribute('data-customer') || '';
            const ph = r.getAttribute('data-phone') || '';
            const em = r.getAttribute('data-email') || '';
            const st = r.getAttribute('data-status') || '';

            const matchesSearch = !query || num.includes(query) || cust.includes(query) || ph.includes(query) || em.includes(query);
            const matchesStatus = !stat || st === stat;

            if (matchesSearch && matchesStatus) {
                r.style.display = '';
                visible++;
            } else {
                r.style.display = 'none';
            }
        });

        if (countDisplay) countDisplay.textContent = visible;
        if (noResultsRow) noResultsRow.style.display = visible === 0 && rows.length > 0 ? '' : 'none';
    }

    if (searchInput) searchInput.addEventListener('input', filterOrders);
    if (statusFilter) statusFilter.addEventListener('change', filterOrders);
});
</script>

</body>
</html>
