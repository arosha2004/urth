<?php
require 'config.php';

$result = $conn->query("SELECT * FROM projects ORDER BY id DESC LIMIT 1");
$projects = [];
while($row = $result->fetch_assoc()) {
    $projects[] = $row;
}

$images_result = $conn->query("SELECT project_id, id, image_path FROM project_images ORDER BY id ASC");
$gallery_images = [];
while($img = $images_result->fetch_assoc()) {
    $gallery_images[$img['project_id']][] = [
        'id' => $img['id'],
        'path' => $img['image_path']
    ];
}

foreach ($projects as &$p) {
    $p['gallery'] = $gallery_images[$p['id']] ?? [];
}

$project = $projects[0];
echo "Project ID: " . $project['id'] . "\n";
echo "Gallery Data:\n";
print_r($project['gallery']);
echo "\nJSON Encoded:\n";
echo json_encode($project['gallery']);
echo "\nHTML Special Chars:\n";
echo htmlspecialchars(json_encode($project['gallery']), ENT_QUOTES);
?>
