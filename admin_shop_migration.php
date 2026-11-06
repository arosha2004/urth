<?php

require_once __DIR__ . '/config.php';

echo "Migrating URTH database schema for advanced shop & order management...\n";

// 1. Ensure product_categories exists and has proper columns
$catSql = "CREATE TABLE IF NOT EXISTS product_categories (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT DEFAULT NULL,
    display_order INT(11) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
$conn->query($catSql);

// 2. Expand products table columns
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
$res = $conn->query("SHOW COLUMNS FROM products");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $existingProductCols[] = $row['Field'];
    }
}

foreach ($productColsToAdd as $col => $def) {
    if (!in_array($col, $existingProductCols, true)) {
        $alter = "ALTER TABLE products ADD COLUMN {$col} {$def}";
        if ($conn->query($alter)) {
            echo "Added column products.{$col}\n";
        } else {
            echo "Error adding products.{$col}: " . $conn->error . "\n";
        }
    }
}

// 2b. Preserve the customer's selected product option in each order line.
$existingItemCols = [];
$res = $conn->query("SHOW COLUMNS FROM order_items");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $existingItemCols[] = $row['Field'];
    }
    if (!in_array('selected_option', $existingItemCols, true)) {
        if ($conn->query("ALTER TABLE order_items ADD COLUMN selected_option VARCHAR(255) DEFAULT NULL")) {
            echo "Added column order_items.selected_option\n";
        } else {
            echo "Error adding order_items.selected_option: " . $conn->error . "\n";
        }
    }
} else {
    echo "Could not inspect order_items; run setup.php before this migration: " . $conn->error . "\n";
}

// 3. Expand orders table columns
$orderColsToAdd = [
    'order_type' => "VARCHAR(30) DEFAULT 'order'",
    'payment_status' => "VARCHAR(50) DEFAULT 'pending_payment'",
    'fulfilment_status' => "VARCHAR(50) DEFAULT 'in_queue'",
    'deposit_required' => "DECIMAL(10, 2) DEFAULT 0.00",
    'deposit_paid' => "DECIMAL(10, 2) DEFAULT 0.00",
    'amount_paid' => "DECIMAL(10, 2) DEFAULT 0.00",
    'balance_due' => "DECIMAL(10, 2) DEFAULT 0.00",
    'payment_method' => "VARCHAR(100) DEFAULT NULL",
    'transaction_reference' => "VARCHAR(255) DEFAULT NULL",
    'receipt_url' => "VARCHAR(255) DEFAULT NULL",
    'payment_confirmed_by' => "VARCHAR(100) DEFAULT NULL",
    'payment_confirmed_at' => "DATETIME DEFAULT NULL",
    'status_changed_by' => "VARCHAR(100) DEFAULT NULL",
    'courier_name' => "VARCHAR(100) DEFAULT NULL",
    'tracking_number' => "VARCHAR(100) DEFAULT NULL",
    'tracking_url' => "VARCHAR(500) DEFAULT NULL",
    'dispatched_at' => "DATETIME DEFAULT NULL",
    'estimated_delivery' => "VARCHAR(100) DEFAULT NULL",
    'booking_date' => "DATETIME DEFAULT NULL",
    'booking_meeting_link' => "VARCHAR(500) DEFAULT NULL",
    'fulfilment_progress' => "INT(11) DEFAULT 0",
    'digital_deliverable_file' => "VARCHAR(255) DEFAULT NULL",
    'digital_access_link' => "TEXT DEFAULT NULL",
    'quote_details' => "LONGTEXT DEFAULT NULL",
    'quote_sent_at' => "DATETIME DEFAULT NULL",
    'quote_accepted_at' => "DATETIME DEFAULT NULL",
    'quote_accepted_notes' => "TEXT DEFAULT NULL",
    'quote_accepted_by' => "VARCHAR(100) DEFAULT NULL",
    'cancellation_reason' => "TEXT DEFAULT NULL",
    'cancelled_by' => "VARCHAR(100) DEFAULT NULL",
    'cancelled_at' => "DATETIME DEFAULT NULL",
    'refund_amount' => "DECIMAL(10, 2) DEFAULT 0.00",
    'refund_status' => "VARCHAR(50) DEFAULT 'none'",
    'refund_reason' => "TEXT DEFAULT NULL",
    'refund_reference' => "VARCHAR(255) DEFAULT NULL",
    'refund_processed_by' => "VARCHAR(100) DEFAULT NULL",
    'refund_processed_at' => "DATETIME DEFAULT NULL",
    'internal_notes' => "LONGTEXT DEFAULT NULL",
    'customer_updates' => "LONGTEXT DEFAULT NULL",
    'terms_accepted_at' => "DATETIME DEFAULT NULL",
    'customer_uploads' => "LONGTEXT DEFAULT NULL",
    'customer_status_token_hash' => "CHAR(64) DEFAULT NULL",
    'customer_status_token_expires_at' => "DATETIME DEFAULT NULL"
];

