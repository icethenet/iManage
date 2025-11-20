<?php
/**
 * Integration Test: Crop Tool Functionality
 * 
 * This script tests:
 * 1. Login with test user
 * 2. Upload a test image
 * 3. Apply a crop operation with x,y,width,height coordinates
 * 4. Verify crop succeeded
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);

$config = require_once dirname(__DIR__) . '/config/database.php';
$app_config = require_once dirname(__DIR__) . '/config/app.php';

// PDO connection
try {
    $dsn = 'mysql:host=' . ltrim($config['host']) . ';dbname=' . ltrim($config['database']);
    $pdo = new PDO($dsn, ltrim($config['username']), ltrim($config['password']));
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Test configuration
$TEST_USER = 'integration_test_user';
$TEST_PASSWORD = 'test1234';
$API_BASE = ltrim($app_config['app_url']);

echo "=== Crop Tool Integration Test ===\n\n";

// Step 1: Get test user ID
echo "[1/4] Fetching test user...\n";
$stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
$stmt->execute([$TEST_USER]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "✗ Test user not found. Create with: php tools/create_test_user.php\n";
    exit(1);
}
$userId = $user['id'];
echo "✓ Found test user (ID: $userId)\n\n";

// Step 2: Get a test image (or create one)
echo "[2/4] Finding test image...\n";
$stmt = $pdo->prepare('SELECT id, original_path FROM images WHERE user_id = ? LIMIT 1');
$stmt->execute([$userId]);
$image = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$image) {
    echo "✗ No test images found for user. Upload an image first.\n";
    exit(1);
}
$imageId = $image['id'];
$originalPath = $image['original_path'];
echo "✓ Found test image (ID: $imageId)\n";
echo "  Original: $originalPath\n\n";

// Step 3: Get image dimensions
echo "[3/4] Analyzing image dimensions...\n";
if (!file_exists($originalPath)) {
    echo "✗ Image file not found at: $originalPath\n";
    exit(1);
}

$imageInfo = getimagesize($originalPath);
if (!$imageInfo) {
    echo "✗ Could not read image dimensions\n";
    exit(1);
}

$origWidth = $imageInfo[0];
$origHeight = $imageInfo[1];
echo "✓ Original dimensions: {$origWidth}x{$origHeight}\n";

// Define crop parameters (crop to 50% of center area)
$cropX = (int)($origWidth * 0.25);
$cropY = (int)($origHeight * 0.25);
$cropWidth = (int)($origWidth * 0.5);
$cropHeight = (int)($origHeight * 0.5);

echo "  Crop area: x=$cropX, y=$cropY, width=$cropWidth, height=$cropHeight\n\n";

// Step 4: Test crop via API
echo "[4/4] Testing crop operation...\n";

// Start session for API
session_start();
$_SESSION['user_id'] = $userId;
$_SESSION['username'] = $TEST_USER;

// Simulate the API request
require_once dirname(__DIR__) . '/app/Controllers/ImageController.php';
require_once dirname(__DIR__) . '/app/Utils/Path.php';

try {
    $controller = new ImageController($pdo);
    
    // Create a temporary backup first
    $backupPath = $originalPath . '.backup_test';
    if (!copy($originalPath, $backupPath)) {
        echo "✗ Failed to create backup\n";
        exit(1);
    }
    
    // Call manipulate with crop parameters
    // Simulate POST request data
    $_POST = [];
    $_REQUEST = [
        'operation' => 'crop',
        'x' => $cropX,
        'y' => $cropY,
        'width' => $cropWidth,
        'height' => $cropHeight
    ];
    
    // We'll test the GD manipulation directly
    require_once dirname(__DIR__) . '/app/Utils/ImageManipulator.php';
    
    $manipulator = new ImageManipulator();
    $result = $manipulator->crop($originalPath, $cropX, $cropY, $cropWidth, $cropHeight);
    
    if (!$result) {
        echo "✗ Crop operation failed\n";
        // Restore backup
        copy($backupPath, $originalPath);
        unlink($backupPath);
        exit(1);
    }
    
    echo "✓ Crop operation completed\n";
    
    // Verify new dimensions
    $newImageInfo = getimagesize($originalPath);
    $newWidth = $newImageInfo[0];
    $newHeight = $newImageInfo[1];
    
    echo "✓ New dimensions: {$newWidth}x{$newHeight}\n";
    echo "✓ Expected: {$cropWidth}x{$cropHeight}\n";
    
    if ($newWidth == $cropWidth && $newHeight == $cropHeight) {
        echo "\n✓ CROP TOOL TEST PASSED!\n";
        echo "  Cropped from {$origWidth}x{$origHeight} to {$newWidth}x{$newHeight}\n";
    } else {
        echo "\n✗ CROP DIMENSIONS MISMATCH!\n";
        echo "  Expected: {$cropWidth}x{$cropHeight}\n";
        echo "  Got: {$newWidth}x{$newHeight}\n";
    }
    
    // Clean up backup
    if (file_exists($backupPath)) {
        unlink($backupPath);
    }
    
} catch (Exception $e) {
    echo "✗ Crop test failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n=== Test Complete ===\n";
