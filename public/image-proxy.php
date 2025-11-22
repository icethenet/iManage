<?php
/**
 * Image Proxy with CORS headers and User Authentication
 * Serves images with proper CORS headers for AI features
 * SECURITY: Only serves images belonging to the authenticated user
 */

// Start session to check authentication
session_start();

// Set CORS headers for same-origin requests
// Allow credentials so session cookies work
$origin = $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? '';
if ($origin) {
    // Extract origin from referer if needed
    $parsedOrigin = parse_url($origin);
    $allowedOrigin = ($parsedOrigin['scheme'] ?? 'http') . '://' . ($parsedOrigin['host'] ?? 'localhost');
    header('Access-Control-Allow-Origin: ' . $allowedOrigin);
    header('Access-Control-Allow-Credentials: true');
} else {
    // Same-origin request, no CORS needed
    header('Access-Control-Allow-Origin: *');
}
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Require authentication for image access
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die('Authentication required');
}

// Get the image path from query parameter
$imagePath = $_GET['path'] ?? '';

if (empty($imagePath)) {
    http_response_code(400);
    error_log('image-proxy: No path specified');
    die('No image path specified');
}

error_log('image-proxy: Received path: ' . $imagePath);

// Security: Ensure the path is within uploads directory
$imagePath = str_replace(['../', '..\\'], '', $imagePath);

// URL decode the path (handles spaces and special characters)
$imagePath = urldecode($imagePath);
error_log('image-proxy: Decoded path: ' . $imagePath);

// SECURITY: Extract username from path and verify it matches current user
$pathParts = explode('/', $imagePath);
$requestedUsername = $pathParts[0] ?? '';
error_log('image-proxy: Requested username: ' . $requestedUsername);

// Load database to verify user
require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/Models/User.php';

$userModel = new User();
$currentUser = $userModel->findById($_SESSION['user_id']);

if (!$currentUser) {
    http_response_code(401);
    die('Invalid user session');
}

// SECURITY CHECK: Verify the requested image belongs to the current user
if ($requestedUsername !== $currentUser['username']) {
    http_response_code(403);
    error_log("image-proxy: Access denied - User '{$currentUser['username']}' attempted to access '{$requestedUsername}'s image");
    die('Access denied: You can only access your own images');
}

error_log('image-proxy: User verified. Building full path...');

$fullPath = __DIR__ . '/uploads/' . $imagePath;
error_log('image-proxy: Full path: ' . $fullPath);

if (!file_exists($fullPath)) {
    http_response_code(404);
    error_log('image-proxy: File not found at: ' . $fullPath);
    die('Image not found');
}

if (!is_file($fullPath)) {
    http_response_code(404);
    error_log('image-proxy: Path exists but is not a file: ' . $fullPath);
    die('Not a valid file');
}

error_log('image-proxy: File found, serving image');

// Get mime type
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $fullPath);
finfo_close($finfo);

// Set content type
header('Content-Type: ' . $mimeType);
header('Content-Length: ' . filesize($fullPath));

// Output the image
readfile($fullPath);
