<?php
/**
 * Test Advanced Filters (Blur, Sepia, Vignette)
 */

require_once __DIR__ . '/../app/Utils/ImageManipulator.php';

echo "=== Advanced Filters Test ===\n\n";

// Check if a sample image exists
$sampleImagePath = __DIR__ . '/../public/uploads/sample_test.jpg';
if (!file_exists($sampleImagePath)) {
    echo "⚠️  No sample image found at: {$sampleImagePath}\n";
    echo "Please ensure you have an image to test with.\n";
    exit(1);
}

try {
    echo "Testing image: {$sampleImagePath}\n\n";
    
    // Test 1: Blur
    echo "1. Testing Blur Filter...\n";
    $manipulator = new ImageManipulator($sampleImagePath, 85);
    $manipulator->blur(3);
    $blurPath = __DIR__ . '/../public/uploads/test_blur.jpg';
    $manipulator->save($blurPath);
    echo "   ✅ Blur applied and saved to: {$blurPath}\n\n";
    unset($manipulator);
    
    // Test 2: Sepia
    echo "2. Testing Sepia Filter...\n";
    $manipulator = new ImageManipulator($sampleImagePath, 85);
    $manipulator->sepia(80);
    $sepiaPath = __DIR__ . '/../public/uploads/test_sepia.jpg';
    $manipulator->save($sepiaPath);
    echo "   ✅ Sepia applied and saved to: {$sepiaPath}\n\n";
    unset($manipulator);
    
    // Test 3: Vignette
    echo "3. Testing Vignette Filter...\n";
    $manipulator = new ImageManipulator($sampleImagePath, 85);
    $manipulator->vignette(60);
    $vignettePath = __DIR__ . '/../public/uploads/test_vignette.jpg';
    $manipulator->save($vignettePath);
    echo "   ✅ Vignette applied and saved to: {$vignettePath}\n\n";
    unset($manipulator);
    
    // Test 4: Combined filters
    echo "4. Testing Combined Filters (Sepia + Vignette)...\n";
    $manipulator = new ImageManipulator($sampleImagePath, 85);
    $manipulator->sepia(70);
    $manipulator->vignette(50);
    $combinedPath = __DIR__ . '/../public/uploads/test_combined.jpg';
    $manipulator->save($combinedPath);
    echo "   ✅ Combined filters applied and saved to: {$combinedPath}\n\n";
    unset($manipulator);
    
    echo "🎉 All advanced filter tests passed!\n";
    echo "\nGenerated test images:\n";
    echo "  - test_blur.jpg\n";
    echo "  - test_sepia.jpg\n";
    echo "  - test_vignette.jpg\n";
    echo "  - test_combined.jpg\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
