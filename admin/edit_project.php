<?php
require_once '../config.php';
require_once 'resize_image.php';

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}
$id = (int)$_GET['id'];

// Handle image deletion
if (isset($_GET['delete_image'])) {
    $img_id = (int)$_GET['delete_image'];
    // get image path to delete file
    $img_query = $conn->query("SELECT image_path FROM project_images WHERE id=$img_id AND project_id=$id");
    if ($img_query->num_rows > 0) {
        $img = $img_query->fetch_assoc();
        $file_path = '../' . $img['image_path'];
        if (file_exists($file_path) && !is_dir($file_path)) {
            unlink($file_path);
        }
        $conn->query("DELETE FROM project_images WHERE id=$img_id AND project_id=$id");
    }
    header("Location: edit_project.php?id=$id");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $conn->real_escape_string($_POST['title']);
    $category = $conn->real_escape_string($_POST['category']);
    $description = $conn->real_escape_string($_POST['description']);
    
    $upload_dir = '../img/uploads/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
    
    // Handle Hero Image (image1)
    $image1_path = false;
    if (isset($_FILES['image1']) && $_FILES['image1']['error'] === UPLOAD_ERR_OK) {
        $filename = time() . '_hero_' . basename($_FILES['image1']['name']);
        $target_file = $upload_dir . $filename;
        if (move_uploaded_file($_FILES['image1']['tmp_name'], $target_file)) {
            resizeImage($target_file, $target_file, 1920, 1080);
            $image1_path = 'img/uploads/' . $filename;
        }
    }

    // Build update query
    $updates = ["title='$title'", "category='$category'", "description='$description'"];
    if ($image1_path) $updates[] = "image1='$image1_path'";

    $sql = "UPDATE projects SET " . implode(', ', $updates) . " WHERE id=$id";
    
    if ($conn->query($sql) === TRUE) {
        
        // Handle Gallery Images Upload
        if (isset($_FILES['gallery_images'])) {
            $total = count($_FILES['gallery_images']['name']);
            for ($i = 0; $i < $total; $i++) {
                if ($_FILES['gallery_images']['error'][$i] === UPLOAD_ERR_OK) {
                    $filename = time() . '_' . $i . '_gal_' . basename($_FILES['gallery_images']['name'][$i]);
                    $target_file = $upload_dir . $filename;
                    if (move_uploaded_file($_FILES['gallery_images']['tmp_name'][$i], $target_file)) {
                        // Resize gallery image to max 800x800
                        resizeImage($target_file, $target_file, 800, 800);
                        $rel_path = 'img/uploads/' . $filename;
                        
                        $stmt = $conn->prepare("INSERT INTO project_images (project_id, image_path) VALUES (?, ?)");
                        $stmt->bind_param("is", $id, $rel_path);
                        $stmt->execute();
                    }
                }
            }
        }
        
        header('Location: index.php');
        exit;
    } else {
        $error = "Error: " . $conn->error;
    }
}

$result = $conn->query("SELECT * FROM projects WHERE id=$id");
$project = $result->fetch_assoc();
if (!$project) {
    header('Location: index.php');
    exit;
}

$gallery_result = $conn->query("SELECT * FROM project_images WHERE project_id=$id");
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
        <?php if(isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Project Title</label>
                <input type="text" name="title" value="<?= htmlspecialchars($project['title']) ?>" required>
            </div>
            <div class="form-group">
                <label>Category</label>
                <input type="text" name="category" value="<?= htmlspecialchars($project['category']) ?>" required>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="4" required><?= htmlspecialchars($project['description']) ?></textarea>
            </div>
            <div class="form-group">
                <label>Hero Image</label>
                <input type="file" name="image1" accept="image/*">
                <?php if($project['image1']): ?>
                    <img src="../<?= htmlspecialchars($project['image1']) ?>" class="current-img">
                <?php endif; ?>
            </div>
            
            <hr style="margin: 20px 0;">
            
            <div class="form-group">
                <label>Add New Gallery Images (Multiple)</label>
                <input type="file" name="gallery_images[]" accept="image/*" multiple>
            </div>
            
            <div class="form-group">
                <label>Current Gallery Images</label>
                <div class="gallery-preview">
                    <?php while ($gimg = $gallery_result->fetch_assoc()): ?>
                        <div class="gallery-item">
                            <img src="../<?= htmlspecialchars($gimg['image_path']) ?>">
                            <a href="?id=<?= $id ?>&delete_image=<?= $gimg['id'] ?>" class="btn-danger" onclick="return confirm('Delete this image?')">Delete</a>
                        </div>
                    <?php endwhile; ?>
                    <?php if ($gallery_result->num_rows == 0) echo "<p>No gallery images yet.</p>"; ?>
                </div>
            </div>
            
            <button type="submit" class="btn">Update Project</button>
            <a href="index.php" style="margin-left: 10px;">Cancel</a>
        </form>
    </div>
</body>
</html>
