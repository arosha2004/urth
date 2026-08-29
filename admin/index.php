<?php
/**
 * URTH Admin Panel - Main Dashboard & Manage Projects
 */

require_once __DIR__ . '/auth.php';
require_admin_auth();

// Fetch all projects sorted by ID DESC from MySQL
require_once '../config.php';
$result = $conn->query("SELECT * FROM projects ORDER BY id DESC");
$projects = [];
if ($result) {
    while($row = $result->fetch_assoc()) {
        // Map MySQL schema to what the Admin UI expects
        $row['image_url'] = $row['image1'] ?? '';
        $projects[] = $row;
    }
}

// Statistics calculation
$totalProjects = count($projects);
$categories = array_unique(array_column($projects, 'category'));
$totalCategories = count($categories);

// Status notification messages
$msg = $_GET['msg'] ?? '';
$alertMessage = '';
$alertType = 'success';

switch ($msg) {
    case 'login_success':
        $alertMessage = 'Welcome back, ' . htmlspecialchars($_SESSION['admin_username']) . '! You are now signed in.';
        break;
    case 'added':
        $alertMessage = 'New project created successfully.';
        break;
    case 'updated':
        $alertMessage = 'Project details updated successfully.';
        break;
    case 'deleted':
        $alertMessage = 'Project has been removed.';
        $alertType = 'danger';
        break;
    case 'error_missing':
    case 'error_invalid':
        $alertMessage = 'Error processing request. Please check all fields.';
        $alertType = 'danger';
        break;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Projects — URTH Admin Panel</title>
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>

<div class="admin-layout">

    <!-- Header Navigation Bar -->
    <header class="admin-header">
        <a href="index.php" class="header-brand">
            <div class="brand-logo-mark">U</div>
            <div class="brand-text">
                <span class="brand-name">URTH</span>
                <span class="brand-sub">Admin Console</span>
            </div>
        </a>

        <div class="header-actions">
            <div class="user-badge">
                <span class="user-avatar"><?= strtoupper(substr($_SESSION['admin_username'] ?? 'A', 0, 1)) ?></span>
                <span><?= htmlspecialchars($_SESSION['admin_username'] ?? 'Admin') ?></span>
            </div>

            <a href="../index.html" class="btn btn-outline btn-sm" title="Return to public portfolio website">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                Back to Main Site
            </a>

            <a href="logout.php" class="btn btn-danger btn-sm" title="Log out of admin session">
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
                <h1>Manage Projects</h1>
                <p>Add, edit, or remove architecture & design showcase projects</p>
            </div>

            <button type="button" id="openAddModal" class="btn btn-gold">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Add New Project
            </button>
        </div>

        <!-- Summary Statistics Widgets -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-info">
                    <div class="stat-label">Total Projects</div>
                    <div class="stat-value"><?= $totalProjects ?></div>
                </div>
                <div class="stat-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-info">
                    <div class="stat-label">Project Categories</div>
                    <div class="stat-value"><?= $totalCategories ?></div>
                </div>
                <div class="stat-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-info">
                    <div class="stat-label">System Status</div>
                    <div class="stat-value" style="font-size: 18px; color: var(--success); display: flex; align-items: center; gap: 8px;">
                        <span style="width: 10px; height: 10px; border-radius: 50%; background: var(--success); display: inline-block;"></span> Active
                    </div>
                </div>
                <div class="stat-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                </div>
            </div>
        </div>

        <!-- Toolbar & Filter Card -->
        <div class="toolbar-card">
            <div class="search-filter-group">
                <div class="search-box">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="text" id="tableSearch" class="form-control" placeholder="Search by title, category or ID...">
                </div>

                <div style="min-width: 180px;">
                    <select id="categoryFilter" class="form-control">
                        <option value="">All Categories</option>
                        <option value="Ideas Hub">Ideas Hub</option>
                        <option value="Spatial Redesign">Spatial Redesign</option>
                        <option value="Custom Furnitures">Custom Furnitures</option>
                        <option value="Landscape Design">Landscape Design</option>
                        <option value="Architectural Design">Architectural Design</option>
                        <option value="Interior Architecture">Interior Architecture</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Projects Data Table Card -->
        <div class="table-card">
            <div class="table-responsive">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th style="width: 70px;">ID</th>
                            <th>Title</th>
                            <th>Category</th>
                            <th style="width: 100px;">Images</th>
                            <th style="width: 160px; text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($projects)): ?>
                            <tr id="noResultsRow">
                                <td colspan="6">
                                    <div class="empty-state">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="9" y1="9" x2="15" y2="15"/><line x1="15" y1="9" x2="9" y2="15"/></svg>
                                        <h3>No projects found</h3>
                                        <p>Click "Add New Project" to create your first portfolio entry.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($projects as $project): ?>
                                <?php
                                    $catSlug = strtolower(str_replace(' ', '-', $project['category']));
                                ?>
                                <tr class="project-row" 
                                    data-id="<?= $project['id'] ?>"
                                    data-title="<?= strtolower(htmlspecialchars($project['title'])) ?>"
                                    data-category="<?= strtolower(htmlspecialchars($project['category'])) ?>">
                                    
                                    <td><span class="id-badge"><?= $project['id'] ?></span></td>
                                    
                                    <td style="font-weight: 600;">
                                        <?= htmlspecialchars($project['title']) ?>
                                    </td>
                                    
                                    <td>
                                        <span class="category-pill <?= $catSlug ?>">
                                            <?= htmlspecialchars($project['category']) ?>
                                        </span>
                                    </td>
                                    
                                    <td>
                                        <?php 
                                            $thumbSrc = $project['image_url'];
                                            if (!empty($thumbSrc) && !preg_match('/^https?:\/\//i', $thumbSrc)) {
                                                $thumbSrc = '../' . $thumbSrc;
                                            }
                                        ?>
                                        <img src="<?= htmlspecialchars($thumbSrc) ?>" 
                                             alt="<?= htmlspecialchars($project['title']) ?>" 
                                             class="project-thumb"
                                             onerror="this.src='https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=600&q=80'">
                                    </td>
                                    
                                    <td>
                                        <div class="table-actions" style="justify-content: flex-end;">
                                            <button type="button" 
                                                    class="btn btn-outline btn-sm edit-project-btn"
                                                    data-id="<?= $project['id'] ?>"
                                                    data-title="<?= htmlspecialchars($project['title'], ENT_QUOTES) ?>"
                                                    data-category="<?= htmlspecialchars($project['category'], ENT_QUOTES) ?>"
                                                    data-description="<?= htmlspecialchars($project['description'] ?? '', ENT_QUOTES) ?>"
                                                    data-image="<?= htmlspecialchars($project['image_url'], ENT_QUOTES) ?>"
                                                    title="Edit project">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                                Edit
                                            </button>

                                            <button type="button" 
                                                    class="btn btn-danger btn-sm delete-project-btn"
                                                    data-id="<?= $project['id'] ?>"
                                                    data-title="<?= htmlspecialchars($project['title'], ENT_QUOTES) ?>"
                                                    title="Delete project">
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
                                        <h3>No matching projects</h3>
                                        <p>Try searching for a different keyword or clearing filters.</p>
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
        &copy; <?= date('Y') ?> URTH Architecture Studio — Admin Management Panel
    </footer>

</div>

<!-- Modal: Add New Project -->
<div id="addProjectModal" class="modal-overlay">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title">Add New Project</h3>
            <button type="button" class="modal-close" data-modal-close>&times;</button>
        </div>
        <form action="project_add.php" method="POST" enctype="multipart/form-data">
            <div class="modal-body">
                <div class="form-group">
                    <label for="add_title" class="form-label">Project Title</label>
                    <input type="text" id="add_title" name="title" class="form-control" placeholder="e.g. Ideas Hub Showcase" required>
                </div>

                <div class="form-group">
                    <label for="add_category" class="form-label">Category</label>
                    <select id="add_category" name="category" class="form-control" required>
                        <option value="Ideas Hub">Ideas Hub</option>
                        <option value="Spatial Redesign">Spatial Redesign</option>
                        <option value="Custom Furnitures">Custom Furnitures</option>
                        <option value="Landscape Design">Landscape Design</option>
                        <option value="Architectural Design">Architectural Design</option>
                        <option value="Interior Architecture">Interior Architecture</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="add_description" class="form-label">Description</label>
                    <textarea id="add_description" name="description" class="form-control" rows="4" placeholder="Enter project description..."></textarea>
                </div>

                <div class="form-group">
                    <label for="add_image_file" class="form-label">Hero Image / Thumbnail</label>
                    <input type="file" id="add_image_file" name="image1" class="form-control" accept="image/*" required>
                    <div class="img-preview-box" id="add_hero_preview_container" style="display: none; margin-top: 10px;">
                        <img id="add_hero_preview" src="" alt="Hero Preview" style="max-width: 100%; max-height: 200px; border-radius: 4px; object-fit: cover;">
                    </div>
                </div>

                <div class="form-group" id="gallery_section">
                    <label class="form-label">Gallery Images (Optional - Max 12)</label>
                    <div id="gallery_inputs_wrapper">
                        <div class="gallery-input-row" style="display: flex; gap: 10px; align-items: flex-start; margin-bottom: 10px;">
                            <div style="flex-grow: 1;">
                                <input type="file" name="gallery_images[]" class="form-control gallery-file-input" accept="image/*">
                            </div>
                            <div class="preview-slot" style="width: 42px; height: 42px; border-radius: 4px; border: 1px solid #ddd; background: #f9f9f9; display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0;">
                                <span style="color: #bbb; font-size: 10px;">No img</span>
                            </div>
                            <button type="button" class="btn btn-danger btn-sm remove-gallery-row" style="padding: 0 10px; height: 42px; display: none;" title="Remove">&times;</button>
                        </div>
                    </div>
                    <button type="button" class="btn btn-outline btn-sm" id="add_gallery_btn" style="margin-top: 5px;">+ Add Another Image</button>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-gold">Create Project</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit Project -->
<div id="editProjectModal" class="modal-overlay">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title">Edit Project</h3>
            <button type="button" class="modal-close" data-modal-close>&times;</button>
        </div>
        <form action="project_edit.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" id="edit_id" name="id">
            <div class="modal-body">
                <div class="form-group">
                    <label for="edit_title" class="form-label">Project Title</label>
                    <input type="text" id="edit_title" name="title" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="edit_category" class="form-label">Category</label>
                    <select id="edit_category" name="category" class="form-control" required>
                        <option value="Ideas Hub">Ideas Hub</option>
                        <option value="Spatial Redesign">Spatial Redesign</option>
                        <option value="Custom Furnitures">Custom Furnitures</option>
                        <option value="Landscape Design">Landscape Design</option>
                        <option value="Architectural Design">Architectural Design</option>
                        <option value="Interior Architecture">Interior Architecture</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="edit_description" class="form-label">Description</label>
                    <textarea id="edit_description" name="description" class="form-control" rows="4"></textarea>
                </div>

                <div class="form-group">
                    <label for="edit_image_file" class="form-label">Hero Image (Leave empty to keep current)</label>
                    <input type="file" id="edit_image_file" name="image1" class="form-control" accept="image/*">
                    <div class="img-preview-box" id="edit_hero_preview_container" style="margin-top: 10px;">
                        <img id="edit_hero_preview" src="" alt="Hero Preview" style="max-width: 100%; max-height: 200px; border-radius: 4px; object-fit: cover;">
                    </div>
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
<div id="deleteProjectModal" class="modal-overlay">
    <div class="modal-dialog" style="max-width: 440px;">
        <div class="modal-header">
            <h3 class="modal-title">Confirm Deletion</h3>
            <button type="button" class="modal-close" data-modal-close>&times;</button>
        </div>
        <form action="project_delete.php" method="POST">
            <input type="hidden" id="delete_project_id" name="id">
            <div class="modal-body" style="text-align: center;">
                <div style="width: 56px; height: 56px; border-radius: 50%; background: var(--danger-bg); color: var(--danger); display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                </div>
                <h4 style="font-size: 16px; margin-bottom: 8px;">Are you sure?</h4>
                <p style="color: var(--text-secondary); font-size: 13px;">
                    Do you really want to delete "<strong id="delete_project_name" style="color: var(--text-primary);"></strong>"? This action cannot be undone.
                </p>
            </div>
            <div class="modal-footer" style="justify-content: center;">
                <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-danger">Delete Project</button>
            </div>
        </form>
    </div>
</div>

<script src="assets/js/admin.js"></script>
</body>
</html>
