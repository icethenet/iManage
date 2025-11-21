-- Add file_type column to support video files
-- Run: mysql -u root -p imanage < database/migrations/add_file_type_column.sql

ALTER TABLE images 
ADD COLUMN file_type ENUM('image', 'video') DEFAULT 'image' NOT NULL 
AFTER mime_type;

-- Add index for filtering by file type
CREATE INDEX idx_file_type ON images(file_type);
