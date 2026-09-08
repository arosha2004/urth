<?php
require 'config.php';
$result = $conn->query('SELECT id, title, image1 FROM projects');
while($row = $result->fetch_assoc()) {
    echo $row['id'] . ': ' . $row['title'] . ' | img: ' . $row['image1'] . PHP_EOL;
}
?>
