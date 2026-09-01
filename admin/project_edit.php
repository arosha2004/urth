<?php
/**
 * URTH Admin Panel - Edit Project Handler
 */

require_once __DIR__ . '/auth.php';
require_admin_auth();
require_once '../config.php';
require_once 'resize_image.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    $title = $conn->real_escape_string(trim($_POST['title'] ?? ''));
    $category = $conn->real_escape_string(trim($_POST['category'] ?? ''));
    $description = $conn->real_escape_string(trim($_POST['description'] ?? ''));

    if ($id > 0 && !empty($title) && !empty($category)) {
        
        $sql = "UPDATE projects SET title = '$title', category = '$category', description = '$description' WHERE id = $id";
        $conn->query($sql);
        
        $upload_dir = '../img/uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

        // Handle Hero Image if uploaded
        if (isset($_FILES['image1']) && $_FILES['image1']['error'] === UPLOAD_ERR_OK) {
            $filename = time() . '_hero_' . basename($_FILES['image1']['name']);
            $target_file = $upload_dir . $filename;
            if (move_uploaded_file($_FILES['image1']['tmp_name'], $target_file)) {
                resizeImage($target_file, $target_file, 1920, 1080);
                $image1_path = 'img/uploads/' . $filename;
                $conn->query("UPDATE projects SET image1 = '$image1_path' WHERE id = $id");
            }
        }

        // Handle deleted gallery images
        if (!empty($_POST['deleted_gallery_images'])) {
            $deleted_ids = explode(',', $_POST['deleted_gallery_images']);
            foreach ($deleted_ids as $del_id) {
                $del_id = intval($del_id);
                if ($del_id > 0) {
                    $conn->query("DELETE FROM project_images WHERE id = $del_id AND project_id = $id");
                }
            }
        }

        // Handle new gallery images
        if (isset($_FILES['gallery_images'])) {
            $curr_result = $conn->query("SELECT COUNT(id) as count FROM project_images WHERE project_id = $id");
            $curr_count = 0;
            if ($curr_row = $curr_result->fetch_assoc()) {
                $curr_count = intval($curr_row['count']);
            }
            
            $total = count($_FILES['gallery_images']['name']);
            for ($i = 0; $i < $total; $i++) {
                if ($curr_count >= 12) break;
                if ($_FILES['gallery_images']['error'][$i] === UPLOAD_ERR_OK) {
                    $filename = time() . '_' . $i . '_gal_' . basename($_FILES['gallery_images']['name'][$i]);
                    $target_file = $upload_dir . $filename;
                    if (move_uploaded_file($_FILES['gallery_images']['tmp_name'][$i], $target_file)) {
                        resizeImage($target_file, $target_file, 800, 800);
                        $rel_path = 'img/uploads/' . $filename;
                        
                        $stmt = $conn->prepare("INSERT INTO project_images (project_id, image_path) VALUES (?, ?)");
                        $stmt->bind_param("is", $id, $rel_path);
                        $stmt->execute();
                        $curr_count++;
                    }
                }
            }
        }

        header('Location: index.php?msg=updated');
        exit;
    } else {
        header('Location: index.php?msg=error_invalid');
        exit;
    }
}

header('Location: index.php');
exit;
