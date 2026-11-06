<?php
// config.php

// 1. Try to load variables from .env file if it exists
$envFile = __DIR__ . '/.env';
if (file_exists($envFile) && is_readable($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $val) = explode('=', $line, 2);
            $key = trim($key);
            $val = trim(trim($val), "\"'");
            if (!getenv($key)) {
                putenv("$key=$val");
                $_ENV[$key] = $val;
            }
        }
    }
}

// 2. Detect environment (local XAMPP vs Hostinger production)
$is_local = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)
         || php_sapi_name() === 'cli'
         || (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false);

$db_host = getenv('DB_HOST') ?: 'localhost';
$db_user = getenv('DB_USER');
$db_pass = getenv('DB_PASS');
$db_name = getenv('DB_NAME');

if (!$db_user) {
    if ($is_local) {
        $db_user = 'root';
        $db_pass = '';
        $db_name = 'urth_db';
    } else {
        // Hostinger production database credentials
        $db_user = 'u828029692_urthadmin';
        $db_pass = 'RootFh@21$';
        $db_name = 'u828029692_urth_db';
    }
}

// Create connection
try {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    // Set charset to ensure proper encoding
    $conn->set_charset("utf8mb4");
} catch (Throwable $e) {
    
    // If production credentials fail or local fails, log and display appropriate message
    error_log('DB connection failed: ' . $e->getMessage());
    if ($is_local) {
        // Detailed output only for local/developer access
        echo "<div style='border: 1px solid red; padding: 20px; font-family: sans-serif;'>";
        echo "<h3>Database Connection Failed</h3>";
        echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p>Check DB_HOST, DB_USER, DB_PASS, DB_NAME environment variables or .env file.</p>";
        echo "</div>";
    } else {
        echo "<div style='border: 1px solid red; padding: 20px; font-family: sans-serif;'>";
        echo "<h3>Service Unavailable</h3>";
        echo "<p>The site is temporarily unable to connect to the database. Please try again later.</p>";
        echo "</div>";
    }
    die();
}

/**
 * Resolves a project image path to a valid, existing file on disk.
 * Falls back gracefully to matching filenames, project titles, or default hero images.
 */
function resolve_project_image(?string $image_path, string $title = ''): string {
    $raw_path = trim((string)$image_path);

    // If it's an external URL (http:// or https://), return as-is
    if (preg_match('/^https?:\/\//i', $raw_path)) {
        return $raw_path;
    }

    $clean_path = ltrim($raw_path, '/\\');
    $root_dir = __DIR__;

    // Special check for European Lard Station: ensure it uses its dedicated hero image
    // if the assigned path points to a generic or missing file
    $title_lower = strtolower($title);
    if ((strpos($title_lower, 'european') !== false || strpos($title_lower, 'lard') !== false)
        && ($clean_path === '' || !file_exists($root_dir . '/' . $clean_path) || strpos($clean_path, '4plex') !== false)
    ) {
        if (file_exists($root_dir . '/img/european_lard_station.png')) {
            return 'img/european_lard_station.png';
        }
    }

    // 1. Direct path exists
    if ($clean_path !== '' && file_exists($root_dir . '/' . $clean_path)) {
        return $clean_path;
    }

    // 2. Try removing timestamp / prefix hashes from uploads
    if ($clean_path !== '') {
        $base = basename($clean_path);
        $stripped = preg_replace('/^\d+_(hero_|\d+_gal_)?/', '', $base);
        $candidates = [
            'img/' . $stripped,
            'img/uploads/' . $stripped,
            'img/urthprojs/' . $stripped,
            'img/servicesnew/' . $stripped,
            'img/' . $base,
            'img/uploads/' . $base,
            'img/urthprojs/' . $base,
        ];
        $stripped2 = preg_replace('/^\d+_(hero_|\d+_gal_)?/', '', $stripped);
        if ($stripped2 !== $stripped) {
            $candidates[] = 'img/' . $stripped2;
            $candidates[] = 'img/urthprojs/' . $stripped2;
            $candidates[] = 'img/uploads/' . $stripped2;
        }

        foreach ($candidates as $cand) {
            if (file_exists($root_dir . '/' . $cand)) {
                return $cand;
            }
        }
    }

    // 3. Match known project titles / keywords
    if (strpos($title_lower, 'european') !== false || strpos($title_lower, 'lard') !== false) {
        if (file_exists($root_dir . '/img/european_lard_station.png')) return 'img/european_lard_station.png';
    }
    if (strpos($title_lower, 'poolscape') !== false) {
        if (file_exists($root_dir . '/img/poolscape_villa.png')) return 'img/poolscape_villa.png';
    }
    if (strpos($title_lower, 'yabroudi') !== false || strpos($title_lower, 'desert') !== false) {
        if (file_exists($root_dir . '/img/desert_residence.png')) return 'img/desert_residence.png';
    }
    if (strpos($title_lower, 'cultural') !== false || strpos($title_lower, 'american') !== false || strpos($title_lower, 'furnitures') !== false) {
        if (file_exists($root_dir . '/img/american_lard_station.png')) return 'img/american_lard_station.png';
    }
    if (strpos($title_lower, 'tower') !== false || strpos($title_lower, 'dalbourne') !== false) {
        if (file_exists($root_dir . '/img/urban_tower.png')) return 'img/urban_tower.png';
    }
    if (strpos($title_lower, 'spatial') !== false || strpos($title_lower, 'redesign') !== false) {
        if (file_exists($root_dir . '/img/uploads/1788022116_2_gal_4plex.png')) return 'img/uploads/1788022116_2_gal_4plex.png';
        if (file_exists($root_dir . '/img/urthprojs/4plex.png')) return 'img/urthprojs/4plex.png';
    }

    // 4. Default fallback hero images
    if (file_exists($root_dir . '/img/projects_hero.jpg')) {
        return 'img/projects_hero.jpg';
    }
    if (file_exists($root_dir . '/img/about_hero.jpg')) {
        return 'img/about_hero.jpg';
    }

    return $clean_path !== '' ? $clean_path : 'img/projects_hero.jpg';
}
?>
