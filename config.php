<?php
// config.php
// Credentials are loaded from environment variables (set in the server / hosting control panel).
// NEVER commit real credentials to source control.
// For local XAMPP development, the fallback values below are used automatically.

$db_host = getenv('DB_HOST') ?: 'localhost';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') ?: '';
$db_name = getenv('DB_NAME') ?: 'urth_db';

// Create connection
try {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    // Set charset to ensure proper encoding
    $conn->set_charset("utf8mb4");
} catch (Throwable $e) {
    // FIX #13: Never expose raw DB error details to the browser in production.
    // Log the real error server-side; show a safe message to the user.
    error_log('DB connection failed: ' . $e->getMessage());
    $is_local = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);
    if ($is_local) {
        // Detailed output only for local/developer access
        echo "<div style='border: 1px solid red; padding: 20px; font-family: sans-serif;'>";
        echo "<h3>Database Connection Failed</h3>";
        echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p>Check DB_HOST, DB_USER, DB_PASS, DB_NAME environment variables.</p>";
        echo "</div>";
    } else {
        echo "<div style='border: 1px solid red; padding: 20px; font-family: sans-serif;'>";
        echo "<h3>Service Unavailable</h3>";
        echo "<p>The site is temporarily unable to connect to the database. Please try again later.</p>";
        echo "</div>";
    }
    die();
}
?>
