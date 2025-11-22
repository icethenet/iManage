<?php
/**
 * Check page preview status
 */

require_once __DIR__ . '/../app/Database.php';

try {
    $db = Database::getInstance();
    
    echo "🔍 Checking landing pages...\n\n";
    
    $stmt = $db->query("SELECT id, page_title, preview_image, created_at, updated_at FROM landing_pages ORDER BY updated_at DESC");
    $pages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($pages)) {
        echo "❌ No landing pages found in database\n";
        echo "   Create a page in the page designer first!\n";
        exit(0);
    }
    
    echo "Found " . count($pages) . " page(s):\n\n";
    
    foreach ($pages as $page) {
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "📄 ID: " . $page['id'] . "\n";
        echo "📝 Title: " . $page['page_title'] . "\n";
        echo "📅 Updated: " . $page['updated_at'] . "\n";
        
        if ($page['preview_image']) {
            echo "🖼️  Preview: ✅ " . $page['preview_image'] . "\n";
            
            // Check if file exists
            $previewPath = __DIR__ . '/../public/uploads/page-previews/' . $page['preview_image'];
            if (file_exists($previewPath)) {
                $size = filesize($previewPath);
                echo "   File exists: " . number_format($size / 1024, 2) . " KB\n";
            } else {
                echo "   ⚠️  File missing on disk!\n";
            }
        } else {
            echo "🖼️  Preview: ❌ Not generated\n";
            echo "   💡 Re-save this page to generate a preview\n";
        }
        echo "\n";
    }
    
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

