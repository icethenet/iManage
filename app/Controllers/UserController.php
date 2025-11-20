<?php

class UserController {

    private $userModel;

    public function __construct() {
        $this->userModel = new User();
    }

    /**
     * POST /api.php?action=register
     * Register a new user
     */
    public function register() {
        $data = json_decode(file_get_contents('php://input'), true);

        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';

        if (empty($username) || empty($password)) {
            $this->error('Username and password are required.', 400);
        }

        if (strlen($password) < 8) {
            $this->error('Password must be at least 8 characters long.', 400);
        }

        if ($this->userModel->findByUsername($username)) {
            $this->error('Username already exists.', 409); // 409 Conflict
        }

        $userId = $this->userModel->create($username, $password);
        if (!$userId) {
            $this->error('Could not create user record in the database.', 500);
        }

        // Physically create the user's root folder on the file system
        $config = require dirname(dirname(__DIR__)) . '/config/app.php';
        // Build a clean, OS-agnostic path using Path helper when available
        if (class_exists('Path')) {
            $userFolderPath = Path::buildUserFsPath($username);
        } else {
            $projectRoot = dirname(dirname(__DIR__));
            $userFolderPath = $projectRoot . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . ltrim($config['upload_dir'], '/') . DIRECTORY_SEPARATOR . $username;
        }

        if (!is_dir($userFolderPath)) {
            if (!mkdir($userFolderPath, 0775, true)) {
                // This is a critical file system error.
                $this->logError("Failed to create user root directory: " . $userFolderPath . ". Permissions issue?");
                $this->error('User was created, but could not create the user\'s folder on the file system. Check server permissions.', 500);
            }
        }

        // Automatically create a root folder for the new user in the database
        $folderModel = new Folder();
        $folderCreated = $folderModel->create($username, $userId, "Root folder for " . $username);

        if (!$folderCreated) {
            $this->error('User and physical folder were created, but failed to create the folder record in the database.', 500);
        }

        $this->response(['success' => true, 'message' => 'User registered successfully.', 'userId' => $userId], 201);
    }

