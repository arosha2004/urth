<?php
/**
 * URTH Admin Panel - Shared Header Navigation Bar Component
 * Ensures consistency across all admin pages without having to edit headers one by one.
 */

// Auto-determine active navigation tab if not explicitly set
if (!isset($activeNav)) {
    $scriptName = basename($_SERVER['PHP_SELF'] ?? '');
    if ($scriptName === 'products.php' || strpos($scriptName, 'product') !== false) {
        $activeNav = 'products';
    } elseif ($scriptName === 'orders.php' || strpos($scriptName, 'order') !== false) {
        $activeNav = 'orders';
    } elseif ($scriptName === 'partners.php' || strpos($scriptName, 'partner') !== false) {
        $activeNav = 'partners';
    } elseif ($scriptName === 'emails.php' || strpos($scriptName, 'email') !== false) {
        $activeNav = 'emails';
    } else {
        $activeNav = 'projects';
    }
}

// Check for pending refunds badge on Orders if not already computed
if (!isset($pendingRefundsCount)) {
    $pendingRefundsCount = 0;
    if (isset($conn) && $conn instanceof mysqli) {
        try {
            $badgeCheck = $conn->query("SHOW TABLES LIKE 'orders'");
            if ($badgeCheck && $badgeCheck->num_rows > 0) {
                $badgeRes = $conn->query("SELECT COUNT(*) AS c FROM orders WHERE refund_status = 'pending_action' OR payment_status = 'refund_pending'");
                if ($badgeRes) {
                    $badgeRow = $badgeRes->fetch_assoc();
                    $pendingRefundsCount = (int)($badgeRow['c'] ?? 0);
                }
            }
        } catch (Throwable $e) {
            $pendingRefundsCount = 0;
        }
    }
}

// Contextual action link for the current page
if (!isset($headerPreviewUrl) || !isset($headerPreviewLabel)) {
    if ($activeNav === 'products') {
        $headerPreviewUrl = '../shop.php';
        $headerPreviewLabel = 'View Shop';
        $headerPreviewTitle = 'Preview shop';
    } elseif ($activeNav === 'orders') {
        $headerPreviewUrl = '../cart.php';
        $headerPreviewLabel = 'View Cart';
        $headerPreviewTitle = 'Preview checkout';
    } elseif ($activeNav === 'partners') {
        $headerPreviewUrl = '../about.html#partner';
        $headerPreviewLabel = 'View Site';
        $headerPreviewTitle = 'Preview slideshow';
    } else {
        $headerPreviewUrl = '../index.php';
        $headerPreviewLabel = 'View Site';
        $headerPreviewTitle = 'Preview website';
    }
}

$adminUsername = $_SESSION['admin_username'] ?? 'Admin';
$adminInitial = strtoupper(substr($adminUsername, 0, 1));
?>
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
        <a href="index.php" class="admin-nav-link <?= ($activeNav === 'projects') ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            Projects
        </a>
        <a href="products.php" class="admin-nav-link <?= ($activeNav === 'products') ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
            Products
        </a>
        <a href="orders.php" class="admin-nav-link <?= ($activeNav === 'orders') ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
            Orders<?php if (!empty($pendingRefundsCount) && $pendingRefundsCount > 0): ?> <span style="background: var(--danger); color: #fff; font-size: 10px; padding: 1px 6px; border-radius: 10px; margin-left: 4px;" title="<?= (int)$pendingRefundsCount ?> refund actions pending"><?= (int)$pendingRefundsCount ?></span><?php endif; ?>
        </a>
        <a href="partners.php" class="admin-nav-link <?= ($activeNav === 'partners') ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m4.93 4.93 4.24 4.24"/><path d="m14.83 9.17 4.24-4.24"/><path d="m14.83 14.83 4.24 4.24"/><path d="m9.17 14.83-4.24 4.24"/></svg>
            Partners
        </a>
        <a href="emails.php" class="admin-nav-link <?= ($activeNav === 'emails') ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
            Failed Emails
        </a>
    </nav>

    <!-- Header Actions -->
    <div class="header-actions">
        <div class="user-badge">
            <span class="user-avatar"><?= htmlspecialchars($adminInitial) ?></span>
            <span><?= htmlspecialchars($adminUsername) ?></span>
        </div>

        <a href="<?= htmlspecialchars($headerPreviewUrl) ?>" target="_blank" class="btn btn-outline btn-sm" title="<?= htmlspecialchars($headerPreviewTitle ?? $headerPreviewLabel) ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
            <?= htmlspecialchars($headerPreviewLabel) ?>
        </a>

        <a href="logout.php" class="btn btn-danger btn-sm" title="Log out">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            Logout
        </a>
    </div>
</header>

