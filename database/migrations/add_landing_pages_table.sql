-- Migration: Add landing pages table for GrapesJS custom designs
-- Created: 2025-11-21
-- Purpose: Store custom HTML/CSS designs for shared gallery pages

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