    /**
     * POST /api.php?action=login
     * Log in a user
     */
    public function login() {
        $data = json_decode(file_get_contents('php://input'), true);

        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';

        if (empty($username) || empty($password)) {
            $this->error('Username and password are required.', 400);
        }

        $user = $this->userModel->findByUsername($username);

        if ($user && password_verify($password, $user['password_hash'])) {
            // Start session and store user data
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];

            $this->response([
                'success' => true, 
                'message' => 'Login successful.',
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username']
                ]
            ]);
        } else {
            $this->error('Invalid username or password.', 401); // 401 Unauthorized
        }
    }

    /**
     * GET /api.php?action=check_status
     * Check if a user is logged in via session.
     */
    public function checkStatus() {
        if (isset($_SESSION['user_id']) && isset($_SESSION['username'])) {
            $this->response([
                'success' => true,
                'logged_in' => true,
                'user' => [
                    'id' => $_SESSION['user_id'],
                    'username' => $_SESSION['username']
                ]
            ]);
        } else {
            $this->response(['success' => true, 'logged_in' => false]);
        }
    }

    /**
     * GET /api.php?action=get_oauth_error
     * Get OAuth error from session (if any)
     */
    public function getOAuthError() {
        $error = $_SESSION['oauth_error'] ?? null;
        
        if ($error) {
            unset($_SESSION['oauth_error']);
            $this->response(['error' => $error]);
        } else {
            $this->response(['error' => null]);
        }
    }

    /**
     * POST /api.php?action=logout
     * Log out a user
     */
    public function logout() {
        session_unset();
        session_destroy();
        $this->response(['success' => true, 'message' => 'Logout successful.']);
    }

    /**
     * GET /api.php?action=get_profile
     * Get current user's profile information
     */
    public function getProfile() {
        if (!isset($_SESSION['user_id'])) {
            $this->error('Not logged in', 401);
            return;
        }

        $user = $this->userModel->findById($_SESSION['user_id']);
        if (!$user) {
            $this->error('User not found', 404);
            return;
        }

        $this->response([
            'success' => true,
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'] ?? null,
                'created_at' => $user['created_at'],
                'last_login' => $user['last_login'] ?? null,
                'oauth_provider' => $user['oauth_provider'] ?? null,
                'avatar_url' => $user['avatar_url'] ?? null
            ]
        ]);
    }

    /**
     * POST /api.php?action=update_email
     * Update user's email address
     */
    public function updateEmail() {
        if (!isset($_SESSION['user_id'])) {
            $this->error('Not logged in', 401);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $email = $data['email'] ?? '';

        if (empty($email)) {
            $this->error('Email is required', 400);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Invalid email format', 400);
            return;
        }

        // Check if email is already taken
        $existingUser = $this->userModel->findByEmail($email);
        if ($existingUser && $existingUser['id'] != $_SESSION['user_id']) {
            $this->error('Email already in use', 409);
            return;
        }

        $updated = $this->userModel->updateEmail($_SESSION['user_id'], $email);
        if ($updated) {
            $this->response(['success' => true, 'message' => 'Email updated successfully']);
        } else {
            $this->error('Failed to update email', 500);
        }
    }

    /**
     * POST /api.php?action=change_password
     * Change user's password
     */
    public function changePassword() {
        if (!isset($_SESSION['user_id'])) {
            $this->error('Not logged in', 401);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $currentPassword = $data['current_password'] ?? '';
        $newPassword = $data['new_password'] ?? '';

        if (empty($currentPassword) || empty($newPassword)) {
            $this->error('Current password and new password are required', 400);
            return;
        }

        if (strlen($newPassword) < 8) {
            $this->error('New password must be at least 8 characters long', 400);
            return;
        }

        $user = $this->userModel->findById($_SESSION['user_id']);
        if (!$user) {
            $this->error('User not found', 404);
            return;
        }

        // OAuth users might not have passwords
        if (empty($user['password_hash'])) {
            $this->error('Cannot change password for OAuth accounts', 400);
            return;
        }

        if (!password_verify($currentPassword, $user['password_hash'])) {
            $this->error('Current password is incorrect', 401);
            return;
        }

        $updated = $this->userModel->updatePassword($_SESSION['user_id'], $newPassword);
        if ($updated) {
            $this->response(['success' => true, 'message' => 'Password changed successfully']);
        } else {
            $this->error('Failed to change password', 500);
        }
    }

    /**
     * GET /api.php?action=get_account_stats
     * Get user's account statistics
     */
    public function getAccountStats() {
        if (!isset($_SESSION['user_id'])) {
            $this->error('Not logged in', 401);
            return;
        }

        $imageModel = new Image();
        $folderModel = new Folder();
        
        // Get total images
        $totalImages = $imageModel->countByUser($_SESSION['user_id']);
        
        // Get total folders
        $totalFolders = $folderModel->countByUser($_SESSION['user_id']);
        
        // Get shared images count
        $sharedImages = $imageModel->countSharedByUser($_SESSION['user_id']);
        
        // Get storage used
        $storageUsed = $imageModel->getTotalSizeByUser($_SESSION['user_id']);

        $this->response([
            'success' => true,
            'stats' => [
                'total_images' => $totalImages,
                'total_folders' => $totalFolders,
                'shared_images' => $sharedImages,
                'storage_used' => $storageUsed
            ]
        ]);
    }

    /**
     * DELETE /api.php?action=delete_account
     * Delete user account and all associated data
     */
    public function deleteAccount() {
        if (!isset($_SESSION['user_id'])) {
            $this->error('Not logged in', 401);
            return;
        }

        $userId = $_SESSION['user_id'];
        $user = $this->userModel->findById($userId);
        
        if (!$user) {
            $this->error('User not found', 404);
            return;
        }

        // Delete user's physical files
        $config = require dirname(dirname(__DIR__)) . '/config/app.php';
        if (class_exists('Path')) {
            $userFolderPath = Path::buildUserFsPath($user['username']);
        } else {
            $projectRoot = dirname(dirname(__DIR__));
            $userFolderPath = $projectRoot . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . ltrim($config['upload_dir'], '/') . DIRECTORY_SEPARATOR . $user['username'];
        }

        if (is_dir($userFolderPath)) {
            $this->deleteDirectory($userFolderPath);
        }

        // Delete user record (cascade will delete images and folders)
        $deleted = $this->userModel->delete($userId);
        
        if ($deleted) {
            session_unset();
            session_destroy();
            $this->response(['success' => true, 'message' => 'Account deleted successfully']);
        } else {
            $this->error('Failed to delete account', 500);
        }
    }

    /**
     * Recursively delete a directory
     */
    private function deleteDirectory($dir) {
        if (!is_dir($dir)) {
            return false;
        }

        $items = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($items as $item) {
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                unlink($path);
            }
        }

        return rmdir($dir);
    }

    /**
     * Send JSON response
     */
    private function response($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Send error response
     */
    private function error($message, $statusCode = 500) {
        $this->response([
            'success' => false,
            'error' => $message
        ], $statusCode);
    }

    /**
     * Log errors to a specific folder_errors.log file.
     */
    private function logError($message) {
        $projectRoot = dirname(dirname(__DIR__));
        $logDir = $projectRoot . DIRECTORY_SEPARATOR . 'logs';
        if (!is_dir($logDir)) {
            if (!mkdir($logDir, 0755, true)) {
                // can't create logs dir; fall back to PHP error_log
                error_log($message);
                return;
            }
        }
        $logFile = $logDir . DIRECTORY_SEPARATOR . 'folder_errors.log';
        $entry = "[" . date('Y-m-d H:i:s') . "] " . $message . "\n";
        file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
    }
}