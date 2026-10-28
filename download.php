<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/app/Core/private_storage.php';

$token = (string)($_GET['token'] ?? '');
if (!preg_match('/^[A-Za-z0-9_-]{43}$/D', $token)) {
    http_response_code(404);
    exit('Download link is invalid or has expired.');
}

$tableCheck = $conn->query("SHOW TABLES LIKE 'shop_download_tokens'");
if (!$tableCheck || $tableCheck->num_rows === 0) {
    http_response_code(404);
    exit('Download link is invalid or has expired.');
}

$tokenHash = hash('sha256', $token);
$stmt = $conn->prepare("SELECT t.file_key FROM shop_download_tokens t INNER JOIN orders o ON o.id = t.order_id WHERE t.token_hash = ? AND t.revoked_at IS NULL AND t.expires_at > NOW() AND o.payment_status = 'fully_paid' LIMIT 1");
if (!$stmt) {
    throw new RuntimeException('Could not prepare secure download lookup.');
}
$stmt->bind_param('s', $tokenHash);
$stmt->execute();
$result = $stmt->get_result();
$download = $result ? $result->fetch_assoc() : null;
$stmt->close();

$fileKey = $download['file_key'] ?? '';
$path = $fileKey !== '' ? private_storage_path($fileKey) : null;
$realPath = $path !== null ? realpath($path) : false;
$root = realpath(private_storage_root());
if ($realPath === false || $root === false || strpos($realPath, $root . DIRECTORY_SEPARATOR) !== 0 || !is_file($realPath) || !is_readable($realPath)) {
    http_response_code(404);
    exit('Download link is invalid or has expired.');
}

header('Content-Type: application/octet-stream');
header('Content-Length: ' . filesize($realPath));
header('Content-Disposition: attachment; filename="' . basename($realPath) . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store, no-cache, must-revalidate');
readfile($realPath);
