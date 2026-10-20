<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/app/Core/private_storage.php';

function write_backup_chunk($handle, string $chunk): void
{
    if (fwrite($handle, $chunk) === false) {
        throw new RuntimeException('Could not write the database backup.');
    }
}

function copy_backup_tree(string $sourceRoot, string $destinationRoot, string $excludedRoot): void
{
    if (!is_dir($sourceRoot)) {
        return;
    }
    $directoryIterator = new RecursiveDirectoryIterator($sourceRoot, FilesystemIterator::SKIP_DOTS);
    $filteredIterator = new RecursiveCallbackFilterIterator($directoryIterator, static function ($entry) use ($excludedRoot) {
        $path = $entry->getPathname();
        return $path !== $excludedRoot && strpos($path, $excludedRoot . DIRECTORY_SEPARATOR) !== 0;
    });
    $iterator = new RecursiveIteratorIterator($filteredIterator, RecursiveIteratorIterator::SELF_FIRST);
    foreach ($iterator as $entry) {
        $source = $entry->getPathname();
        if (strpos($source, $excludedRoot . DIRECTORY_SEPARATOR) === 0) {
            continue;
        }
        $relative = substr($source, strlen($sourceRoot) + 1);
        $destination = $destinationRoot . DIRECTORY_SEPARATOR . $relative;
        if ($entry->isDir()) {
            if (!is_dir($destination) && !mkdir($destination, 0700, true) && !is_dir($destination)) {
                throw new RuntimeException('Could not create private-file backup directory.');
            }
        } elseif ($entry->isFile()) {
            $destinationDirectory = dirname($destination);
            if (!is_dir($destinationDirectory) && !mkdir($destinationDirectory, 0700, true) && !is_dir($destinationDirectory)) {
                throw new RuntimeException('Could not create private-file backup directory.');
            }
            if (!copy($source, $destination)) {
                throw new RuntimeException('Could not copy a private file into the backup.');
            }
            @chmod($destination, 0600);
        }
    }
}

try {
    $privateRoot = private_storage_root();
    $backupRoot = $privateRoot . DIRECTORY_SEPARATOR . 'backups';
    $backupName = 'urth-' . date('Ymd-His');
    $backupDirectory = $backupRoot . DIRECTORY_SEPARATOR . $backupName;
    if (!is_dir($backupDirectory) && !mkdir($backupDirectory, 0700, true) && !is_dir($backupDirectory)) {
        throw new RuntimeException('Could not create the private backup directory.');
    }

    $sqlPath = $backupDirectory . DIRECTORY_SEPARATOR . 'database.sql';
    $sqlHandle = fopen($sqlPath, 'xb');
    if ($sqlHandle === false) {
        throw new RuntimeException('Could not create the SQL backup file.');
    }
    @chmod($sqlPath, 0600);
    write_backup_chunk($sqlHandle, "SET FOREIGN_KEY_CHECKS=0;\n");

    $tablesResult = $conn->query('SHOW TABLES');
    if (!$tablesResult) {
        throw new RuntimeException('Could not list database tables: ' . $conn->error);
    }
    while ($tableRow = $tablesResult->fetch_array(MYSQLI_NUM)) {
        $table = (string)$tableRow[0];
        $quotedTable = '`' . str_replace('`', '``', $table) . '`';
        $createResult = $conn->query('SHOW CREATE TABLE ' . $quotedTable);
        if (!$createResult) {
            throw new RuntimeException('Could not read table definition: ' . $table);
        }
        $createRow = $createResult->fetch_assoc();
        $createSql = $createRow['Create Table'] ?? '';
        if ($createSql === '') {
            throw new RuntimeException('Table definition is missing for: ' . $table);
        }
        write_backup_chunk($sqlHandle, "\nDROP TABLE IF EXISTS {$quotedTable};\n{$createSql};\n");

        $dataResult = $conn->query('SELECT * FROM ' . $quotedTable, MYSQLI_USE_RESULT);
        if (!$dataResult) {
            throw new RuntimeException('Could not read table data: ' . $table);
        }
        while ($row = $dataResult->fetch_assoc()) {
            $values = [];
            foreach ($row as $value) {
                $values[] = $value === null ? 'NULL' : "'" . $conn->real_escape_string((string)$value) . "'";
            }
            write_backup_chunk($sqlHandle, 'INSERT INTO ' . $quotedTable . ' VALUES (' . implode(', ', $values) . ");\n");
        }
        $dataResult->free();
    }
    write_backup_chunk($sqlHandle, "SET FOREIGN_KEY_CHECKS=1;\n");
    fclose($sqlHandle);

    $filesBackup = $backupDirectory . DIRECTORY_SEPARATOR . 'private-files';
    if (!mkdir($filesBackup, 0700, true) && !is_dir($filesBackup)) {
        throw new RuntimeException('Could not create the private-file backup directory.');
    }
    copy_backup_tree($privateRoot, $filesBackup, $backupRoot);

    $productImages = __DIR__ . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'shop' . DIRECTORY_SEPARATOR . 'uploads';
    $productImagesBackup = $backupDirectory . DIRECTORY_SEPARATOR . 'product-images';
    if (!mkdir($productImagesBackup, 0700, true) && !is_dir($productImagesBackup)) {
        throw new RuntimeException('Could not create the product-image backup directory.');
    }
    copy_backup_tree($productImages, $productImagesBackup, $backupRoot);

    $adminDatabase = __DIR__ . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'urth_admin.sqlite';
    if (is_file($adminDatabase)) {
        $adminBackupDir = $backupDirectory . DIRECTORY_SEPARATOR . 'admin-console';
        if (!mkdir($adminBackupDir, 0700, true) && !is_dir($adminBackupDir)) {
            throw new RuntimeException('Could not create the admin database backup directory.');
        }
        if (!copy($adminDatabase, $adminBackupDir . DIRECTORY_SEPARATOR . 'urth_admin.sqlite')) {
            throw new RuntimeException('Could not back up the admin database.');
        }
        @chmod($adminBackupDir . DIRECTORY_SEPARATOR . 'urth_admin.sqlite', 0600);
    }

    echo "Backup created privately at: {$backupDirectory}\n";
} catch (Throwable $e) {
    if (isset($sqlHandle) && is_resource($sqlHandle)) {
        fclose($sqlHandle);
    }
    error_log('URTH backup failed: ' . $e->getMessage());
    fwrite(STDERR, 'Backup failed: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
