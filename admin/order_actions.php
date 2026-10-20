<?php
/**
 * URTH Admin Panel - Orders & Enquiries Action Handler
 * Handles: Quotes, Payments, Deposits, Balances, Deliverables, Couriers, Bookings, Notes, Cancellations & Refunds
 * Records staff member attribution in order_activity_logs
 */

ob_start();

require_once __DIR__ . '/auth.php';
require_admin_auth();
require_once '../config.php';
require_once __DIR__ . '/../app/Core/private_storage.php';
require_once __DIR__ . '/../app/Core/shop_mail.php';

$staff_username = $_SESSION['admin_username'] ?? 'urthadmin';
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$order_id = (int)($_POST['order_id'] ?? $_GET['order_id'] ?? 0);

$allowed_doc_exts = ['pdf', 'png', 'jpg', 'jpeg', 'webp', 'zip', 'rar', 'brushset', 'dwg', 'dxf', 'rfa', 'blend', 'epub'];

// Helper function to log audit trail activity
function log_order_activity($conn, int $order_id, string $staff, string $action_type, ?string $prev_val, ?string $new_val, ?string $note): void {
    $stmt = $conn->prepare("INSERT INTO order_activity_logs (order_id, staff_username, action_type, previous_value, new_value, note) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("isssss", $order_id, $staff, $action_type, $prev_val, $new_val, $note);
        $stmt->execute();
        $stmt->close();
    }
}

// Fetch current order helper
function get_order_by_id($conn, int $id): ?array {
    $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $order = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    return $order;
}

