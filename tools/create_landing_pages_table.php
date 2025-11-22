<?php
/**
 * Create landing_pages table
 * Run: php tools/create_landing_pages_table.php
 */

require_once __DIR__ . '/../app/Database.php';

try {
    $db = Database::getInstance();
    
    echo "Creating landing_pages table...\n";
    
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $db->query($sql);
    
    echo "✅ landing_pages table created successfully!\n";
    
    // Verify table
    $stmt = $db->query("SHOW TABLES LIKE 'landing_pages'");
    $exists = $stmt->fetch();
    
    if ($exists) {
        echo "✅ Table verified: landing_pages exists\n";
        
        // Show columns
        echo "\nTable structure:\n";
        $stmt = $db->query("DESCRIBE landing_pages");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "  - {$row['Field']} ({$row['Type']})\n";
        }
    }
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
