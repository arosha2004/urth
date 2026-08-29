<?php
/**
 * URTH Static Site Exporter
 * This script exports the dynamic PHP site into a static HTML site
 * suitable for hosting on GitHub Pages.
 */

$export_dir = __DIR__ . '/docs';
if (!is_dir($export_dir)) {
    mkdir($export_dir, 0777, true);
}

function copy_directory($src, $dst) {
    $dir = opendir($src);
    @mkdir($dst, 0777, true);
    while (false !== ($file = readdir($dir))) {
        if (($file != '.') && ($file != '..')) {
            if (is_dir($src . '/' . $file)) {
                copy_directory($src . '/' . $file, $dst . '/' . $file);
            } else {
                copy($src . '/' . $file, $dst . '/' . $file);
            }
        }
    }
    closedir($dir);
}

// 1. Copy static assets (img, css, js, html)
echo "Copying static assets...\n";
$extensions_to_copy = ['css', 'js', 'html', 'txt', 'json'];
$files = scandir(__DIR__);
foreach ($files as $file) {
    if ($file === '.' || $file === '..') continue;
    
    $path = __DIR__ . '/' . $file;
    if (is_file($path)) {
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        if (in_array($ext, $extensions_to_copy)) {
            // Read file and fix any links if necessary
            $content = file_get_contents($path);
            $content = str_replace('projects.php', 'projects.html', $content);
            $content = preg_replace('/project_detail\.php\?id=(\d+)/', 'project-$1.html', $content);
            file_put_contents($export_dir . '/' . $file, $content);
        }
    }
}

// Copy img directory
if (is_dir(__DIR__ . '/img')) {
    echo "Copying images...\n";
    copy_directory(__DIR__ . '/img', $export_dir . '/img');
}

// Helper to capture PHP output
function render_php_file($file, $get_params = []) {
    global $conn;
    $_GET = $get_params;
    ob_start();
    include $file;
    $content = ob_get_clean();
    return $content;
}

// 2. Export projects.php to projects.html
echo "Rendering projects.php...\n";
$projects_html = render_php_file(__DIR__ . '/projects.php');
// Replace links to project details
$projects_html = preg_replace('/project_detail\.php\?id=(\d+)/', 'project-$1.html', $projects_html);
// In case there are links to projects.php, rename them
$projects_html = str_replace('projects.php', 'projects.html', $projects_html);
file_put_contents($export_dir . '/projects.html', $projects_html);

// 3. Export project_detail.php for each project
echo "Rendering individual project pages...\n";
require __DIR__ . '/config.php';
$result = $conn->query("SELECT id FROM projects");

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $id = $row['id'];
        echo " - Exporting project ID $id...\n";
        
        $detail_html = render_php_file(__DIR__ . '/project_detail.php', ['id' => $id]);
        
        // Replace dynamic links in the generated HTML
        $detail_html = preg_replace('/project_detail\.php\?id=(\d+)/', 'project-$1.html', $detail_html);
        $detail_html = str_replace('projects.php', 'projects.html', $detail_html);
        
        file_put_contents($export_dir . '/project-' . $id . '.html', $detail_html);
    }
} else {
    echo "No projects found in the database.\n";
}

echo "Export completed successfully! Your static site is in the 'github_pages_export' folder.\n";
?>
