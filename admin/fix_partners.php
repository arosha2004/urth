<?php
/**
 * URTH Admin Panel - Database Schema Repair Tool for Partners Table
 */

require_once __DIR__ . '/auth.php';
require_admin_auth();
require_once '../config.php';

$messages = [];
$status = 'success';

try {
    // 1. Check if partners table exists
    $tblCheck = $conn->query("SHOW TABLES LIKE 'partners'");
    if (!$tblCheck || $tblCheck->num_rows === 0) {
        // Create table from scratch
        $createSql = "CREATE TABLE IF NOT EXISTS partners (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            logo_url VARCHAR(255) NOT NULL,
            website_url VARCHAR(255) DEFAULT NULL,
            display_order INT(11) DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        if ($conn->query($createSql)) {
            $messages[] = "Created partners table with AUTO_INCREMENT PRIMARY KEY.";
        } else {
            throw new Exception("Failed to create table: " . $conn->error);
        }
    } else {
        // 2. Inspect existing schema
        $colCheck = $conn->query("SHOW COLUMNS FROM partners LIKE 'id'");
        $colInfo = $colCheck ? $colCheck->fetch_assoc() : null;

        $zeroCountRes = $conn->query("SELECT COUNT(*) AS cnt FROM partners WHERE id <= 0 OR id IS NULL");
        $zeroCount = ($zeroCountRes && ($row = $zeroCountRes->fetch_assoc())) ? (int)$row['cnt'] : 0;

        $isAutoInc = $colInfo && (stripos($colInfo['Extra'] ?? '', 'auto_increment') !== false);
        $isPrimary = $colInfo && (($colInfo['Key'] ?? '') === 'PRI');

        if (!$colInfo || !$isAutoInc || !$isPrimary || $zeroCount > 0) {
            $messages[] = "Discrepancy detected: ID column missing AUTO_INCREMENT/PRIMARY KEY or records have id = 0 ($zeroCount records found).";

            // Drop existing primary key only if present
            if ($isPrimary) {
                try {
                    $conn->query("ALTER TABLE partners DROP PRIMARY KEY");
                } catch (Throwable $ignore) {}
            }

            // Drop non-functional id column if present
            if ($colInfo) {
                if (!$conn->query("ALTER TABLE partners DROP COLUMN id")) {
                    // In case drop failed, try modify directly with variable renumbering
                    $conn->query("SET @new_id = 0");
                    $conn->query("UPDATE partners SET id = (@new_id := @new_id + 1) ORDER BY display_order ASC, name ASC");
                    $conn->query("ALTER TABLE partners MODIFY id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY");
                } else {
                    $conn->query("ALTER TABLE partners ADD id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST");
                }
            } else {
                $conn->query("ALTER TABLE partners ADD id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST");
            }

            $messages[] = "Successfully rebuilt 'id' column as AUTO_INCREMENT PRIMARY KEY. Existing records re-indexed with sequential positive IDs.";
        } else {
            $messages[] = "Partners table schema is already verified and healthy. AUTO_INCREMENT PRIMARY KEY is active.";
        }
    }

    // Fetch current rows for visual confirmation
    $currRows = [];
    $res = $conn->query("SELECT id, name, display_order, is_active FROM partners ORDER BY id ASC");
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $currRows[] = $r;
        }
    }

} catch (Throwable $e) {
    $status = 'error';
    $messages[] = "Error: " . $e->getMessage();
    $currRows = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Repair Partners Database Schema — URTH Admin</title>
    <link rel="stylesheet" href="assets/css/admin.css?v=<?= time() ?>">
    <style>
        .repair-card {
            background: #fff;
            border-radius: var(--radius-md);
            padding: 28px;
            box-shadow: 0 4px 14px rgba(0,0,0,0.06);
            max-width: 800px;
            margin: 40px auto;
        }
        .status-badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 20px;
        }
        .status-success { background: #d4edda; color: #155724; }
        .status-error { background: #f8d7da; color: #721c24; }
        .log-box {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: 16px;
            font-family: monospace;
            font-size: 13px;
            line-height: 1.6;
            margin-bottom: 24px;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
            font-size: 14px;
        }
        .data-table th, .data-table td {
            padding: 10px 14px;
            border: 1px solid #dee2e6;
            text-align: left;
        }
        .data-table th { background: #f1f3f5; }
    </style>
</head>
<body>
<div class="admin-layout">
    <header class="admin-header">
        <a href="partners.php" class="header-brand">
            <span class="brand-name">URTH</span>
            <span class="brand-sub">Schema Repair Tool</span>
        </a>
    </header>

    <main class="admin-container">
        <div class="repair-card">
            <h2>Partners Table Database Repair</h2>
            <p style="color: var(--text-secondary); margin-bottom: 20px;">
                This utility verifies that the <code>partners</code> table contains a valid <code>AUTO_INCREMENT PRIMARY KEY</code> on the <code>id</code> column so that partner logos can be edited and deleted without encountering the "Invalid partner record specified" error.
            </p>

            <div class="status-badge status-<?= $status ?>">
                Status: <?= strtoupper($status) ?>
            </div>

            <div class="log-box">
                <?php foreach ($messages as $msg): ?>
                    <div>&bull; <?= htmlspecialchars($msg) ?></div>
                <?php endforeach; ?>
            </div>

            <h3>Current Partner Records in Database</h3>
            <?php if (!empty($currRows)): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Database ID</th>
                            <th>Partner Name</th>
                            <th>Display Order</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($currRows as $r): ?>
                            <tr>
                                <td><strong style="color: <?= $r['id'] > 0 ? '#28a745' : '#dc3545' ?>;">#<?= (int)$r['id'] ?></strong></td>
                                <td><?= htmlspecialchars($r['name']) ?></td>
                                <td><?= (int)$r['display_order'] ?></td>
                                <td><?= $r['is_active'] ? 'Active' : 'Hidden' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="color: var(--text-muted);">No partner records found.</p>
            <?php endif; ?>

            <div style="display: flex; gap: 12px; margin-top: 24px;">
                <a href="partners.php" class="btn btn-gold">Go to Manage Partner Logos</a>
                <a href="index.php" class="btn btn-outline">Go to Projects</a>
            </div>
        </div>
    </main>
</div>
</body>
</html>
