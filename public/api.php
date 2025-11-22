<?php
/**
 * Autoloader for classes
 */

// Add CORS headers for AI features
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

function autoload($class) {
    $appDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'app';

    // Try class-specific locations first
    $candidates = [
        $appDir . DIRECTORY_SEPARATOR . 'Controllers' . DIRECTORY_SEPARATOR . $class . '.php',
        $appDir . DIRECTORY_SEPARATOR . 'Models' . DIRECTORY_SEPARATOR . $class . '.php',
        $appDir . DIRECTORY_SEPARATOR . 'Utils' . DIRECTORY_SEPARATOR . $class . '.php',
        // Fallback to top-level files (Database, Router, etc.) only if exactly matching
        $appDir . DIRECTORY_SEPARATOR . $class . '.php'
    ];

    foreach ($candidates as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
}

spl_autoload_register('autoload');

// Load config and set PHP runtime limits dynamically
$appConfig = require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'app.php';
if (isset($appConfig['image']['max_file_size'])) {
    $maxSizeMB = ceil($appConfig['image']['max_file_size'] / (1024 * 1024));
    @ini_set('upload_max_filesize', $maxSizeMB . 'M');
    @ini_set('post_max_size', ($maxSizeMB + 2) . 'M');
    @ini_set('memory_limit', max(128, $maxSizeMB * 3) . 'M');
}

// Set error handling
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Configure secure session settings BEFORE starting session
@ini_set('session.cookie_httponly', '1');
// Only set secure flag if using HTTPS
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    @ini_set('session.cookie_secure', '1');
}
@ini_set('session.use_strict_mode', '1');
// SameSite setting might not be supported in all PHP versions
if (PHP_VERSION_ID >= 70300) {
    @ini_set('session.cookie_samesite', 'Lax');
}

// Start session
session_start();

// Initialize i18n (multi-language support) - only set language, don't start session again
if (class_exists('I18n')) {
    if (!isset($_SESSION['language'])) {
        // Detect language on first call
        $lang = 'en';
        if (isset($_GET['lang']) && preg_match('/^[a-z]{2}$/', $_GET['lang'])) {
            $lang = $_GET['lang'];
        } elseif (isset($_COOKIE['language'])) {
            $lang = $_COOKIE['language'];
        }
        $_SESSION['language'] = $lang;
    }
}

// Set security headers AFTER session (prevents XSS, MIME sniffing, clickjacking)
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\' https://cdn.jsdelivr.net; style-src \'self\' \'unsafe-inline\'; img-src \'self\' data: blob:; font-src \'self\';');
// Only set HSTS if using HTTPS
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

// Session timeout protection (30 minutes inactivity)
$sessionTimeout = 30 * 60;
if (isset($_SESSION['last_activity']) && time() - $_SESSION['last_activity'] > $sessionTimeout) {
    session_destroy();
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Session expired due to inactivity.'
    ]);
    exit;
}
if (isset($_SESSION['user_id'])) {
    $_SESSION['last_activity'] = time();
    
    // Update active_sessions table with last activity
    try {
        $db = Database::getInstance();
        $stmt = $db->prepare("UPDATE active_sessions SET last_activity = NOW() WHERE session_id = ?");
        $stmt->execute([session_id()]);
    } catch (Exception $e) {
        error_log("Failed to update session activity: " . $e->getMessage());
    }
}


// Set headers for CORS (restrict to same origin for security)
// Only allow CORS if specifically needed, otherwise keep same-origin policy
$allowedOrigins = [
    'http://localhost:8000',
    'http://127.0.0.1:8000'
];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Credentials: true');
}
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');

// Helper function to check authentication
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Authentication required']);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/**
 * Simple action-based routing
 * Query parameters determine the action
 */

// Rate limiting check (basic implementation)
function checkRateLimit() {
    $key = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $maxRequests = 100; // requests per minute
    $window = 60; // seconds
    
    if (!isset($_SESSION['rate_limit'])) {
        $_SESSION['rate_limit'] = [];
    }
    
    $now = time();
    $_SESSION['rate_limit'] = array_filter($_SESSION['rate_limit'], function($timestamp) use ($now, $window) {
        return ($now - $timestamp) < $window;
    });
    
    if (count($_SESSION['rate_limit']) >= $maxRequests) {
        http_response_code(429);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Rate limit exceeded. Please try again later.']);
        exit;
    }
    
    $_SESSION['rate_limit'][] = $now;
}

// Apply rate limiting
checkRateLimit();

$action = isset($_GET['action']) ? preg_replace('/[^a-z0-9_]/', '', strtolower($_GET['action'])) : 'list';
$id = isset($_GET['id']) ? intval($_GET['id']) : null;
$name = isset($_GET['name']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['name']) : null;

// (debug helpers removed)

