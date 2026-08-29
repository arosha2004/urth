<?php

/**
 * Resizes an image to fit within max width and max height, keeping aspect ratio.
 * 
 * @param string $source_path Path to the uploaded temporary file.
 * @param string $dest_path Path where the resized image should be saved.
 * @param int $max_width Maximum allowed width.
 * @param int $max_height Maximum allowed height.
 * @return bool True on success, false on failure.
 */
function resizeImage($source_path, $dest_path, $max_width = 1920, $max_height = 1080) {
    // Get original image dimensions and type
    $imgInfo = getimagesize($source_path);
    if ($imgInfo === false) {
        return false; // Not a valid image
    }
    
    list($orig_width, $orig_height, $image_type) = $imgInfo;
    
    // Calculate new dimensions
    $ratio = $orig_width / $orig_height;
    $new_width = $orig_width;
    $new_height = $orig_height;
    
    if ($new_width > $max_width) {
        $new_width = $max_width;
        $new_height = $new_width / $ratio;
    }
    
    if ($new_height > $max_height) {
        $new_height = $max_height;
        $new_width = $new_height * $ratio;
    }
    
    // Create new image resource
    $new_image = imagecreatetruecolor(round($new_width), round($new_height));
    
    // Handle transparency for PNG and GIF
    if ($image_type == IMAGETYPE_PNG || $image_type == IMAGETYPE_GIF) {
        imagecolortransparent($new_image, imagecolorallocatealpha($new_image, 0, 0, 0, 127));
        imagealphablending($new_image, false);
        imagesavealpha($new_image, true);
    }
    
    // Load original image
    switch ($image_type) {
        case IMAGETYPE_JPEG:
            $source_image = imagecreatefromjpeg($source_path);
            break;
        case IMAGETYPE_PNG:
            $source_image = imagecreatefrompng($source_path);
            break;
        case IMAGETYPE_GIF:
            $source_image = imagecreatefromgif($source_path);
            break;
        case IMAGETYPE_WEBP:
            $source_image = imagecreatefromwebp($source_path);
            break;
        default:
            return false;
    }
    
    if (!$source_image) {
        return false;
    }
    
    // Resize
    imagecopyresampled(
        $new_image,
        $source_image,
        0, 0, 0, 0,
        round($new_width),
        round($new_height),
        $orig_width,
        $orig_height
    );
    
    // Save the resized image
    $success = false;
    switch ($image_type) {
        case IMAGETYPE_JPEG:
            $success = imagejpeg($new_image, $dest_path, 85); // 85 quality
            break;
        case IMAGETYPE_PNG:
            $success = imagepng($new_image, $dest_path, 8); // Compression level 8
            break;
        case IMAGETYPE_GIF:
            $success = imagegif($new_image, $dest_path);
            break;
        case IMAGETYPE_WEBP:
            $success = imagewebp($new_image, $dest_path, 85);
            break;
    }
    
    // Free up memory
    imagedestroy($new_image);
    imagedestroy($source_image);
    
    return $success;
}
?>
