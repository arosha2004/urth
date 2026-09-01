<?php
require 'config.php';
$res = $conn->query('SHOW CREATE TABLE project_images');
$row = $res->fetch_row();
echo $row[1];
?>
