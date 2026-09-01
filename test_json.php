<?php
$array = [
    ['id' => 1, 'path' => "img/uploads/caf\xE9.jpg"] // \xE9 is invalid UTF-8 (it's iso-8859-1 for é)
];
$json = json_encode($array);
echo "JSON: ";
var_dump($json);
?>
