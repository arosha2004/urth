<?php
/**
 * URTH Admin Panel - Orders, Custom Enquiries, Quotes, Payments & Fulfilment Management
 * Comprehensive operational dashboard for routine shop and bespoke client order management
 */

require_once __DIR__ . '/auth.php';
require_admin_auth();
require_once '../config.php';

// Auto-check tables
$tblCheck = $conn->query("SHOW TABLES LIKE 'orders'");
if (!$tblCheck || $tblCheck->num_rows === 0) {
    require_once __DIR__ . '/../setup.php';
}

// Fetch all orders and enquiries
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

// Fetch audit activity logs grouped by order_id
$logsResult = $conn->query("SELECT * FROM order_activity_logs ORDER BY id DESC");
$orderLogs = [];
if ($logsResult) {
    while ($lg = $logsResult->fetch_assoc()) {
        $orderLogs[$lg['order_id']][] = $lg;
    }
}

foreach ($orders as &$ord) {
    $oid = (int)$ord['id'];
    $ord['items'] = $orderItems[$oid] ?? [];
    $ord['activity_logs'] = $orderLogs[$oid] ?? [];
}
unset($ord);

// Statistics & Metrics
$totalOrders = count($orders);
$totalEnquiries = count(array_filter($orders, function($o) { return ($o['order_type'] ?? 'order') === 'custom_enquiry'; }));
$pendingPayments = count(array_filter($orders, function($o) { return in_array($o['payment_status'] ?? '', ['pending_payment', 'balance_pending']); }));
$inProductionCount = count(array_filter($orders, function($o) { return ($o['fulfilment_status'] ?? '') === 'in_production'; }));
$pendingRefundsCount = count(array_filter($orders, function($o) { return ($o['refund_status'] ?? '') === 'pending_action' || ($o['payment_status'] ?? '') === 'refund_pending'; }));
$totalRevenue = array_sum(array_column($orders, 'amount_paid'));
$totalOutstanding = array_sum(array_column($orders, 'balance_due'));

$csrf_token = generate_csrf_token();

// Alerts
$msg = $_GET['msg'] ?? '';
$alertMessage = '';
$alertType = 'success';

