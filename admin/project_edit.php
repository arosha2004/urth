<?php
/**
 * URTH Admin Panel - Edit Project Handler
 */

require_once __DIR__ . '/auth.php';
require_admin_auth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $link_url = trim($_POST['link_url'] ?? '');
    $image_url = trim($_POST['image_url'] ?? '');

    if ($id > 0 && !empty($title) && !empty($category) && !empty($link_url)) {
        if (empty($image_url)) {
            $image_url = 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=600&q=80';
        }

        require_once '../config.php';
        $stmt = $conn->prepare("UPDATE projects SET title = ?, category = ?, link_url = ?, image1 = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $title, $category, $link_url, $image_url, $id);
        $stmt->execute();

        header('Location: index.php?msg=updated');
        exit;
    } else {
        header('Location: index.php?msg=error_invalid');
        exit;
    }
}

header('Location: index.php');
exit;
