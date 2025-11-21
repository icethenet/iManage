<?php
/**
 * AdminController - Handles admin-specific operations
 */

require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Models/Image.php';
require_once __DIR__ . '/../Models/Folder.php';

class AdminController {
    
    /**
     * Check if current user is admin
     */
    public function isAdmin() {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
            $this->jsonResponse(['success' => false, 'error' => 'Not authenticated']);
            return;
        }
        
        // Verify session integrity
        if (!$this->verifySessionIntegrity()) {
            session_destroy();
            $this->jsonResponse(['success' => false, 'error' => 'Session validation failed']);
            return;
        }
        
        // Admin is determined by username being 'admin'
        $isAdmin = $_SESSION['username'] === 'admin';
        
        $this->jsonResponse([
            'success' => true,
            'data' => ['is_admin' => $isAdmin]
        ]);
    }
    
    /**
     * Get system statistics
     */
    public function getSystemStats() {
        if (!$this->requireAdmin()) return;
        
        $userModel = new User();
        $imageModel = new Image();
        $folderModel = new Folder();
        
        try {
            // Get total users
            $totalUsers = $userModel->getTotalCount();
            
            // Get total images
            $totalImages = $imageModel->getTotalCount();
            
            // Get total storage
            $totalStorage = $imageModel->getTotalStorage();
            
            // Get total folders
            $totalFolders = $folderModel->getTotalCount();
            
            $this->jsonResponse([
                'success' => true,
                'data' => [
                    'total_users' => $totalUsers,
                    'total_images' => $totalImages,
                    'total_storage' => $totalStorage,
                    'total_folders' => $totalFolders
                ]
            ]);
        } catch (Exception $e) {
            error_log("Error getting system stats: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'error' => 'Failed to get system statistics']);
        }
    }
    
    /**
     * Get list of all users with their statistics
     */
    public function getUsersList() {
        if (!$this->requireAdmin()) return;
        
        $userModel = new User();
        $imageModel = new Image();
        
        try {
            $users = $userModel->getAllUsers();
            
            // Add statistics for each user
            foreach ($users as &$user) {
                $user['image_count'] = $imageModel->countByUser($user['id']);
                $user['total_storage'] = $imageModel->getTotalSizeByUser($user['id']);
            }
            
            error_log("Users list: Found " . count($users) . " users");
            
            $this->jsonResponse([
                'success' => true,
                'data' => $users
            ]);
        } catch (Exception $e) {
            error_log("Error getting users list: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'error' => 'Failed to get users list: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Get active user sessions
     */
    public function getActiveSessions() {
        if (!$this->requireAdmin()) return;
        
        try {
            $config = require __DIR__ . '/../../config/database.php';
            $pdo = new PDO(
                'mysql:host=' . $config['host'] . ';dbname=' . $config['database'],
                $config['username'],
                $config['password']
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Clean up old sessions first
            $pdo->exec("DELETE FROM active_sessions WHERE last_activity < DATE_SUB(NOW(), INTERVAL 30 MINUTE)");
            
            // Get active sessions
            $stmt = $pdo->query("
                SELECT session_id, user_id, username, ip_address, user_agent,
                       DATE_FORMAT(started_at, '%Y-%m-%d %H:%i:%s') as started_at,
                       DATE_FORMAT(last_activity, '%Y-%m-%d %H:%i:%s') as last_activity
                FROM active_sessions
                ORDER BY last_activity DESC
            ");
            $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $this->jsonResponse([
                'success' => true,
                'data' => $sessions
            ]);
        } catch (Exception $e) {
            error_log("Error getting active sessions: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'error' => 'Failed to get active sessions']);
        }
    }
    
    /**
     * Get failed login attempts
     */
    public function getFailedLogins() {
        if (!$this->requireAdmin()) return;
        
        try {
            $config = require __DIR__ . '/../../config/database.php';
            $pdo = new PDO(
                'mysql:host=' . $config['host'] . ';dbname=' . $config['database'],
                $config['username'],
                $config['password']
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Get failed logins from last 24 hours
            $stmt = $pdo->query("
                SELECT username, ip_address, user_agent,
                       DATE_FORMAT(attempted_at, '%Y-%m-%d %H:%i:%s') as attempted_at
                FROM failed_logins
                WHERE attempted_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
                ORDER BY attempted_at DESC
                LIMIT 100
            ");
            $failedLogins = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $this->jsonResponse([
                'success' => true,
                'data' => $failedLogins
            ]);
        } catch (Exception $e) {
            error_log("Error getting failed logins: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'error' => 'Failed to get failed logins']);
        }
    }
    
    /**
     * Get IP blacklist
     */
    public function getIpBlacklist() {
        if (!$this->requireAdmin()) return;
        
        try {
            $config = require __DIR__ . '/../../config/database.php';
            $pdo = new PDO(
                'mysql:host=' . $config['host'] . ';dbname=' . $config['database'],
                $config['username'],
                $config['password']
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $stmt = $pdo->query("
                SELECT id, ip_address, reason, added_by,
                       DATE_FORMAT(added_at, '%Y-%m-%d %H:%i:%s') as added_at
                FROM ip_access_control
                WHERE type = 'blacklist'
                ORDER BY added_at DESC
            ");
            $blacklist = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $this->jsonResponse([
                'success' => true,
                'data' => $blacklist
            ]);
        } catch (Exception $e) {
            error_log("Error getting IP blacklist: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'error' => 'Failed to get IP blacklist']);
        }
    }
    
    /**
     * Get IP whitelist
     */
    public function getIpWhitelist() {
        if (!$this->requireAdmin()) return;
        
        try {
            $config = require __DIR__ . '/../../config/database.php';
            $pdo = new PDO(
                'mysql:host=' . $config['host'] . ';dbname=' . $config['database'],
                $config['username'],
                $config['password']
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $stmt = $pdo->query("
                SELECT id, ip_address, reason, added_by,
                       DATE_FORMAT(added_at, '%Y-%m-%d %H:%i:%s') as added_at
                FROM ip_access_control
                WHERE type = 'whitelist'
                ORDER BY added_at DESC
            ");
            $whitelist = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $this->jsonResponse([
                'success' => true,
                'data' => $whitelist
            ]);
        } catch (Exception $e) {
            error_log("Error getting IP whitelist: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'error' => 'Failed to get IP whitelist']);
        }
    }
    
    /**
     * Add IP to blacklist or whitelist
     */
    public function addIpToList() {
        if (!$this->requireAdmin()) return;
        
        $data = json_decode(file_get_contents('php://input'), true);
        $ipAddress = trim($data['ip_address'] ?? '');
        $type = $data['type'] ?? '';
        $reason = trim($data['reason'] ?? '');
        
        if (!$ipAddress || !in_array($type, ['blacklist', 'whitelist'])) {
            $this->jsonResponse(['success' => false, 'error' => 'Invalid IP address or type']);
            return;
        }
        
        try {
            $config = require __DIR__ . '/../../config/database.php';
            $pdo = new PDO(
                'mysql:host=' . $config['host'] . ';dbname=' . $config['database'],
                $config['username'],
                $config['password']
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $stmt = $pdo->prepare("
                INSERT INTO ip_access_control (ip_address, type, reason, added_by, added_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$ipAddress, $type, $reason, $_SESSION['username']]);
            
            $this->jsonResponse([
                'success' => true,
                'message' => "IP $ipAddress added to $type"
            ]);
        } catch (Exception $e) {
            error_log("Error adding IP to list: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'error' => 'Failed to add IP address']);
        }
    }
    
    /**
     * Remove IP from blacklist or whitelist
     */
    public function removeIpFromList() {
        if (!$this->requireAdmin()) return;
        
        $data = json_decode(file_get_contents('php://input'), true);
        $id = intval($data['id'] ?? 0);
        
        if (!$id) {
            $this->jsonResponse(['success' => false, 'error' => 'Invalid ID']);
            return;
        }
        
        try {
            $config = require __DIR__ . '/../../config/database.php';
            $pdo = new PDO(
                'mysql:host=' . $config['host'] . ';dbname=' . $config['database'],
                $config['username'],
                $config['password']
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $stmt = $pdo->prepare("DELETE FROM ip_access_control WHERE id = ?");
            $stmt->execute([$id]);
            
            $this->jsonResponse([
                'success' => true,
                'message' => 'IP address removed'
            ]);
        } catch (Exception $e) {
            error_log("Error removing IP from list: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'error' => 'Failed to remove IP address']);
        }
    }
    
    /**
     * Delete a user (admin action)
     */
    public function deleteUser() {
        if (!$this->requireAdmin()) return;
        
        $data = json_decode(file_get_contents('php://input'), true);
        $userId = intval($data['user_id'] ?? 0);
        
        if (!$userId) {
            $this->jsonResponse(['success' => false, 'error' => 'User ID is required']);
            return;
        }
        
        // Prevent admin from deleting themselves
        if ($userId == $_SESSION['user_id']) {
            $this->jsonResponse(['success' => false, 'error' => 'Cannot delete your own account']);
            return;
        }
        
        $userModel = new User();
        
        try {
            // Get user info to delete their files
            $user = $userModel->findById($userId);
            
            if (!$user) {
                $this->jsonResponse(['success' => false, 'error' => 'User not found']);
                return;
            }
            
            // Delete user's files
            $uploadsPath = __DIR__ . '/../../public/uploads/' . $user['username'];
            if (is_dir($uploadsPath)) {
                $this->deleteDirectory($uploadsPath);
            }
            
            // Delete user from database (cascading should handle images and folders)
            $userModel->delete($userId);
            
            $this->jsonResponse(['success' => true, 'message' => 'User deleted successfully']);
        } catch (Exception $e) {
            error_log("Error deleting user: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'error' => 'Failed to delete user']);
        }
    }
    
    /**
     * Check OAuth provider configuration status
     */
    public function getOAuthStatus() {
        if (!$this->requireAdmin()) return;
        
        $oauthConfigPath = __DIR__ . '/../../config/oauth.php';
        
        if (!file_exists($oauthConfigPath)) {
            $this->jsonResponse([
                'success' => true,
                'data' => [
                    'google' => false,
                    'facebook' => false,
                    'github' => false,
                    'microsoft' => false
                ]
            ]);
            return;
        }
        
        $oauthConfig = require $oauthConfigPath;
        
        $status = [
            'google' => !empty($oauthConfig['google']['client_id']) && !empty($oauthConfig['google']['client_secret']),
            'facebook' => !empty($oauthConfig['facebook']['client_id']) && !empty($oauthConfig['facebook']['client_secret']),
            'github' => !empty($oauthConfig['github']['client_id']) && !empty($oauthConfig['github']['client_secret']),
            'microsoft' => !empty($oauthConfig['microsoft']['client_id']) && !empty($oauthConfig['microsoft']['client_secret'])
        ];
        
        $this->jsonResponse([
            'success' => true,
            'data' => $status
        ]);
    }
    
    /**
     * Test OAuth provider configuration
     */
    public function testOAuthProvider() {
        if (!$this->requireAdmin()) return;
        
        $provider = preg_replace('/[^a-z]/', '', strtolower($_GET['provider'] ?? ''));
        
        if (!$provider || !in_array($provider, ['google', 'facebook', 'github', 'microsoft'], true)) {
            $this->jsonResponse(['success' => false, 'error' => 'Invalid provider']);
            return;
        }
        
        $oauthConfigPath = __DIR__ . '/../../config/oauth.php';
        
        if (!file_exists($oauthConfigPath)) {
            $this->jsonResponse(['success' => false, 'error' => 'OAuth configuration file not found']);
            return;
        }
        
        $oauthConfig = require $oauthConfigPath;
        
        if (empty($oauthConfig[$provider]['client_id']) || empty($oauthConfig[$provider]['client_secret'])) {
            $this->jsonResponse(['success' => false, 'error' => 'Provider not configured']);
            return;
        }
        
        // Basic validation - provider has required credentials
        $this->jsonResponse([
            'success' => true,
            'message' => 'Provider configuration looks valid'
        ]);
    }
    
    /**
     * Require admin access
     */
    private function requireAdmin() {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
            $this->jsonResponse(['success' => false, 'error' => 'Not authenticated']);
            return false;
        }
        
        if ($_SESSION['username'] !== 'admin') {
            $this->jsonResponse(['success' => false, 'error' => 'Access denied. Admin privileges required.']);
            return false;
        }
        
        return true;
    }
    
    /**
     * Verify session integrity to prevent session hijacking
     */
    private function verifySessionIntegrity() {
        // Check if session has required fingerprint data
        if (!isset($_SESSION['ip_address']) || !isset($_SESSION['user_agent'])) {
            return true; // Legacy sessions without fingerprinting still allowed
        }
        
        // Verify IP address hasn't changed
        $currentIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        if ($_SESSION['ip_address'] !== $currentIP) {
            error_log("Session hijacking attempt detected: IP changed from {$_SESSION['ip_address']} to {$currentIP}");
            return false;
        }
        
        // Verify user agent hasn't changed
        $currentUA = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        if ($_SESSION['user_agent'] !== $currentUA) {
            error_log("Session hijacking attempt detected: User agent changed");
            return false;
        }
        
        return true;
    }
    
    /**
     * Recursively delete a directory
     */
    private function deleteDirectory($dir) {
        if (!is_dir($dir)) return;
        
        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        
        rmdir($dir);
    }
    
    /**
     * Send JSON response
     */
    private function jsonResponse($data) {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    /**
     * Get security audit log
     */
    public function getSecurityAudit() {
        if (!$this->requireAdmin()) return;
        
        $filter = $_GET['filter'] ?? 'all';
        
        try {
            $config = require __DIR__ . '/../../config/database.php';
            $pdo = new PDO(
                'mysql:host=' . $config['host'] . ';dbname=' . $config['database'],
                $config['username'],
                $config['password']
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $sql = "SELECT * FROM security_audit_log";
            
            if ($filter !== 'all') {
                $sql .= " WHERE event_type = ?";
                $stmt = $pdo->prepare($sql . " ORDER BY timestamp DESC LIMIT 100");
                $stmt->execute([$filter]);
            } else {
                $stmt = $pdo->prepare($sql . " ORDER BY timestamp DESC LIMIT 100");
                $stmt->execute();
            }
            
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $this->jsonResponse([
                'success' => true,
                'data' => $logs
            ]);
        } catch (PDOException $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Failed to fetch audit log: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Log security event
     */
    public static function logSecurityEvent($eventType, $description, $userId = null, $username = null) {
        try {
            $config = require __DIR__ . '/../../config/database.php';
            $pdo = new PDO(
                'mysql:host=' . $config['host'] . ';dbname=' . $config['database'],
                $config['username'],
                $config['password']
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $stmt = $pdo->prepare("
                INSERT INTO security_audit_log 
                (user_id, username, event_type, event_description, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $userId,
                $username,
                $eventType,
                $description,
                $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
            ]);
        } catch (PDOException $e) {
            error_log("Failed to log security event: " . $e->getMessage());
        }
    }
    
    /**
     * Get application settings
     */
    public function getSettings() {
        if (!$this->requireAdmin()) return;
        
        $config = require __DIR__ . '/../../config/app.php';
        
        $this->jsonResponse([
            'success' => true,
            'data' => [
                'max_file_size' => $config['image']['max_file_size'],
                'max_file_size_mb' => round($config['image']['max_file_size'] / (1024 * 1024), 2),
                'allowed_types' => $config['image']['allowed_types'],
                'default_quality' => $config['image']['default_quality'],
                'thumbnail_width' => $config['image']['thumbnail_width'],
                'thumbnail_height' => $config['image']['thumbnail_height'],
            ]
        ]);
    }
    
    /**
     * Update application settings
     */
    public function updateSettings() {
        if (!$this->requireAdmin()) return;
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['max_file_size_mb'])) {
            $this->jsonResponse(['success' => false, 'error' => 'Missing max_file_size_mb parameter']);
            return;
        }
        
        $maxFileSizeMB = floatval($input['max_file_size_mb']);
        
        // Validate range (1MB to 50MB)
        if ($maxFileSizeMB < 1 || $maxFileSizeMB > 50) {
            $this->jsonResponse(['success' => false, 'error' => 'Max file size must be between 1MB and 50MB']);
            return;
        }
        
        $maxFileSizeBytes = (int)($maxFileSizeMB * 1024 * 1024);
        
        // Update config file
        $configPath = __DIR__ . '/../../config/app.php';
        $config = require $configPath;
        $config['image']['max_file_size'] = $maxFileSizeBytes;
        
        // Write updated config
        $configContent = "<?php\n/**\n * Application Configuration\n * Cross-Platform (Windows/Linux/macOS)\n */\n\nreturn " . var_export($config, true) . ";\n";
        
        if (file_put_contents($configPath, $configContent) === false) {
            $this->jsonResponse(['success' => false, 'error' => 'Failed to update configuration file']);
            return;
        }
        
        // Set PHP runtime limits for current request
        @ini_set('upload_max_filesize', $maxFileSizeMB . 'M');
        @ini_set('post_max_size', ($maxFileSizeMB + 2) . 'M');
        
        $this->logSecurityEvent($_SESSION['user_id'], $_SESSION['username'], 'settings_change', 
            "Updated max file size to {$maxFileSizeMB}MB");
        
        $this->jsonResponse([
            'success' => true,
            'data' => [
                'max_file_size' => $maxFileSizeBytes,
                'max_file_size_mb' => $maxFileSizeMB,
                'message' => 'Settings updated successfully'
            ]
        ]);
    }
}
