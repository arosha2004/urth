<?php

require_once __DIR__ . '/auth.php';
require_admin_auth();
require_once __DIR__ . '/../app/Core/private_storage.php';

$key = (string)($_GET['key'] ?? '');
$path = private_storage_path($key);
$realPath = $path !== null ? realpath($path) : false;
$root = realpath(private_storage_root());

if ($realPath === false || $root === false || strpos($realPath, $root . DIRECTORY_SEPARATOR) !== 0 || !is_file($realPath) || !is_readable($realPath)) {
    http_response_code(404);
    exit('Private file not found.');
}

$filename = basename($realPath);
header('Content-Type: application/octet-stream');
header('Content-Length: ' . filesize($realPath));
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store, no-cache, must-revalidate');
readfile($realPath);
