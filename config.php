<?php
// config.php
$db_host = 'localhost';
$db_user = 'u828029692_urthadmin';
$db_pass = 'RootFh@21$';
$db_name = 'u828029692_urth_db';

// Create connection
try {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    // Set charset to ensure proper encoding
    $conn->set_charset("utf8mb4");
} catch (Throwable $e) {    
    try {
        $conn = new mysqli('localhost', 'root', '', 'urth_db');
        $conn->set_charset("utf8mb4");
    } catch (Throwable $e2) {
        // We avoid sending a 500 status code because browsers like Chrome hide the error text if the response is too short.
        echo "<div style='border: 1px solid red; padding: 20px; font-family: sans-serif;'>";
        echo "<h3>Database Connection Failed</h3>";
        echo "<p><strong>Error Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p><strong>Troubleshooting for Hostinger:</strong></p>";
        echo "<ul>";
        echo "<li>Double-check that the password is exactly correct.</li>";
        echo "<li>Make sure you have <strong>added the user to the database</strong> in Hostinger's MySQL Databases section and granted <strong>All Privileges</strong>. Creating them separately isn't enough; they must be linked.</li>";
        echo "</ul>";
        echo "</div>";
        die();
    }
}
?>
