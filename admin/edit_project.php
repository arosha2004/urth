<?php
require_once __DIR__ . '/auth.php';
require_admin_auth(); // FIX #6: Auth guard was completely missing

require_once '../config.php';
require_once 'resize_image.php';

// FIX #7 & #10: Allowed MIME types validated server-side
$allowed_mime_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

/**
 * Validate and move an uploaded image file.
 * Returns the absolute saved path on success, '' on failure.
 */
function handle_image_upload(array $file, string $upload_dir, string $suffix): string {
    global $allowed_mime_types;

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return '';
    }

    // FIX #7: Validate MIME type using finfo (server-side, not client filename)
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    if (!in_array($mime, $allowed_mime_types, true)) {
        return '';
    }

    $mime_to_ext = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
    ];
    $ext      = $mime_to_ext[$mime];
    $filename = time() . '_' . $suffix . '_' . bin2hex(random_bytes(4)) . '.' . $ext;

    // FIX #10: Secure directory permissions (755)
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $target_file = $upload_dir . $filename;
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return $target_file;
    }
    return '';
}

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}
$id = (int) $_GET['id'];

// Handle image deletion
if (isset($_GET['delete_image'])) {
    $img_id = (int) $_GET['delete_image'];

    // FIX #2 & #8: Use prepared statement; verify ownership before deleting
    $img_stmt = $conn->prepare("SELECT image_path FROM project_images WHERE id = ? AND project_id = ?");
    $img_stmt->bind_param('ii', $img_id, $id);
    $img_stmt->execute();
    $img_result = $img_stmt->get_result();

    if ($img_result && $img_result->num_rows > 0) {
        $img = $img_result->fetch_assoc();

        // FIX #8: Ensure the stored path is within the expected uploads directory
        $real_upload_dir = realpath(__DIR__ . '/../img/uploads');
        $file_path       = realpath(__DIR__ . '/../' . $img['image_path']);

        if ($file_path !== false
            && $real_upload_dir !== false
            && str_starts_with($file_path, $real_upload_dir)
            && is_file($file_path)
        ) {
            unlink($file_path);
        }

        $del_stmt = $conn->prepare("DELETE FROM project_images WHERE id = ? AND project_id = ?");
        $del_stmt->bind_param('ii', $img_id, $id);
        $del_stmt->execute();
    }
    header("Location: edit_project.php?id=$id");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title']       ?? '');
    $category    = trim($_POST['category']    ?? '');
    $description = trim($_POST['description'] ?? '');
    $location    = trim($_POST['location']    ?? 'Sri Lanka');

    $upload_dir  = '../img/uploads/';
    $image1_path = null; // null means "don't update"

    // Handle Hero Image
    if (isset($_FILES['image1']) && $_FILES['image1']['error'] !== UPLOAD_ERR_NO_FILE) {
        $saved_path = handle_image_upload($_FILES['image1'], $upload_dir, 'hero');
        if ($saved_path !== '') {
            resizeImage($saved_path, $saved_path, 1920, 1080);
            $image1_path = 'img/uploads/' . basename($saved_path);
        }
    }

    // FIX #2: Use prepared statements — build query safely
    if ($image1_path !== null) {
        $stmt = $conn->prepare(
            "UPDATE projects SET title = ?, category = ?, description = ?, location = ?, image1 = ? WHERE id = ?"
        );
        $stmt->bind_param('sssssi', $title, $category, $description, $location, $image1_path, $id);
    } else {
        $stmt = $conn->prepare(
            "UPDATE projects SET title = ?, category = ?, description = ?, location = ? WHERE id = ?"
        );
        $stmt->bind_param('ssssi', $title, $category, $description, $location, $id);
    }

    if ($stmt->execute()) {
        // Handle Gallery Images Upload
        if (isset($_FILES['gallery_images'])) {
            $total = min(count($_FILES['gallery_images']['name']), 12);
            for ($i = 0; $i < $total; $i++) {
                $file_entry = [
                    'name'     => $_FILES['gallery_images']['name'][$i],
                    'tmp_name' => $_FILES['gallery_images']['tmp_name'][$i],
                    'error'    => $_FILES['gallery_images']['error'][$i],
                    'size'     => $_FILES['gallery_images']['size'][$i],
                    'type'     => $_FILES['gallery_images']['type'][$i],
                ];
                $saved_path = handle_image_upload($file_entry, $upload_dir, 'gal');
                if ($saved_path !== '') {
                    resizeImage($saved_path, $saved_path, 800, 800);
                    $rel_path = 'img/uploads/' . basename($saved_path);

                    $img_stmt = $conn->prepare("INSERT INTO project_images (project_id, image_path) VALUES (?, ?)");
                    $img_stmt->bind_param('is', $id, $rel_path);
                    $img_stmt->execute();
                }
            }
        }

        header('Location: index.php');
        exit;
    } else {
        $error = "Database update failed. Please try again.";
    }
}

