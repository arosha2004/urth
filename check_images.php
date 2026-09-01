<?php
require 'config.php';
$res = $conn->query('SELECT * FROM project_images');
while($r = $res->fetch_assoc()) {
    print_r($r);
}
?>
