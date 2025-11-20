<?php
/**
 * Folder API Controller
 * Handles REST API endpoints for folder operations
 */

class FolderController {
    private $folderModel;

    public function __construct() {
        $this->folderModel = new Folder();
    }

    /**
     * GET /api/folders
     * List all folders
     */
    public function list() {
        try {
            if (!isset($_SESSION['user_id'])) {
                $this->error('Authentication required.', 401);
                return;
            }
            $userId = $_SESSION['user_id'];

            // Also get username to filter out their root folder
            $username = $_SESSION['username'];

            $folders = $this->folderModel->getByUserId($userId);

            // Filter out the user's root folder, which is named after their username
            $filteredFolders = array_filter($folders, fn($folder) => $folder['name'] !== $username && $folder['name'] !== 'default');
            
            $this->response([
                'success' => true,
                'data' => array_values($filteredFolders) // Re-index array for clean JSON
            ]);
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * POST /api/folders
     * Create new folder
     */
    public function create() {
        try {
            if (!isset($_SESSION['user_id'])) {
                $this->error('Authentication required.', 401);
                return;
            }
            $userId = $_SESSION['user_id'];

            $data = json_decode(file_get_contents('php://input'), true);

            if (!isset($data['name']) || empty($data['name'])) {
                $this->error('Folder name is required', 400);
                return;
            }

            // Sanitize folder name: allow only alphanumeric, dash, underscore, space
            $folderName = $data['name'];
            $sanitizedName = preg_replace('/[^a-zA-Z0-9_\-\s]/', '', $folderName);
            if (empty($sanitizedName) || $sanitizedName !== $folderName) {
                $this->error('Folder names must contain only letters, numbers, spaces, dashes, and underscores.', 400);
                return;
            }
            $data['name'] = $sanitizedName;
            
            // Physically create the folder on the file system.
            // All folders created via the UI are inside the user's root directory.
            $config = require dirname(dirname(__DIR__)) . '/config/app.php';
            $userRootFolder = $_SESSION['username']; // The user's main folder is named after their username.
            // Build a clean, OS-agnostic path using Path helper when available.
            if (class_exists('Path')) {
                $newFolderPath = Path::buildUserFsPath($userRootFolder, $data['name']);
            } else {
                $projectRoot = dirname(dirname(__DIR__));
                $newFolderPath = $projectRoot . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . ltrim($config['upload_dir'], '/') . DIRECTORY_SEPARATOR . $userRootFolder . DIRECTORY_SEPARATOR . $data['name'];
            }

            if (!is_dir($newFolderPath)) {
                if (!mkdir($newFolderPath, 0775, true)) {
                    // This is a critical file system error.
                    $this->logError("Failed to create directory: " . $newFolderPath . ". Permissions issue?");
                    $this->error('Could not create the folder on the file system. Check server permissions.', 500);
                    return;
                } else {
                    // Also create the necessary subdirectories for image storage
                    if (!is_dir($newFolderPath . DIRECTORY_SEPARATOR . $config['original_dir'])) {
                        mkdir($newFolderPath . DIRECTORY_SEPARATOR . $config['original_dir'], 0775, true);
                    }
                    if (!is_dir($newFolderPath . DIRECTORY_SEPARATOR . $config['thumb_dir'])) {
                        mkdir($newFolderPath . DIRECTORY_SEPARATOR . $config['thumb_dir'], 0775, true);
                    }
                    if (!is_dir($newFolderPath . DIRECTORY_SEPARATOR . 'pristine')) {
                        mkdir($newFolderPath . DIRECTORY_SEPARATOR . 'pristine', 0775, true);
                    }
                }
            }

            $folder = $this->folderModel->create(
                $data['name'],
                $userId,
                $data['description'] ?? '',
                $data['parent_id'] ?? null
            );

            if (!$folder) {
                $this->error('Folder already exists', 400);
                return;
            }

            $this->response([
                'success' => true,
                'message' => 'Folder created successfully',
                'data' => $folder
            ], 201);
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * PUT /api/folders/{name}
     * Update folder
     */
    public function update($name) {
        try {
            if (!isset($_SESSION['user_id'])) {
                $this->error('Authentication required.', 401);
                return;
            }
            $userId = $_SESSION['user_id'];

            $folder = $this->folderModel->getByNameForUser($name, $userId);
            if (!$folder) {
                $this->error('Folder not found', 404);
                return;
            }

            $data = json_decode(file_get_contents('php://input'), true);
            $this->folderModel->update($name, $userId, $data);

            $this->response([
                'success' => true,
                'message' => 'Folder updated successfully'
            ]);
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * DELETE /api/folders/{name}
     * Delete folder
     */
    public function delete($name) {
        try {
            if (!isset($_SESSION['user_id'])) {
                $this->error('Authentication required.', 401);
                return;
            }
            $userId = $_SESSION['user_id'];

            if (!$this->folderModel->delete($name, $userId)) {
                $this->error('Cannot delete default folder or folder does not exist', 400);
                return;
            }

            // After deleting from DB, delete the physical folder
            $config = require dirname(dirname(__DIR__)) . '/config/app.php';
            $userRootFolder = $_SESSION['username'];
            if (class_exists('Path')) {
                $folderPath = Path::buildUserFsPath($userRootFolder, $name);
            } else {
                $projectRoot = dirname(dirname(__DIR__));
                $folderPath = $projectRoot . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . ltrim($config['upload_dir'], '/') . DIRECTORY_SEPARATOR . $userRootFolder . DIRECTORY_SEPARATOR . $name;
            }

            if (is_dir($folderPath)) {
                $this->deleteDirectory($folderPath);
            }

            $this->response([
                'success' => true,
                'message' => 'Folder deleted successfully'
            ]);
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
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
                error_log($message);
                return;
            }
        }
        $logFile = $logDir . DIRECTORY_SEPARATOR . 'folder_errors.log';
        $entry = "[" . date('Y-m-d H:i:s') . "] " . $message . "\n";
        file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
    }

    /**
     * Recursively delete a directory and its contents.
     */
    private function deleteDirectory($dir) {
        if (!file_exists($dir)) {
            return true;
        }
        if (!is_dir($dir)) {
            return unlink($dir);
        }
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }
            if (!$this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }
        return rmdir($dir);
    }
}
