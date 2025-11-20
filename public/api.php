<?php
/**
 * Autoloader for classes
 */

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

$action = isset($_GET['action']) ? preg_replace('/[^a-z_]/', '', strtolower($_GET['action'])) : 'list';
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
            // Return mock session data (TODO: implement real session tracking)
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => []
            ]);
            exit;

        case 'oauth_status':
            $controller = new AdminController();
            $controller->getOAuthStatus();
            break;

        case 'test_oauth_provider':
            $controller = new AdminController();
            $controller->testOAuthProvider();
            break;

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
