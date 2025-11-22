<?php
// Quick setup for GrapesJS Landing Pages
require_once __DIR__ . '/app/Database.php';

echo "=== GrapesJS Landing Pages Setup ===\n\n";

try {
    $db = Database::getInstance();
    echo "✅ Database connection successful\n\n";
    
    // Check if table already exists
    $stmt = $db->query("SHOW TABLES LIKE 'landing_pages'");
    if ($stmt->fetch()) {
        echo "ℹ️  Table 'landing_pages' already exists\n";
    } else {
        echo "Creating 'landing_pages' table...\n";
        
        $sql = "
        CREATE TABLE IF NOT EXISTS landing_pages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            share_token VARCHAR(64) UNIQUE,
            page_title VARCHAR(255) DEFAULT 'Shared Gallery',
            html_content LONGTEXT,
            css_content LONGTEXT,
            grapesjs_data LONGTEXT COMMENT 'JSON: GrapesJS project data for editing',
            is_active BOOLEAN DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_share_token (share_token),
            INDEX idx_user_id (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $db->query($sql);
        echo "✅ Table created successfully!\n\n";
    }
    
    // Show instructions
    echo "\n=== How to Use GrapesJS Feature ===\n\n";
    echo "1. Login to iManage at: http://localhost/imanage/public/\n";
    echo "2. Upload and share an image (click Share button)\n";
    echo "3. Copy the share link\n";
    echo "4. Visit the share page while logged in\n";
    echo "5. Look for the '🎨 Design Landing Page' button (bottom-right)\n";
    echo "6. Click it to open the visual editor\n";
    echo "7. Build your custom page with drag-and-drop blocks\n";
    echo "8. Click 'Save' to store your design\n";
    echo "9. Anyone visiting your share link will see your custom page!\n\n";
    
    echo "📚 Documentation: docs/CUSTOM_LANDING_PAGES.md\n";
    echo "🚀 Quick Start: docs/GRAPESJS_QUICKSTART.md\n\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n\n";
    echo "Make sure MySQL is running and check your database config.\n";
}