switch ($msg) {
    case 'payment_recorded':
        $alertMessage = 'Payment / deposit recorded successfully. Outstanding balance updated.';
        break;
    case 'payment_status_updated':
        $alertMessage = 'Payment status updated successfully.';
        break;
    case 'fulfilment_updated':
        $alertMessage = 'Fulfilment status and progress milestone updated.';
        break;
    case 'quote_saved':
        $alertMessage = 'Quote and scope details prepared and saved successfully.';
        break;
    case 'quote_accepted':
        $alertMessage = 'Customer quote acceptance recorded. Order moved to confirmed queue.';
        break;
    case 'dispatch_updated':
        $alertMessage = 'Courier and tracking consignment details updated successfully.';
        break;
    case 'booking_updated':
        $alertMessage = 'Session booking schedule and meeting room details updated.';
        break;
    case 'deliverable_updated':
        $alertMessage = 'Digital deliverables and protected access links assigned.';
        break;
    case 'note_added':
        $alertMessage = 'Internal staff note added to order file.';
        break;
    case 'update_logged':
        $alertMessage = 'Customer communication log entry saved.';
        break;
    case 'cancelled':
        $alertMessage = 'Order cancellation recorded. Any pending refund actions queued for review.';
        $alertType = 'danger';
        break;
    case 'refund_processed':
        $alertMessage = 'Refund action confirmed and recorded successfully.';
        break;
    case 'order_created':
        $alertMessage = 'New manual order / custom enquiry created successfully.';
        break;
    case 'deleted':
        $alertMessage = 'Order record removed from database.';
        $alertType = 'danger';
        break;
    case 'error_missing':
        $alertMessage = 'Please fill in all required fields.';
        $alertType = 'danger';
        break;
    case 'error_invalid':
        $alertMessage = 'Invalid order or enquiry record specified.';
        $alertType = 'danger';
        break;
    case 'error_invalid_amount':
        $alertMessage = 'Invalid payment or refund amount specified.';
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
    <title>Customer Orders &amp; Custom Enquiries — URTH Admin Panel</title>
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
                Orders &amp; Enquiries <?php if ($pendingRefundsCount > 0): ?><span style="background: var(--danger); color: #fff; font-size: 10px; padding: 1px 6px; border-radius: 10px; margin-left: 4px;" title="<?= $pendingRefundsCount ?> refund actions pending"><?= $pendingRefundsCount ?></span><?php endif; ?>
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

            <a href="../cart.php" target="_blank" class="btn btn-outline btn-sm" title="Preview public checkout">
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

        <!-- Pending Refund Action Alert -->
        <?php if ($pendingRefundsCount > 0): ?>
            <div style="background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3); border-radius: var(--radius-sm); padding: 12px 16px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; gap: 12px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span style="display: inline-flex; width: 28px; height: 28px; border-radius: 50%; background: #ef4444; color: #fff; align-items: center; justify-content: center; font-weight: 800; font-size: 13px;">!</span>
                    <div>
                        <strong style="color: #b91c1c; font-size: 13px;">Attention Required: <?= $pendingRefundsCount ?> Pending Refund Action<?= $pendingRefundsCount > 1 ? 's' : '' ?></strong>
                        <div style="font-size: 12px; color: #7f1d1d;">Customer cancellations requiring refund verification in payment gateway. Filter by "Refund Action Pending" below to process.</div>
                    </div>
                </div>
                <button type="button" class="btn btn-danger btn-sm" onclick="filterByPendingRefund()">View Refund Queue</button>
            </div>
        <?php endif; ?>

        <!-- Page Header -->
        <div class="page-header">
            <div class="page-title-wrap">
                <h1>Customer Orders &amp; Custom Enquiries</h1>
                <p>Manage shop checkout orders, bespoke quotes, payments &amp; deposits, fulfilment, dispatch tracking, bookings, and refunds</p>
            </div>

            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <a href="export_orders.php" id="exportOrdersBtn" class="btn btn-outline" title="Export orders to CSV spreadsheet">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    Export Order List (CSV)
                </a>
                <button type="button" id="openManualOrderBtn" class="btn btn-gold">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    + New Order / Enquiry
                </button>
            </div>
        </div>

        <!-- Summary Statistics Widgets -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-info">
                    <div class="stat-label">Total Orders &amp; Enquiries</div>
                    <div class="stat-value"><?= $totalOrders ?> <span style="font-size: 11px; font-weight: 500; color: var(--text-muted);">(<?= $totalEnquiries ?> bespoke)</span></div>
                </div>
                <div class="stat-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-info">
                    <div class="stat-label">In Production / Crafting</div>
                    <div class="stat-value" style="color: var(--gold-primary);"><?= $inProductionCount ?></div>
                </div>
                <div class="stat-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-info">
                    <div class="stat-label">Payments to Collect</div>
                    <div class="stat-value" style="color: #f59e0b;">£<?= number_format($totalOutstanding, 2) ?> <span style="font-size: 11px; font-weight: 500; color: var(--text-muted);">(<?= $pendingPayments ?> orders)</span></div>
                </div>
                <div class="stat-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-info">
                    <div class="stat-label">Total Revenue Collected</div>
                    <div class="stat-value" style="color: var(--success);">£<?= number_format($totalRevenue, 2) ?></div>
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
                    <input type="text" id="orderSearchInput" class="form-control" placeholder="Search by order #, customer, email, phone, tracking code...">
                </div>

                <div style="min-width: 140px;">
                    <select id="typeFilter" class="form-control" style="cursor: pointer;">
                        <option value="">All Types</option>
                        <option value="order">Shop Cart Orders</option>
                        <option value="custom_enquiry">Custom Enquiries</option>
                        <option value="quote_request">Quote Requests</option>
                    </select>
                </div>

                <div style="min-width: 160px;">
                    <select id="paymentFilter" class="form-control" style="cursor: pointer;">
                        <option value="">All Payment Statuses</option>
                        <option value="pending_payment">Pending Payment</option>
                        <option value="deposit_paid">Deposit Paid</option>
                        <option value="balance_pending">Balance Pending</option>
                        <option value="fully_paid">Fully Paid</option>
                        <option value="refund_pending">Refund Action Pending</option>
                        <option value="partially_refunded">Partially Refunded</option>
                        <option value="refunded">Refunded</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>

                <div style="min-width: 170px;">
                    <select id="fulfilmentFilter" class="form-control" style="cursor: pointer;">
                        <option value="">All Fulfilment Statuses</option>
                        <option value="enquiry">New Enquiry</option>
                        <option value="quote_prepared">Quote Prepared</option>
                        <option value="quote_sent">Quote Sent to Client</option>
                        <option value="quote_accepted">Client Accepted Quote</option>
                        <option value="in_queue">In Queue / Confirmed</option>
                        <option value="in_production">In Production / Crafting</option>
                        <option value="booking_scheduled">Booking Scheduled</option>
                        <option value="digital_ready">Digital Deliverables Ready</option>
                        <option value="dispatched">Dispatched / In Transit</option>
                        <option value="completed">Delivered / Fulfilled</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
            </div>

            <div style="font-size: 13px; color: var(--text-secondary); white-space: nowrap;">
                Showing <strong id="orderCount" style="color: var(--text-primary);"><?= $totalOrders ?></strong> records
            </div>
        </div>

        <!-- Table Card Section -->
        <div class="table-card">
            <div class="table-responsive">
                <table class="custom-table" id="ordersTable">
                    <thead>
                        <tr>
                            <th style="width: 140px;">Order / Enquiry #</th>
                            <th style="width: 120px;">Date</th>
                            <th>Customer &amp; Contact</th>
                            <th>Items &amp; Scope</th>
                            <th style="width: 150px;">Financials</th>
                            <th style="width: 130px;">Payment Status</th>
                            <th style="width: 150px;">Fulfilment Status</th>
                            <th style="width: 90px;">Staff</th>
                            <th style="width: 130px; text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                            <tr id="emptyRow">
                                <td colspan="9">
                                    <div class="empty-state">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                        <h3>No Orders or Enquiries Found</h3>
                                        <p>Click "+ New Order / Enquiry" or receive client checkouts from the shop page.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($orders as $ord): ?>
                                <?php
                                    $pStatus = $ord['payment_status'] ?? 'pending_payment';
                                    $fStatus = $ord['fulfilment_status'] ?? 'in_queue';
                                    $oType = $ord['order_type'] ?? 'order';
                                    $tot = (float)$ord['total'];
                                    $paid = (float)$ord['amount_paid'];
                                    $due = (float)$ord['balance_due'];

                                    // Payment Badge CSS
                                    $pClass = 'pay-badge-pending';
                                    $pLabel = 'Pending Payment';
                                    if ($pStatus === 'fully_paid') {
                                        $pClass = 'pay-badge-paid';
                                        $pLabel = 'Fully Paid';
                                    } elseif ($pStatus === 'deposit_paid') {
                                        $pClass = 'pay-badge-deposit';
                                        $pLabel = 'Deposit Paid';
                                    } elseif ($pStatus === 'balance_pending') {
                                        $pClass = 'pay-badge-balance';
                                        $pLabel = 'Balance Pending';
                                    } elseif ($pStatus === 'refund_pending') {
                                        $pClass = 'pay-badge-refund-pending';
                                        $pLabel = 'Refund Pending';
                                    } elseif ($pStatus === 'refunded' || $pStatus === 'partially_refunded') {
                                        $pClass = 'pay-badge-refunded';
                                        $pLabel = ucwords(str_replace('_', ' ', $pStatus));
                                    } elseif ($pStatus === 'cancelled') {
                                        $pClass = 'pay-badge-cancelled';
                                        $pLabel = 'Cancelled / Void';
                                    }

                                    // Fulfilment Badge CSS
                                    $fClass = 'ful-badge-queue';
                                    $fLabel = ucwords(str_replace('_', ' ', $fStatus));
                                    if ($fStatus === 'in_production') {
                                        $fClass = 'ful-badge-production';
                                        $fLabel = 'In Production';
                                    } elseif ($fStatus === 'booking_scheduled') {
                                        $fClass = 'ful-badge-booking';
                                        $fLabel = 'Booking Scheduled';
                                    } elseif ($fStatus === 'digital_ready') {
                                        $fClass = 'ful-badge-digital';
                                        $fLabel = 'Deliverable Ready';
                                    } elseif ($fStatus === 'dispatched') {
                                        $fClass = 'ful-badge-dispatched';
                                        $fLabel = 'Dispatched (' . ($ord['courier_name'] ?: 'In Transit') . ')';
                                    } elseif ($fStatus === 'completed') {
                                        $fClass = 'ful-badge-completed';
                                        $fLabel = 'Completed';
                                    } elseif ($fStatus === 'enquiry') {
                                        $fClass = 'ful-badge-enquiry';
                                        $fLabel = 'Custom Enquiry';
                                    } elseif ($fStatus === 'quote_prepared' || $fStatus === 'quote_sent' || $fStatus === 'quote_accepted') {
                                        $fClass = 'ful-badge-quote';
                                        $fLabel = ucwords(str_replace('_', ' ', $fStatus));
                                    } elseif ($fStatus === 'cancelled') {
                                        $fClass = 'ful-badge-cancelled';
                                        $fLabel = 'Cancelled';
                                    }

                                    $cleanPhone = preg_replace('/[^0-9]/', '', $ord['customer_phone']);
                                ?>
                                <tr class="order-row"
                                    data-id="<?= $ord['id'] ?>"
                                    data-number="<?= htmlspecialchars(strtolower($ord['order_number'])) ?>"
                                    data-type="<?= htmlspecialchars($oType) ?>"
                                    data-customer="<?= htmlspecialchars(strtolower($ord['customer_name'])) ?>"
                                    data-phone="<?= htmlspecialchars(strtolower($ord['customer_phone'])) ?>"
                                    data-email="<?= htmlspecialchars(strtolower($ord['customer_email'] ?? '')) ?>"
                                    data-tracking="<?= htmlspecialchars(strtolower($ord['tracking_number'] ?? '')) ?>"
                                    data-payment-status="<?= htmlspecialchars($pStatus) ?>"
                                    data-fulfilment-status="<?= htmlspecialchars($fStatus) ?>"
                                    data-raw='<?= htmlspecialchars(json_encode($ord), ENT_QUOTES, 'UTF-8') ?>'>
                                    <td>
                                        <strong style="color: var(--text-primary); font-family: monospace; font-size: 13px;"><?= htmlspecialchars($ord['order_number']) ?></strong>
                                        <div>
                                            <span style="font-size: 10px; font-weight: 700; text-transform: uppercase; color: <?= $oType === 'custom_enquiry' ? '#e11d48' : 'var(--gold-primary)' ?>;">
                                                <?= $oType === 'custom_enquiry' ? '★ Custom Enquiry' : ($oType === 'quote_request' ? '⚡ Quote Request' : '● Shop Order') ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <span style="font-size: 12px; color: var(--text-secondary);"><?= date('M j, Y', strtotime($ord['created_at'])) ?></span>
                                        <div style="font-size: 11px; color: var(--text-muted);"><?= date('H:i', strtotime($ord['created_at'])) ?></div>
                                    </td>
                                    <td>
                                        <strong style="color: var(--text-primary); font-size: 14px;"><?= htmlspecialchars($ord['customer_name']) ?></strong>
                                        <div style="font-size: 11px; margin-top: 2px;">
                                            <a href="https://wa.me/<?= $cleanPhone ?>" target="_blank" style="color: #25d366; text-decoration: none; font-weight: 600;" title="WhatsApp customer">
                                                <?= htmlspecialchars($ord['customer_phone']) ?>
                                            </a>
                                            <?php if (!empty($ord['customer_email'])): ?>
                                                <div style="color: var(--text-muted); font-size: 11px;"><?= htmlspecialchars($ord['customer_email']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if (!empty($ord['items'])): ?>
                                            <div style="font-size: 12px; color: var(--text-primary); font-weight: 500;">
                                                <?= htmlspecialchars($ord['items'][0]['product_name']) ?>
                                                <?php if (count($ord['items']) > 1): ?>
                                                    <span style="color: var(--text-muted); font-size: 11px;">+ <?= count($ord['items']) - 1 ?> more</span>
                                                <?php endif; ?>
                                            </div>
                                        <?php elseif (!empty($ord['order_notes'])): ?>
                                            <div style="font-size: 12px; color: var(--text-secondary);"><?= htmlspecialchars(substr($ord['order_notes'], 0, 45)) ?>...</div>
                                        <?php else: ?>
                                            <span style="font-size: 12px; color: var(--text-muted);">Custom Scope</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="font-size: 13px; font-weight: 700; color: var(--text-primary);">Total: £<?= number_format($tot, 2) ?></div>
                                        <div style="font-size: 11px; color: #059669;">Paid: £<?= number_format($paid, 2) ?></div>
                                        <?php if ($due > 0.01): ?>
                                            <div style="font-size: 11px; color: #d97706; font-weight: 600;">Due: £<?= number_format($due, 2) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="pay-badge <?= $pClass ?>">
                                            <?= htmlspecialchars($pLabel) ?>
                                        </span>
                                        <?php if (!empty($ord['payment_confirmed_by'])): ?>
                                            <div style="font-size: 10px; color: var(--text-muted); margin-top: 3px;">by <?= htmlspecialchars($ord['payment_confirmed_by']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="ful-badge <?= $fClass ?>">
                                            <?= htmlspecialchars($fLabel) ?>
                                        </span>
                                        <?php if ((int)$ord['fulfilment_progress'] > 0 && (int)$ord['fulfilment_progress'] < 100): ?>
                                            <div style="font-size: 10px; color: var(--text-muted); margin-top: 3px;"><?= (int)$ord['fulfilment_progress'] ?>% completed</div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="timeline-staff-badge"><?= htmlspecialchars($ord['status_changed_by'] ?: ($ord['payment_confirmed_by'] ?: 'Admin')) ?></span>
                                    </td>
                                    <td>
                                        <div class="table-actions" style="justify-content: flex-end; gap: 4px;">
                                            <button type="button" class="btn btn-outline btn-sm manage-order-btn" title="Open master management console">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                                                Manage
                                            </button>
                                            <button type="button" class="btn btn-danger btn-sm delete-order-btn" title="Delete record">
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
                                        <h3>No matching orders found</h3>
                                        <p>Adjust your search query or filters.</p>
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
<!-- MASTER MODAL: ORDER & CUSTOM ENQUIRY MANAGEMENT -->
<!-- ========================================================================= -->
<div id="masterOrderModal" class="modal-overlay">
    <div class="modal-dialog" style="max-width: 920px; max-height: 92vh; display: flex; flex-direction: column;">
        
        <div class="modal-header">
            <div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <h3 class="modal-title" id="m_order_number">Order #</h3>
                    <span id="m_order_type_badge" style="font-size: 11px; font-weight: 700; text-transform: uppercase; padding: 2px 7px; border-radius: 4px; background: var(--gold-subtle); color: var(--gold-primary);"></span>
                </div>
                <div id="m_order_date" style="font-size: 12px; color: var(--text-muted); margin-top: 2px;"></div>
            </div>
            <button type="button" class="modal-close" data-modal-close>&times;</button>
        </div>

        <div class="modal-body" style="overflow-y: auto; padding: 18px 24px; display: flex; flex-direction: column; gap: 16px;">
            
            <!-- Navigation Sub-Tabs -->
            <div class="modal-nav-tabs">
                <button type="button" class="modal-tab-btn active" data-tab="tab_overview">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                    Overview &amp; Items
                </button>
                <button type="button" class="modal-tab-btn" data-tab="tab_payments">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"/><path d="M12 18V6"/></svg>
                    Payments &amp; Balances
                </button>
                <button type="button" class="modal-tab-btn" data-tab="tab_quote">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                    Quote &amp; Acceptance
                </button>
                <button type="button" class="modal-tab-btn" data-tab="tab_logistics">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                    Dispatch &amp; Booking
                </button>
                <button type="button" class="modal-tab-btn" data-tab="tab_deliverables">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    Digital Vault
                </button>
                <button type="button" class="modal-tab-btn" data-tab="tab_cancellation">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                    Cancellation &amp; Refund
                </button>
                <button type="button" class="modal-tab-btn" data-tab="tab_timeline">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 14 14"/></svg>
                    Audit Timeline &amp; Notes
                </button>
            </div>

            <!-- TAB 1: OVERVIEW & ITEMS -->
            <div id="tab_overview" class="modal-tab-content active">
                <!-- Customer Details Grid -->
                <div style="background: var(--bg-canvas); padding: 14px 16px; border-radius: var(--radius-sm); border: 1px solid var(--border-color); display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 16px;">
                    <div>
                        <span style="font-size: 10px; text-transform: uppercase; color: var(--text-muted); font-weight: 700; display: block; margin-bottom: 2px;">Customer Contact</span>
                        <strong id="m_cust_name" style="font-size: 15px; color: var(--text-primary); display: block;"></strong>
                        <div style="margin-top: 4px;">
                            <a href="#" target="_blank" id="m_cust_phone_link" style="color: #25d366; text-decoration: none; font-weight: 600; font-size: 13px;"></a>
                        </div>
                        <div id="m_cust_email" style="font-size: 12px; color: var(--text-secondary); margin-top: 2px;"></div>
                    </div>
                    <div>
                        <span style="font-size: 10px; text-transform: uppercase; color: var(--text-muted); font-weight: 700; display: block; margin-bottom: 2px;">Delivery / Studio Address</span>
                        <div id="m_cust_address" style="font-size: 13px; color: var(--text-secondary); line-height: 1.4;"></div>
                        <div id="m_cust_shipping_method" style="font-size: 11px; color: var(--gold-primary); font-weight: 600; margin-top: 4px;"></div>
                    </div>
                </div>

                <div id="m_notes_box" style="display: none; background: #fffbeb; padding: 10px 14px; border-radius: var(--radius-sm); border: 1px solid #fde68a; margin-bottom: 16px;">
                    <strong style="color: #92400e; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; display: block;">Client Specific Request / Notes:</strong>
                    <div id="m_cust_notes" style="color: #78350f; font-size: 13px; margin-top: 2px;"></div>
                </div>

                <!-- Financial Breakdown Cards -->
                <div class="financial-grid" style="margin-bottom: 16px;">
                    <div class="financial-box">
                        <div class="financial-box-label">Grand Total</div>
                        <div class="financial-box-value highlight-gold" id="m_fin_total">£0.00</div>
                    </div>
                    <div class="financial-box">
                        <div class="financial-box-label">Deposit Req.</div>
                        <div class="financial-box-value" id="m_fin_dep_req">£0.00</div>
                    </div>
                    <div class="financial-box">
                        <div class="financial-box-label">Amount Paid</div>
                        <div class="financial-box-value highlight-green" id="m_fin_paid">£0.00</div>
                    </div>
                    <div class="financial-box">
                        <div class="financial-box-label">Outstanding Balance</div>
                        <div class="financial-box-value" id="m_fin_balance">£0.00</div>
                    </div>
                </div>

                <!-- Items Container -->
                <div>
                    <h4 style="font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; color: var(--text-primary);">Order Line Items</h4>
                    <div id="m_items_list" style="display: flex; flex-direction: column; gap: 8px;"></div>
                </div>
            </div>

            <!-- TAB 2: PAYMENTS & BALANCES -->
            <div id="tab_payments" class="modal-tab-content">
                <div style="background: var(--bg-canvas); border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 16px; margin-bottom: 16px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                        <div>
                            <span style="font-size: 11px; text-transform: uppercase; color: var(--text-muted); font-weight: 700;">Current Payment Status:</span>
                            <div id="m_current_pay_badge" style="margin-top: 4px;"></div>
                            <div id="m_pay_audit_info" style="font-size: 11px; color: var(--text-muted); margin-top: 4px;"></div>
                        </div>

                        <!-- Quick Change Payment Status -->
                        <form action="order_actions.php" method="POST" style="display: flex; align-items: center; gap: 8px;">
                            <input type="hidden" name="action" value="update_payment_status">
                            <input type="hidden" name="order_id" class="m_sync_order_id">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

                            <select name="payment_status" id="m_select_payment_status" class="form-control" style="font-size: 12px; width: 170px;">
                                <option value="pending_payment">Pending Payment</option>
                                <option value="deposit_paid">Deposit Paid</option>
                                <option value="balance_pending">Balance Pending</option>
                                <option value="fully_paid">Fully Paid</option>
                                <option value="refund_pending">Refund Action Pending</option>
                                <option value="partially_refunded">Partially Refunded</option>
                                <option value="refunded">Refunded</option>
                                <option value="cancelled">Cancelled / Void</option>
                            </select>
                            <button type="submit" class="btn btn-outline btn-sm">Update Status</button>
                        </form>
                    </div>
                </div>

                <!-- Record Payment Form -->
                <form action="order_actions.php" method="POST" enctype="multipart/form-data" style="background: rgba(16,185,129,0.03); border: 1px solid rgba(16,185,129,0.3); border-radius: var(--radius-sm); padding: 16px; margin-bottom: 16px;">
                    <input type="hidden" name="action" value="record_payment">
                    <input type="hidden" name="order_id" class="m_sync_order_id">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

                    <h4 style="font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; color: #059669; font-weight: 700; margin-bottom: 12px;">+ Record Payment Received</h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px;">
                        <div class="form-group">
                            <label class="form-label" style="font-size: 11px;">Payment Classification *</label>
                            <select name="payment_type" id="m_pay_type_select" class="form-control" required>
                                <option value="deposit">Deposit Payment (50% or custom)</option>
                                <option value="balance">Outstanding Balance Payment</option>
                                <option value="full">Full Payment Settlement</option>
                                <option value="custom">Custom Interim Installment</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" style="font-size: 11px;">Amount Received (£) *</label>
                            <input type="number" step="0.01" min="0.01" name="payment_amount" id="m_pay_amount_input" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" style="font-size: 11px;">Payment Method *</label>
                            <select name="payment_method" class="form-control" required>
                                <option value="Bank Transfer (BACS)">Bank Transfer (BACS)</option>
                                <option value="Direct Wire / Swift">Direct Wire / Swift Transfer</option>
                                <option value="Stripe / Card Link">Stripe / Card Payment</option>
                                <option value="Cash / Studio Pickup">Cash / Studio Payment</option>
                                <option value="PayPal">PayPal Invoice</option>
                            </select>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 10px;">
                        <div class="form-group">
                            <label class="form-label" style="font-size: 11px;">Transaction Ref # / Receipt Code</label>
                            <input type="text" name="transaction_reference" class="form-control" placeholder="e.g. BACS-984120 or Stripe ch_3Nx...">
                        </div>
                        <div class="form-group">
                            <label class="form-label" style="font-size: 11px;">Upload Receipt / Bank Remittance (PDF, JPG, PNG)</label>
                            <input type="file" name="receipt_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp">
                        </div>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 12px;">
                        <div style="font-size: 11px; color: var(--text-muted);">Recorded under staff username: <strong><?= htmlspecialchars($_SESSION['admin_username'] ?? 'Admin') ?></strong></div>
                        <button type="submit" class="btn btn-gold btn-sm">Confirm &amp; Record Payment</button>
                    </div>
                </form>

                <!-- Payment Instructions Generator -->
                <div style="background: var(--bg-canvas); border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 14px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <h4 style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: var(--text-primary); margin: 0;">Payment Instructions for Customer</h4>
                        <div style="display: flex; gap: 6px;">
                            <button type="button" class="btn btn-outline btn-sm" id="copyBankInstructionsBtn" style="font-size: 11px;">Copy Instructions</button>
                            <a href="#" target="_blank" id="sendWhatsAppBankBtn" class="btn btn-outline btn-sm" style="font-size: 11px; color: #25d366;">Send via WhatsApp</a>
                        </div>
                    </div>
                    <textarea id="m_bank_instructions_text" class="form-control" rows="4" readonly style="font-family: monospace; font-size: 11px; background: #fff;"></textarea>
                </div>
            </div>

            <!-- TAB 3: QUOTE & ACCEPTANCE -->
            <div id="tab_quote" class="modal-tab-content">
                <form action="order_actions.php" method="POST">
                    <input type="hidden" name="action" value="save_quote">
                    <input type="hidden" name="order_id" class="m_sync_order_id">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                        <h4 style="font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; color: var(--gold-primary); font-weight: 700; margin: 0;">Prepare / Revise Studio Quote</h4>
                        <span id="m_quote_status_tag" style="font-size: 11px; color: var(--text-muted);"></span>
                    </div>

                    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 12px; margin-bottom: 12px;">
                        <div class="form-group">
                            <label class="form-label" style="font-size: 11px;">Scope / Item Description *</label>
                            <input type="text" name="quote_item_title" id="m_quote_title" class="form-control" placeholder="e.g. Custom Oak Dining Table (2400 x 1000mm) with brushed brass accents" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" style="font-size: 11px;">Quoted Price (£) *</label>
                            <input type="number" step="0.01" min="0" name="quote_item_rate" id="m_quote_rate" class="form-control" placeholder="2400.00" required>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                        <div class="form-group">
                            <label class="form-label" style="font-size: 11px;">Packaging &amp; White-Glove Shipping (£)</label>
                            <input type="number" step="0.01" min="0" name="quote_shipping_cost" id="m_quote_shipping" class="form-control" value="0.00">
                        </div>
                        <div class="form-group">
                            <label class="form-label" style="font-size: 11px;">Deposit Required (%)</label>
                            <input type="number" name="quote_deposit_percentage" id="m_quote_deposit_pct" class="form-control" value="50" min="10" max="100">
                        </div>
                        <div class="form-group">
                            <label class="form-label" style="font-size: 11px;">Quote Validity (Days)</label>
                            <input type="number" name="quote_validity_days" id="m_quote_validity_days" class="form-control" value="30">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" style="font-size: 11px;">Terms &amp; Craft Conditions</label>
                        <textarea name="quote_terms" id="m_quote_terms" class="form-control" rows="2" placeholder="e.g. 50% commitment deposit required before timber selection. Estimated production lead time 4 weeks."></textarea>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 14px;">
                        <label style="display: flex; align-items: center; gap: 8px; font-size: 12px; cursor: pointer;">
                            <input type="checkbox" name="mark_quote_sent" value="1" style="accent-color: var(--gold-primary);">
                            Mark Quote as Dispatched to Client
                        </label>
                        <button type="submit" class="btn btn-gold btn-sm">Save &amp; Generate Quote</button>
                    </div>
                </form>

                <!-- Record Customer Acceptance Section -->
                <form action="order_actions.php" method="POST" style="margin-top: 16px; background: rgba(16,185,129,0.03); border: 1px solid rgba(16,185,129,0.3); border-radius: var(--radius-sm); padding: 14px;">
                    <input type="hidden" name="action" value="record_quote_acceptance">
                    <input type="hidden" name="order_id" class="m_sync_order_id">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

                    <h4 style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: #059669; margin-bottom: 8px;">Record Customer Acceptance</h4>
                    <div style="display: grid; grid-template-columns: 1fr 2fr auto; gap: 10px; align-items: flex-end;">
                        <div class="form-group">
                            <label class="form-label" style="font-size: 11px;">Acceptance Channel</label>
                            <select name="acceptance_channel" class="form-control">
                                <option value="Email">Signed PDF via Email</option>
                                <option value="WhatsApp">WhatsApp Confirmation</option>
                                <option value="Phone Call">Phone Verbal Agreement</option>
                                <option value="In-Studio">In-Studio Agreement</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" style="font-size: 11px;">Client Notes / Confirmation Code</label>
                            <input type="text" name="acceptance_notes" class="form-control" placeholder="e.g. Client confirmed timber sample #3 and approved delivery window">
                        </div>
                        <button type="submit" class="btn btn-gold btn-sm" style="height: 38px;">Record Acceptance</button>
                    </div>
                </form>
            </div>

            <!-- TAB 4: DISPATCH & BOOKING -->
            <div id="tab_logistics" class="modal-tab-content">
                <!-- Fulfilment Progress Milestone Bar -->
                <div class="stepper-wrap" style="margin-bottom: 16px;">
                    <div class="stepper-header">
                        <span>Fulfilment Milestone: <strong id="m_fulfilment_label" style="color: var(--gold-primary);"></strong></span>
                        <span id="m_fulfilment_pct">0%</span>
                    </div>
                    <div class="stepper-bar-bg">
                        <div id="m_fulfilment_bar" class="stepper-bar-fill" style="width: 0%;"></div>
                    </div>

                    <!-- Fulfilment Status Changer -->
                    <form action="order_actions.php" method="POST" style="display: flex; gap: 10px; align-items: center; margin-top: 6px;">
                        <input type="hidden" name="action" value="update_fulfilment_status">
                        <input type="hidden" name="order_id" class="m_sync_order_id">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

                        <select name="fulfilment_status" id="m_select_fulfilment_status" class="form-control" style="font-size: 12px; flex: 1;">
                            <option value="enquiry">New Enquiry</option>
                            <option value="quote_prepared">Quote Prepared</option>
                            <option value="quote_sent">Quote Sent</option>
                            <option value="quote_accepted">Quote Accepted</option>
                            <option value="in_queue">In Queue / Confirmed (25%)</option>
                            <option value="in_production">In Production / Crafting (50%)</option>
                            <option value="booking_scheduled">Booking Scheduled (60%)</option>
                            <option value="digital_ready">Deliverables Ready (100%)</option>
                            <option value="dispatched">Dispatched / In Transit (85%)</option>
                            <option value="completed">Delivered / Fulfilled (100%)</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                        <input type="number" name="fulfilment_progress" id="m_input_progress" class="form-control" min="0" max="100" style="width: 80px;" placeholder="%">
                        <button type="submit" class="btn btn-outline btn-sm">Save Milestone</button>
                    </form>
                </div>

                <!-- Dispatch & Courier Details -->
                <form action="order_actions.php" method="POST" style="background: var(--bg-canvas); border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 14px; margin-bottom: 16px;">
                    <input type="hidden" name="action" value="update_dispatch">
                    <input type="hidden" name="order_id" class="m_sync_order_id">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

                    <h4 style="font-size: 13px; font-weight: 700; text-transform: uppercase; color: var(--gold-primary); margin-bottom: 10px;">Dispatch, Courier &amp; Live Tracking</h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div class="form-group">
                            <label class="form-label" style="font-size: 11px;">Courier / Logistics Partner</label>
                            <input type="text" name="courier_name" id="m_courier_name" class="form-control" placeholder="e.g. DHL Express, DPD, White-Glove Logistics">
                        </div>
                        <div class="form-group">
                            <label class="form-label" style="font-size: 11px;">Tracking / Consignment Code</label>
                            <input type="text" name="tracking_number" id="m_tracking_number" class="form-control" placeholder="e.g. DHL-99214-GB">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 12px; margin-top: 10px;">
                        <div class="form-group">
                            <label class="form-label" style="font-size: 11px;">Direct Tracking URL</label>
                            <input type="url" name="tracking_url" id="m_tracking_url" class="form-control" placeholder="https://track.dhl.com/en?tracking-id=...">
                        </div>
                        <div class="form-group">
                            <label class="form-label" style="font-size: 11px;">Estimated Delivery</label>
                            <input type="text" name="estimated_delivery" id="m_estimated_delivery" class="form-control" placeholder="e.g. Oct 28, 2026">
                        </div>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 12px;">
                        <label style="display: flex; align-items: center; gap: 8px; font-size: 12px; cursor: pointer;">
                            <input type="checkbox" name="mark_dispatched" value="1" style="accent-color: var(--gold-primary);">
                            Update Fulfilment Status to Dispatched (85%)
                        </label>
                        <button type="submit" class="btn btn-gold btn-sm">Save Logistics Details</button>
                    </div>
                </form>

                <!-- Booking Session Management -->
                <form action="order_actions.php" method="POST" style="background: rgba(14,165,233,0.03); border: 1px solid rgba(14,165,233,0.3); border-radius: var(--radius-sm); padding: 14px;">
                    <input type="hidden" name="action" value="update_booking">
                    <input type="hidden" name="order_id" class="m_sync_order_id">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

                    <h4 style="font-size: 13px; font-weight: 700; text-transform: uppercase; color: #0284c7; margin-bottom: 10px;">Booking Dates &amp; Consultation Link</h4>
                    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 12px;">
                        <div class="form-group">
                            <label class="form-label" style="font-size: 11px;">Session Date &amp; Time</label>
                            <input type="datetime-local" name="booking_date" id="m_booking_date" class="form-control">
                        </div>
                        <div class="form-group">
                            <label class="form-label" style="font-size: 11px;">Meeting Room Link / Studio Location</label>
                            <input type="text" name="booking_meeting_link" id="m_booking_link" class="form-control" placeholder="https://meet.google.com/xxx-xxxx-xxx or Studio Address">
                        </div>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 12px;">
                        <label style="display: flex; align-items: center; gap: 8px; font-size: 12px; cursor: pointer;">
                            <input type="checkbox" name="mark_scheduled" value="1" style="accent-color: #0284c7;">
                            Set Fulfilment Status to Booking Scheduled (60%)
                        </label>
                        <button type="submit" class="btn btn-gold btn-sm">Update Booking</button>
                    </div>
                </form>
            </div>

            <!-- TAB 5: DIGITAL VAULT -->
            <div id="tab_deliverables" class="modal-tab-content">
                <form action="order_actions.php" method="POST" enctype="multipart/form-data" style="background: var(--bg-canvas); border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 16px;">
                    <input type="hidden" name="action" value="update_deliverable">
                    <input type="hidden" name="order_id" class="m_sync_order_id">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

                    <h4 style="font-size: 13px; font-weight: 700; text-transform: uppercase; color: #4f46e5; margin-bottom: 10px;">Upload Deliverables or Assign Protected Links</h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div class="form-group">
                            <label class="form-label" style="font-size: 11px;">Upload Deliverable Archive (.ZIP, .PDF, .RFA)</label>
                            <input type="file" name="deliverable_file" class="form-control" accept=".zip,.rar,.pdf,.brushset,.dwg,.dxf,.rfa,.blend">
                            <div id="m_current_file_path" style="font-size: 11px; color: var(--text-muted); margin-top: 4px;"></div>
                        </div>
                        <div class="form-group">
                            <label class="form-label" style="font-size: 11px;">Protected Access Link / Secret Token URL</label>
                            <input type="url" name="digital_access_link" id="m_digital_access_link" class="form-control" placeholder="https://urthspaces.com/download.php?token=sec_xxx">
                            <div style="display: flex; gap: 6px; margin-top: 4px;">
                                <button type="button" class="btn btn-outline btn-sm" id="generateTokenLinkBtn" style="font-size: 10px; padding: 2px 8px;">Auto-Generate Token</button>
                                <button type="button" class="btn btn-outline btn-sm" id="copyDeliverableLinkBtn" style="font-size: 10px; padding: 2px 8px;">Copy Link</button>
                            </div>
                        </div>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 14px;">
                        <label style="display: flex; align-items: center; gap: 8px; font-size: 12px; cursor: pointer;">
                            <input type="checkbox" name="mark_digital_ready" value="1" style="accent-color: #4f46e5;">
                            Mark as Digital Deliverable Ready (100% Fulfilled)
                        </label>
                        <button type="submit" class="btn btn-gold btn-sm">Save Deliverable</button>
                    </div>
                </form>
            </div>

            <!-- TAB 6: CANCELLATIONS & REFUNDS -->
            <div id="tab_cancellation" class="modal-tab-content">
                <!-- Record Cancellation & Refund Action -->
                <form action="order_actions.php" method="POST" style="background: rgba(239,68,68,0.03); border: 1px solid rgba(239,68,68,0.3); border-radius: var(--radius-sm); padding: 16px; margin-bottom: 16px;">
                    <input type="hidden" name="action" value="record_cancellation">
                    <input type="hidden" name="order_id" class="m_sync_order_id">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

                    <h4 style="font-size: 13px; font-weight: 700; text-transform: uppercase; color: #dc2626; margin-bottom: 10px;">Record Cancellation</h4>
                    <div class="form-group">
                        <label class="form-label" style="font-size: 11px;">Cancellation Reason *</label>
                        <textarea name="cancellation_reason" id="m_cancel_reason" class="form-control" rows="2" placeholder="e.g. Customer change of mind, bespoke timber unavailable, project cancelled..." required></textarea>
                    </div>

                    <div style="margin-top: 10px; padding: 10px; background: #fff; border: 1px solid var(--border-color); border-radius: var(--radius-sm);">
                        <label style="display: flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 700; color: #b91c1c; cursor: pointer;">
                            <input type="checkbox" name="request_refund" id="m_request_refund_chk" value="1" style="accent-color: #dc2626;">
                            Mark as "Pending Refund Action" in Studio Queue
                        </label>
                        <div id="m_refund_fields_wrap" style="display: none; margin-top: 10px;">
                            <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 10px;">
                                <div class="form-group">
                                    <label class="form-label" style="font-size: 11px;">Refund Amount (£) *</label>
                                    <input type="number" step="0.01" min="0" name="refund_amount" id="m_refund_amount_req" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label class="form-label" style="font-size: 11px;">Refund Instructions for Finance</label>
                                    <input type="text" name="refund_reason" class="form-control" placeholder="e.g. Full refund via Stripe dashboard">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div style="text-align: right; margin-top: 12px;">
                        <button type="submit" class="btn btn-danger btn-sm">Confirm Cancellation</button>
                    </div>
                </form>

                <!-- Process Refund Execution -->
                <form action="order_actions.php" method="POST" style="background: rgba(139,92,246,0.03); border: 1px solid rgba(139,92,246,0.3); border-radius: var(--radius-sm); padding: 16px;">
                    <input type="hidden" name="action" value="process_refund">
                    <input type="hidden" name="order_id" class="m_sync_order_id">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

                    <h4 style="font-size: 13px; font-weight: 700; text-transform: uppercase; color: #7c3aed; margin-bottom: 10px;">Confirm &amp; Record Processed Refund</h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px;">
                        <div class="form-group">
                            <label class="form-label" style="font-size: 11px;">Refund Amount (£) *</label>
                            <input type="number" step="0.01" min="0.01" name="processed_refund_amount" id="m_refund_processed_amount" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" style="font-size: 11px;">Refund Method</label>
                            <select name="refund_method" class="form-control">
                                <option value="Original Payment Method">Original Payment Method</option>
                                <option value="Bank Transfer (BACS)">Bank Transfer (BACS)</option>
                                <option value="Stripe Portal">Stripe Payment Gateway</option>
                                <option value="Studio Credit">Studio Credit Voucher</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" style="font-size: 11px;">Refund Transaction Reference</label>
                            <input type="text" name="refund_reference" class="form-control" placeholder="e.g. re_3Nx992 or BACS-REF-RFD">
                        </div>
                    </div>
                    <div class="form-group" style="margin-top: 10px;">
                        <label class="form-label" style="font-size: 11px;">Refund Notes</label>
                        <input type="text" name="refund_notes" class="form-control" placeholder="Staff reconciliation notes...">
                    </div>
                    <div style="text-align: right; margin-top: 12px;">
                        <button type="submit" class="btn btn-gold btn-sm">Record Refund as Executed</button>
                    </div>
                </form>
            </div>

            <!-- TAB 7: TIMELINE & NOTES -->
            <div id="tab_timeline" class="modal-tab-content">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <!-- Internal Staff Notes -->
                    <div>
                        <h4 style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: var(--text-primary); margin-bottom: 8px;">Internal Studio Team Notes</h4>
                        <form action="order_actions.php" method="POST" style="margin-bottom: 12px;">
                            <input type="hidden" name="action" value="add_internal_note">
                            <input type="hidden" name="order_id" class="m_sync_order_id">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

                            <div style="display: flex; gap: 8px;">
                                <input type="text" name="internal_note_text" class="form-control" placeholder="Add private note for workshop or finance..." required>
                                <button type="submit" class="btn btn-gold btn-sm">Add</button>
                            </div>
                        </form>
                        <div id="m_internal_notes_list" style="display: flex; flex-direction: column; gap: 6px; max-height: 250px; overflow-y: auto;"></div>
                    </div>

                    <!-- Customer Communications -->
                    <div>
                        <h4 style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: var(--text-primary); margin-bottom: 8px;">Customer Updates Log</h4>
                        <form action="order_actions.php" method="POST" style="margin-bottom: 12px;">
                            <input type="hidden" name="action" value="send_customer_update">
                            <input type="hidden" name="order_id" class="m_sync_order_id">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

                            <div style="display: flex; gap: 8px; margin-bottom: 6px;">
                                <input type="text" name="update_title" class="form-control" placeholder="Update Title" style="font-size: 12px;" required>
                                <select name="update_channel" class="form-control" style="width: 110px; font-size: 12px;">
                                    <option value="Email">Email</option>
                                    <option value="WhatsApp">WhatsApp</option>
                                    <option value="Phone">Phone</option>
                                </select>
                            </div>
                            <div style="display: flex; gap: 8px;">
                                <input type="text" name="update_message" class="form-control" placeholder="Message content dispatched..." style="font-size: 12px;" required>
                                <button type="submit" class="btn btn-gold btn-sm">Log</button>
                            </div>
                        </form>
                        <div id="m_customer_updates_list" style="display: flex; flex-direction: column; gap: 6px; max-height: 250px; overflow-y: auto;"></div>
                    </div>
                </div>

                <!-- Staff Attribution Audit Trail -->
                <div style="margin-top: 20px; border-top: 1px solid var(--border-color); padding-top: 14px;">
                    <h4 style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: var(--gold-primary); margin-bottom: 12px;">Staff Attribution Audit Log</h4>
                    <div id="m_audit_timeline" class="audit-timeline"></div>
                </div>
            </div>

        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-outline" data-modal-close>Close</button>
        </div>

    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: MANUAL ORDER / CUSTOM ENQUIRY CREATION -->
<!-- ========================================================================= -->
<div id="manualOrderModal" class="modal-overlay">
    <div class="modal-dialog" style="max-width: 650px;">
        <div class="modal-header">
            <h3 class="modal-title">+ Add Manual Order or Custom Enquiry</h3>
            <button type="button" class="modal-close" data-modal-close>&times;</button>
        </div>
        <form action="order_actions.php" method="POST">
            <input type="hidden" name="action" value="create_manual_order">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

            <div class="modal-body" style="display: flex; flex-direction: column; gap: 14px;">
                <div class="form-group">
                    <label class="form-label">Record Type *</label>
                    <select name="order_type" class="form-control" required>
                        <option value="order">Standard Shop Order (Customer phone/email sale)</option>
                        <option value="custom_enquiry">Bespoke Custom Furniture / Interior Enquiry</option>
                        <option value="quote_request">Architecture / Design Session Quote Request</option>
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="form-group">
                        <label class="form-label">Customer Name *</label>
                        <input type="text" name="customer_name" class="form-control" placeholder="e.g. Julian Sterling" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Customer Phone *</label>
                        <input type="text" name="customer_phone" class="form-control" placeholder="e.g. +44 7911 123456" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Customer Email</label>
                    <input type="email" name="customer_email" class="form-control" placeholder="e.g. client@studio.com">
                </div>

                <div class="form-group">
                    <label class="form-label">Delivery Address / Project Location</label>
                    <textarea name="delivery_address" class="form-control" rows="2" placeholder="Full address or project site..."></textarea>
                </div>

                <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 12px;">
                    <div class="form-group">
                        <label class="form-label">Item / Scope Title *</label>
                        <input type="text" name="item_name" class="form-control" placeholder="e.g. Custom Dining Table" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Unit Price (£) *</label>
                        <input type="number" step="0.01" min="0" name="item_price" class="form-control" placeholder="2400.00" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Quantity</label>
                        <input type="number" name="item_qty" class="form-control" value="1" min="1">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Initial Project Notes / Requirements</label>
                    <textarea name="order_notes" class="form-control" rows="2" placeholder="Specifications, timber requirements, target dates..."></textarea>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-gold">Create Record</button>
            </div>
        </form>
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
                    This will delete the customer order, line items, and audit logs permanently.
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

    // Sub-Tabs inside Master Order Modal
    const tabBtns = document.querySelectorAll('.modal-tab-btn');
    const tabContents = document.querySelectorAll('.modal-tab-content');
    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const target = btn.getAttribute('data-tab');
            tabBtns.forEach(b => b.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active'));
            btn.classList.add('active');
            const targetEl = document.getElementById(target);
            if (targetEl) targetEl.classList.add('active');
        });
    });

    // Manual Order Modal
    const manualOrderModal = document.querySelector('#manualOrderModal');
    const openManualBtn = document.querySelector('#openManualOrderBtn');
    if (openManualBtn) openManualBtn.addEventListener('click', () => openModal(manualOrderModal));

    // Cancellation & Refund Checkbox toggle
    const reqRefundChk = document.querySelector('#m_request_refund_chk');
    const refundFieldsWrap = document.querySelector('#m_refund_fields_wrap');
    if (reqRefundChk && refundFieldsWrap) {
        reqRefundChk.addEventListener('change', () => {
            refundFieldsWrap.style.display = reqRefundChk.checked ? 'block' : 'none';
        });
    }

    // Master Order Modal Logic
    const masterModal = document.querySelector('#masterOrderModal');
    document.querySelectorAll('.manage-order-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const row = btn.closest('.order-row');
            if (!row) return;

            const ord = JSON.parse(row.getAttribute('data-raw'));
            const curr = ord.currency || '£';
            const tot = parseFloat(ord.total || 0);
            const paid = parseFloat(ord.amount_paid || 0);
            const due = parseFloat(ord.balance_due || (tot - paid));
            const depReq = parseFloat(ord.deposit_required || (tot * 0.5));

            // Sync order_id across all forms in modal
            document.querySelectorAll('.m_sync_order_id').forEach(inp => inp.value = ord.id);

            // Header
            document.querySelector('#m_order_number').textContent = ord.order_number;
            document.querySelector('#m_order_type_badge').textContent = (ord.order_type || 'order').replace('_', ' ');
            document.querySelector('#m_order_date').textContent = 'Placed on ' + ord.created_at;

            // Overview
            document.querySelector('#m_cust_name').textContent = ord.customer_name;
            const cleanPhone = (ord.customer_phone || '').replace(/[^0-9]/g, '');
            const phoneLink = document.querySelector('#m_cust_phone_link');
            phoneLink.textContent = ord.customer_phone;
            phoneLink.href = 'https://wa.me/' + cleanPhone;
            document.querySelector('#m_cust_email').textContent = ord.customer_email || 'No email provided';
            document.querySelector('#m_cust_address').textContent = ord.delivery_address || 'No address provided';
            document.querySelector('#m_cust_shipping_method').textContent = ord.shipping_method || 'Standard';

            const notesBox = document.querySelector('#m_notes_box');
            if (ord.order_notes) {
                notesBox.style.display = 'block';
                document.querySelector('#m_cust_notes').textContent = ord.order_notes;
            } else {
                notesBox.style.display = 'none';
            }

            // Financial boxes
            document.querySelector('#m_fin_total').textContent = curr + tot.toFixed(2);
            document.querySelector('#m_fin_dep_req').textContent = curr + depReq.toFixed(2);
            document.querySelector('#m_fin_paid').textContent = curr + paid.toFixed(2);
            const balEl = document.querySelector('#m_fin_balance');
            balEl.textContent = curr + due.toFixed(2);
            balEl.className = 'financial-box-value ' + (due > 0.01 ? 'highlight-amber' : 'highlight-green');

            // Line items
            const itemsList = document.querySelector('#m_items_list');
            itemsList.innerHTML = (ord.items || []).map(it => {
                const img = it.image ? (!/^https?:\/\//i.test(it.image) ? '../' + it.image.replace(/^\/+/, '') : it.image) : '../img/shop/prod-1.jpg';
                return `
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; background: var(--bg-canvas); border-radius: 4px; border: 1px solid var(--border-color);">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <img src="${img}" style="width: 40px; height: 40px; object-fit: contain; background: #fff; border-radius: 4px; border: 1px solid var(--border-color);" alt="${it.product_name}">
                            <div>
                                <strong style="font-size: 13px; color: var(--text-primary); display: block;">${it.product_name}</strong>
                                <span style="font-size: 11px; color: var(--text-muted);">${it.product_sku || ''}</span>
                            </div>
                        </div>
                        <div style="text-align: right; font-size: 13px;">
                            <span style="color: var(--text-secondary);">${it.quantity} &times; ${curr}${parseFloat(it.price).toFixed(2)}</span>
                            <strong style="color: var(--text-primary); margin-left: 10px;">${curr}${parseFloat(it.total).toFixed(2)}</strong>
                        </div>
                    </div>
                `;
            }).join('');

            // TAB 2: Payments
            const payBadge = document.querySelector('#m_current_pay_badge');
            payBadge.innerHTML = `<span class="pay-badge pay-badge-${(ord.payment_status || 'pending').replace('_', '-')}">${(ord.payment_status || 'pending').replace('_', ' ').toUpperCase()}</span>`;
            document.querySelector('#m_pay_audit_info').textContent = ord.payment_confirmed_by ? `Confirmed by ${ord.payment_confirmed_by} on ${ord.payment_confirmed_at}` : 'No payment confirmation on record';
            document.querySelector('#m_select_payment_status').value = ord.payment_status || 'pending_payment';

            // Auto-fill payment amount suggestion
            const payAmtInp = document.querySelector('#m_pay_amount_input');
            payAmtInp.value = due > 0 ? due.toFixed(2) : tot.toFixed(2);
            document.querySelector('#m_pay_type_select').addEventListener('change', (e) => {
                if (e.target.value === 'deposit') payAmtInp.value = depReq.toFixed(2);
                else if (e.target.value === 'balance') payAmtInp.value = due.toFixed(2);
                else if (e.target.value === 'full') payAmtInp.value = tot.toFixed(2);
            });

            // Bank instructions generator
            const bankInstructions = `STUDIO PAYMENT INSTRUCTIONS — URTH ARCHITECTURE STUDIO
Order Ref: ${ord.order_number}
Customer: ${ord.customer_name}
Total: ${curr}${tot.toFixed(2)} | Balance Due: ${curr}${due.toFixed(2)}

Bank: Barclays Bank UK
Account Name: URTH Architecture Studio Ltd
Sort Code: 20-00-00
Account No: 12345678
IBAN: GB29BARC20000012345678
Payment Reference: ${ord.order_number}

Please email remittance confirmation or attach receipt in customer portal.`;
            document.querySelector('#m_bank_instructions_text').value = bankInstructions;
            document.querySelector('#copyBankInstructionsBtn').onclick = () => {
                navigator.clipboard.writeText(bankInstructions);
                alert('Payment instructions copied to clipboard!');
            };
            const waBankMsg = encodeURIComponent(bankInstructions);
            document.querySelector('#sendWhatsAppBankBtn').href = `https://wa.me/${cleanPhone}?text=${waBankMsg}`;

            // TAB 3: Quote & Acceptance
            let quoteData = null;
            if (ord.quote_details) {
                try { quoteData = typeof ord.quote_details === 'string' ? JSON.parse(ord.quote_details) : ord.quote_details; } catch(e){}
            }
            if (quoteData && quoteData.items && quoteData.items.length > 0) {
                document.querySelector('#m_quote_title').value = quoteData.items[0].title || '';
                document.querySelector('#m_quote_rate').value = quoteData.items[0].rate || '';
                document.querySelector('#m_quote_shipping').value = ord.shipping_cost || 0;
                document.querySelector('#m_quote_deposit_pct').value = quoteData.deposit_percentage || 50;
                document.querySelector('#m_quote_validity_days').value = quoteData.validity_days || 30;
                document.querySelector('#m_quote_terms').value = quoteData.terms || '';
            } else {
                document.querySelector('#m_quote_title').value = ord.items && ord.items[0] ? ord.items[0].product_name : 'Bespoke Studio Commission';
                document.querySelector('#m_quote_rate').value = tot || '';
            }
            document.querySelector('#m_quote_status_tag').textContent = ord.quote_accepted_at ? `Accepted on ${ord.quote_accepted_at} (${ord.quote_accepted_notes || ''})` : (ord.quote_sent_at ? `Sent on ${ord.quote_sent_at}` : 'Draft quote');

            // TAB 4: Logistics & Bookings
            const prog = parseInt(ord.fulfilment_progress || 0, 10);
            document.querySelector('#m_fulfilment_label').textContent = (ord.fulfilment_status || 'In Queue').replace('_', ' ');
            document.querySelector('#m_fulfilment_pct').textContent = prog + '%';
            document.querySelector('#m_fulfilment_bar').style.width = prog + '%';
            document.querySelector('#m_select_fulfilment_status').value = ord.fulfilment_status || 'in_queue';
            document.querySelector('#m_input_progress').value = prog;

            document.querySelector('#m_courier_name').value = ord.courier_name || '';
            document.querySelector('#m_tracking_number').value = ord.tracking_number || '';
            document.querySelector('#m_tracking_url').value = ord.tracking_url || '';
            document.querySelector('#m_estimated_delivery').value = ord.estimated_delivery || '';

            if (ord.booking_date) {
                const bDate = ord.booking_date.replace(' ', 'T').substring(0, 16);
                document.querySelector('#m_booking_date').value = bDate;
            } else {
                document.querySelector('#m_booking_date').value = '';
            }
            document.querySelector('#m_booking_link').value = ord.booking_meeting_link || '';

            // TAB 5: Deliverables
            document.querySelector('#m_digital_access_link').value = ord.digital_access_link || '';
            document.querySelector('#m_current_file_path').textContent = ord.digital_deliverable_file ? 'Assigned file: ' + ord.digital_deliverable_file : 'No deliverable archive attached yet';
            document.querySelector('#generateTokenLinkBtn').onclick = () => {
                const token = 'sec_' + Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);
                document.querySelector('#m_digital_access_link').value = 'https://urthspaces.com/download.php?token=' + token;
            };
            document.querySelector('#copyDeliverableLinkBtn').onclick = () => {
                const linkVal = document.querySelector('#m_digital_access_link').value;
                if (linkVal) {
                    navigator.clipboard.writeText(linkVal);
                    alert('Protected download link copied!');
                } else {
                    alert('No access link specified.');
                }
            };

            // TAB 6: Cancellations & Refunds
            document.querySelector('#m_cancel_reason').value = ord.cancellation_reason || '';
            document.querySelector('#m_refund_amount_req').value = ord.refund_amount > 0 ? ord.refund_amount : paid;
            document.querySelector('#m_refund_processed_amount').value = ord.refund_amount > 0 ? ord.refund_amount : paid;

            // TAB 7: Notes, Communication & Audit Timeline
            // Internal Notes
            let intNotes = [];
            if (ord.internal_notes) {
                try { intNotes = typeof ord.internal_notes === 'string' ? JSON.parse(ord.internal_notes) : ord.internal_notes; } catch(e){}
            }
            const intList = document.querySelector('#m_internal_notes_list');
            if (intNotes && intNotes.length > 0) {
                intList.innerHTML = intNotes.map(n => `
                    <div style="background: var(--bg-canvas); padding: 8px 12px; border-radius: 4px; border: 1px solid var(--border-color); font-size: 12px;">
                        <div style="display: flex; justify-content: space-between; font-size: 11px; color: var(--text-muted); margin-bottom: 2px;">
                            <strong style="color: var(--gold-primary);">${n.staff || 'Staff'}</strong>
                            <span>${n.time || ''}</span>
                        </div>
                        <div style="color: var(--text-primary);">${n.note || ''}</div>
                    </div>
                `).join('');
            } else {
                intList.innerHTML = '<span style="font-size: 12px; color: var(--text-muted);">No internal team notes recorded.</span>';
            }

            // Customer Updates
            let custUpdates = [];
            if (ord.customer_updates) {
                try { custUpdates = typeof ord.customer_updates === 'string' ? JSON.parse(ord.customer_updates) : ord.customer_updates; } catch(e){}
            }
            const custUpList = document.querySelector('#m_customer_updates_list');
            if (custUpdates && custUpdates.length > 0) {
                custUpList.innerHTML = custUpdates.map(u => `
                    <div style="background: var(--bg-canvas); padding: 8px 12px; border-radius: 4px; border: 1px solid var(--border-color); font-size: 12px;">
                        <div style="display: flex; justify-content: space-between; font-size: 11px; margin-bottom: 2px;">
                            <strong style="color: var(--text-primary);">${u.title || 'Update'} [${u.channel || 'Email'}]</strong>
                            <span style="color: var(--text-muted);">${u.time || ''} (${u.staff || ''})</span>
                        </div>
                        <div style="color: var(--text-secondary);">${u.message || ''}</div>
                    </div>
                `).join('');
            } else {
                custUpList.innerHTML = '<span style="font-size: 12px; color: var(--text-muted);">No customer notifications logged yet.</span>';
            }

            // Audit Timeline
            const auditTimeline = document.querySelector('#m_audit_timeline');
            const logs = ord.activity_logs || [];
            if (logs.length > 0) {
                auditTimeline.innerHTML = logs.map(l => `
                    <div class="timeline-entry">
                        <div class="timeline-entry-header">
                            <span class="timeline-staff-badge">${l.staff_username}</span>
                            <span class="timeline-time">${l.created_at}</span>
                        </div>
                        <div class="timeline-note">
                            <strong>${l.action_type.replace('_', ' ').toUpperCase()}:</strong>
                            ${l.note || ''}
                        </div>
                    </div>
                `).join('');
            } else {
                auditTimeline.innerHTML = '<span style="font-size: 12px; color: var(--text-muted); padding-left: 10px;">No audit trail events logged yet.</span>';
            }

            // Reset to Overview Tab
            tabBtns[0].click();

            openModal(masterModal);
        });
    });

    // Delete Modal
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

    // Filter Logic
    const searchInput = document.querySelector('#orderSearchInput');
    const typeFilter = document.querySelector('#typeFilter');
    const paymentFilter = document.querySelector('#paymentFilter');
    const fulfilmentFilter = document.querySelector('#fulfilmentFilter');
    const countDisplay = document.querySelector('#orderCount');
    const rows = document.querySelectorAll('.order-row');
    const noResultsRow = document.querySelector('#noResultsRow');

    function filterOrders() {
        const query = (searchInput ? searchInput.value : '').toLowerCase().trim();
        const selType = typeFilter ? typeFilter.value : '';
        const selPay = paymentFilter ? paymentFilter.value : '';
        const selFul = fulfilmentFilter ? fulfilmentFilter.value : '';

        let visible = 0;
        rows.forEach(r => {
            const num = r.getAttribute('data-number') || '';
            const typ = r.getAttribute('data-type') || '';
            const cust = r.getAttribute('data-customer') || '';
            const ph = r.getAttribute('data-phone') || '';
            const em = r.getAttribute('data-email') || '';
            const trk = r.getAttribute('data-tracking') || '';
            const pay = r.getAttribute('data-payment-status') || '';
            const ful = r.getAttribute('data-fulfilment-status') || '';

            const matchesSearch = !query || num.includes(query) || cust.includes(query) || ph.includes(query) || em.includes(query) || trk.includes(query);
            const matchesType = !selType || typ === selType;
            const matchesPay = !selPay || pay === selPay;
            const matchesFul = !selFul || ful === selFul;

            if (matchesSearch && matchesType && matchesPay && matchesFul) {
                r.style.display = '';
                visible++;
            } else {
                r.style.display = 'none';
            }
        });

        if (countDisplay) countDisplay.textContent = visible;
        if (noResultsRow) noResultsRow.style.display = visible === 0 && rows.length > 0 ? '' : 'none';

        // Update CSV export link with current filters
        const exportBtn = document.querySelector('#exportOrdersBtn');
        if (exportBtn) {
            exportBtn.href = `export_orders.php?search=${encodeURIComponent(query)}&type=${encodeURIComponent(selType)}&payment_status=${encodeURIComponent(selPay)}&fulfilment_status=${encodeURIComponent(selFul)}`;
        }
    }

    if (searchInput) searchInput.addEventListener('input', filterOrders);
    if (typeFilter) typeFilter.addEventListener('change', filterOrders);
    if (paymentFilter) paymentFilter.addEventListener('change', filterOrders);
    if (fulfilmentFilter) fulfilmentFilter.addEventListener('change', filterOrders);

    // Helper for quick refund filter from top alert banner
    window.filterByPendingRefund = function() {
        if (paymentFilter) {
            paymentFilter.value = 'refund_pending';
            filterOrders();
        }
    };
});
</script>

</body>
</html>