switch ($action) {

    // ==========================================
    // 1. RECORD PAYMENT / DEPOSIT / BALANCE
    // ==========================================
    case 'record_payment':
        if (!validate_csrf_token()) {
            header('Location: orders.php?msg=error_csrf');
            exit;
        }

        if ($order_id <= 0) {
            header('Location: orders.php?msg=error_invalid');
            exit;
        }

        $order = get_order_by_id($conn, $order_id);
        if (!$order) {
            header('Location: orders.php?msg=error_invalid');
            exit;
        }

        $payment_type = trim($_POST['payment_type'] ?? 'deposit'); // deposit, balance, full, custom
        $amount = (float)($_POST['payment_amount'] ?? 0);
        $payment_method = trim($_POST['payment_method'] ?? 'Bank Transfer (BACS)');
        $transaction_ref = trim($_POST['transaction_reference'] ?? '');
        $note = trim($_POST['payment_note'] ?? '');

        if ($amount <= 0) {
            header('Location: orders.php?msg=error_invalid_amount');
            exit;
        }

        $currTotal = (float)$order['total'];
        $currPaid = (float)$order['amount_paid'];
        $currDepositPaid = (float)$order['deposit_paid'];

        $newPaid = round($currPaid + $amount, 2);
        $newBalance = max(0.00, round($currTotal - $newPaid, 2));

        $newDepositPaid = $currDepositPaid;
        if ($payment_type === 'deposit') {
            $newDepositPaid = round($currDepositPaid + $amount, 2);
        }

        // Determine new payment status
        $prevPaymentStatus = $order['payment_status'] ?? 'pending_payment';
        if ($newBalance <= 0.001) {
            $newPaymentStatus = 'fully_paid';
        } elseif ($newDepositPaid > 0 && $newBalance > 0) {
            $newPaymentStatus = 'deposit_paid';
        } else {
            $newPaymentStatus = 'balance_pending';
        }

        // Handle receipt upload if provided
        $receipt_url = $order['receipt_url'];
        if (isset($_FILES['receipt_file']) && $_FILES['receipt_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadError = null;
            $storedReceipt = store_private_upload($_FILES['receipt_file'], 'receipts', ['pdf', 'png', 'jpg', 'jpeg', 'webp'], 10 * 1024 * 1024, $uploadError);
            if ($storedReceipt === null) {
                header('Location: orders.php?msg=' . urlencode($uploadError ?? 'error_upload'));
                exit;
            }
            $receipt_url = $storedReceipt;
        }

        $now = date('Y-m-d H:i:s');
        $stmt = $conn->prepare("UPDATE orders SET 
            amount_paid = ?, deposit_paid = ?, balance_due = ?, payment_status = ?,
            payment_method = ?, transaction_reference = ?, receipt_url = ?,
            payment_confirmed_by = ?, payment_confirmed_at = ?, status_changed_by = ?
            WHERE id = ?");

        if ($stmt) {
            $stmt->bind_param("dddsssssssi", 
                $newPaid, $newDepositPaid, $newBalance, $newPaymentStatus,
                $payment_method, $transaction_ref, $receipt_url,
                $staff_username, $now, $staff_username,
                $order_id
            );
            $stmt->execute();
            $stmt->close();

            // Log activity
            $logNote = "Payment of {$order['currency']}" . number_format($amount, 2) . " confirmed via {$payment_method} (Ref: {$transaction_ref}). Balance due: {$order['currency']}" . number_format($newBalance, 2);
            if (!empty($note)) $logNote .= " — Note: " . $note;

            log_order_activity($conn, $order_id, $staff_username, 'payment_confirmed', $prevPaymentStatus, $newPaymentStatus, $logNote);

            header('Location: orders.php?msg=payment_recorded');
            exit;
        }

        header('Location: orders.php?msg=error_db');
        exit;

    // ==========================================
    // 2. UPDATE PAYMENT STATUS SEPARATELY
    // ==========================================
    case 'update_payment_status':
        if (!validate_csrf_token()) {
            header('Location: orders.php?msg=error_csrf');
            exit;
        }

        $new_payment_status = trim($_POST['payment_status'] ?? '');
        $allowed = ['pending_payment', 'deposit_paid', 'balance_pending', 'fully_paid', 'refund_pending', 'partially_refunded', 'refunded', 'cancelled'];

        if (!in_array($new_payment_status, $allowed, true) || $order_id <= 0) {
            header('Location: orders.php?msg=error_invalid');
            exit;
        }

        $order = get_order_by_id($conn, $order_id);
        if (!$order) {
            header('Location: orders.php?msg=error_invalid');
            exit;
        }

        $prev = $order['payment_status'];
        $now = date('Y-m-d H:i:s');
        $note = trim($_POST['status_note'] ?? '');

        // If explicitly set to fully_paid, zero the balance due and set amount_paid to total
        $amtPaid = (float)$order['amount_paid'];
        $balDue = (float)$order['balance_due'];
        if ($new_payment_status === 'fully_paid') {
            $amtPaid = (float)$order['total'];
            $balDue = 0.00;
        }

        $stmt = $conn->prepare("UPDATE orders SET payment_status = ?, amount_paid = ?, balance_due = ?, status_changed_by = ?, payment_confirmed_by = ?, payment_confirmed_at = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("sddsssi", $new_payment_status, $amtPaid, $balDue, $staff_username, $staff_username, $now, $order_id);
            $stmt->execute();
            $stmt->close();

            log_order_activity($conn, $order_id, $staff_username, 'payment_status_changed', $prev, $new_payment_status, "Staff changed payment status to {$new_payment_status}. " . $note);

            header('Location: orders.php?msg=payment_status_updated');
            exit;
        }

        header('Location: orders.php?msg=error_db');
        exit;

    // ==========================================
    // 3. UPDATE FULFILMENT STATUS SEPARATELY
    // ==========================================
    case 'update_fulfilment_status':
        if (!validate_csrf_token()) {
            header('Location: orders.php?msg=error_csrf');
            exit;
        }

        $new_fulfilment_status = trim($_POST['fulfilment_status'] ?? '');
        $allowed = ['enquiry', 'quote_prepared', 'quote_sent', 'quote_accepted', 'in_queue', 'in_production', 'booking_scheduled', 'digital_ready', 'dispatched', 'completed', 'cancelled'];

        if (!in_array($new_fulfilment_status, $allowed, true) || $order_id <= 0) {
            header('Location: orders.php?msg=error_invalid');
            exit;
        }

        $order = get_order_by_id($conn, $order_id);
        if (!$order) {
            header('Location: orders.php?msg=error_invalid');
            exit;
        }

        $prev = $order['fulfilment_status'];
        $progress = isset($_POST['fulfilment_progress']) ? max(0, min(100, (int)$_POST['fulfilment_progress'])) : (int)$order['fulfilment_progress'];
        $note = trim($_POST['fulfilment_note'] ?? '');

        // Auto calculate sensible progress if switching to completed
        if ($new_fulfilment_status === 'completed') {
            $progress = 100;
        } elseif ($new_fulfilment_status === 'dispatched') {
            $progress = max(80, $progress);
        } elseif ($new_fulfilment_status === 'in_production') {
            $progress = max(40, $progress);
        }

        $stmt = $conn->prepare("UPDATE orders SET fulfilment_status = ?, fulfilment_progress = ?, status_changed_by = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("sisi", $new_fulfilment_status, $progress, $staff_username, $order_id);
            $stmt->execute();
            $stmt->close();

            log_order_activity($conn, $order_id, $staff_username, 'fulfilment_status_changed', $prev, $new_fulfilment_status, "Fulfilment progress: {$progress}%. " . $note);

            header('Location: orders.php?msg=fulfilment_updated');
            exit;
        }

        header('Location: orders.php?msg=error_db');
        exit;

    // ==========================================
    // 4. PREPARE & SAVE QUOTE
    // ==========================================
    case 'save_quote':
        if (!validate_csrf_token()) {
            header('Location: orders.php?msg=error_csrf');
            exit;
        }

        if ($order_id <= 0) {
            header('Location: orders.php?msg=error_invalid');
            exit;
        }

        $order = get_order_by_id($conn, $order_id);
        if (!$order) {
            header('Location: orders.php?msg=error_invalid');
            exit;
        }

        $itemsJson = trim($_POST['quote_items_json'] ?? '[]');
        $parsedItems = json_decode($itemsJson, true);
        if (!is_array($parsedItems) || empty($parsedItems)) {
            // fallback: check if single title & rate provided
            $qTitle = trim($_POST['quote_item_title'] ?? 'Custom Architecture / Furniture Scope');
            $qRate = (float)($_POST['quote_item_rate'] ?? 0);
            $parsedItems = [
                ['title' => $qTitle, 'qty' => 1, 'rate' => $qRate, 'amount' => $qRate]
            ];
        }

        $subtotal = 0;
        foreach ($parsedItems as $it) {
            $subtotal += (float)($it['amount'] ?? ($it['qty'] * $it['rate']));
        }

        $shipping_cost = (float)($_POST['quote_shipping_cost'] ?? 0);
        $total = $subtotal + $shipping_cost;

        $deposit_pct = max(10, min(100, (int)($_POST['quote_deposit_percentage'] ?? 50)));
        $deposit_required = round($total * ($deposit_pct / 100), 2);
        $validity_days = max(7, (int)($_POST['quote_validity_days'] ?? 30));
        $terms = trim($_POST['quote_terms'] ?? '50% initial commitment deposit to commence craft production; 50% upon final inspection.');

        $quoteDetails = json_encode([
            'items' => $parsedItems,
            'deposit_percentage' => $deposit_pct,
            'validity_days' => $validity_days,
            'terms' => $terms,
            'prepared_by' => $staff_username,
            'prepared_at' => date('Y-m-d H:i:s')
        ]);

        $mark_sent = isset($_POST['mark_quote_sent']) ? 1 : 0;
        $now = date('Y-m-d H:i:s');
        $newFulfilment = $mark_sent ? 'quote_sent' : 'quote_prepared';
        $quote_sent_at = $mark_sent ? $now : $order['quote_sent_at'];

        $currPaid = (float)$order['amount_paid'];
        $balance_due = max(0.00, round($total - $currPaid, 2));

        $stmt = $conn->prepare("UPDATE orders SET 
            subtotal = ?, shipping_cost = ?, total = ?, deposit_required = ?, balance_due = ?,
            quote_details = ?, quote_sent_at = ?, fulfilment_status = ?, status_changed_by = ?
            WHERE id = ?");

        if ($stmt) {
            $stmt->bind_param("dddddssssi", 
                $subtotal, $shipping_cost, $total, $deposit_required, $balance_due,
                $quoteDetails, $quote_sent_at, $newFulfilment, $staff_username,
                $order_id
            );
            $stmt->execute();
            $stmt->close();

            $logNote = "Prepared official quote for {$order['currency']}" . number_format($total, 2) . " with {$deposit_pct}% deposit required ({$order['currency']}" . number_format($deposit_required, 2) . ").";
            if ($mark_sent) $logNote .= " Marked as Sent to Client.";

            log_order_activity($conn, $order_id, $staff_username, 'quote_prepared', $order['fulfilment_status'], $newFulfilment, $logNote);

            header('Location: orders.php?msg=quote_saved');
            exit;
        }

        header('Location: orders.php?msg=error_db');
        exit;

    // ==========================================
    // 5. RECORD CLIENT QUOTE ACCEPTANCE
    // ==========================================
    case 'record_quote_acceptance':
        if (!validate_csrf_token()) {
            header('Location: orders.php?msg=error_csrf');
            exit;
        }

        if ($order_id <= 0) {
            header('Location: orders.php?msg=error_invalid');
            exit;
        }

        $order = get_order_by_id($conn, $order_id);
        if (!$order) {
            header('Location: orders.php?msg=error_invalid');
            exit;
        }

        $acceptance_notes = trim($_POST['acceptance_notes'] ?? 'Client accepted terms via communication channel.');
        $channel = trim($_POST['acceptance_channel'] ?? 'Email');
        $now = date('Y-m-d H:i:s');
        $newFulfilment = 'quote_accepted';

        $fullNotes = "Channel: {$channel} — Notes: {$acceptance_notes}";

        $stmt = $conn->prepare("UPDATE orders SET 
            quote_accepted_at = ?, quote_accepted_by = ?, quote_accepted_notes = ?,
            fulfilment_status = ?, status_changed_by = ?
            WHERE id = ?");

        if ($stmt) {
            $stmt->bind_param("sssssi", $now, $staff_username, $fullNotes, $newFulfilment, $staff_username, $order_id);
            $stmt->execute();
            $stmt->close();

            log_order_activity($conn, $order_id, $staff_username, 'quote_accepted', $order['fulfilment_status'], $newFulfilment, "Customer acceptance recorded by {$staff_username}. {$fullNotes}");

            header('Location: orders.php?msg=quote_accepted');
            exit;
        }

        header('Location: orders.php?msg=error_db');
        exit;

    // ==========================================
    // 6. DISPATCH, COURIER & TRACKING DETAILS
    // ==========================================
    case 'update_dispatch':
        if (!validate_csrf_token()) {
            header('Location: orders.php?msg=error_csrf');
            exit;
        }

        if ($order_id <= 0) {
            header('Location: orders.php?msg=error_invalid');
            exit;
        }

        $order = get_order_by_id($conn, $order_id);
        if (!$order) {
            header('Location: orders.php?msg=error_invalid');
            exit;
        }

        $courier_name = trim($_POST['courier_name'] ?? '');
        $tracking_number = trim($_POST['tracking_number'] ?? '');
        $tracking_url = trim($_POST['tracking_url'] ?? '');
        $estimated_delivery = trim($_POST['estimated_delivery'] ?? '');
        $mark_dispatched = isset($_POST['mark_dispatched']) ? 1 : 0;

        // Auto-generate standard tracking URL if courier matches and URL empty
        if (empty($tracking_url) && !empty($tracking_number)) {
            $cLower = strtolower($courier_name);
            if (strpos($cLower, 'dhl') !== false) {
                $tracking_url = 'https://track.dhl.com/en?tracking-id=' . urlencode($tracking_number);
            } elseif (strpos($cLower, 'dpd') !== false) {
                $tracking_url = 'https://www.dpd.co.uk/tracking/' . urlencode($tracking_number);
            } elseif (strpos($cLower, 'royal') !== false) {
                $tracking_url = 'https://www.royalmail.com/track-your-item#/tracking-results/' . urlencode($tracking_number);
            } elseif (strpos($cLower, 'fedex') !== false) {
                $tracking_url = 'https://www.fedex.com/fedextrack/?trknbr=' . urlencode($tracking_number);
            }
        }

        $now = date('Y-m-d H:i:s');
        $dispatched_at = $mark_dispatched ? $now : $order['dispatched_at'];
        $newFulfilment = $mark_dispatched ? 'dispatched' : $order['fulfilment_status'];
        $progress = $mark_dispatched ? max(85, (int)$order['fulfilment_progress']) : (int)$order['fulfilment_progress'];

        $stmt = $conn->prepare("UPDATE orders SET 
            courier_name = ?, tracking_number = ?, tracking_url = ?, estimated_delivery = ?,
            dispatched_at = ?, fulfilment_status = ?, fulfilment_progress = ?, status_changed_by = ?
            WHERE id = ?");

        if ($stmt) {
            $stmt->bind_param("ssssssisi", 
                $courier_name, $tracking_number, $tracking_url, $estimated_delivery,
                $dispatched_at, $newFulfilment, $progress, $staff_username,
                $order_id
            );
            $stmt->execute();
            $stmt->close();

            $logNote = "Logistics updated: Courier: {$courier_name}, Tracking #: {$tracking_number}. Est. delivery: {$estimated_delivery}.";
            if ($mark_dispatched) $logNote .= " Marked as Dispatched.";

            log_order_activity($conn, $order_id, $staff_username, 'courier_updated', $order['fulfilment_status'], $newFulfilment, $logNote);

            header('Location: orders.php?msg=dispatch_updated');
            exit;
        }

        header('Location: orders.php?msg=error_db');
        exit;

    // ==========================================
    // 7. MANAGE BOOKING DATES & SESSIONS
    // ==========================================
    case 'update_booking':
        if (!validate_csrf_token()) {
            header('Location: orders.php?msg=error_csrf');
            exit;
        }

        if ($order_id <= 0) {
            header('Location: orders.php?msg=error_invalid');
            exit;
        }

        $order = get_order_by_id($conn, $order_id);
        if (!$order) {
            header('Location: orders.php?msg=error_invalid');
            exit;
        }

        $raw_booking_date = trim($_POST['booking_date'] ?? '');
        $booking_date = !empty($raw_booking_date) ? date('Y-m-d H:i:s', strtotime($raw_booking_date)) : null;
        $booking_meeting_link = trim($_POST['booking_meeting_link'] ?? '');
        $mark_scheduled = isset($_POST['mark_scheduled']) ? 1 : 0;

        $newFulfilment = $mark_scheduled ? 'booking_scheduled' : $order['fulfilment_status'];
        $progress = $mark_scheduled ? max(60, (int)$order['fulfilment_progress']) : (int)$order['fulfilment_progress'];

        $stmt = $conn->prepare("UPDATE orders SET 
            booking_date = ?, booking_meeting_link = ?, fulfilment_status = ?, fulfilment_progress = ?, status_changed_by = ?
            WHERE id = ?");

        if ($stmt) {
            $stmt->bind_param("sssisi", $booking_date, $booking_meeting_link, $newFulfilment, $progress, $staff_username, $order_id);
            $stmt->execute();
            $stmt->close();

            $logNote = "Booking schedule set for {$booking_date}. Room: {$booking_meeting_link}.";
            log_order_activity($conn, $order_id, $staff_username, 'booking_set', $order['fulfilment_status'], $newFulfilment, $logNote);

            header('Location: orders.php?msg=booking_updated');
            exit;
        }

        header('Location: orders.php?msg=error_db');
        exit;

    // ==========================================
    // 8. DIGITAL DELIVERABLES & ACCESS LINKS
    // ==========================================
    case 'update_deliverable':
        if (!validate_csrf_token()) {
            header('Location: orders.php?msg=error_csrf');
            exit;
        }

        if ($order_id <= 0) {
            header('Location: orders.php?msg=error_invalid');
            exit;
        }

        $order = get_order_by_id($conn, $order_id);
        if (!$order) {
            header('Location: orders.php?msg=error_invalid');
            exit;
        }

        $digital_access_link = trim($_POST['digital_access_link'] ?? '');
        $digital_deliverable_file = $order['digital_deliverable_file'];

        if (isset($_FILES['deliverable_file']) && $_FILES['deliverable_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadError = null;
            $storedDeliverable = store_private_upload($_FILES['deliverable_file'], 'deliverables', $allowed_doc_exts, 100 * 1024 * 1024, $uploadError);
            if ($storedDeliverable === null) {
                header('Location: orders.php?msg=' . urlencode($uploadError ?? 'error_upload'));
                exit;
            }
            $digital_deliverable_file = $storedDeliverable;
        }

        $mark_ready = isset($_POST['mark_digital_ready']) ? 1 : 0;
        $newFulfilment = $mark_ready ? 'digital_ready' : $order['fulfilment_status'];
        $progress = $mark_ready ? 100 : (int)$order['fulfilment_progress'];

        $stmt = $conn->prepare("UPDATE orders SET 
            digital_deliverable_file = ?, digital_access_link = ?, fulfilment_status = ?, fulfilment_progress = ?, status_changed_by = ?
            WHERE id = ?");

        if ($stmt) {
            $stmt->bind_param("sssisi", $digital_deliverable_file, $digital_access_link, $newFulfilment, $progress, $staff_username, $order_id);
            $stmt->execute();
            $stmt->close();

            $logNote = "Digital deliverable updated. Access Link: {$digital_access_link}. Deliverable file: {$digital_deliverable_file}.";
            log_order_activity($conn, $order_id, $staff_username, 'deliverable_assigned', $order['fulfilment_status'], $newFulfilment, $logNote);

            header('Location: orders.php?msg=deliverable_updated');
            exit;
        }

        header('Location: orders.php?msg=error_db');
        exit;

    // ==========================================
    // 9. INTERNAL NOTES
    // ==========================================
    case 'add_internal_note':
        if (!validate_csrf_token()) {
            header('Location: orders.php?msg=error_csrf');
            exit;
        }

        $note_text = trim($_POST['internal_note_text'] ?? '');
        if (empty($note_text) || $order_id <= 0) {
            header('Location: orders.php?msg=error_missing');
            exit;
        }

        $order = get_order_by_id($conn, $order_id);
        if (!$order) {
            header('Location: orders.php?msg=error_invalid');
            exit;
        }

        $existingNotes = [];
        if (!empty($order['internal_notes'])) {
            $decoded = json_decode($order['internal_notes'], true);
            if (is_array($decoded)) $existingNotes = $decoded;
        }

        $newEntry = [
            'id' => time() . '_' . rand(100, 999),
            'time' => date('Y-m-d H:i'),
            'staff' => $staff_username,
            'note' => $note_text
        ];
        $existingNotes[] = $newEntry;
        $notesJson = json_encode($existingNotes);

        $stmt = $conn->prepare("UPDATE orders SET internal_notes = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("si", $notesJson, $order_id);
            $stmt->execute();
            $stmt->close();

            log_order_activity($conn, $order_id, $staff_username, 'internal_note', null, null, "Staff added internal note: " . substr($note_text, 0, 100));

            header('Location: orders.php?msg=note_added');
            exit;
        }

        header('Location: orders.php?msg=error_db');
        exit;

    // ==========================================
    // 10. CUSTOMER COMMUNICATION LOG
    // ==========================================
    case 'send_customer_update':
        if (!validate_csrf_token()) {
            header('Location: orders.php?msg=error_csrf');
            exit;
        }

        $title = trim($_POST['update_title'] ?? 'Order Status Update');
        $message = trim($_POST['update_message'] ?? '');
        $channel = trim($_POST['update_channel'] ?? 'Email');
        if (!in_array($channel, ['Email', 'WhatsApp', 'Phone'], true)) {
            header('Location: orders.php?msg=error_invalid');
            exit;
        }

        if (empty($message) || $order_id <= 0) {
            header('Location: orders.php?msg=error_missing');
            exit;
        }

        $order = get_order_by_id($conn, $order_id);
        if (!$order) {
            header('Location: orders.php?msg=error_invalid');
            exit;
        }

        $emailSent = true;
        $emailAttempted = $channel === 'Email';
        if ($emailAttempted) {
            if (empty($order['customer_email']) || !filter_var($order['customer_email'], FILTER_VALIDATE_EMAIL)) {
                header('Location: orders.php?msg=error_email_missing');
                exit;
            }
            try {
                $emailSent = send_shop_email($conn, $order['customer_email'], $title, $message);
            } catch (RuntimeException $e) {
                error_log('URTH customer email could not be recorded: ' . $e->getMessage());
                header('Location: orders.php?msg=error_email_queue');
                exit;
            }
        }

        $existingUpdates = [];
        if (!empty($order['customer_updates'])) {
            $decoded = json_decode($order['customer_updates'], true);
            if (is_array($decoded)) $existingUpdates = $decoded;
        }

        $newEntry = [
            'id' => time() . '_' . rand(100, 999),
            'time' => date('Y-m-d H:i'),
            'staff' => $staff_username,
            'title' => $title,
            'message' => $message,
            'channel' => $channel
        ];
        $existingUpdates[] = $newEntry;
        $updatesJson = json_encode($existingUpdates);

        $stmt = $conn->prepare("UPDATE orders SET customer_updates = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("si", $updatesJson, $order_id);
            $stmt->execute();
            $stmt->close();

            log_order_activity($conn, $order_id, $staff_username, 'customer_update', null, null, "Logged customer notification via {$channel}: {$title}");

            header('Location: orders.php?msg=' . ($emailAttempted && !$emailSent ? 'email_failed' : 'update_logged'));
            exit;
        }

        header('Location: orders.php?msg=error_db');
        exit;

    // ==========================================
    // 11. RECORD CANCELLATION & PENDING REFUND
    // ==========================================
    case 'record_cancellation':
        if (!validate_csrf_token()) {
            header('Location: orders.php?msg=error_csrf');
            exit;
        }

        if ($order_id <= 0) {
            header('Location: orders.php?msg=error_invalid');
            exit;
        }

        $order = get_order_by_id($conn, $order_id);
        if (!$order) {
            header('Location: orders.php?msg=error_invalid');
            exit;
        }

        $reason = trim($_POST['cancellation_reason'] ?? 'Customer request');
        $request_refund = isset($_POST['request_refund']) ? 1 : 0;
        $refund_amount = (float)($_POST['refund_amount'] ?? $order['amount_paid']);
        $refund_reason = trim($_POST['refund_reason'] ?? $reason);

        $now = date('Y-m-d H:i:s');
        $newFulfilment = 'cancelled';
        $newPaymentStatus = $order['payment_status'];
        $newRefundStatus = 'none';

        if ($request_refund && $refund_amount > 0) {
            $newPaymentStatus = 'refund_pending';
            $newRefundStatus = 'pending_action';
        } else {
            $newPaymentStatus = 'cancelled';
        }

        $stmt = $conn->prepare("UPDATE orders SET 
            fulfilment_status = ?, payment_status = ?, cancellation_reason = ?, cancelled_by = ?, cancelled_at = ?,
            refund_amount = ?, refund_status = ?, refund_reason = ?, status_changed_by = ?
            WHERE id = ?");

        if ($stmt) {
            $stmt->bind_param("sssssdsssi", 
                $newFulfilment, $newPaymentStatus, $reason, $staff_username, $now,
                $refund_amount, $newRefundStatus, $refund_reason, $staff_username,
                $order_id
            );
            $stmt->execute();
            $stmt->close();

            $logNote = "Order cancelled by {$staff_username}. Reason: {$reason}.";
            if ($request_refund) {
                $logNote .= " Marked pending refund action for {$order['currency']}" . number_format($refund_amount, 2) . ".";
            }

            log_order_activity($conn, $order_id, $staff_username, 'order_cancelled', $order['fulfilment_status'], 'cancelled', $logNote);

            header('Location: orders.php?msg=cancelled');
            exit;
        }

        header('Location: orders.php?msg=error_db');
        exit;

    // ==========================================
    // 12. PROCESS / RECORD COMPLETED REFUND
    // ==========================================
    case 'process_refund':
        if (!validate_csrf_token()) {
            header('Location: orders.php?msg=error_csrf');
            exit;
        }

        if ($order_id <= 0) {
            header('Location: orders.php?msg=error_invalid');
            exit;
        }

        $order = get_order_by_id($conn, $order_id);
        if (!$order) {
            header('Location: orders.php?msg=error_invalid');
            exit;
        }

        $refund_amount = (float)($_POST['processed_refund_amount'] ?? $order['refund_amount']);
        $refund_method = trim($_POST['refund_method'] ?? 'Original Payment Method');
        $refund_ref = trim($_POST['refund_reference'] ?? '');
        $refund_notes = trim($_POST['refund_notes'] ?? '');

        if ($refund_amount <= 0) {
            header('Location: orders.php?msg=error_invalid_amount');
            exit;
        }

        $now = date('Y-m-d H:i:s');
        $currPaid = (float)$order['amount_paid'];
        $newPaymentStatus = ($refund_amount >= $currPaid && $currPaid > 0) ? 'refunded' : 'partially_refunded';

        $stmt = $conn->prepare("UPDATE orders SET 
            payment_status = ?, refund_amount = ?, refund_status = 'processed',
            refund_reference = ?, refund_processed_by = ?, refund_processed_at = ?, status_changed_by = ?
            WHERE id = ?");

        if ($stmt) {
            $stmt->bind_param("sdssssi", $newPaymentStatus, $refund_amount, $refund_ref, $staff_username, $now, $staff_username, $order_id);
            $stmt->execute();
            $stmt->close();

            $logNote = "Refund of {$order['currency']}" . number_format($refund_amount, 2) . " processed by {$staff_username} via {$refund_method} (Ref: {$refund_ref}). " . $refund_notes;
            log_order_activity($conn, $order_id, $staff_username, 'refund_processed', $order['payment_status'], $newPaymentStatus, $logNote);

            header('Location: orders.php?msg=refund_processed');
            exit;
        }

        header('Location: orders.php?msg=error_db');
        exit;

    // ==========================================
    // 13. CREATE MANUAL ORDER / ENQUIRY
    // ==========================================
    case 'create_manual_order':
        if (!validate_csrf_token()) {
            header('Location: orders.php?msg=error_csrf');
            exit;
        }

        $order_type = trim($_POST['order_type'] ?? 'order');
        if (!in_array($order_type, ['order', 'custom_enquiry', 'quote_request'])) {
            $order_type = 'order';
        }

        $customer_name = trim($_POST['customer_name'] ?? '');
        $customer_phone = trim($_POST['customer_phone'] ?? '');
        $customer_email = trim($_POST['customer_email'] ?? '');
        $delivery_address = trim($_POST['delivery_address'] ?? '');
        $order_notes = trim($_POST['order_notes'] ?? '');

        $item_name = trim($_POST['item_name'] ?? 'Custom Project Scope');
        $item_sku = trim($_POST['item_sku'] ?? 'MANUAL-01');
        $item_price = (float)($_POST['item_price'] ?? 0);
        $item_qty = max(1, (int)($_POST['item_qty'] ?? 1));
        $shipping_cost = (float)($_POST['shipping_cost'] ?? 0);

        if (empty($customer_name) || empty($customer_phone)) {
            header('Location: orders.php?msg=error_missing');
            exit;
        }

        $subtotal = $item_price * $item_qty;
        $total = $subtotal + $shipping_cost;
        $currency = '£';

        $prefix = $order_type === 'custom_enquiry' ? 'ENQ' : 'URTH';
        $order_number = $prefix . '-' . date('ymd') . '-' . strtoupper(substr(md5(uniqid('', true)), 0, 4));

        $payment_status = 'pending_payment';
        $fulfilment_status = $order_type === 'custom_enquiry' ? 'enquiry' : 'in_queue';
        $balance_due = $total;

        $stmt = $conn->prepare("INSERT INTO orders (
            order_number, order_type, customer_name, customer_email, customer_phone, delivery_address,
            order_notes, shipping_method, subtotal, shipping_cost, total, currency,
            payment_status, fulfilment_status, amount_paid, balance_due, status_changed_by
        ) VALUES (
            ?, ?, ?, ?, ?, ?,
            ?, 'Studio Standard', ?, ?, ?, ?,
            ?, ?, 0.00, ?, ?
        )");

        if ($stmt) {
            $stmt->bind_param("sssssssdddsssds", 
                $order_number, $order_type, $customer_name, $customer_email, $customer_phone, $delivery_address,
                $order_notes, $subtotal, $shipping_cost, $total, $currency,
                $payment_status, $fulfilment_status, $balance_due, $staff_username
            );
            $stmt->execute();
            $newOrderId = $conn->insert_id;
            $stmt->close();

            // Insert item
            $iStmt = $conn->prepare("INSERT INTO order_items (order_id, product_name, product_sku, price, quantity, total) VALUES (?, ?, ?, ?, ?, ?)");
            if ($iStmt) {
                $iStmt->bind_param("issdid", $newOrderId, $item_name, $item_sku, $item_price, $item_qty, $subtotal);
                $iStmt->execute();
                $iStmt->close();
            }

            log_order_activity($conn, $newOrderId, $staff_username, 'order_created', null, $fulfilment_status, "Staff {$staff_username} created manual {$order_type} for {$customer_name}. Total: £" . number_format($total, 2));

            header('Location: orders.php?msg=order_created');
            exit;
        }

        header('Location: orders.php?msg=error_db');
        exit;

    // ==========================================
    // 14. DELETE ORDER
    // ==========================================
    case 'delete':
        if (!validate_csrf_token()) {
            header('Location: orders.php?msg=error_csrf');
            exit;
        }

        if ($order_id <= 0) {
            header('Location: orders.php?msg=error_invalid');
            exit;
        }

        $stmt = $conn->prepare("DELETE FROM orders WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $order_id);
            if ($stmt->execute()) {
                @$conn->query("DELETE FROM order_items WHERE order_id = " . $order_id);
                @$conn->query("DELETE FROM order_activity_logs WHERE order_id = " . $order_id);
                header('Location: orders.php?msg=deleted');
                exit;
            }
        }

        header('Location: orders.php?msg=error_db');
        exit;

    default:
        header('Location: orders.php');
        exit;
}
