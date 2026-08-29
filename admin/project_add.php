<?php
/**
 * URTH Admin Panel - Add Project Handler
 */

require_once __DIR__ . '/auth.php';
require_admin_auth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $link_url = trim($_POST['link_url'] ?? '');
    $image_url = trim($_POST['image_url'] ?? '');

    if (!empty($title) && !empty($category) && !empty($link_url)) {
        if (empty($image_url)) {
            $image_url = 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=600&q=80';
        }

        require_once '../config.php';
        $stmt = $conn->prepare("INSERT INTO projects (title, category, link_url, image1, description, image2, image3) VALUES (?, ?, ?, ?, '', '', '')");
        $stmt->bind_param("ssss", $title, $category, $link_url, $image_url);
        $stmt->execute();

        header('Location: index.php?msg=added');
        exit;
    } else {
        header('Location: index.php?msg=error_missing');
        exit;
    }
}

header('Location: index.php');
exit;
