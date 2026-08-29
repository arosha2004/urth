<?php
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    
    // Optional: fetch project to delete image files from server
    // $result = $conn->query("SELECT image1, image2, image3 FROM projects WHERE id=$id");
    // $project = $result->fetch_assoc();
    // if($project) {
    //     if(file_exists("../".$project['image1'])) unlink("../".$project['image1']);
    //     if(file_exists("../".$project['image2'])) unlink("../".$project['image2']);
    //     if(file_exists("../".$project['image3'])) unlink("../".$project['image3']);
    // }

    $sql = "DELETE FROM projects WHERE id=$id";
    $conn->query($sql);
}

header('Location: index.php');
exit;
?>
