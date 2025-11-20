<?php
/**
 * Test ImageManipulator class directly
 */

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Utils' . DIRECTORY_SEPARATOR . 'ImageManipulator.php';

// Create a test image
$testImagePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_image.png';
$image = imagecreatetruecolor(200, 200);
$red = imagecolorallocate($image, 255, 0, 0);
imagefilledrectangle($image, 0, 0, 200, 200, $red);
imagepng($image, $testImagePath);
imagedestroy($image);

echo "Test image created at: $testImagePath\n";

try {
    $manipulator = new ImageManipulator($testImagePath);
    echo "ImageManipulator initialized successfully\n";

    // Test thumbnail
    $thumbPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_thumb.png';
    $manipulator->thumbnail(100, 100);
    $manipulator->save($thumbPath);
    echo "Thumbnail created at: $thumbPath\n";

    // Test resize
    $resizePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_resize.png';
    $manipulator = new ImageManipulator($testImagePath);
    $manipulator->resize(150, 150);
    $manipulator->save($resizePath);
    echo "Resized image created at: $resizePath\n";

    echo "All tests passed!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

// Cleanup
if (file_exists($testImagePath)) unlink($testImagePath);
if (isset($thumbPath) && file_exists($thumbPath)) unlink($thumbPath);
if (isset($resizePath) && file_exists($resizePath)) unlink($resizePath);
?>
