<?php

function private_storage_root(): string
{
    $configured = getenv('URTH_PRIVATE_STORAGE');
    $root = $configured !== false && trim($configured) !== ''
        ? trim($configured)
        : dirname(__DIR__, 4) . DIRECTORY_SEPARATOR . 'urth-private';

    $candidate = rtrim($root, DIRECTORY_SEPARATOR);
    if (!preg_match('~^(?:[A-Za-z]:[\\\\/]|/)~', $candidate)) {
        throw new RuntimeException('Private storage path must be absolute.');
    }
    $documentRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
    $resolvedCandidate = realpath($candidate);
    if ($resolvedCandidate === false) {
        $parent = realpath(dirname($candidate));
        if ($parent === false) {
            throw new RuntimeException('Private storage parent directory does not exist.');
        }
        $resolvedCandidate = $parent . DIRECTORY_SEPARATOR . basename($candidate);
    }
    if ($documentRoot !== false) {
        $normalizedDocumentRoot = strtolower(rtrim(str_replace('\\', '/', $documentRoot), '/'));
        $normalizedCandidate = strtolower(rtrim(str_replace('\\', '/', $resolvedCandidate), '/'));
        if ($normalizedCandidate === $normalizedDocumentRoot || strpos($normalizedCandidate, $normalizedDocumentRoot . '/') === 0) {
            throw new RuntimeException('Private storage must be configured outside the web document root.');
        }
    }

    if (!is_dir($resolvedCandidate) && !mkdir($resolvedCandidate, 0700, true) && !is_dir($resolvedCandidate)) {
        throw new RuntimeException('Could not create private storage directory.');
    }
    if (!is_writable($resolvedCandidate)) {
        throw new RuntimeException('Private storage directory is not writable.');
    }

    return $resolvedCandidate;
}

function private_storage_key_is_valid(string $key): bool
{
    return preg_match('~^(receipts|deliverables|products|customer-uploads)/[A-Za-z0-9][A-Za-z0-9._-]{0,119}$~D', $key) === 1;
}

function private_storage_path(string $key): ?string
{
    if (!private_storage_key_is_valid($key)) {
        return null;
    }

    return private_storage_root() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $key);
}

function store_private_upload(array $upload, string $category, array $allowedExtensions, int $maxBytes, ?string &$error = null): ?string
{
    $error = null;
    if (!in_array($category, ['receipts', 'deliverables', 'products', 'customer-uploads'], true)) {
        throw new InvalidArgumentException('Unsupported private upload category.');
    }

    if (!isset($upload['error']) || $upload['error'] !== UPLOAD_ERR_OK) {
        $error = ($upload['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_INI_SIZE
            || ($upload['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_FORM_SIZE
            ? 'error_size'
            : 'error_upload';
        return null;
    }

    if (!isset($upload['tmp_name'], $upload['name'], $upload['size'])
        || !is_uploaded_file($upload['tmp_name'])
        || (int)$upload['size'] <= 0
        || (int)$upload['size'] > $maxBytes) {
        $error = 'error_size';
        return null;
    }

    $extension = strtolower(pathinfo($upload['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedExtensions, true)) {
        $error = 'error_filetype';
        return null;
    }

    $root = private_storage_root();
    $directory = $root . DIRECTORY_SEPARATOR . $category;
    if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
        error_log('URTH private upload directory creation failed: ' . $directory);
        $error = 'error_upload';
        return null;
    }

    $filename = bin2hex(random_bytes(20)) . '.' . $extension;
    $destination = $directory . DIRECTORY_SEPARATOR . $filename;
    if (!move_uploaded_file($upload['tmp_name'], $destination)) {
        error_log('URTH private upload move failed for category: ' . $category);
        $error = 'error_upload';
        return null;
    }

    @chmod($destination, 0600);
    return $category . '/' . $filename;
}
