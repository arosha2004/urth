<?php
require 'config.php';
$res = $conn->query('SHOW CREATE TABLE projects');
$row = $res->fetch_row();
echo $row[1];
?>