try {
    switch ($action) {
        // Image endpoints
        case 'list':
            $controller = new ImageController();
            $controller->list();
            break;
            
        case 'get':
            $controller = new ImageController();
            $controller->get($id);
            break;
            
        case 'upload':
            $controller = new ImageController();
            $controller->upload();
            break;
            
        case 'update':
            $controller = new ImageController();
            $controller->update($id);
            break;
            
        case 'delete':
            $controller = new ImageController();
            $controller->delete($id);
            break;
            
        case 'manipulate':
            $controller = new ImageController();
            $controller->manipulate($id);
            break;
            
        case 'download':
            $controller = new ImageController();
            $controller->download($id);
            break;

        case 'revert':
            $controller = new ImageController();
            $controller->revert($id);
            break;

        case 'shared':
            // Public endpoint to view shared images (no auth required)
            $token = $_GET['token'] ?? null;
            if (!$token) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Share token required']);
                exit;
            }
            $controller = new ImageController();
            $controller->getShared($token);
            break;

        // Landing Page endpoints
        case 'savelandingpage':
            requireLogin();
            $input = json_decode(file_get_contents('php://input'), true);
            $token = $input['token'] ?? null;
            $html = $input['html_content'] ?? '';
            $css = $input['css_content'] ?? '';
            $gjsData = $input['grapesjs_data'] ?? '';
            $title = $input['page_title'] ?? 'Shared Gallery';

            if (!$token) {
                echo json_encode(['success' => false, 'message' => 'Token required']);
                exit;
            }

            $db = Database::getInstance()->getConnection();
            $userId = $_SESSION['user_id'];

            // Check if landing page exists
            $stmt = $db->prepare("SELECT id FROM landing_pages WHERE user_id = ? AND share_token = ?");
            $stmt->execute([$userId, $token]);
            $existing = $stmt->fetch();

            if ($existing) {
                // Update existing
                $stmt = $db->prepare("
                    UPDATE landing_pages 
                    SET html_content = ?, css_content = ?, grapesjs_data = ?, page_title = ?, updated_at = NOW()
                    WHERE id = ? AND user_id = ?
                ");
                $result = $stmt->execute([$html, $css, $gjsData, $title, $existing['id'], $userId]);
            } else {
                // Insert new
                $stmt = $db->prepare("
                    INSERT INTO landing_pages (user_id, share_token, html_content, css_content, grapesjs_data, page_title)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $result = $stmt->execute([$userId, $token, $html, $css, $gjsData, $title]);
            }

            echo json_encode(['success' => $result, 'message' => $result ? 'Saved' : 'Failed']);
            break;

        case 'loadlandingpage':
            $token = $_GET['token'] ?? null;
            if (!$token) {
                echo json_encode(['success' => false, 'message' => 'Token required']);
                exit;
            }

            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("
                SELECT html_content, css_content, grapesjs_data, page_title 
                FROM landing_pages 
                WHERE share_token = ? AND is_active = 1
            ");
            $stmt->execute([$token]);
            $design = $stmt->fetch(PDO::FETCH_ASSOC);

            echo json_encode(['success' => !!$design, 'design' => $design]);
            break;

        case 'savecustompage':
            error_log("=== savecustompage called ===");
            error_log("Session user_id: " . ($_SESSION['user_id'] ?? 'NOT SET'));
            error_log("Request method: " . $_SERVER['REQUEST_METHOD']);
            
            requireLogin();
            
            try {
                $rawInput = file_get_contents('php://input');
                error_log("Raw input length: " . strlen($rawInput));
                
                $input = json_decode($rawInput, true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    error_log("JSON error: " . json_last_error_msg());
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Invalid JSON: ' . json_last_error_msg()]);
                    exit;
                }
                
                $pageId = $input['id'] ?? null;
                $html = $input['html_content'] ?? '';
                $css = $input['css_content'] ?? '';
                $gjsData = $input['grapesjs_data'] ?? '';
                $title = $input['page_title'] ?? 'Untitled Page';
                $previewImageData = $input['preview_image'] ?? null;
                $userId = $_SESSION['user_id'];
                
                error_log("Page ID: " . ($pageId ?? 'NEW'));
                error_log("Title: " . $title);
                error_log("User ID: " . $userId);

                $db = Database::getInstance();
                
                // Save preview image if provided
                $previewFilename = null;
                if ($previewImageData && strpos($previewImageData, 'data:image') === 0) {
                    try {
                        // Extract base64 data
                        $imageData = explode(',', $previewImageData)[1];
                        $imageData = base64_decode($imageData);
                        
                        // Create preview directory if it doesn't exist
                        $previewDir = __DIR__ . '/uploads/page-previews';
                        if (!is_dir($previewDir)) {
                            mkdir($previewDir, 0755, true);
                        }
                        
                        // Generate unique filename
                        $previewFilename = 'page_' . ($pageId ?? uniqid()) . '_' . time() . '.jpg';
                        $previewPath = $previewDir . '/' . $previewFilename;
                        
                        file_put_contents($previewPath, $imageData);
                        error_log("Preview saved: " . $previewFilename);
                    } catch (Exception $e) {
                        error_log("Failed to save preview: " . $e->getMessage());
                    }
                }

                if ($pageId) {
                    // Update existing page
                    error_log("Updating existing page");
                    if ($previewFilename) {
                        $stmt = $db->prepare("
                            UPDATE landing_pages 
                            SET html_content = ?, css_content = ?, grapesjs_data = ?, page_title = ?, preview_image = ?, updated_at = NOW()
                            WHERE id = ? AND user_id = ?
                        ");
                        $result = $stmt->execute([$html, $css, $gjsData, $title, $previewFilename, $pageId, $userId]);
                    } else {
                        $stmt = $db->prepare("
                            UPDATE landing_pages 
                            SET html_content = ?, css_content = ?, grapesjs_data = ?, page_title = ?, updated_at = NOW()
                            WHERE id = ? AND user_id = ?
                        ");
                        $result = $stmt->execute([$html, $css, $gjsData, $title, $pageId, $userId]);
                    }
                    error_log("Update result: " . ($result ? 'SUCCESS' : 'FAILED'));
                    echo json_encode(['success' => $result, 'pageId' => $pageId, 'message' => $result ? 'Updated' : 'Failed to update']);
                } else {
                    // Create new page with unique token
                    error_log("Creating new page");
                    $token = bin2hex(random_bytes(16));
                    $stmt = $db->prepare("
                        INSERT INTO landing_pages (user_id, share_token, html_content, css_content, grapesjs_data, page_title, preview_image)
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    $result = $stmt->execute([$userId, $token, $html, $css, $gjsData, $title, $previewFilename]);
                    $newId = $db->lastInsertId();
                    error_log("Insert result: " . ($result ? 'SUCCESS' : 'FAILED') . ", ID: " . $newId);
                    echo json_encode(['success' => $result, 'pageId' => $newId, 'token' => $token, 'message' => $result ? 'Created' : 'Failed to create']);
                }
            } catch (Exception $e) {
                error_log("Exception in saveCustomPage: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
            }
            break;

        case 'loadcustompage':
            requireLogin();
            $pageId = $_GET['id'] ?? null;
            $userId = $_SESSION['user_id'];

            if (!$pageId) {
                echo json_encode(['success' => false, 'message' => 'Page ID required']);
                exit;
            }

            $db = Database::getInstance();
            $stmt = $db->prepare("
                SELECT id, html_content, css_content, grapesjs_data, page_title, share_token
                FROM landing_pages 
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$pageId, $userId]);
            $page = $stmt->fetch(PDO::FETCH_ASSOC);

            echo json_encode(['success' => !!$page, 'page' => $page]);
            break;

        case 'deletecustompage':
            requireLogin();
            $input = json_decode(file_get_contents('php://input'), true);
            $pageId = $input['id'] ?? null;
            $userId = $_SESSION['user_id'];

            if (!$pageId) {
                echo json_encode(['success' => false, 'message' => 'Page ID required']);
                exit;
            }

            $db = Database::getInstance();
            $stmt = $db->prepare("DELETE FROM landing_pages WHERE id = ? AND user_id = ?");
            $result = $stmt->execute([$pageId, $userId]);

            echo json_encode(['success' => $result, 'message' => $result ? 'Deleted' : 'Failed to delete']);
            break;

        case 'getmyimages':
            // Get user's SHARED images for GrapesJS Asset Manager (page designer)
            requireLogin();
            
            try {
                $userId = $_SESSION['user_id'];
                $db = Database::getInstance();
                
                // Get username for path construction
                $userStmt = $db->prepare("SELECT username FROM users WHERE id = ?");
                $userStmt->execute([$userId]);
                $user = $userStmt->fetch(PDO::FETCH_ASSOC);
                $username = $user ? $user['username'] : 'unknown';
                
                $stmt = $db->prepare("
                    SELECT id, filename, original_name, file_size, width, height, created_at, folder
                    FROM images 
                    WHERE user_id = ? AND shared = 1
                    ORDER BY created_at DESC
                ");
                $stmt->execute([$userId]);
                $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Format for GrapesJS Asset Manager
                $assets = [];
                foreach ($images as $img) {
                    // Construct full URL with protocol and domain
                    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                    $host = $_SERVER['HTTP_HOST']; // e.g., localhost
                    $scriptName = dirname($_SERVER['SCRIPT_NAME']); // e.g., /imanage/public
                    
                    // Build path: uploads/username/folder/original/filename
                    // Files are stored in: uploads/username/folder/original/, /thumb/, /pristine/
                    $folderPath = $img['folder'] ? '/' . rawurlencode($img['folder']) : '';
                    $uploadsPath = $protocol . '://' . $host . $scriptName . '/uploads/' . $username . $folderPath . '/original/';
                    $fullPath = $uploadsPath . $img['filename'];
                    
                    // Thumbnail path
                    $thumbPath = $protocol . '://' . $host . $scriptName . '/uploads/' . $username . $folderPath . '/thumb/' . $img['filename'];
                    
                    // Check if it's a video based on extension
                    $ext = strtolower(pathinfo($img['filename'], PATHINFO_EXTENSION));
                    $isVideo = in_array($ext, ['mp4', 'webm', 'ogg', 'mov', 'avi']);
                    
                    $assets[] = [
                        'id' => $img['id'],
                        'type' => $isVideo ? 'video' : 'image',
                        'src' => $fullPath, // Full size image from original/
                        'height' => $img['height'] ?? 300,
                        'width' => $img['width'] ?? 300,
                        'name' => $img['original_name'] ?? $img['filename']
                    ];
                }
                
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'assets' => $assets, 'count' => count($assets)]);
            } catch (Exception $e) {
                error_log("getmyimages error: " . $e->getMessage());
                header('Content-Type: application/json');
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            break;

        case 'getpublicimages':
            // Get images for a public landing page (no authentication required)
            try {
                $token = $_GET['token'] ?? null;
                
                if (!$token) {
                    header('Content-Type: application/json');
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'No token provided']);
                    break;
                }
                
                $db = Database::getInstance();
                
                // Get user_id from landing page token
                $stmt = $db->prepare("SELECT user_id FROM landing_pages WHERE share_token = ? AND is_active = 1");
                $stmt->execute([$token]);
                $page = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$page) {
                    header('Content-Type: application/json');
                    http_response_code(404);
                    echo json_encode(['success' => false, 'error' => 'Page not found']);
                    break;
                }
                
                $userId = $page['user_id'];
                
                // Get username for path construction
                $userStmt = $db->prepare("SELECT username FROM users WHERE id = ?");
                $userStmt->execute([$userId]);
                $user = $userStmt->fetch(PDO::FETCH_ASSOC);
                $username = $user ? $user['username'] : 'unknown';
                
                // Get user's SHARED images only
                $stmt = $db->prepare("
                    SELECT id, filename, original_name, file_size, width, height, created_at, folder
                    FROM images 
                    WHERE user_id = ? AND shared = 1
                    ORDER BY created_at DESC
                ");
                $stmt->execute([$userId]);
                $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Format for GrapesJS Asset Manager
                $assets = [];
                foreach ($images as $img) {
                    // Construct full URL with protocol and domain
                    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                    $host = $_SERVER['HTTP_HOST']; // e.g., localhost
                    $scriptName = dirname($_SERVER['SCRIPT_NAME']); // e.g., /imanage/public
                    
                    // Build path: uploads/username/folder/original/filename
                    $folderPath = $img['folder'] ? '/' . rawurlencode($img['folder']) : '';
                    $uploadsPath = $protocol . '://' . $host . $scriptName . '/uploads/' . $username . $folderPath . '/original/';
                    $fullPath = $uploadsPath . $img['filename'];
                    
                    // Thumbnail path
                    $thumbPath = $protocol . '://' . $host . $scriptName . '/uploads/' . $username . $folderPath . '/thumb/' . $img['filename'];
                    
                    // Check if it's a video based on extension
                    $ext = strtolower(pathinfo($img['filename'], PATHINFO_EXTENSION));
                    $isVideo = in_array($ext, ['mp4', 'webm', 'ogg', 'mov', 'avi']);
                    
                    $assets[] = [
                        'id' => $img['id'],
                        'type' => $isVideo ? 'video' : 'image',
                        'src' => $fullPath,
                        'height' => $img['height'] ?? 300,
                        'width' => $img['width'] ?? 300,
                        'name' => $img['original_name'] ?? $img['filename'],
                        'folder' => $img['folder'] ?? ''
                    ];
                }
                
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'assets' => $assets, 'count' => count($assets)]);
            } catch (Exception $e) {
                error_log("getpublicimages error: " . $e->getMessage());
                header('Content-Type: application/json');
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            break;

        case 'getpublicvideos':
            // Get videos for a public landing page (no authentication required)
            try {
                $token = $_GET['token'] ?? null;
                
                if (!$token) {
                    header('Content-Type: application/json');
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'No token provided']);
                    break;
                }
                
                $db = Database::getInstance();
                
                // Get user_id from landing page token
                $stmt = $db->prepare("SELECT user_id FROM landing_pages WHERE share_token = ? AND is_active = 1");
                $stmt->execute([$token]);
                $page = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$page) {
                    header('Content-Type: application/json');
                    http_response_code(404);
                    echo json_encode(['success' => false, 'error' => 'Page not found']);
                    break;
                }
                
                $userId = $page['user_id'];
                
                // Get username for path construction
                $userStmt = $db->prepare("SELECT username FROM users WHERE id = ?");
                $userStmt->execute([$userId]);
                $user = $userStmt->fetch(PDO::FETCH_ASSOC);
                $username = $user ? $user['username'] : 'unknown';
                
                // Get user's SHARED videos only (file_type = 'video')
                $stmt = $db->prepare("
                    SELECT id, filename, original_name, file_size, width, height, created_at, folder
                    FROM images 
                    WHERE user_id = ? AND shared = 1 AND file_type = 'video'
                    ORDER BY created_at DESC
                ");
                $stmt->execute([$userId]);
                $videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Format for video gallery
                $assets = [];
                foreach ($videos as $video) {
                    // Construct full URL with protocol and domain
                    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                    $host = $_SERVER['HTTP_HOST'];
                    $scriptName = dirname($_SERVER['SCRIPT_NAME']);
                    
                    // Build path: uploads/username/folder/original/filename
                    $folderPath = $video['folder'] ? '/' . rawurlencode($video['folder']) : '';
                    $uploadsPath = $protocol . '://' . $host . $scriptName . '/uploads/' . $username . $folderPath . '/original/';
                    $fullPath = $uploadsPath . $video['filename'];
                    
                    // Thumbnail path
                    $thumbPath = $protocol . '://' . $host . $scriptName . '/uploads/' . $username . $folderPath . '/thumb/' . $video['filename'];
                    
                    $assets[] = [
                        'id' => $video['id'],
                        'type' => 'video',
                        'src' => $fullPath,
                        'thumb' => $thumbPath,
                        'name' => $video['original_name'] ?? $video['filename'],
                        'folder' => $video['folder'] ?? ''
                    ];
                }
                
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'assets' => $assets, 'count' => count($assets)]);
            } catch (Exception $e) {
                error_log("getpublicvideos error: " . $e->getMessage());
                header('Content-Type: application/json');
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            break;

        case 'getlandingpages':
            // Get all active landing pages for gallery display (public access)
            try {
                $db = Database::getInstance();
                
                // If logged in, show all user's pages. If not logged in, show all active pages from all users
                if (isset($_SESSION['user_id'])) {
                    $userId = $_SESSION['user_id'];
                    $stmt = $db->prepare("
                        SELECT id, page_title, share_token, is_active, created_at, updated_at, user_id, preview_image
                        FROM landing_pages 
                        WHERE user_id = ? 
                        ORDER BY updated_at DESC
                    ");
                    $stmt->execute([$userId]);
                } else {
                    // Public view - show all active pages
                    $stmt = $db->prepare("
                        SELECT id, page_title, share_token, is_active, created_at, updated_at, user_id, preview_image
                        FROM landing_pages 
                        WHERE is_active = 1 
                        ORDER BY updated_at DESC
                    ");
                    $stmt->execute();
                }
                
                $pages = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Format for gallery display
                $formatted = array_map(function($page) {
                    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                    $host = $_SERVER['HTTP_HOST'];
                    $scriptName = dirname($_SERVER['SCRIPT_NAME']);
                    
                    $result = [
                        'id' => $page['id'],
                        'type' => 'landing_page',
                        'title' => $page['page_title'],
                        'share_token' => $page['share_token'],
                        'is_active' => $page['is_active'],
                        'created_at' => $page['created_at'],
                        'updated_at' => $page['updated_at'],
                        'view_url' => $protocol . '://' . $host . $scriptName . '/landing-page.php?token=' . $page['share_token'],
                        'preview_image' => $page['preview_image'] ? $protocol . '://' . $host . $scriptName . '/uploads/page-previews/' . $page['preview_image'] : null
                    ];
                    
                    // Only include edit URL if user owns this page
                    if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $page['user_id']) {
                        $result['edit_url'] = $protocol . '://' . $host . $scriptName . '/page-designer.php?id=' . $page['id'];
                        $result['can_edit'] = true;
                    } else {
                        $result['can_edit'] = false;
                    }
                    
                    return $result;
                }, $pages);
                
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'pages' => $formatted, 'count' => count($formatted)]);
            } catch (Exception $e) {
                error_log("getlandingpages error: " . $e->getMessage());
                header('Content-Type: application/json');
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            break;

        // User endpoints
        case 'register':
            $controller = new UserController();
            $controller->register();
            break;

        case 'login':
            $controller = new UserController();
            $controller->login();
            break;

        case 'logout':
            $controller = new UserController();
            $controller->logout();
            break;

        case 'check_status':
            $controller = new UserController();
            $controller->checkStatus();
            break;

        case 'get_oauth_error':
            $controller = new UserController();
            $controller->getOAuthError();
            break;

        case 'get_profile':
            $controller = new UserController();
            $controller->getProfile();
            break;

        case 'update_email':
            $controller = new UserController();
            $controller->updateEmail();
            break;

        case 'change_password':
            $controller = new UserController();
            $controller->changePassword();
            break;

        case 'get_account_stats':
            $controller = new UserController();
            $controller->getAccountStats();
            break;

        case 'delete_account':
            $controller = new UserController();
            $controller->deleteAccount();
            break;

        // 2FA endpoints
        case 'get_2fa_status':
            $controller = new UserController();
            $controller->get2FAStatus();
            break;

        case 'generate_2fa_secret':
            $controller = new UserController();
            $controller->generate2FASecret();
            break;

        case 'enable_2fa':
            $controller = new UserController();
            $controller->enable2FA();
            break;

        case 'disable_2fa':
            $controller = new UserController();
            $controller->disable2FA();
            break;

        case 'regenerate_backup_codes':
            $controller = new UserController();
            $controller->regenerateBackupCodes();
            break;

        // Admin endpoints
        case 'is_admin':
            $controller = new AdminController();
            $controller->isAdmin();
            break;

        case 'admin_stats':
            $controller = new AdminController();
            $controller->getSystemStats();
            break;

        case 'admin_users':
            $controller = new AdminController();
            $controller->getUsersList();
            break;

        case 'admin_delete_user':
            $controller = new AdminController();
            $controller->deleteUser();
            break;

        case 'admin_analytics':
            // Return mock analytics data (TODO: implement real analytics)
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => [
                    'uploads' => array_fill(0, 7, rand(5, 20)),
                    'views' => array_fill(0, 7, rand(50, 200)),
                    'users' => array_fill(0, 7, rand(1, 5)),
                    'storage' => array_fill(0, 7, rand(100, 500))
                ]
            ]);
            exit;

        case 'admin_system_health':
            // Return mock system health data (TODO: implement real monitoring)
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => [
                    'cpu' => rand(10, 60),
                    'memory' => rand(30, 70),
                    'disk' => rand(40, 80),
                    'uptime' => '5 days 12 hours'
                ]
            ]);
            exit;

        case 'admin_active_sessions':
            $controller = new AdminController();
            $controller->getActiveSessions();
            break;

        case 'admin_failed_logins':
            $controller = new AdminController();
            $controller->getFailedLogins();
            break;

        case 'admin_ip_blacklist':
            $controller = new AdminController();
            $controller->getIpBlacklist();
            break;

        case 'admin_ip_whitelist':
            $controller = new AdminController();
            $controller->getIpWhitelist();
            break;

        case 'admin_add_ip':
            $controller = new AdminController();
            $controller->addIpToList();
            break;

        case 'admin_remove_ip':
            $controller = new AdminController();
            $controller->removeIpFromList();
            break;

        case 'admin_security_audit':
            $controller = new AdminController();
            $controller->getSecurityAudit();
            break;

        case 'admin_oauth_status':
            $controller = new AdminController();
            $controller->getOAuthStatus();
            break;

        case 'test_oauth_provider':
            $controller = new AdminController();
            $controller->testOAuthProvider();
            break;

        case 'admin_get_settings':
            $controller = new AdminController();
            $controller->getSettings();
            break;

        case 'admin_update_settings':
            $controller = new AdminController();
            $controller->updateSettings();
            break;

        case 'get_upload_config':
            // Public endpoint to get upload configuration
            $config = require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'app.php';
            $maxSizeMB = round($config['image']['max_file_size'] / (1024 * 1024), 2);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => [
                    'max_file_size_mb' => $maxSizeMB,
                    'allowed_types' => $config['image']['allowed_types']
                ]
            ]);
            exit;

        // Folder endpoints
        case 'list_folders':
            $controller = new FolderController();
            $controller->list();
            break;
            
        case 'create_folder':
            $controller = new FolderController();
            $controller->create();
            break;
            
        case 'update_folder':
            $controller = new FolderController();
            $controller->update($name);
            break;
            
        case 'delete_folder':
            $controller = new FolderController();
            $controller->delete($name);
            break;

        // Language/i18n endpoints
        case 'get_translations':
            $lang = $_GET['lang'] ?? $_SESSION['language'] ?? 'en';
            $lang = preg_replace('/[^a-z]/', '', $lang);
            $filePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'i18n' . DIRECTORY_SEPARATOR . $lang . '.php';
            
            if (!file_exists($filePath)) {
                $filePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'i18n' . DIRECTORY_SEPARATOR . 'en.php';
            }
            
            header('Content-Type: application/json');
            if (file_exists($filePath)) {
                $translations = require($filePath);
                echo json_encode($translations);
            } else {
                echo json_encode([]);
            }
            exit;

        case 'set_language':
            // Handle JSON body for POST requests
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            $lang = $data['language'] ?? $_POST['language'] ?? $_GET['language'] ?? null;
            
            if ($lang && preg_match('/^[a-z]{2}$/', $lang)) {
                $_SESSION['language'] = $lang;
                setcookie('language', $lang, time() + (30 * 24 * 60 * 60), '/');
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'language' => $lang
                ]);
            } else {
                http_response_code(400);
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'error' => 'Language parameter required'
                ]);
            }
            exit;

        case 'get_supported_languages':
            header('Content-Type: application/json');
            $languages = ['en', 'es', 'fr', 'de', 'zh'];
            $languageNames = [
                'en' => 'English',
                'es' => 'Español',
                'fr' => 'Français',
                'de' => 'Deutsch',
                'zh' => '简体中文'
            ];
            $data = [];
            foreach ($languages as $code) {
                $data[] = [
                    'code' => $code,
                    'name' => $languageNames[$code]
                ];
            }
            echo json_encode([
                'success' => true,
                'data' => $data
            ]);
            exit;

        case 'get_current_language':
            header('Content-Type: application/json');
            // Check session first, then cookie, then default to 'en'
            $lang = $_SESSION['language'] ?? $_COOKIE['language'] ?? 'en';
            // Ensure it's a valid language code
            $supportedLangs = ['en', 'es', 'fr', 'de', 'zh'];
            if (!in_array($lang, $supportedLangs)) {
                $lang = 'en';
            }
            $languageNames = [
                'en' => 'English',
                'es' => 'Español',
                'fr' => 'Français',
                'de' => 'Deutsch',
                'zh' => '简体中文'
            ];
            echo json_encode([
                'success' => true,
                'language' => $lang,
                'name' => $languageNames[$lang] ?? 'English'
            ]);
            exit;

        case 'saveopenaikey':
        case 'saveaisettings':
            error_log("=== saveaisettings API called ===");
            requireLogin();
            
            // Check if user is admin
            $db = Database::getInstance();
            $stmt = $db->prepare("SELECT is_admin FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user || !$user['is_admin']) {
                echo json_encode(['success' => false, 'message' => 'Admin access required']);
                break;
            }
            
            try {
                $input = json_decode(file_get_contents('php://input'), true);
                error_log("Received input: " . json_encode($input));
                
                // Save all AI settings
                $settings = [
                    'ai_provider' => $input['aiProvider'] ?? 'none',
                    'ollama_endpoint' => $input['ollamaEndpoint'] ?? 'http://localhost:11434',
                    'ollama_model' => $input['ollamaModel'] ?? 'llama3.2',
                    'lmstudio_endpoint' => $input['lmstudioEndpoint'] ?? 'http://localhost:1234',
                    'gemini_api_key' => $input['geminiApiKey'] ?? '',
                    'openai_api_key' => $input['api_key'] ?? $input['openaiApiKey'] ?? ''
                ];
                
                error_log("Saving settings: " . json_encode($settings));
                
                foreach ($settings as $key => $value) {
                    error_log("  Saving $key = $value");
                    $stmt = $db->prepare("SELECT id FROM system_settings WHERE setting_key = ?");
                    $stmt->execute([$key]);
                    $exists = $stmt->fetch();
                    
                    if ($exists) {
                        $stmt = $db->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = ?");
                        $stmt->execute([$value, $key]);
                    } else {
                        $stmt = $db->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?)");
                        $stmt->execute([$key, $value]);
                    }
                }
                
                echo json_encode(['success' => true, 'message' => 'AI settings saved']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            break;
            
        case 'getopenaikey':
        case 'getaisettings':
            requireLogin();
            
            // Check if user is admin
            $db = Database::getInstance();
            $stmt = $db->prepare("SELECT is_admin FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user || !$user['is_admin']) {
                echo json_encode(['success' => false, 'message' => 'Admin access required']);
                break;
            }
            
            try {
                $settings = [
                    'provider' => 'none',
                    'ollama_endpoint' => 'http://localhost:11434',
                    'ollama_model' => 'llama3.2',
                    'lmstudio_endpoint' => 'http://localhost:1234',
                    'gemini_api_key' => '',
                    'openai_api_key' => ''
                ];
                
                // Load all AI settings
                $stmt = $db->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key LIKE 'ai_%' OR setting_key LIKE '%_api_key' OR setting_key LIKE 'ollama_%' OR setting_key LIKE 'lmstudio_%' OR setting_key LIKE 'gemini_%'");
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $key = str_replace('ai_', '', $row['setting_key']);
                    $settings[$key] = $row['setting_value'];
                }
                
                $settings['success'] = true;
                echo json_encode($settings);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            break;

        case 'spintext':
            requireLogin();
            
            try {
                $input = json_decode(file_get_contents('php://input'), true);
                $text = $input['text'] ?? '';
                $tone = $input['tone'] ?? 'professional';
                $length = $input['length'] ?? 'same';
                
                if (empty($text)) {
                    echo json_encode(['success' => false, 'message' => 'Text is required']);
                    break;
                }
                
                // Get AI provider settings
                $db = Database::getInstance();
                $stmt = $db->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'ai_provider'");
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $provider = $result['setting_value'] ?? 'none';
                
                error_log("AI Provider detected: " . $provider);
                
                if ($provider === 'none' || empty($provider)) {
                    error_log("AI Provider is none or empty. Checking all settings...");
                    // Debug: Check what settings exist
                    $debugStmt = $db->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key LIKE '%ai%' OR setting_key LIKE '%ollama%' OR setting_key LIKE '%gemini%'");
                    while ($row = $debugStmt->fetch(PDO::FETCH_ASSOC)) {
                        error_log("  - " . $row['setting_key'] . " = " . $row['setting_value']);
                    }
                    
                    echo json_encode([
                        'success' => false,
                        'message' => 'AI provider not configured. Please select a provider in Admin Settings and click Save.'
                    ]);
                    break;
                }
                
                // Build prompt based on options
                $lengthInstruction = match($length) {
                    'shorter' => 'Make the rewrite shorter and more concise.',
                    'longer' => 'Make the rewrite longer and more detailed.',
                    default => 'Keep the rewrite approximately the same length.'
                };
                
                $prompt = "Rewrite the following text in a {$tone} tone. {$lengthInstruction}\n\nOriginal text: {$text}\n\nRewritten text:";
                $systemPrompt = "You are a professional copywriter and content editor. Only output the rewritten text, no explanations.";
                
                $spunText = '';
                
                // Handle different AI providers
                switch ($provider) {
                    case 'ollama':
                        // Get Ollama settings
                        $stmt = $db->prepare("SELECT setting_value FROM system_settings WHERE setting_key IN ('ollama_endpoint', 'ollama_model')");
                        $stmt->execute();
                        $ollamaSettings = ['ollama_endpoint' => 'http://localhost:11434', 'ollama_model' => 'llama3.2'];
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            $ollamaSettings[$row['setting_key']] = $row['setting_value'];
                        }
                        
                        $ch = curl_init($ollamaSettings['ollama_endpoint'] . '/api/generate');
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_POST, true);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                            'model' => $ollamaSettings['ollama_model'],
                            'prompt' => $systemPrompt . "\n\n" . $prompt,
                            'stream' => false
                        ]));
                        
                        $response = curl_exec($ch);
                        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                        curl_close($ch);
                        
                        if ($httpCode !== 200) {
                            throw new Exception("Ollama error (HTTP $httpCode). Is Ollama running?");
                        }
                        
                        $data = json_decode($response, true);
                        $spunText = $data['response'] ?? '';
                        break;
                        
                    case 'lmstudio':
                        // Get LM Studio settings
                        $stmt = $db->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'lmstudio_endpoint'");
                        $stmt->execute();
                        $result = $stmt->fetch(PDO::FETCH_ASSOC);
                        $endpoint = $result['setting_value'] ?? 'http://localhost:1234';
                        
                        $ch = curl_init($endpoint . '/v1/chat/completions');
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_POST, true);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                            'messages' => [
                                ['role' => 'system', 'content' => $systemPrompt],
                                ['role' => 'user', 'content' => $prompt]
                            ],
                            'temperature' => 0.7,
                            'max_tokens' => 1000
                        ]));
                        
                        $response = curl_exec($ch);
                        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                        curl_close($ch);
                        
                        if ($httpCode !== 200) {
                            throw new Exception("LM Studio error (HTTP $httpCode). Is LM Studio running with a model loaded?");
                        }
                        
                        $data = json_decode($response, true);
                        $spunText = $data['choices'][0]['message']['content'] ?? '';
                        break;
                        
                    case 'gemini':
                        // Get Gemini API key
                        $stmt = $db->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'gemini_api_key'");
                        $stmt->execute();
                        $result = $stmt->fetch(PDO::FETCH_ASSOC);
                        $apiKey = $result['setting_value'] ?? '';
                        
                        if (empty($apiKey)) {
                            throw new Exception('Gemini API key not configured');
                        }
                        
                        $ch = curl_init('https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=' . $apiKey);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_POST, true);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                            'contents' => [
                                ['parts' => [['text' => $systemPrompt . "\n\n" . $prompt]]]
                            ]
                        ]));
                        
                        $response = curl_exec($ch);
                        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                        curl_close($ch);
                        
                        if ($httpCode !== 200) {
                            error_log("Gemini API error: $response");
                            throw new Exception("Gemini API error (HTTP $httpCode)");
                        }
                        
                        $data = json_decode($response, true);
                        $spunText = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
                        break;
                        
                    case 'openai':
                        // Get OpenAI API key
                        $stmt = $db->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'openai_api_key'");
                        $stmt->execute();
                        $result = $stmt->fetch(PDO::FETCH_ASSOC);
                        $apiKey = $result['setting_value'] ?? '';
                        
                        if (empty($apiKey)) {
                            throw new Exception('OpenAI API key not configured');
                        }
                        
                        $ch = curl_init('https://api.openai.com/v1/chat/completions');
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_POST, true);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, [
                            'Content-Type: application/json',
                            'Authorization: Bearer ' . $apiKey
                        ]);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                            'model' => 'gpt-3.5-turbo',
                            'messages' => [
                                ['role' => 'system', 'content' => $systemPrompt],
                                ['role' => 'user', 'content' => $prompt]
                            ],
                            'temperature' => 0.7,
                            'max_tokens' => 1000
                        ]));
                        
                        $response = curl_exec($ch);
                        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                        curl_close($ch);
                        
                        if ($httpCode !== 200) {
                            throw new Exception("OpenAI API error (HTTP $httpCode)");
                        }
                        
                        $data = json_decode($response, true);
                        $spunText = $data['choices'][0]['message']['content'] ?? '';
                        break;
                        
                    default:
                        throw new Exception("Unknown AI provider: $provider");
                }
                
                if (empty($spunText)) {
                    echo json_encode(['success' => false, 'message' => 'No response from AI']);
                    break;
                }
                
                echo json_encode([
                    'success' => true,
                    'spun_text' => trim($spunText)
                ]);
                
            } catch (Exception $e) {
                error_log("spintext error: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            break;

        default:
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Unknown action: ' . htmlspecialchars($action)
            ]);
            break;
    }
} catch (Throwable $e) { // Catch both Error and Exception
    // Log the error to a project-local log for debugging
    $projectRoot = dirname(__DIR__);
    $logDir = $projectRoot . DIRECTORY_SEPARATOR . 'logs';
    if (!is_dir($logDir)) {
        if (!mkdir($logDir, 0755, true)) {
            // Can't create log dir, write to PHP error_log instead
            error_log($e->getMessage());
        }
    }
    $logFile = $logDir . DIRECTORY_SEPARATOR . 'api_errors.log';
    $logEntry = "[" . date('Y-m-d H:i:s') . "] Exception: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);

    http_response_code(500);
    header('Content-Type: application/json');
    // Don't expose internal error details in production
    $errorMessage = (getenv('APP_ENV') === 'development') ? $e->getMessage() : 'An internal error occurred';
    echo json_encode([
        'success' => false,
        'error' => $errorMessage
    ]);
}
