<?php
/**
 * URTH Admin Panel - Partner Actions Handler (Add, Edit, Delete)
 */

require_once __DIR__ . '/auth.php';
require_admin_auth();
require_once '../config.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$upload_dir = '../img/partners/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Allowed image mime types and extensions
$allowed_exts = ['svg', 'png', 'jpg', 'jpeg', 'webp', 'avif'];

switch ($action) {
    case 'add':
        $name = trim($_POST['name'] ?? '');
        $website_url = trim($_POST['website_url'] ?? '');
        $display_order = (int)($_POST['display_order'] ?? 0);
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if (empty($name)) {
            header('Location: partners.php?msg=error_missing');
            exit;
        }

        if (!isset($_FILES['logo_image']) || $_FILES['logo_image']['error'] !== UPLOAD_ERR_OK) {
            header('Location: partners.php?msg=error_file');
            exit;
        }

        $file_info = pathinfo($_FILES['logo_image']['name']);
        $ext = strtolower($file_info['extension'] ?? '');

        if (!in_array($ext, $allowed_exts)) {
            header('Location: partners.php?msg=error_filetype');
            exit;
        }

        $safe_name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $file_info['filename']);
        $filename = 'partner_' . time() . '_' . substr($safe_name, 0, 30) . '.' . $ext;
        $target_path = $upload_dir . $filename;

        if (move_uploaded_file($_FILES['logo_image']['tmp_name'], $target_path)) {
            $logo_url = 'img/partners/' . $filename;

            $stmt = $conn->prepare("INSERT INTO partners (name, logo_url, website_url, display_order, is_active) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssii", $name, $logo_url, $website_url, $display_order, $is_active);
            if ($stmt->execute()) {
                header('Location: partners.php?msg=added');
                exit;
            } else {
                header('Location: partners.php?msg=error_db');
                exit;
            }
        } else {
            header('Location: partners.php?msg=error_upload');
            exit;
        }
        break;

    case 'edit':
        $id = (int)($_POST['partner_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $website_url = trim($_POST['website_url'] ?? '');
        $display_order = (int)($_POST['display_order'] ?? 0);
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if ($id <= 0 || empty($name)) {
            header('Location: partners.php?msg=error_missing');
            exit;
        }

        // Check if new image uploaded
        if (isset($_FILES['logo_image']) && $_FILES['logo_image']['error'] === UPLOAD_ERR_OK) {
            $file_info = pathinfo($_FILES['logo_image']['name']);
            $ext = strtolower($file_info['extension'] ?? '');

            if (!in_array($ext, $allowed_exts)) {
                header('Location: partners.php?msg=error_filetype');
                exit;
            }

            $safe_name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $file_info['filename']);
            $filename = 'partner_' . time() . '_' . substr($safe_name, 0, 30) . '.' . $ext;
            $target_path = $upload_dir . $filename;

            if (move_uploaded_file($_FILES['logo_image']['tmp_name'], $target_path)) {
                $new_logo_url = 'img/partners/' . $filename;
                $stmt = $conn->prepare("UPDATE partners SET name = ?, logo_url = ?, website_url = ?, display_order = ?, is_active = ? WHERE id = ?");
                $stmt->bind_param("sssiii", $name, $new_logo_url, $website_url, $display_order, $is_active, $id);
            } else {
                header('Location: partners.php?msg=error_upload');
                exit;
            }
        } else {
            // Update without changing image
            $stmt = $conn->prepare("UPDATE partners SET name = ?, website_url = ?, display_order = ?, is_active = ? WHERE id = ?");
            $stmt->bind_param("ssiii", $name, $website_url, $display_order, $is_active, $id);
        }

        if ($stmt->execute()) {
            header('Location: partners.php?msg=updated');
            exit;
        } else {
            header('Location: partners.php?msg=error_db');
            exit;
        }
        break;

    case 'delete':
        $id = (int)($_POST['partner_id'] ?? $_GET['partner_id'] ?? $_POST['id'] ?? $_GET['id'] ?? 0);
        if ($id <= 0) {
            header('Location: partners.php?msg=error_invalid');
            exit;
        }

        // Fetch logo_url to clean up custom uploaded file if needed
        $stmt = $conn->prepare("SELECT logo_url FROM partners WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $file_to_delete = '../' . $row['logo_url'];
            // Only delete if it starts with img/partners/partner_
            if (file_exists($file_to_delete) && strpos($row['logo_url'], 'img/partners/partner_') === 0) {
                @unlink($file_to_delete);
            }
        }

        $del_stmt = $conn->prepare("DELETE FROM partners WHERE id = ?");
        $del_stmt->bind_param("i", $id);
        if ($del_stmt->execute()) {
            header('Location: partners.php?msg=deleted');
            exit;
        } else {
            header('Location: partners.php?msg=error_db');
            exit;
        }
        break;

    default:
        header('Location: partners.php');
        exit;
}
