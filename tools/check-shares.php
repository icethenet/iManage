<?php
// Check if user has any shared images
require_once __DIR__ . '/app/Database.php';

try {
    $db = Database::getInstance();
    $stmt = $db->query("SELECT id, original_name, share_token FROM images WHERE share_token IS NOT NULL LIMIT 5");
    $images = $stmt->fetchAll();
    
    if (count($images) > 0) {
        echo "✅ Found " . count($images) . " shared image(s):\n\n";
        foreach ($images as $img) {
            echo "📷 {$img['original_name']}\n";
            echo "   Share link: http://localhost/imanage/public/share.php?share={$img['share_token']}\n\n";
        }
        echo "TO SEE THE DESIGN BUTTON:\n";
        echo "1. Make sure you're logged in to iManage\n";
        echo "2. Visit one of the share links above\n";
        echo "3. Look for the 🎨 button at the BOTTOM-RIGHT corner\n";
        echo "4. The button only shows if you OWN that shared image\n\n";
    } else {
        echo "❌ No shared images found yet.\n\n";
        echo "TO CREATE A SHARED IMAGE:\n";
        echo "1. Login at: http://localhost/imanage/public/\n";
        echo "2. Upload an image (or use an existing one)\n";
        echo "3. Click the 🔗 Share button on the image\n";
        echo "4. Copy the share link\n";
        echo "5. Visit that link while logged in\n";
        echo "6. You'll see the 🎨 Design Landing Page button\n\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
