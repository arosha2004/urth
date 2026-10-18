<?php
/**
 * URTH Admin Panel - Orders Action Handler
 */

ob_start();

require_once __DIR__ . '/auth.php';
require_admin_auth();
require_once '../config.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$order_id = (int)($_POST['order_id'] ?? $_GET['order_id'] ?? 0);

switch ($action) {
    case 'update_status':
        if (!validate_csrf_token()) {
            header('Location: orders.php?msg=error_csrf');
            exit;
        }

        $new_status = trim($_POST['status'] ?? 'Pending');
        $allowed_statuses = ['Pending', 'Processing', 'Completed', 'Cancelled'];

        if (!in_array($new_status, $allowed_statuses, true) || $order_id <= 0) {
            header('Location: orders.php?msg=error_invalid');
            exit;
        }

        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("si", $new_status, $order_id);
            if ($stmt->execute()) {
                header('Location: orders.php?msg=status_updated');
                exit;
            }
        }

        header('Location: orders.php?msg=error_db');
        exit;

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
