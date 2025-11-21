<?php
/**
 * Application Configuration
 * Cross-Platform (Windows/Linux/macOS)
 */

return array (
  'app_name' => 'Image Management System',
  'app_url' => 'http://localhost/imanage/public',
  'upload_dir' => '/uploads',
  'original_dir' => 'original',
  'thumb_dir' => 'thumb',
  'image' => 
  array (
    'max_file_size' => 52428800,
    'allowed_types' => 
    array (
      0 => 'jpg',
      1 => 'jpeg',
      2 => 'png',
      3 => 'gif',
      4 => 'webp',
    ),
    'allowed_mimes' => 
    array (
      0 => 'image/jpeg',
      1 => 'image/png',
      2 => 'image/gif',
      3 => 'image/webp',
    ),
    'default_quality' => 85,
    'thumbnail_width' => 200,
    'thumbnail_height' => 200,
  ),
  'video' => 
  array (
    'enabled' => true,
    'max_file_size' => 104857600,
    'allowed_types' => 
    array (
      0 => 'mp4',
      1 => 'mov',
      2 => 'avi',
      3 => 'mkv',
      4 => 'webm',
    ),
    'allowed_mimes' => 
    array (
      0 => 'video/mp4',
      1 => 'video/quicktime',
      2 => 'video/x-msvideo',
      3 => 'video/x-matroska',
      4 => 'video/webm',
    ),
    'thumbnail_timestamp' => 1,
    'thumbnail_width' => 200,
    'thumbnail_height' => 200,
  ),
  'pagination' => 
  array (
    'default_limit' => 12,
  ),
);