// FIX #2: Use prepared statement to fetch project
$proj_stmt = $conn->prepare("SELECT * FROM projects WHERE id = ?");
$proj_stmt->bind_param('i', $id);
$proj_stmt->execute();
$result  = $proj_stmt->get_result();
$project = $result->fetch_assoc();
if (!$project) {
    header('Location: index.php');
    exit;
}

$gal_stmt = $conn->prepare("SELECT * FROM project_images WHERE project_id = ?");
$gal_stmt->bind_param('i', $id);
$gal_stmt->execute();
$gallery_result = $gal_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Project</title>
    <style>
        body { font-family: sans-serif; background: #f4f4f4; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: #fff; padding: 20px; border-radius: 5px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="file"], textarea { width: 100%; padding: 8px; box-sizing: border-box; }
        .btn { padding: 8px 12px; background: #007bff; color: #fff; border: none; cursor: pointer; }
        .btn-danger { background: #dc3545; color: white; padding: 4px 8px; text-decoration: none; font-size: 12px; }
        .current-img { max-width: 150px; margin-top: 5px; display: block; }
        .gallery-preview { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 10px; }
        .gallery-item { border: 1px solid #ccc; padding: 5px; text-align: center; background: #fafafa; }
        .gallery-item img { height: 100px; object-fit: cover; display: block; margin-bottom: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Edit Project</h2>
        <?php if (isset($error)): ?>
            <!-- FIX #15: Escape error output to prevent XSS -->
            <p style='color:red;'><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Title</label>
                <input type="text" name="title" value="<?= htmlspecialchars($project['title']) ?>" required>
            </div>
            <div class="form-group">
                <label>Category</label>
                <input type="text" name="category" value="<?= htmlspecialchars($project['category']) ?>" required>
            </div>
            <div class="form-group">
                <label>Location</label>
                <input type="text" name="location" value="<?= htmlspecialchars($project['location'] ?? 'Sri Lanka') ?>" required>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="4" required><?= htmlspecialchars($project['description']) ?></textarea>
            </div>
            <div class="form-group">
                <label>Hero Image</label>
                <input type="file" name="image1" accept="image/*">
                <?php if ($project['image1']): ?>
                    <img src="../<?= htmlspecialchars($project['image1']) ?>" class="current-img">
                <?php endif; ?>
            </div>

            <hr style="margin: 20px 0;">

            <div class="form-group">
                <label>Gallery Images</label>
                <input type="file" name="gallery_images[]" accept="image/*" multiple>
            </div>

            <div class="form-group">
                <label>Gallery</label>
                <div class="gallery-preview">
                    <?php while ($gimg = $gallery_result->fetch_assoc()): ?>
                        <div class="gallery-item">
                            <img src="../<?= htmlspecialchars($gimg['image_path']) ?>">
                            <a href="?id=<?= $id ?>&delete_image=<?= (int)$gimg['id'] ?>" class="btn-danger" onclick="return confirm('Delete this image?')">Delete</a>
                        </div>
                    <?php endwhile; ?>
                    <?php if ($gallery_result->num_rows === 0) echo "<p>No gallery images yet.</p>"; ?>
                </div>
            </div>

            <button type="submit" class="btn">Save Changes</button>
            <a href="index.php" style="margin-left: 10px;">Cancel</a>
        </form>
    </div>
</body>
</html>
