<?php
/**
 * Image Proxy with CORS headers
 * Serves images with proper CORS headers for AI features
 */

// Set CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Get the image path from query parameter
$imagePath = $_GET['path'] ?? '';

if (empty($imagePath)) {
    http_response_code(400);
    die('No image path specified');
}

// Security: Ensure the path is within uploads directory
$imagePath = str_replace(['../', '..\\'], '', $imagePath);
$fullPath = __DIR__ . '/uploads/' . $imagePath;

if (!file_exists($fullPath) || !is_file($fullPath)) {
    http_response_code(404);
    die('Image not found');
}

// Get mime type
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $fullPath);
finfo_close($finfo);

// Set content type
header('Content-Type: ' . $mimeType);
header('Content-Length: ' . filesize($fullPath));

// Output the image
readfile($fullPath);
