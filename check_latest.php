<?php
require 'config.php';
$res = $conn->query("SELECT id, title FROM projects ORDER BY id DESC LIMIT 1");
print_r($res->fetch_assoc());
$res = $conn->query("SELECT * FROM project_images ORDER BY id DESC LIMIT 5");
while($r = $res->fetch_assoc()) print_r($r);
?>
