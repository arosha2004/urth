<?php
/**
 * URTH Admin Panel - Orders CSV Exporter
 * Exports orders and custom enquiries with full financial, fulfilment & tracking metadata
 */

require_once __DIR__ . '/auth.php';
require_admin_auth();
require_once '../config.php';

// Collect filters
$typeFilter = trim($_GET['type'] ?? '');
$paymentFilter = trim($_GET['payment_status'] ?? '');
$fulfilmentFilter = trim($_GET['fulfilment_status'] ?? '');
$search = trim($_GET['search'] ?? '');

$where = ["1=1"];
$params = [];
$types = "";

if (!empty($typeFilter)) {
    $where[] = "order_type = ?";
    $params[] = $typeFilter;
    $types .= "s";
}

if (!empty($paymentFilter)) {
    $where[] = "payment_status = ?";
    $params[] = $paymentFilter;
    $types .= "s";
}

if (!empty($fulfilmentFilter)) {
    $where[] = "fulfilment_status = ?";
    $params[] = $fulfilmentFilter;
    $types .= "s";
}

if (!empty($search)) {
    $where[] = "(order_number LIKE ? OR customer_name LIKE ? OR customer_email LIKE ? OR customer_phone LIKE ? OR tracking_number LIKE ?)";
    $like = "%" . $search . "%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= "sssss";
}

$sql = "SELECT * FROM orders WHERE " . implode(" AND ", $where) . " ORDER BY id DESC";
$stmt = $conn->prepare($sql);
if (!empty($types) && $stmt) {
    $stmt->bind_param($types, ...$params);
}
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}

// Fetch order items grouped by order_id
$itemsRes = $conn->query("SELECT order_id, product_name, quantity, total FROM order_items ORDER BY id ASC");
$itemsMap = [];
if ($itemsRes) {
    while ($it = $itemsRes->fetch_assoc()) {
        $itemsMap[$it['order_id']][] = $it['quantity'] . "x " . $it['product_name'] . " (LKR " . number_format((float)$it['total'], 2) . ")";
    }
}

$filename = "urth_orders_export_" . date('Y-m-d_His') . ".csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Output UTF-8 Byte Order Mark for Excel compatibility
echo "\xEF\xBB\xBF";

$fp = fopen('php://output', 'w');

// CSV Headers
$headers = [
    'Order #',
    'Type',
    'Created At',
    'Customer Name',
    'Customer Email',
    'Customer Phone',
    'Delivery Address',
    'Items Summary',
    'Subtotal',
    'Shipping Cost',
    'Total',
    'Currency',
    'Payment Status',
    'Deposit Required',
    'Deposit Paid',
    'Amount Paid',
    'Balance Due',
    'Payment Method',
    'Transaction Ref',
    'Payment Confirmed By',
    'Payment Confirmed At',
    'Fulfilment Status',
    'Fulfilment Progress (%)',
    'Status Changed By',
    'Courier Carrier',
    'Tracking Number',
    'Tracking URL',
    'Dispatched At',
    'Est. Delivery',
    'Booking Date',
    'Meeting Link',
    'Cancellation Reason',
    'Cancelled By',
    'Refund Amount',
    'Refund Status',
    'Refund Reference',
    'Refund Processed By',
    'Customer Notes',
    'Staff Internal Notes Summary'
];

fputcsv($fp, $headers);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $oid = (int)$row['id'];
        $itemsSummary = isset($itemsMap[$oid]) ? implode("; ", $itemsMap[$oid]) : 'No items listed';

        $notesSummary = '';
        if (!empty($row['internal_notes'])) {
            $nDec = json_decode($row['internal_notes'], true);
            if (is_array($nDec)) {
                $notesList = [];
                foreach ($nDec as $n) {
                    $notesList[] = ($n['time'] ?? '') . " [" . ($n['staff'] ?? '') . "]: " . ($n['note'] ?? '');
                }
                $notesSummary = implode(" | ", $notesList);
            }
        }

        $line = [
            $row['order_number'],
            $row['order_type'] ?? 'order',
            $row['created_at'],
            $row['customer_name'],
            $row['customer_email'],
            $row['customer_phone'],
            $row['delivery_address'],
            $itemsSummary,
            number_format((float)$row['subtotal'], 2),
            number_format((float)($row['shipping_cost'] ?? 0), 2),
            number_format((float)$row['total'], 2),
            (!empty($row['currency']) && $row['currency'] !== '£' && $row['currency'] !== 'GBP') ? $row['currency'] : 'LKR',
            $row['payment_status'] ?? 'pending_payment',
            number_format((float)($row['deposit_required'] ?? 0), 2),
            number_format((float)($row['deposit_paid'] ?? 0), 2),
            number_format((float)($row['amount_paid'] ?? 0), 2),
            number_format((float)($row['balance_due'] ?? 0), 2),
            $row['payment_method'] ?? '',
            $row['transaction_reference'] ?? '',
            $row['payment_confirmed_by'] ?? '',
            $row['payment_confirmed_at'] ?? '',
            $row['fulfilment_status'] ?? 'in_queue',
            (int)($row['fulfilment_progress'] ?? 0),
            $row['status_changed_by'] ?? '',
            $row['courier_name'] ?? '',
            $row['tracking_number'] ?? '',
            $row['tracking_url'] ?? '',
            $row['dispatched_at'] ?? '',
            $row['estimated_delivery'] ?? '',
            $row['booking_date'] ?? '',
            $row['booking_meeting_link'] ?? '',
            $row['cancellation_reason'] ?? '',
            $row['cancelled_by'] ?? '',
            number_format((float)($row['refund_amount'] ?? 0), 2),
            $row['refund_status'] ?? 'none',
            $row['refund_reference'] ?? '',
            $row['refund_processed_by'] ?? '',
            $row['order_notes'] ?? '',
            $notesSummary
        ];

        fputcsv($fp, $line);
    }
}

fclose($fp);
exit;
