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
}
