<?php
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $conn->real_escape_string($_POST['title']);
    $category = $conn->real_escape_string($_POST['category']);
    $description = $conn->real_escape_string($_POST['description']);
    $link_url = $conn->real_escape_string($_POST['link_url']);
    
    $upload_dir = '../img/uploads/';
    $image1_path = '';
    $image2_path = '';
    $image3_path = '';

    // Helper to upload
    function uploadImage($file_key, $upload_dir) {
        if (isset($_FILES[$file_key]) && $_FILES[$file_key]['error'] === UPLOAD_ERR_OK) {
            $filename = time() . '_' . basename($_FILES[$file_key]['name']);
            $target_file = $upload_dir . $filename;
            if (move_uploaded_file($_FILES[$file_key]['tmp_name'], $target_file)) {
                return 'img/uploads/' . $filename;
            }
        }
        return '';
    }

    $image1_path = uploadImage('image1', $upload_dir);
    $image2_path = uploadImage('image2', $upload_dir);
    $image3_path = uploadImage('image3', $upload_dir);

    $sql = "INSERT INTO projects (title, category, description, link_url, image1, image2, image3) 
            VALUES ('$title', '$category', '$description', '$link_url', '$image1_path', '$image2_path', '$image3_path')";
    
    if ($conn->query($sql) === TRUE) {
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
        .container { max-width: 600px; margin: 0 auto; background: #fff; padding: 20px; border-radius: 5px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input[type="text"], input[type="file"] { width: 100%; padding: 8px; box-sizing: border-box; }
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
                <textarea name="description" rows="4" style="width: 100%; padding: 8px; box-sizing: border-box;" required></textarea>
            </div>
            <div class="form-group">
                <label>Link URL (e.g. urth_clone/poolscape-villa.html)</label>
                <input type="text" name="link_url" required>
            </div>
            <div class="form-group">
                <label>Image 1</label>
                <input type="file" name="image1" accept="image/*" required>
            </div>
            <div class="form-group">
                <label>Image 2</label>
                <input type="file" name="image2" accept="image/*" required>
            </div>
            <div class="form-group">
                <label>Image 3</label>
                <input type="file" name="image3" accept="image/*" required>
            </div>
            <button type="submit" class="btn">Save Project</button>
            <a href="index.php" style="margin-left: 10px;">Cancel</a>
        </form>
    </div>
</body>
</html>
