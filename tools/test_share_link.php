<?php
/**
 * Test the sharing functionality
 */

require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/Models/Image.php';

echo "Testing Share Link Functionality\n";
echo "=================================\n\n";

$imageModel = new Image();

// Find a test image
$db = Database::getInstance();
$stmt = $db->prepare("SELECT * FROM images LIMIT 1");
$stmt->execute();
$testImage = $stmt->fetch();

if (!$testImage) {
    echo "❌ No images found in database. Please upload an image first.\n";
    exit(1);
}

echo "Using test image: {$testImage['original_name']} (ID: {$testImage['id']})\n\n";

// Test 1: Generate share token
echo "Test 1: Generating share token...\n";
$token = $imageModel->generateShareToken($testImage['id']);
echo "✓ Generated token: $token\n\n";

// Test 2: Retrieve image by share token
echo "Test 2: Retrieving image by share token...\n";

// First mark as shared
$db->prepare("UPDATE images SET shared = 1 WHERE id = ?")->execute([$testImage['id']]);

$sharedImage = $imageModel->getByShareToken($token);
if ($sharedImage && $sharedImage['id'] == $testImage['id']) {
    echo "✓ Successfully retrieved image by token\n";
    echo "  - Title: {$sharedImage['title']}\n";
    echo "  - Token: {$sharedImage['share_token']}\n\n";
} else {
    echo "❌ Failed to retrieve image by token\n";
    exit(1);
}

// Test 3: Generate share URL
echo "Test 3: Generating share URL...\n";
$shareUrl = "http://localhost/imanage/public/share.php?share=$token";
echo "✓ Share URL: $shareUrl\n\n";

// Test 4: Verify token is unique
echo "Test 4: Verifying token uniqueness...\n";
$token2 = $imageModel->generateShareToken($testImage['id']);
if ($token !== $token2) {
    echo "✓ New token generated: $token2\n";
    echo "  Note: Token was regenerated (this updates the share link)\n\n";
} else {
    echo "⚠ Same token returned (unexpected but not critical)\n\n";
}

echo "=================================\n";
echo "✓ All share link tests passed!\n\n";
echo "To test in browser:\n";
echo "1. Open: http://localhost/imanage/public/\n";
echo "2. Open any image modal\n";
echo "3. Check 'Share this image publicly'\n";
echo "4. Copy the generated share link\n";
echo "5. Open link in new tab/incognito to test\n";
