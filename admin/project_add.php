<?php
/**
 * URTH Admin Panel - Add Project Handler
 */

require_once __DIR__ . '/auth.php';
require_admin_auth();
require_once '../config.php';
require_once 'resize_image.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $conn->real_escape_string(trim($_POST['title'] ?? ''));
    $category = $conn->real_escape_string(trim($_POST['category'] ?? ''));
    $description = $conn->real_escape_string(trim($_POST['description'] ?? ''));

    if (!empty($title) && !empty($category)) {
        
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
        
        if (empty($image1_path)) {
            $image1_path = 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=600&q=80';
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
                if ($total > 12) $total = 12; // Enforce maximum of 1
                // 2 images
                
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

            header('Location: index.php?msg=added');
            exit;
        } else {
            header('Location: index.php?msg=error_invalid');
            exit;
        }
    } else {
        header('Location: index.php?msg=error_missing');
        exit;
    }
}

header('Location: index.php');
exit;
