<?php
/**
 * Application Configuration
 * Cross-Platform (Windows/Linux/macOS)
 */

return [
    'app_name' => 'Image Management System',
    'app_url' => 'http://localhost/imanage/public',  // Updated for web server path
    'upload_dir' => '/uploads',
    'original_dir' => 'original',
    'thumb_dir' => 'thumb',
    
    // Image settings
    'image' => [
        'max_file_size' => 5 * 1024 * 1024, // 5MB
        'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
        'allowed_mimes' => [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp'
        ],
        'default_quality' => 85,
        'thumbnail_width' => 200,
        'thumbnail_height' => 200,
    ],

    // Pagination settings
    'pagination' => [
        'default_limit' => 12,
    ],
];
