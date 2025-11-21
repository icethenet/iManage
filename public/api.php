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

// Initialize i18n (multi-language support)
I18n::init();

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
            I18n::init();
            $lang = $_GET['lang'] ?? I18n::getCurrentLanguage();
            header('Content-Type: application/json');
            echo I18n::getJSON($lang);
            exit;

        case 'set_language':
            I18n::init();
            $lang = $_POST['language'] ?? $_GET['language'] ?? null;
            if ($lang) {
                I18n::setLanguage($lang);
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'language' => I18n::getCurrentLanguage()
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
            $languages = I18n::getSupportedLanguages();
            $data = [];
            foreach ($languages as $code) {
                $data[] = [
                    'code' => $code,
                    'name' => I18n::getLanguageName($code)
                ];
            }
            echo json_encode([
                'success' => true,
                'data' => $data
            ]);
            exit;

        case 'get_current_language':
            I18n::init();
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'language' => I18n::getCurrentLanguage(),
                'name' => I18n::getLanguageName(I18n::getCurrentLanguage())
            ]);
            exit;

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
