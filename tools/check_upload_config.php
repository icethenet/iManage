<?php
/**
 * Check Upload Configuration
 */

$config = require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'app.php';

$maxSizeMB = $config['image']['max_file_size'] / (1024 * 1024);
$allowedTypes = implode(', ', $config['image']['allowed_types']);

echo "=== Upload Configuration ===\n";
echo "Max File Size: {$maxSizeMB} MB\n";
echo "Allowed Types: {$allowedTypes}\n";
echo "Default Quality: {$config['image']['default_quality']}\n";
echo "\n=== PHP Runtime Settings ===\n";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "post_max_size: " . ini_get('post_max_size') . "\n";
echo "memory_limit: " . ini_get('memory_limit') . "\n";
echo "\n✅ Configuration loaded successfully!\n";
