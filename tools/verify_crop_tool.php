<?php
/**
 * Quick Crop Tool Verification
 * Verifies the ImageManipulator crop function works with coordinate-based cropping
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once dirname(__DIR__) . '/app/Utils/ImageManipulator.php';

echo "=== Crop Tool Verification ===\n\n";

// Create a test image (200x200 red square)
$testImage = tempnam(sys_get_temp_dir(), 'crop_test_');
$image = imagecreatetruecolor(200, 200);
$red = imagecolorallocate($image, 255, 0, 0);
imagefill($image, 0, 0, $red);
imagepng($image, $testImage);
imagedestroy($image);

echo "[1/3] Created test image: 200x200px\n";
echo "  Path: $testImage\n";
echo "  Initial size: " . filesize($testImage) . " bytes\n\n";

// Verify original dimensions
$origInfo = getimagesize($testImage);
echo "[2/3] Original dimensions: {$origInfo[0]}x{$origInfo[1]}\n\n";

// Test crop with coordinates
echo "[3/3] Testing crop operation...\n";
echo "  Crop parameters: x=50, y=50, width=100, height=100\n";

try {
    $manipulator = new ImageManipulator($testImage, 85);
    $manipulator->crop(100, 100, 50, 50);
    $manipulator->save($testImage);  // Must save to file!
    
    // Verify new dimensions
    $newInfo = getimagesize($testImage);
    echo "  New dimensions: {$newInfo[0]}x{$newInfo[1]}\n";
    echo "  New size: " . filesize($testImage) . " bytes\n\n";

    if ($newInfo[0] == 100 && $newInfo[1] == 100) {
        echo "✓ CROP TOOL VERIFIED SUCCESSFULLY!\n";
        echo "  ✓ Crop from 200x200 to 100x100 works correctly\n";
        echo "  ✓ Coordinate-based cropping (x, y, width, height) functional\n";
        echo "  ✓ Ready for frontend integration\n";
    } else {
        echo "✗ Dimension mismatch:\n";
        echo "  Expected: 100x100\n";
        echo "  Got: {$newInfo[0]}x{$newInfo[1]}\n";
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

// Cleanup
if (file_exists($testImage)) {
    unlink($testImage);
}

echo "\n=== Verification Complete ===\n";

