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
    // We avoid sending a 500 status code because browsers like Chrome hide the error text if the response is too short.
    echo "<div style='border: 1px solid red; padding: 20px; font-family: sans-serif;'>";
    echo "<h3>Database Connection Failed</h3>";
    echo "<p><strong>Error Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Troubleshooting for Hostinger:</strong></p>";
    echo "<ul>";
    echo "<li>Ensure DB_HOST, DB_USER, DB_PASS, and DB_NAME environment variables are set in your hosting control panel.</li>";
    echo "<li>Double-check that the password is exactly correct.</li>";
    echo "<li>Make sure you have <strong>added the user to the database</strong> in Hostinger's MySQL Databases section and granted <strong>All Privileges</strong>. Creating them separately isn't enough; they must be linked.</li>";
    echo "</ul>";
    echo "</div>";
    die();
}
?>
