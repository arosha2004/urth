<?php
// setup.php
$db_host = 'localhost';
$db_user = 'root';
$db_pass = ''; // Default XAMPP password is empty

// Create connection
$conn = new mysqli($db_host, $db_user, $db_pass);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS urth_db";
if ($conn->query($sql) === TRUE) {
    echo "Database urth_db created successfully or already exists.<br>";
} else {
    echo "Error creating database: " . $conn->error . "<br>";
}

// Select the database
$conn->select_db('urth_db');

// Create table
$sql = "CREATE TABLE IF NOT EXISTS projects (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    category VARCHAR(255) DEFAULT 'Architectural Design',
    description TEXT DEFAULT NULL,
    link_url VARCHAR(255) NOT NULL,
    image1 VARCHAR(255) NOT NULL,
    image2 VARCHAR(255) NOT NULL,
    image3 VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Table projects created successfully or already exists.<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

// Create project_images table
$sql_images = "CREATE TABLE IF NOT EXISTS project_images (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    project_id INT(6) UNSIGNED NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
)";

if ($conn->query($sql_images) === TRUE) {
    echo "Table project_images created successfully or already exists.<br>";
} else {
    echo "Error creating project_images table: " . $conn->error . "<br>";
}

$conn->close();
echo "<a href='index.html'>Return to Home</a>";
?>
