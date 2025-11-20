<?php
/**
 * Generate a sample PNG image via GD for testing
 * Usage: php create_sample_image.php <output_path>
 */

if ($argc < 2) {
    fwrite(STDERR, "Usage: php create_sample_image.php <output_path>\n");
    exit(1);
}

$outputPath = $argv[1];

// Create a simple 64x48 PNG image
$image = imagecreatetruecolor(64, 48);
$white = imagecolorallocate($image, 255, 255, 255);
$blue = imagecolorallocate($image, 0, 0, 255);

// Fill with white
imagefilledrectangle($image, 0, 0, 64, 48, $white);

// Draw a blue rectangle
imagefilledrectangle($image, 10, 10, 54, 38, $blue);

// Save as PNG
if (imagepng($image, $outputPath)) {
    echo "Sample image created at: {$outputPath}\n";
    imagedestroy($image);
    exit(0);
} else {
    fwrite(STDERR, "Failed to create sample image\n");
    imagedestroy($image);
    exit(1);
}
?>
