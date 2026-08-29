<?php
/**
 * URTH Admin Panel - Delete Project Handler
 */

require_once __DIR__ . '/auth.php';
require_admin_auth();

$id = intval($_POST['id'] ?? $_GET['id'] ?? 0);

if ($id > 0) {
    $stmt = $pdo->prepare("DELETE FROM projects WHERE id = :id");
    $stmt->execute(['id' => $id]);
    header('Location: index.php?msg=deleted');
    exit;
}

header('Location: index.php');
exit;
