<?php
require_once '../config.php';
require_once 'resize_image.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $conn->real_escape_string($_POST['title']);
    $category = $conn->real_escape_string($_POST['category']);
    $description = $conn->real_escape_string($_POST['description']);
    
    $upload_dir = '../img/uploads/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
    
    $image1_path = '';

    // Handle Hero Image
    if (isset($_FILES['image1']) && $_FILES['image1']['error'] === UPLOAD_ERR_OK) {
        $filename = time() . '_hero_' . basename($_FILES['image1']['name']);
        $target_file = $upload_dir . $filename;
        if (move_uploaded_file($_FILES['image1']['tmp_name'], $target_file)) {
            resizeImage($target_file, $target_file, 1920, 1080);
            $image1_path = 'img/uploads/' . $filename;
        }
    }

    $sql = "INSERT INTO projects (title, category, description, link_url, image1, image2, image3) 
            VALUES ('$title', '$category', '$description', '', '$image1_path', '', '')";
    
    if ($conn->query($sql) === TRUE) {
        $new_id = $conn->insert_id;
        
        // Set dynamic link_url
        $conn->query("UPDATE projects SET link_url='project_detail.php?id=$new_id' WHERE id=$new_id");
        
        // Handle Gallery Images
        if (isset($_FILES['gallery_images'])) {
            $total = count($_FILES['gallery_images']['name']);
            for ($i = 0; $i < $total; $i++) {
                if ($_FILES['gallery_images']['error'][$i] === UPLOAD_ERR_OK) {
                    $filename = time() . '_' . $i . '_gal_' . basename($_FILES['gallery_images']['name'][$i]);
                    $target_file = $upload_dir . $filename;
                    if (move_uploaded_file($_FILES['gallery_images']['tmp_name'][$i], $target_file)) {
                        resizeImage($target_file, $target_file, 800, 800);
                        $rel_path = 'img/uploads/' . $filename;
                        
                        $stmt = $conn->prepare("INSERT INTO project_images (project_id, image_path) VALUES (?, ?)");
                        $stmt->bind_param("is", $new_id, $rel_path);
                        $stmt->execute();
                    }
                }
            }
        }
        
        header('Location: index.php');
        exit;
    } else {
        $error = "Error: " . $sql . "<br>" . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Project</title>
    <style>
        body { font-family: sans-serif; background: #f4f4f4; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: #fff; padding: 20px; border-radius: 5px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="file"], textarea { width: 100%; padding: 8px; box-sizing: border-box; }
        .btn { padding: 8px 12px; background: #333; color: #fff; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Add New Project</h2>
        <?php if(isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Project Title</label>
                <input type="text" name="title" required>
            </div>
            <div class="form-group">
                <label>Category</label>
                <input type="text" name="category" required>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="4" required></textarea>
            </div>
            <div class="form-group">
                <label>Hero Image</label>
                <input type="file" name="image1" accept="image/*" required>
            </div>
            <hr style="margin: 20px 0;">
            <div class="form-group">
                <label>Gallery Images (Multiple)</label>
                <input type="file" name="gallery_images[]" accept="image/*" multiple>
            </div>
            <button type="submit" class="btn">Save Project</button>
            <a href="index.php" style="margin-left: 10px;">Cancel</a>
        </form>
    </div>
</body>
</html>
