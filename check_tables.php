<?php
require 'config.php';
$res = $conn->query('SHOW TABLES');
while($r = $res->fetch_row()) {
    echo $r[0] . "\n";
}
if ($res = $conn->query('DESCRIBE project_images')) {
    echo "project_images exists\n";
} else {
    echo "project_images does NOT exist\n";
}
?>
