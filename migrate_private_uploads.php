<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/app/Core/private_storage.php';

function migrate_private_upload(mysqli $conn, string $table, string $column, string $sourcePrefix, string $targetCategory): int
{
    $allowedTargets = ['orders.receipt_url', 'orders.digital_deliverable_file', 'products.digital_deliverable_file'];
    if (!in_array($table . '.' . $column, $allowedTargets, true)) {
        throw new InvalidArgumentException('Unsupported migration target.');
    }

    $stmt = $conn->query("SELECT id, {$column} AS file_key FROM {$table} WHERE {$column} LIKE 'uploads/%'");
    if (!$stmt) {
        throw new RuntimeException('Could not read legacy upload records: ' . $conn->error);
    }

    $updated = 0;
    while ($row = $stmt->fetch_assoc()) {
        $legacyKey = (string)$row['file_key'];
        if (strpos($legacyKey, $sourcePrefix) !== 0) {
            continue;
        }

        $filename = basename($legacyKey);
        if ($filename === '' || $filename !== $legacyKey && strpos($legacyKey, $sourcePrefix . $filename) !== 0) {
            throw new RuntimeException('Unsafe legacy upload path found in record ' . (int)$row['id']);
        }
        $source = realpath(__DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $legacyKey));
        $sourceDirectory = realpath(__DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . basename(dirname($legacyKey)));
        if ($source === false || $sourceDirectory === false || strpos($source, $sourceDirectory . DIRECTORY_SEPARATOR) !== 0 || !is_file($source)) {
            error_log('URTH legacy private upload missing; record ' . (int)$row['id']);
            continue;
        }

        $destinationKey = $targetCategory . '/' . $filename;
        $destination = private_storage_path($destinationKey);
        if ($destination === null) {
            throw new RuntimeException('Could not resolve private upload destination.');
        }
        $destinationDirectory = dirname($destination);
        if (!is_dir($destinationDirectory) && !mkdir($destinationDirectory, 0700, true) && !is_dir($destinationDirectory)) {
            throw new RuntimeException('Could not create private upload directory.');
        }
        if (!copy($source, $destination)) {
            throw new RuntimeException('Could not copy legacy upload into private storage.');
        }
        @chmod($destination, 0600);

        $update = $conn->prepare("UPDATE {$table} SET {$column} = ? WHERE id = ?");
        if (!$update) {
            throw new RuntimeException('Could not prepare legacy upload record update.');
        }
        $recordId = (int)$row['id'];
        $update->bind_param('si', $destinationKey, $recordId);
        if (!$update->execute()) {
            throw new RuntimeException('Could not update legacy upload record.');
        }
        $update->close();
        if (!unlink($source)) {
            error_log('URTH migrated upload remains in legacy directory for record ' . $recordId);
        }
        $updated++;
    }

    $stmt->free();
    return $updated;
}

$total = 0;
$total += migrate_private_upload($conn, 'orders', 'receipt_url', 'uploads/receipts/', 'receipts');
$total += migrate_private_upload($conn, 'orders', 'digital_deliverable_file', 'uploads/deliverables/', 'deliverables');
$total += migrate_private_upload($conn, 'products', 'digital_deliverable_file', 'uploads/deliverables/', 'products');

echo "Moved {$total} legacy file(s) to private storage.\n";
