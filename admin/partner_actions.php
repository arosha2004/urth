<?php
/**
 * URTH Admin Panel - Partner Actions Handler (Add, Edit, Delete)
 */

ob_start();

require_once __DIR__ . '/auth.php';
require_admin_auth();
require_once '../config.php';

// Auto-repair partners table: ensure AUTO_INCREMENT PRIMARY KEY exists and all rows have valid positive IDs
$tblCheck = $conn->query("SHOW TABLES LIKE 'partners'");
if ($tblCheck && $tblCheck->num_rows > 0) {
    $colCheck = $conn->query("SHOW COLUMNS FROM partners LIKE 'id'");
    $colInfo = $colCheck ? $colCheck->fetch_assoc() : null;
    $needsSchemaRepair = false;

    if (!$colInfo || stripos($colInfo['Extra'] ?? '', 'auto_increment') === false || ($colInfo['Key'] ?? '') !== 'PRI') {
        $needsSchemaRepair = true;
    } else {
        $zeroCheck = $conn->query("SELECT COUNT(*) AS zero_count FROM partners WHERE id <= 0 OR id IS NULL");
        if ($zeroCheck && ($zRow = $zeroCheck->fetch_assoc()) && (int)$zRow['zero_count'] > 0) {
            $needsSchemaRepair = true;
        }
    }

    if ($needsSchemaRepair) {
        try {
            if ($colInfo && ($colInfo['Key'] ?? '') === 'PRI') {
                $conn->query("ALTER TABLE partners DROP PRIMARY KEY");
            }
            if ($colInfo) {
                $conn->query("ALTER TABLE partners DROP COLUMN id");
            }
            $conn->query("ALTER TABLE partners ADD id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST");
        } catch (Throwable $ignore) {}
    }
}

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
            @mkdir($upload_dir, 0755, true); // FIX #10: 0755 not 0777
        }

        $safe_name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $file_info['filename']);
        $filename = 'partner_' . time() . '_' . substr($safe_name, 0, 30) . '.' . $ext;
        $target_path = $upload_dir . $filename;

        if (move_uploaded_file($_FILES['logo_image']['tmp_name'], $target_path)) {
            $logo_url = 'img/partners/' . $filename;

            // FIX #4: Use prepared statement — no string interpolation in SQL
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
                @mkdir($upload_dir, 0755, true); // FIX #10: 0755 not 0777
            }

            $safe_name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $file_info['filename']);
            $filename = 'partner_' . time() . '_' . substr($safe_name, 0, 30) . '.' . $ext;
            $target_path = $upload_dir . $filename;

            if (move_uploaded_file($_FILES['logo_image']['tmp_name'], $target_path)) {
                $logo_url = 'img/partners/' . $filename;

                // Delete old custom uploaded logo if present
                $oldStmt = $conn->prepare("SELECT logo_url FROM partners WHERE id = ?");
                if ($oldStmt) {
                    $oldStmt->bind_param("i", $id);
                    $oldStmt->execute();
                    $oldRes = $oldStmt->get_result();
                    if ($oldRes && $row = $oldRes->fetch_assoc()) {
                        $file_to_delete = realpath(__DIR__ . '/../' . ($row['logo_url'] ?? ''));
                        $real_partners_dir = realpath(__DIR__ . '/../img/partners');
                        if ($file_to_delete !== false
                            && $real_partners_dir !== false
                            && str_starts_with($file_to_delete, $real_partners_dir)
                            && is_file($file_to_delete)
                        ) {
                            @unlink($file_to_delete);
                        }
                    }
                }

                // FIX #4: Prepared statement — no string interpolation
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
            // FIX #4: Prepared statement — no string interpolation
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

        // FIX #4 & #8: Use prepared stmt + realpath-based path validation
        $oldStmt = $conn->prepare("SELECT logo_url FROM partners WHERE id = ?");
        if ($oldStmt) {
            $oldStmt->bind_param("i", $id);
            $oldStmt->execute();
            $oldRes = $oldStmt->get_result();
            if ($oldRes && $row = $oldRes->fetch_assoc()) {
                $file_to_delete    = realpath(__DIR__ . '/../' . ($row['logo_url'] ?? ''));
                $real_partners_dir = realpath(__DIR__ . '/../img/partners');
                if ($file_to_delete !== false
                    && $real_partners_dir !== false
                    && str_starts_with($file_to_delete, $real_partners_dir)
                    && is_file($file_to_delete)
                ) {
                    @unlink($file_to_delete);
                }
            }
        }

        // FIX #4: Use prepared statement only — no raw query
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
