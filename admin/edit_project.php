<?php
require_once '../config.php';

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}
$id = (int)$_GET['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $conn->real_escape_string($_POST['title']);
    $category = $conn->real_escape_string($_POST['category']);
    $description = $conn->real_escape_string($_POST['description']);
    $link_url = $conn->real_escape_string($_POST['link_url']);
    
    $upload_dir = '../img/uploads/';
    
    // Helper to upload
    function uploadImage($file_key, $upload_dir) {
        if (isset($_FILES[$file_key]) && $_FILES[$file_key]['error'] === UPLOAD_ERR_OK) {
            $filename = time() . '_' . basename($_FILES[$file_key]['name']);
            $target_file = $upload_dir . $filename;
            if (move_uploaded_file($_FILES[$file_key]['tmp_name'], $target_file)) {
                return 'img/uploads/' . $filename;
            }
        }
        return false;
    }

    $image1_path = uploadImage('image1', $upload_dir);
    $image2_path = uploadImage('image2', $upload_dir);
    $image3_path = uploadImage('image3', $upload_dir);

    // Build update query
    $updates = ["title='$title'", "category='$category'", "description='$description'", "link_url='$link_url'"];
    if ($image1_path) $updates[] = "image1='$image1_path'";
    if ($image2_path) $updates[] = "image2='$image2_path'";
    if ($image3_path) $updates[] = "image3='$image3_path'";

    $sql = "UPDATE projects SET " . implode(', ', $updates) . " WHERE id=$id";
    
    if ($conn->query($sql) === TRUE) {
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Project</title>
    <style>
        body { font-family: sans-serif; background: #f4f4f4; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; padding: 20px; border-radius: 5px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input[type="text"], input[type="file"] { width: 100%; padding: 8px; box-sizing: border-box; }
        .btn { padding: 8px 12px; background: #007bff; color: #fff; border: none; cursor: pointer; }
        .current-img { max-width: 150px; margin-top: 5px; display: block; }
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
                <textarea name="description" rows="4" style="width: 100%; padding: 8px; box-sizing: border-box;" required><?= htmlspecialchars($project['description']) ?></textarea>
            </div>
            <div class="form-group">
                <label>Link URL</label>
                <input type="text" name="link_url" value="<?= htmlspecialchars($project['link_url']) ?>" required>
            </div>
            <div class="form-group">
                <label>Image 1 (Leave blank to keep current)</label>
                <input type="file" name="image1" accept="image/*">
                <?php if($project['image1']): ?>
                    <img src="../<?= htmlspecialchars($project['image1']) ?>" class="current-img">
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label>Image 2 (Leave blank to keep current)</label>
                <input type="file" name="image2" accept="image/*">
                <?php if($project['image2']): ?>
                    <img src="../<?= htmlspecialchars($project['image2']) ?>" class="current-img">
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label>Image 3 (Leave blank to keep current)</label>
                <input type="file" name="image3" accept="image/*">
                <?php if($project['image3']): ?>
                    <img src="../<?= htmlspecialchars($project['image3']) ?>" class="current-img">
                <?php endif; ?>
            </div>
            <button type="submit" class="btn">Update Project</button>
            <a href="index.php" style="margin-left: 10px;">Cancel</a>
        </form>
    </div>
</body>
</html>
