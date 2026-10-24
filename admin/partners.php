<?php
/**
 * URTH Admin Panel - Manage Partner Logos
 */

require_once __DIR__ . '/auth.php';
require_admin_auth();
require_once '../config.php';

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
} else {
    $conn->query("CREATE TABLE IF NOT EXISTS partners (
        id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        logo_url VARCHAR(255) NOT NULL,
        website_url VARCHAR(255) DEFAULT NULL,
        display_order INT(11) DEFAULT 0,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
}

// Fetch all partners ordered by display_order ASC, id DESC
$result = $conn->query("SELECT * FROM partners ORDER BY display_order ASC, id DESC");
$partners = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $partners[] = $row;
    }
}

// Statistics
$totalPartners = count($partners);
$activePartners = count(array_filter($partners, function($p) { return (int)$p['is_active'] === 1; }));
$inactivePartners = $totalPartners - $activePartners;

// Status notification messages
$msg = $_GET['msg'] ?? '';
$alertMessage = '';
$alertType = 'success';

switch ($msg) {
    case 'added':
        $alertMessage = 'Partner logo successfully added to showcase.';
        break;
    case 'updated':
        $alertMessage = 'Partner details updated successfully.';
        break;
    case 'deleted':
        $alertMessage = 'Partner logo has been removed.';
        $alertType = 'danger';
        break;
    case 'error_missing':
        $alertMessage = 'Please fill in all required fields.';
        $alertType = 'danger';
        break;
    case 'error_invalid':
        $alertMessage = 'Invalid partner record specified.';
        $alertType = 'danger';
        break;
    case 'error_file':
        $alertMessage = 'Please select a valid image file for the partner logo.';
        $alertType = 'danger';
        break;
    case 'error_filetype':
        $alertMessage = 'Invalid file type. Supported formats: SVG, PNG, JPG, WEBP.';
        $alertType = 'danger';
        break;
    case 'error_size':
        $alertMessage = 'The uploaded image is too large. Please upload a smaller file (under 2MB).';
        $alertType = 'danger';
        break;
    case 'error_upload':
    case 'error_db':
        $alertMessage = 'An unexpected error occurred. Please try again.';
        $alertType = 'danger';
        break;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Partners — URTH Admin</title>
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
                <span class="brand-sub">Admin</span>
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
                Products
            </a>
            <a href="orders.php" class="admin-nav-link">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                Orders
            </a>
            <a href="partners.php" class="admin-nav-link active">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m4.93 4.93 4.24 4.24"/><path d="m14.83 9.17 4.24-4.24"/><path d="m14.83 14.83 4.24 4.24"/><path d="m9.17 14.83-4.24 4.24"/></svg>
                Partners
            </a>
        </nav>

        <div class="header-actions">
            <div class="user-badge">
                <span class="user-avatar"><?= strtoupper(substr($_SESSION['admin_username'] ?? 'A', 0, 1)) ?></span>
                <span><?= htmlspecialchars($_SESSION['admin_username'] ?? 'Admin') ?></span>
            </div>

            <a href="../about.html#partner" target="_blank" class="btn btn-outline btn-sm" title="Preview slideshow">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                View Site
            </a>

            <a href="logout.php" class="btn btn-danger btn-sm" title="Log out">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                Logout
            </a>
        </div>
    </header>

    <!-- Main Content Container -->
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
                <h1>Partners</h1>
            </div>

            <button type="button" id="openAddPartnerModal" class="btn btn-gold">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                + Add Partner
            </button>
        </div>

        <!-- Summary Statistics Widgets -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-info">
                    <div class="stat-label">Total Partners</div>
                    <div class="stat-value"><?= $totalPartners ?></div>
                </div>
                <div class="stat-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m4.93 4.93 4.24 4.24"/><path d="m14.83 9.17 4.24-4.24"/><path d="m14.83 14.83 4.24 4.24"/><path d="m9.17 14.83-4.24 4.24"/></svg>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-info">
                    <div class="stat-label">Active</div>
                    <div class="stat-value" style="color: var(--success);"><?= $activePartners ?></div>
                </div>
                <div class="stat-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                </div>
            </div>


        </div>

        <!-- Toolbar & Filter Card -->
        <div class="toolbar-card">
            <div class="search-filter-group">
                <div class="search-box">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="text" id="partnerSearchInput" class="form-control" placeholder="Search partners...">
                </div>
            </div>

            <div style="font-size: 13px; color: var(--text-secondary);">
                <strong id="partnerCount" style="color: var(--text-primary);"><?= $totalPartners ?></strong> partners
            </div>
        </div>

        <!-- Table Card Section -->
        <div class="table-card">
            <div class="table-responsive">
                <table class="custom-table" id="partnersTable">
                    <thead>
                        <tr>
                            <th style="width: 70px;">Order</th>
                            <th style="width: 120px;">Logo</th>
                            <th>Partner</th>
                            <th>Website</th>

                            <th style="width: 160px; text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($partners)): ?>
                            <tr id="emptyRow">
                                <td colspan="6">
                                    <div class="empty-state">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m4.93 4.93 4.24 4.24"/><path d="m14.83 9.17 4.24-4.24"/><path d="m14.83 14.83 4.24 4.24"/><path d="m9.17 14.83-4.24 4.24"/></svg>
                                        <h3>No Partners Found</h3>
                                        <p>Click "+ Add Partner" to add a partner logo.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($partners as $partner): ?>
                                <?php
                                    $logoPath = $partner['logo_url'];
                                    if (!preg_match('/^https?:\/\//i', $logoPath)) {
                                        $logoSrc = '../' . ltrim($logoPath, '/');
                                    } else {
                                        $logoSrc = $logoPath;
                                    }
                                ?>
                                <tr class="partner-row"
                                    data-id="<?= $partner['id'] ?>"
                                    data-name="<?= htmlspecialchars($partner['name']) ?>"
                                    data-url="<?= htmlspecialchars($partner['website_url'] ?? '') ?>">
                                    <td>
                                        <span class="order-badge"><?= (int)$partner['display_order'] ?></span>
                                    </td>
                                    <td>
                                        <div class="partner-thumb-box">
                                            <img src="<?= htmlspecialchars($logoSrc) ?>"
                                                 alt="<?= htmlspecialchars($partner['name']) ?> Logo"
                                                 loading="lazy">
                                        </div>
                                    </td>
                                    <td>
                                        <strong style="color: var(--text-primary); font-size: 15px;"><?= htmlspecialchars($partner['name']) ?></strong>
                                    </td>
                                    <td>
                                        <?php if (!empty($partner['website_url'])): ?>
                                            <a href="<?= htmlspecialchars($partner['website_url']) ?>" target="_blank" rel="noopener noreferrer" style="color: var(--gold-primary); text-decoration: none; display: inline-flex; align-items: center; gap: 4px; font-weight: 500;">
                                                <?= htmlspecialchars(parse_url($partner['website_url'], PHP_URL_HOST) ?: $partner['website_url']) ?>
                                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                                            </a>
                                        <?php else: ?>
                                            <span style="color: var(--text-muted); font-size: 12px;">No link specified</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <div class="table-actions" style="justify-content: flex-end; gap: 8px;">
                                            <button type="button"
                                                    class="btn btn-outline btn-sm edit-partner-btn"
                                                    data-id="<?= $partner['id'] ?>"
                                                    data-name="<?= htmlspecialchars($partner['name'], ENT_QUOTES) ?>"
                                                    data-url="<?= htmlspecialchars($partner['website_url'] ?? '', ENT_QUOTES) ?>"
                                                    data-order="<?= (int)$partner['display_order'] ?>"
                                                    data-active="<?= (int)$partner['is_active'] ?>"
                                                    data-logo="<?= htmlspecialchars($logoSrc, ENT_QUOTES) ?>"
                                                    title="Edit partner">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                                Edit
                                            </button>
                                            <button type="button"
                                                    class="btn btn-danger btn-sm delete-partner-btn"
                                                    data-id="<?= $partner['id'] ?>"
                                                    data-name="<?= htmlspecialchars($partner['name'], ENT_QUOTES) ?>"
                                                    title="Delete partner">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                                                Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                            <tr id="noResultsRow" style="display: none;">
                                <td colspan="6">
                                    <div class="empty-state">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                                        <h3>No matching partners</h3>
                                        <p>Try searching for a different keyword or name.</p>
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
        &copy; <?= date('Y') ?> URTH Admin
    </footer>

</div>

<!-- Modal: Add New Partner -->
<div id="addPartnerModal" class="modal-overlay">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title">Add Partner</h3>
            <button type="button" class="modal-close" data-modal-close>&times;</button>
        </div>
        <form action="partner_actions.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add">
            <div class="modal-body">
                <div class="form-group">
                    <label for="add_partner_name" class="form-label">Partner Name *</label>
                    <input type="text" id="add_partner_name" name="name" class="form-control" placeholder="e.g. Studio Form" required>
                </div>

                <div class="form-group">
                    <label for="add_partner_logo" class="form-label">Logo Image *</label>
                    <input type="file" id="add_partner_logo" name="logo_image" class="form-control" accept=".svg,.png,.jpg,.jpeg,.webp" required>
                    <div id="add_logo_preview_wrap" style="display: none; margin-top: 10px;">
                        <span style="font-size: 11px; color: var(--text-muted); display: block; margin-bottom: 4px;">Preview:</span>
                        <div class="partner-thumb-box" style="width: 140px; height: 64px;">
                            <img id="add_logo_preview" src="" alt="Preview">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="add_partner_url" class="form-label">Website URL</label>
                    <input type="url" id="add_partner_url" name="website_url" class="form-control" placeholder="https://example.com">
                </div>

                <div class="form-group">
                    <label for="add_partner_order" class="form-label">Display Order</label>
                    <input type="number" id="add_partner_order" name="display_order" class="form-control" value="<?= $totalPartners + 1 ?>" min="0">
                </div>

                <div class="form-group" style="margin-top: 15px;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 13px; font-weight: 500;">
                        <input type="checkbox" name="is_active" value="1" checked style="width: 16px; height: 16px; accent-color: var(--gold-primary);">
                        Active
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-gold">Add Partner</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit Partner -->
<div id="editPartnerModal" class="modal-overlay">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title">Edit Partner</h3>
            <button type="button" class="modal-close" data-modal-close>&times;</button>
        </div>
        <form action="partner_actions.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="partner_id" id="edit_partner_id">
            <input type="hidden" name="id" id="edit_partner_id_alt">
            <div class="modal-body">
                <div class="form-group">
                    <label for="edit_partner_name" class="form-label">Partner Name *</label>
                    <input type="text" id="edit_partner_name" name="name" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="edit_partner_logo" class="form-label">Logo Image</label>
                    <input type="file" id="edit_partner_logo" name="logo_image" class="form-control" accept=".svg,.png,.jpg,.jpeg,.webp">
                    <div style="margin-top: 10px;">
                        <span style="font-size: 11px; color: var(--text-muted); display: block; margin-bottom: 4px;">Preview:</span>
                        <div class="partner-thumb-box" style="width: 140px; height: 64px;">
                            <img id="edit_logo_preview" src="" alt="Preview">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="edit_partner_url" class="form-label">Website URL</label>
                    <input type="text" id="edit_partner_url" name="website_url" class="form-control" placeholder="https://example.com">
                </div>

                <div class="form-group">
                    <label for="edit_partner_order" class="form-label">Display Order</label>
                    <input type="number" id="edit_partner_order" name="display_order" class="form-control" min="0">
                </div>

                <div class="form-group" style="margin-top: 15px;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 13px; font-weight: 500;">
                        <input type="checkbox" id="edit_partner_active" name="is_active" value="1" style="width: 16px; height: 16px; accent-color: var(--gold-primary);">
                        Active
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-gold">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Delete Confirmation -->
<div id="deletePartnerModal" class="modal-overlay">
    <div class="modal-dialog" style="max-width: 440px;">
        <div class="modal-header">
            <h3 class="modal-title">Delete Partner</h3>
            <button type="button" class="modal-close" data-modal-close>&times;</button>
        </div>
        <form action="partner_actions.php" method="POST">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="partner_id" id="delete_partner_id">
            <input type="hidden" name="id" id="delete_partner_id_alt">
            <div class="modal-body">
                <p style="color: var(--text-secondary); line-height: 1.6;">
                    Are you sure you want to delete <strong id="delete_partner_name" style="color: var(--text-primary);"></strong>?
                </p>
                <p style="color: var(--danger); font-size: 12px; margin-top: 8px;">
                    This cannot be undone.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-danger">Delete</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Modal Helpers
    const openModal = (modal) => {
        if (modal) modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    };
    const closeModal = (modal) => {
        if (modal) modal.classList.remove('active');
        document.body.style.overflow = '';
    };

    // Close buttons
    document.querySelectorAll('[data-modal-close]').forEach(btn => {
        btn.addEventListener('click', () => {
            const overlay = btn.closest('.modal-overlay');
            closeModal(overlay);
        });
    });

    // Close on backdrop click
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) closeModal(overlay);
        });
    });

    // Close on ESC
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-overlay.active').forEach(m => closeModal(m));
        }
    });

    // Open Add Modal
    const addBtn = document.getElementById('openAddPartnerModal');
    const addModal = document.getElementById('addPartnerModal');
    if (addBtn && addModal) {
        addBtn.addEventListener('click', () => openModal(addModal));
    }

    // Add Image Live Preview
    const addFileInput = document.getElementById('add_partner_logo');
    const addPreviewWrap = document.getElementById('add_logo_preview_wrap');
    const addPreviewImg = document.getElementById('add_logo_preview');
    if (addFileInput && addPreviewWrap && addPreviewImg) {
        addFileInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    addPreviewImg.src = e.target.result;
                    addPreviewWrap.style.display = 'block';
                };
                reader.readAsDataURL(this.files[0]);
            } else {
                addPreviewWrap.style.display = 'none';
            }
        });
    }

    // Edit Image Live Preview
    const editFileInput = document.getElementById('edit_partner_logo');
    const editPreviewImg = document.getElementById('edit_logo_preview');
    if (editFileInput && editPreviewImg) {
        editFileInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    editPreviewImg.src = e.target.result;
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
    }

    // Edit & Delete Modal Management
    const editModal = document.getElementById('editPartnerModal');
    const deleteModal = document.getElementById('deletePartnerModal');

    function openEditForElement(targetBtn) {
        if (!targetBtn) return;
        const id = targetBtn.getAttribute('data-id') || targetBtn.dataset.id || '';
        const name = targetBtn.getAttribute('data-name') || targetBtn.dataset.name || '';
        const url = targetBtn.getAttribute('data-url') || targetBtn.dataset.url || '';
        const order = targetBtn.getAttribute('data-order') || targetBtn.dataset.order || '0';
        const active = targetBtn.getAttribute('data-active') || targetBtn.dataset.active || '1';
        const logo = targetBtn.getAttribute('data-logo') || targetBtn.dataset.logo || '';

        const idField = document.getElementById('edit_partner_id');
        if (idField) idField.value = id;
        const idAltField = document.getElementById('edit_partner_id_alt');
        if (idAltField) idAltField.value = id;

        const nameField = document.getElementById('edit_partner_name');
        if (nameField) nameField.value = name;

        const urlField = document.getElementById('edit_partner_url');
        if (urlField) urlField.value = url;

        const orderField = document.getElementById('edit_partner_order');
        if (orderField) orderField.value = order;

        const activeField = document.getElementById('edit_partner_active');
        if (activeField) activeField.checked = (active === '1' || active === 1 || active === true);

        const previewImg = document.getElementById('edit_logo_preview');
        if (previewImg) previewImg.src = logo;

        if (editFileInput) editFileInput.value = '';
        openModal(editModal);
    }

    function openDeleteForElement(targetBtn) {
        if (!targetBtn) return;
        const id = targetBtn.getAttribute('data-id') || targetBtn.dataset.id || '';
        const name = targetBtn.getAttribute('data-name') || targetBtn.dataset.name || '';

        const idField = document.getElementById('delete_partner_id');
        if (idField) idField.value = id;
        const idAltField = document.getElementById('delete_partner_id_alt');
        if (idAltField) idAltField.value = id;

        const nameDisplay = document.getElementById('delete_partner_name');
        if (nameDisplay) nameDisplay.textContent = name;

        openModal(deleteModal);
    }

    // Direct click listeners
    document.querySelectorAll('.edit-partner-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            openEditForElement(e.currentTarget || btn);
        });
    });

    document.querySelectorAll('.delete-partner-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            openDeleteForElement(e.currentTarget || btn);
        });
    });

    // Delegated click listener on document for maximum reliability
    document.addEventListener('click', (e) => {
        const editBtn = e.target.closest('.edit-partner-btn');
        if (editBtn) {
            openEditForElement(editBtn);
            return;
        }

        const deleteBtn = e.target.closest('.delete-partner-btn');
        if (deleteBtn) {
            openDeleteForElement(deleteBtn);
            return;
        }
    });

    // Client-side instant filter/search
    const searchInput = document.getElementById('partnerSearchInput');
    const rows = document.querySelectorAll('.partner-row');
    const noResultsRow = document.getElementById('noResultsRow');
    const countDisplay = document.getElementById('partnerCount');

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const q = this.value.toLowerCase().trim();
            let visibleCount = 0;

            rows.forEach(row => {
                const name = (row.getAttribute('data-name') || '').toLowerCase();
                const url = (row.getAttribute('data-url') || '').toLowerCase();
                if (!q || name.includes(q) || url.includes(q)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            if (noResultsRow) {
                noResultsRow.style.display = (visibleCount === 0 && rows.length > 0) ? '' : 'none';
            }
            if (countDisplay) {
                countDisplay.textContent = visibleCount;
            }
        });
    }
});
</script>

</body>
</html>