$existingOrderCols = [];
$res = $conn->query("SHOW COLUMNS FROM orders");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $existingOrderCols[] = $row['Field'];
    }
}

foreach ($orderColsToAdd as $col => $def) {
    if (!in_array($col, $existingOrderCols, true)) {
        $alter = "ALTER TABLE orders ADD COLUMN {$col} {$def}";
        if ($conn->query($alter)) {
            echo "Added column orders.{$col}\n";
        } else {
            echo "Error adding orders.{$col}: " . $conn->error . "\n";
        }
    }
}

$tokenIndex = $conn->query("SHOW INDEX FROM orders WHERE Key_name = 'idx_order_status_token'");
if ($tokenIndex && $tokenIndex->num_rows === 0) {
    if ($conn->query("ALTER TABLE orders ADD INDEX idx_order_status_token (customer_status_token_hash)")) {
        echo "Added index orders.idx_order_status_token\n";
    } else {
        echo "Error adding orders.idx_order_status_token: " . $conn->error . "\n";
    }
}

// 4. Create order_activity_logs table for audit trail
$logTableSql = "CREATE TABLE IF NOT EXISTS order_activity_logs (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    order_id INT(11) NOT NULL,
    staff_username VARCHAR(100) NOT NULL,
    action_type VARCHAR(100) NOT NULL,
    previous_value TEXT DEFAULT NULL,
    new_value TEXT DEFAULT NULL,
    note TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (order_id),
    INDEX (staff_username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($conn->query($logTableSql)) {
    echo "Table 'order_activity_logs' created/verified successfully.\n";
} else {
    echo "Error creating order_activity_logs: " . $conn->error . "\n";
}

// 5. Backfill existing orders with sensible defaults for separate payment & fulfilment
$resOrders = $conn->query("SELECT id, status, total, payment_status, fulfilment_status FROM orders");
if ($resOrders) {
    while ($ord = $resOrders->fetch_assoc()) {
        $oid = (int)$ord['id'];
        $tot = (float)$ord['total'];
        $oldStatus = $ord['status'] ?? 'Pending';
        $pStatus = $ord['payment_status'] ?? 'pending_payment';
        $fStatus = $ord['fulfilment_status'] ?? 'in_queue';

        if (empty($pStatus) || $pStatus === 'pending_payment') {
            if ($oldStatus === 'Completed') {
                $pStatus = 'fully_paid';
                $fStatus = 'completed';
                $amtPaid = $tot;
                $balDue = 0.00;
            } elseif ($oldStatus === 'Cancelled') {
                $pStatus = 'cancelled';
                $fStatus = 'cancelled';
                $amtPaid = 0.00;
                $balDue = $tot;
            } elseif ($oldStatus === 'Processing') {
                $pStatus = 'deposit_paid';
                $fStatus = 'in_production';
                $amtPaid = round($tot * 0.5, 2);
                $balDue = $tot - $amtPaid;
            } else {
                $pStatus = 'pending_payment';
                $fStatus = 'in_queue';
                $amtPaid = 0.00;
                $balDue = $tot;
            }

            $uStmt = $conn->prepare("UPDATE orders SET payment_status = ?, fulfilment_status = ?, amount_paid = ?, balance_due = ? WHERE id = ?");
            if ($uStmt) {
                $uStmt->bind_param("ssddi", $pStatus, $fStatus, $amtPaid, $balDue, $oid);
                $uStmt->execute();
                $uStmt->close();
            }
        }
    }
}

echo "Migration completed successfully!\n";
