<?php
require_once __DIR__ . '/auth.php';
require_admin_auth(); // FIX #5: Authentication guard was missing entirely

require_once '../config.php';
require_once 'resize_image.php';

// FIX #7 & #10: Allowed MIME types validated server-side; upload dir uses 0755
$allowed_mime_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$allowed_exts       = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

/**
 * Validate and move an uploaded image file.
 * Returns the relative web path on success, '' on failure.
 */
function handle_image_upload(array $file, string $upload_dir, string $suffix): string {
    global $allowed_mime_types, $allowed_exts;

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return '';
    }

    // FIX #7: Validate MIME type using finfo (server-side, not client filename)
    $finfo    = new finfo(FILEINFO_MIME_TYPE);
    $mime     = $finfo->file($file['tmp_name']);
    if (!in_array($mime, $allowed_mime_types, true)) {
        return '';
    }

    // Derive a safe extension from the validated MIME type
    $mime_to_ext = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
    ];
    $ext      = $mime_to_ext[$mime];
    $filename = time() . '_' . $suffix . '_' . bin2hex(random_bytes(4)) . '.' . $ext;

    // FIX #10: Ensure directory exists with secure permissions (755)
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $target_file = $upload_dir . $filename;
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return $target_file;
    }
    return '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title']       ?? '');
    $category    = trim($_POST['category']    ?? '');
    $description = trim($_POST['description'] ?? '');
    $location    = trim($_POST['location']    ?? '');

    if (!empty($title) && !empty($category)) {

        $upload_dir  = '../img/uploads/';
        $image1_path = '';

        // Handle Hero Image
        if (isset($_FILES['image1'])) {
            $saved_path = handle_image_upload($_FILES['image1'], $upload_dir, 'hero');
            if ($saved_path !== '') {
                resizeImage($saved_path, $saved_path, 1920, 1080);
                $image1_path = 'img/uploads/' . basename($saved_path);
            }
        }

        if (empty($image1_path)) {
            $image1_path = 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=600&q=80';
        }

        // FIX #1: Use prepared statements — no more string interpolation in SQL
        $stmt = $conn->prepare(
            "INSERT INTO projects (title, category, location, description, link_url, image1, image2, image3)
             VALUES (?, ?, ?, ?, '', ?, '', '')"
        );
        $stmt->bind_param('sssss', $title, $category, $location, $description, $image1_path);

        if ($stmt->execute()) {
            $new_id = $conn->insert_id;

            // Set dynamic link_url via prepared statement
            $upd = $conn->prepare("UPDATE projects SET link_url = ? WHERE id = ?");
            $link_url = 'project_detail.php?id=' . $new_id;
            $upd->bind_param('si', $link_url, $new_id);
            $upd->execute();

            // Handle Gallery Images (max 12)
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
                        $img_stmt->bind_param('is', $new_id, $rel_path);
                        $img_stmt->execute();
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
