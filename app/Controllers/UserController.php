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