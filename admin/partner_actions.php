<?php
/**
 * URTH Admin Panel - Partner Actions Handler (Add, Edit, Delete)
 */

ob_start();

require_once __DIR__ . '/auth.php';
require_admin_auth();
require_once '../config.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$id = (int)($_POST['partner_id'] ?? $_POST['id'] ?? $_GET['partner_id'] ?? $_GET['id'] ?? 0);

// Check if POST data was dropped due to post_max_size exhaustion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST) && isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {
    header('Location: partners.php?msg=error_size');
    exit;
}

$upload_dir = '../img/partners/';
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
            if (isset($_FILES['logo_image']) && in_array($_FILES['logo_image']['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE])) {
                header('Location: partners.php?msg=error_size');
                exit;
            }
            header('Location: partners.php?msg=error_file');
            exit;
        }

        $file_info = pathinfo($_FILES['logo_image']['name']);
        $ext = strtolower($file_info['extension'] ?? '');

        if (!in_array($ext, $allowed_exts)) {
            header('Location: partners.php?msg=error_filetype');
            exit;
        }

        if (!is_dir($upload_dir)) {
            @mkdir($upload_dir, 0777, true);
        }

        $safe_name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $file_info['filename']);
        $filename = 'partner_' . time() . '_' . substr($safe_name, 0, 30) . '.' . $ext;
        $target_path = $upload_dir . $filename;

        if (move_uploaded_file($_FILES['logo_image']['tmp_name'], $target_path)) {
            $logo_url = 'img/partners/' . $filename;

            $name_esc = $conn->real_escape_string($name);
            $logo_esc = $conn->real_escape_string($logo_url);
            $url_esc = $conn->real_escape_string($website_url);

            $insertSql = "INSERT INTO partners (name, logo_url, website_url, display_order, is_active) VALUES ('$name_esc', '$logo_esc', '$url_esc', $display_order, $is_active)";
            if ($conn->query($insertSql)) {
                header('Location: partners.php?msg=added');
                exit;
            }

            // Prepared statement fallback
            $stmt = $conn->prepare("INSERT INTO partners (name, logo_url, website_url, display_order, is_active) VALUES (?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("sssii", $name, $logo_url, $website_url, $display_order, $is_active);
                if ($stmt->execute()) {
                    header('Location: partners.php?msg=added');
                    exit;
                }
            }

            header('Location: partners.php?msg=error_db');
            exit;
        } else {
            header('Location: partners.php?msg=error_upload');
            exit;
        }
        break;

    case 'edit':
        if ($id <= 0) {
            header('Location: partners.php?msg=error_invalid');
            exit;
        }

        $name = trim($_POST['name'] ?? '');
        $website_url = trim($_POST['website_url'] ?? '');
        $display_order = (int)($_POST['display_order'] ?? 0);
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if (empty($name)) {
            header('Location: partners.php?msg=error_missing');
            exit;
        }

        // Check if new image was uploaded
        if (isset($_FILES['logo_image']) && $_FILES['logo_image']['error'] !== UPLOAD_ERR_NO_FILE) {
            if (in_array($_FILES['logo_image']['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE])) {
                header('Location: partners.php?msg=error_size');
                exit;
            }
            if ($_FILES['logo_image']['error'] !== UPLOAD_ERR_OK) {
                header('Location: partners.php?msg=error_file');
                exit;
            }

            $file_info = pathinfo($_FILES['logo_image']['name']);
            $ext = strtolower($file_info['extension'] ?? '');

            if (!in_array($ext, $allowed_exts)) {
                header('Location: partners.php?msg=error_filetype');
                exit;
            }

            if (!is_dir($upload_dir)) {
                @mkdir($upload_dir, 0777, true);
            }

            $safe_name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $file_info['filename']);
            $filename = 'partner_' . time() . '_' . substr($safe_name, 0, 30) . '.' . $ext;
            $target_path = $upload_dir . $filename;

            if (move_uploaded_file($_FILES['logo_image']['tmp_name'], $target_path)) {
                $logo_url = 'img/partners/' . $filename;

                // Delete old custom uploaded logo if present (avoiding mysqlnd get_result dependency)
                $oldRes = $conn->query("SELECT logo_url FROM partners WHERE id = " . (int)$id);
                if ($oldRes && $row = $oldRes->fetch_assoc()) {
                    $file_to_delete = '../' . ($row['logo_url'] ?? '');
                    if (file_exists($file_to_delete) && preg_match('/^img\/partners\/partner_\d{9,}_/', $row['logo_url'] ?? '')) {
                        @unlink($file_to_delete);
                    }
                }

                $name_esc = $conn->real_escape_string($name);
                $logo_esc = $conn->real_escape_string($logo_url);
                $url_esc = $conn->real_escape_string($website_url);

                $updateSql = "UPDATE partners SET name = '$name_esc', logo_url = '$logo_esc', website_url = '$url_esc', display_order = $display_order, is_active = $is_active WHERE id = $id";
                if ($conn->query($updateSql)) {
                    header('Location: partners.php?msg=updated');
                    exit;
                }

                // Prepared fallback
                $stmt = $conn->prepare("UPDATE partners SET name = ?, logo_url = ?, website_url = ?, display_order = ?, is_active = ? WHERE id = ?");
                if ($stmt) {
                    $stmt->bind_param("sssiii", $name, $logo_url, $website_url, $display_order, $is_active, $id);
                    if ($stmt->execute()) {
                        header('Location: partners.php?msg=updated');
                        exit;
                    }
                }

                header('Location: partners.php?msg=error_db');
                exit;
            } else {
                header('Location: partners.php?msg=error_upload');
                exit;
            }
        } else {
            // Update without changing image
            $name_esc = $conn->real_escape_string($name);
            $url_esc = $conn->real_escape_string($website_url);

            $updateSql = "UPDATE partners SET name = '$name_esc', website_url = '$url_esc', display_order = $display_order, is_active = $is_active WHERE id = $id";
            if ($conn->query($updateSql)) {
                header('Location: partners.php?msg=updated');
                exit;
            }

            // Prepared fallback
            $stmt = $conn->prepare("UPDATE partners SET name = ?, website_url = ?, display_order = ?, is_active = ? WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("ssiii", $name, $website_url, $display_order, $is_active, $id);
                if ($stmt->execute()) {
                    header('Location: partners.php?msg=updated');
                    exit;
                }
            }

            header('Location: partners.php?msg=error_db');
            exit;
        }
        break;

    case 'delete':
        if ($id <= 0) {
            header('Location: partners.php?msg=error_invalid');
            exit;
        }

        // Clean up custom uploaded file if needed (portable without mysqlnd)
        $oldRes = $conn->query("SELECT logo_url FROM partners WHERE id = " . (int)$id);
        if ($oldRes && $row = $oldRes->fetch_assoc()) {
            $file_to_delete = '../' . ($row['logo_url'] ?? '');
            if (file_exists($file_to_delete) && preg_match('/^img\/partners\/partner_\d{9,}_/', $row['logo_url'] ?? '')) {
                @unlink($file_to_delete);
            }
        }

        // Execute delete directly (safe against SQLi since $id is cast to int)
        $delResult = $conn->query("DELETE FROM partners WHERE id = " . (int)$id);
        if ($delResult) {
            header('Location: partners.php?msg=deleted');
            exit;
        }

        // Prepared fallback
        $del_stmt = $conn->prepare("DELETE FROM partners WHERE id = ?");
        if ($del_stmt) {
            $del_stmt->bind_param("i", $id);
            if ($del_stmt->execute()) {
                header('Location: partners.php?msg=deleted');
                exit;
            }
        }

        header('Location: partners.php?msg=error_db');
        exit;
        break;

    default:
        header('Location: partners.php');
        exit;
}
