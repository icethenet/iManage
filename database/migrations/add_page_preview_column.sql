-- Add preview_image column to landing_pages table
ALTER TABLE landing_pages 
ADD COLUMN preview_image VARCHAR(255) DEFAULT NULL AFTER page_title;

