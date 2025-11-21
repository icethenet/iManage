<?php
/**
 * Image API Controller
 * Handles REST API endpoints for image operations
 */

class ImageController {
    
    
    private $imageModel;
    private $uploadHandler;
    private $config;

    public function __construct() {
        $this->imageModel = new Image();
        $this->uploadHandler = new ImageUploadHandler();
        $appConfigPath = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'app.php';
        $this->config = require $appConfigPath;
    }

    /**
     * Build a base URL from config or the current request.
     */
    private function getBaseUrl() {
        // Always prefer the app_url from the config file as the single source of truth.
        // Fallback to a sensible default if not set.
        return rtrim($this->config['app_url'] ?? 'http://localhost:8000', '/');
    }

    /**
     * GET /api/images
     * List all images with pagination
     */
    public function list() {
        try {
            $userId = $_SESSION['user_id'] ?? null;

            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? min((int)$_GET['limit'], 50) : ($this->config['pagination']['default_limit'] ?? 12);
            // Sanitize folder name to prevent path traversal
            $folder = isset($_GET['folder']) ? preg_replace('/[^a-zA-Z0-9_\s-]/', '', $_GET['folder']) : null;
            // Sanitize search query
            $search = isset($_GET['search']) ? htmlspecialchars(trim($_GET['search']), ENT_QUOTES, 'UTF-8') : null;

            if ($page < 1) $page = 1;
            
            if ($search) {
                $images = $this->imageModel->search($search, $page, $limit, $userId);
            } else {
                $images = $this->imageModel->getByFolder($folder, $page, $limit, $userId);
            }

            $total = $this->imageModel->getCount($folder, $userId);
            $totalPages = ceil($total / $limit);

            // Add thumbnail URLs (use current request host if app_url is not applicable)
            $base = $this->getBaseUrl();
            $uploadDir = rtrim($this->config['upload_dir'], '/');
            
            // Get user model to find username for path construction
            $userModel = new User();

            foreach ($images as &$image) {
                $user = $userModel->findById($image['user_id']);
                $username = $user ? $user['username'] : null;
                $encodedFolder = ($image['folder'] && $image['folder'] !== 'default') ? rawurlencode($image['folder']) : null;
                $urlPathSegment = $username . ($encodedFolder ? '/' . $encodedFolder : '');

                // For videos, use .jpg for thumbnail; for images, use original filename
                $thumbFilename = $image['filename'];
                if ($image['file_type'] === 'video') {
                    $thumbFilename = pathinfo($image['filename'], PATHINFO_FILENAME) . '.jpg';
                }
                
                $image['thumbnail_url'] = $base . $uploadDir . '/' . $urlPathSegment . '/' . $this->config['thumb_dir'] . '/' . $thumbFilename;
                $image['original_url'] = $base . $uploadDir . '/' . $urlPathSegment . '/' . $this->config['original_dir'] . '/' . $image['filename'];
            }

            $this->response([
                'success' => true,
                'data' => $images,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => $totalPages,
                    'total_items' => $total,
                    'limit' => $limit
                ]
            ]);
        } catch (Exception $e) {
            // Temporary: return full exception trace in JSON when debugging is enabled
            $debugEnabled = (isset($_GET['__debug']) && $_GET['__debug'] === '1') || file_exists(dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'enable_upload_debug');
            if ($debugEnabled) {
                http_response_code(500);
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                exit;
            } else {
                $this->error($e->getMessage());
            }
        }
    }

    /**
     * GET /api/images/{id}
     * Get single image details
     */
    public function get($id) {
        try {
            $image = $this->imageModel->getById($id);
            if (!$image) {
                $this->error('Image not found', 404);
                return;
            }

            $base = $this->getBaseUrl();
            $uploadDir = rtrim($this->config['upload_dir'], '/');
            $userModel = new User();
            $user = $userModel->findById($image['user_id']);
            $username = $user ? $user['username'] : null;
            $encodedFolder = ($image['folder'] && $image['folder'] !== 'default') ? rawurlencode($image['folder']) : null;
            $urlPathSegment = $username . ($encodedFolder ? '/' . $encodedFolder : '');

            // For videos, use .jpg for thumbnail; for images, use original filename
            $thumbFilename = $image['filename'];
            if ($image['file_type'] === 'video') {
                $thumbFilename = pathinfo($image['filename'], PATHINFO_FILENAME) . '.jpg';
            }

            $image['thumbnail_url'] = $base . $uploadDir . '/' . $urlPathSegment . '/' . $this->config['thumb_dir'] . '/' . $thumbFilename;
            $image['original_url'] = $base . $uploadDir . '/' . $urlPathSegment . '/' . $this->config['original_dir'] . '/' . $image['filename'];
            $image['metadata'] = $this->imageModel->getMetadata($id);

            // EXIF extraction (on-demand, non-fatal)
            $image['exif'] = null;
            if (function_exists('exif_read_data')) {
                // Only attempt for JPEG/JPG
                $ext = strtolower(pathinfo($image['filename'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg','jpeg'])) {
                    // Build filesystem path to original
                    if (class_exists('Path')) {
                        $baseFs = Path::uploadsBaseFs();
                    } else {
                        $projectRoot = dirname(dirname(__DIR__));
                        $baseFs = $projectRoot . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . ltrim($this->config['upload_dir'], '/');
                    }
                    $fsFolderSegment = $username . ($image['folder'] && $image['folder'] !== 'default' ? DIRECTORY_SEPARATOR . $image['folder'] : '');
                    $originalFsPath = $baseFs . DIRECTORY_SEPARATOR . $fsFolderSegment . DIRECTORY_SEPARATOR . $this->config['original_dir'] . DIRECTORY_SEPARATOR . $image['filename'];
                    if (is_readable($originalFsPath)) {
                        try {
                            $raw = @exif_read_data($originalFsPath, null, true, false);
                            if ($raw && is_array($raw)) {
                                $image['exif'] = $this->filterExif($raw);
                            }
                        } catch (Exception $ex) {
                            // Ignore EXIF failures
                        }
                    }
                }
            }

            $this->response([
                'success' => true,
                'data' => $image
            ]);
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * GET /api/shared?token={token}
     * Get shared image by token (public endpoint, no auth required)
     */
    public function getShared($token) {
        try {
            $image = $this->imageModel->getByShareToken($token);
            if (!$image) {
                $this->error('Shared image not found or link is invalid', 404);
                return;
            }

            $base = $this->getBaseUrl();
            $uploadDir = rtrim($this->config['upload_dir'], '/');
            $userModel = new User();
            $user = $userModel->findById($image['user_id']);
            $username = $user ? $user['username'] : null;
            $encodedFolder = ($image['folder'] && $image['folder'] !== 'default') ? rawurlencode($image['folder']) : null;
            $urlPathSegment = $username . ($encodedFolder ? '/' . $encodedFolder : '');

            // For videos, use .jpg for thumbnail; for images, use original filename
            $thumbFilename = $image['filename'];
            if ($image['file_type'] === 'video') {
                $thumbFilename = pathinfo($image['filename'], PATHINFO_FILENAME) . '.jpg';
            }

            $image['thumbnail_url'] = $base . $uploadDir . '/' . $urlPathSegment . '/' . $this->config['thumb_dir'] . '/' . $thumbFilename;
            $image['original_url'] = $base . $uploadDir . '/' . $urlPathSegment . '/' . $this->config['original_dir'] . '/' . $image['filename'];

            // EXIF for shared images (subset, privacy: omit GPS)
            $image['exif'] = null;
            if (function_exists('exif_read_data')) {
                $ext = strtolower(pathinfo($image['filename'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg','jpeg'])) {
                    if (class_exists('Path')) {
                        $baseFs = Path::uploadsBaseFs();
                    } else {
                        $projectRoot = dirname(dirname(__DIR__));
                        $baseFs = $projectRoot . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . ltrim($this->config['upload_dir'], '/');
                    }
                    $fsFolderSegment = $username . ($image['folder'] && $image['folder'] !== 'default' ? DIRECTORY_SEPARATOR . $image['folder'] : '');
                    $originalFsPath = $baseFs . DIRECTORY_SEPARATOR . $fsFolderSegment . DIRECTORY_SEPARATOR . $this->config['original_dir'] . DIRECTORY_SEPARATOR . $image['filename'];
                    if (is_readable($originalFsPath)) {
                        try {
                            $raw = @exif_read_data($originalFsPath, null, true, false);
                            if ($raw && is_array($raw)) {
                                $filtered = $this->filterExif($raw);
                                if ($filtered) {
                                    // Remove GPS keys for shared view
                                    foreach (array_keys($filtered) as $k) {
                                        if (stripos($k, 'gps') !== false) unset($filtered[$k]);
                                    }
                                    $image['exif'] = $filtered;
                                }
                            }
                        } catch (Exception $ex) {
                            // ignore
                        }
                    }
                }
            }

            $this->response([
                'success' => true,
                'data' => $image
            ]);
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * POST /api/images/upload
     * Upload new image
     */
    public function upload() {
        // Upload debug logging can be enabled by either passing __debug=1 on the query string
        // or by creating a file at logs/enable_upload_debug. When enabled, a log file
        // logs/upload_debug.log will collect granular diagnostic entries.
        $projectRoot = dirname(dirname(__DIR__));
        $debugFlagFile = $projectRoot . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'enable_upload_debug';
        $debugEnabled = (isset($_GET['__debug']) && $_GET['__debug'] === '1') || file_exists($debugFlagFile);
        $uploadLogPath = $projectRoot . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'upload_debug.log';
        $logDebug = function($message) use ($debugEnabled, $uploadLogPath) {
            if (!$debugEnabled) return;
            $line = '[' . date('c') . '] ' . $message . PHP_EOL;
            @file_put_contents($uploadLogPath, $line, FILE_APPEND);
        };
        $logDebug('--- BEGIN UPLOAD REQUEST ---');
        $logDebug('Request method: ' . ($_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN'));
        $logDebug('Incoming $_FILES keys: ' . implode(', ', array_keys($_FILES ?: [])));
        $logDebug('Incoming $_POST keys: ' . implode(', ', array_keys($_POST ?: [])));

        try {
            if (!isset($_SESSION['user_id'])) {
                $this->error('Authentication required to upload.', 401);
                return;
            }
            $userId = $_SESSION['user_id'];
            $username = $_SESSION['username'];

            $logDebug("User authenticated: username='{$username}', user_id='{$userId}'.");

            // Debug: Log what we received
            if (empty($_FILES)) {
                $logDebug('$_FILES is empty. Raw content-type: ' . ($_SERVER['CONTENT_TYPE'] ?? 'none'));
                $this->error('No files received. Ensure form has enctype="multipart/form-data".', 400);
                return;
            }

            if (!isset($_FILES['image'])) {
                $availableKeys = implode(', ', array_keys($_FILES));
                $logDebug("Expected field 'image' missing. Available: " . ($availableKeys ?: 'none'));
                $this->error("No 'image' file field found. Available fields: " . ($availableKeys ?: 'none'), 400);
                return;
            }

            // Get form data for folder
            $folder = $_POST['folder'] ?? null;
            if ($folder === null) {
                $logDebug('Folder POST field missing; defaulting to user root.');
            }
            $pathSegment = $folder && $folder !== 'default' ? $username . DIRECTORY_SEPARATOR . $folder : $username;
            
            $logDebug("Determined path segment for upload: '{$pathSegment}'.");

            $uploadResult = $this->uploadHandler->processUpload($_FILES['image'], $pathSegment);
            $logDebug("ImageUploadHandler->processUpload result: " . print_r($uploadResult, true));

            if (!$uploadResult['success']) {
                $logDebug("Upload handler failed. Errors: " . implode(', ', $uploadResult['errors']));
                $this->error(implode(', ', $uploadResult['errors']), 400);
                return;
            }

            // Get form data and sanitize
            $title = htmlspecialchars(trim($_POST['title'] ?? ''), ENT_QUOTES, 'UTF-8');
            $description = htmlspecialchars(trim($_POST['description'] ?? ''), ENT_QUOTES, 'UTF-8');
            $tags = htmlspecialchars(trim($_POST['tags'] ?? ''), ENT_QUOTES, 'UTF-8');
            
            // Validate lengths
            if (strlen($title) > 255) {
                $this->error('Title is too long (max 255 characters)', 400);
                return;
            }
            if (strlen($description) > 1000) {
                $this->error('Description is too long (max 1000 characters)', 400);
                return;
            }

            $logDebug("Attempting to save image metadata to database.");

            // Save to database
            $imageId = $this->imageModel->create([
                'user_id' => $userId,
                'title' => $title,
                'description' => $description,
                'filename' => $uploadResult['filename'],
                'original_name' => $uploadResult['original_name'],
                'mime_type' => $uploadResult['mime_type'],
                'file_size' => $uploadResult['file_size'],
                'width' => $uploadResult['width'],
                'height' => $uploadResult['height'],
                'folder' => $folder,
                'tags' => $tags,
                'file_type' => $uploadResult['file_type'] ?? 'image'
            ]);

            $logDebug("Database record created with ID: {$imageId}.");

            // After successful upload and DB entry, copy the original file to a 'pristine' directory for backup.
            // Use centralized helper to find the uploads base path when available
            if (class_exists('Path')) {
                $baseUploadPath = Path::uploadsBaseFs();
            } else {
                $projectRoot = dirname(dirname(__DIR__));
                $baseUploadPath = $projectRoot . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . ltrim($this->config['upload_dir'], '/');
            }
            $fullUploadPath = $baseUploadPath . DIRECTORY_SEPARATOR . $pathSegment;

            $logDebug("Base path for file operations: '{$fullUploadPath}'.");

            $originalDir = $fullUploadPath . DIRECTORY_SEPARATOR . $this->config['original_dir'];
            $pristineDir = $fullUploadPath . DIRECTORY_SEPARATOR . 'pristine';

            // Create the pristine directory if it doesn't exist (safe create)
            if (!is_dir($pristineDir)) {
                if (!mkdir($pristineDir, 0775, true)) {
                    // Non-fatal: proceed but log in debug builds. Upload will still succeed.
                }
            }

            $sourcePath = $originalDir . DIRECTORY_SEPARATOR . $uploadResult['filename'];
            $destinationPath = $pristineDir . DIRECTORY_SEPARATOR . $uploadResult['filename'];
            
            $logDebug("Attempting to create pristine backup: FROM '{$sourcePath}' TO '{$destinationPath}'.");

            // --- ROBUSTNESS CHECK ---
            // Verify the source file actually exists before attempting to copy.
            // This is the most likely point of failure if the upload handler MOVED the file instead of copying.
            if (!file_exists($sourcePath)) {
                $logDebug("FATAL: Source file for pristine backup does not exist at '{$sourcePath}'. The ImageUploadHandler may have moved the file instead of copying it, or there was a file system error.");
                $this->error('Failed to create image backup: source file not found.', 500);
                return; // Stop execution
            }

            if (!copy($sourcePath, $destinationPath)) {
                $logDebug("FATAL: The 'copy' command failed. Check file system permissions for the 'pristine' directory.");
                // Even though copy failed, we'll continue (revert may not be available but upload succeeds)
                // In production, you may want to fail the upload if pristine backup is critical
            } else {
                $logDebug("Pristine backup copy operation finished successfully.");
            }

            $image = $this->imageModel->getById($imageId);
            $base = $this->getBaseUrl();
            $uploadDir = rtrim($this->config['upload_dir'], '/');
            // Use the same path segment logic for the response URL
            // URL-encode the folder part to handle spaces and special characters.

            $logDebug("Generating response URLs.");
            $encodedFolder = $folder ? rawurlencode($folder) : null;
            $urlPathSegment = $username . ($encodedFolder && $encodedFolder !== 'default' ? '/' . $encodedFolder : '');
            
            // For videos, use .jpg for thumbnail; for images, use original filename
            $thumbFilename = $image['filename'];
            if ($image['file_type'] === 'video') {
                $thumbFilename = pathinfo($image['filename'], PATHINFO_FILENAME) . '.jpg';
            }
            
            $image['thumbnail_url'] = $base . $uploadDir . '/' . $urlPathSegment . '/' . $this->config['thumb_dir'] . '/' . $thumbFilename;
            $image['original_url'] = $base . $uploadDir . '/' . $urlPathSegment . '/' . $this->config['original_dir'] . '/' . $image['filename'];

            $this->response([
                'success' => true,
                'message' => 'Image uploaded successfully',
                'data' => $image,
                'debug' => $debugEnabled ? ['log_file' => 'logs/upload_debug.log'] : null
            ], 201);
        } catch (Exception $e) {            
            $logDebug("An exception occurred: " . $e->getMessage());
            $logDebug("Trace: " . $e->getTraceAsString());
            $this->error($e->getMessage());
        }
    }

    /**
     * PUT /api/images/{id}
     * Update image metadata
     */
    public function update($id) {
        try {
            $image = $this->imageModel->getById($id);
            if (!$image) {
                $this->error('Image not found', 404);
                return;
            }

            // Security Check: Ensure the user owns this image
            if ($image['user_id'] !== ($_SESSION['user_id'] ?? null)) {
                $this->error('Forbidden', 403);
                return;
            }

            $data = json_decode(file_get_contents('php://input'), true);
            
            // Sanitize inputs
            $title = isset($data['title']) ? htmlspecialchars(trim($data['title']), ENT_QUOTES, 'UTF-8') : $image['title'];
            $description = isset($data['description']) ? htmlspecialchars(trim($data['description']), ENT_QUOTES, 'UTF-8') : $image['description'];
            $tags = isset($data['tags']) ? htmlspecialchars(trim($data['tags']), ENT_QUOTES, 'UTF-8') : $image['tags'];
            $shared = isset($data['shared']) ? (bool)$data['shared'] : $image['shared'];
            $folder = isset($data['folder']) ? preg_replace('/[^a-zA-Z0-9_\s-]/', '', $data['folder']) : $image['folder'];
            
            // Validate lengths
            if (strlen($title) > 255 || strlen($description) > 1000) {
                $this->error('Input too long', 400);
                return;
            }

            $this->imageModel->update($id, [
                'title' => $title,
                'description' => $description,
                'tags' => $tags,
                'shared' => $shared,
                'folder' => $folder
            ]);

            $updatedImage = $this->imageModel->getById($id);
            $base = $this->getBaseUrl();
            $uploadDir = rtrim($this->config['upload_dir'], '/');
            
            $userModel = new User();
            $user = $userModel->findById($updatedImage['user_id']);
            $username = $user ? $user['username'] : null;
            $encodedFolder = ($updatedImage['folder'] && $updatedImage['folder'] !== 'default') ? rawurlencode($updatedImage['folder']) : null;
            $urlPathSegment = $username . ($encodedFolder ? '/' . $encodedFolder : '');
            
            // For videos, use .jpg for thumbnail; for images, use original filename
            $thumbFilename = $updatedImage['filename'];
            if ($updatedImage['file_type'] === 'video') {
                $thumbFilename = pathinfo($updatedImage['filename'], PATHINFO_FILENAME) . '.jpg';
            }
            
            $updatedImage['thumbnail_url'] = $base . $uploadDir . '/' . $urlPathSegment . '/' . $this->config['thumb_dir'] . '/' . $thumbFilename;
            $updatedImage['original_url'] = $base . $uploadDir . '/' . $urlPathSegment . '/' . $this->config['original_dir'] . '/' . $updatedImage['filename'];

            $this->response([
                'success' => true,
                'message' => 'Image updated successfully',
                'data' => $updatedImage,
                'share_token' => $updatedImage['share_token'] ?? null
            ]);
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * DELETE /api/images/{id}
     * Delete image
     */
    public function delete($id) {
        try {
            $image = $this->imageModel->getById($id);
            if (!$image) {
                $this->error('Image not found', 404);
                return;
            }

            // Security Check: Ensure user is logged in and owns this image
            if (!isset($_SESSION['user_id'])) {
                $this->error('Authentication required', 401);
                return;
            }

            if ($image['user_id'] !== $_SESSION['user_id']) {
                $this->error('Forbidden - you can only delete your own images', 403);
                return;
            }

            $userModel = new User();
            $user = $userModel->findById($image['user_id']);
            if ($user) {
                $username = $user['username'];
                $pathSegment = ($image['folder'] && $image['folder'] !== 'default') ? $username . DIRECTORY_SEPARATOR . $image['folder'] : $username;
                $this->uploadHandler->deleteImage($image['filename'], $pathSegment);
            }
            $this->imageModel->delete($id);

            $this->response([
                'success' => true,
                'message' => 'Image deleted successfully'
            ]);
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * POST /api/images/{id}/manipulate
     * Apply image manipulation (resize, crop, etc.)
     */
    public function manipulate($id) {
        try {
            $image = $this->imageModel->getById($id);
            if (!$image) {
                $this->error('Image not found', 404);
                return;
            }

            // Security Check: Ensure user is logged in and owns this image
            if (!isset($_SESSION['user_id'])) {
                $this->error('Authentication required', 401);
                return;
            }

            if ($image['user_id'] !== $_SESSION['user_id']) {
                $this->error('Forbidden - you can only manipulate your own images', 403);
                return;
            }

            $data = json_decode(file_get_contents('php://input'), true);
            $operation = $data['operation'] ?? null;

            if (!$operation) {
                $this->error('Operation not specified', 400);
                return;
            }

            $userModel = new User();
            $user = $userModel->findById($image['user_id']);
            $username = $user ? $user['username'] : null;
            $pathSegment = ($image['folder'] && $image['folder'] !== 'default') ? $username . DIRECTORY_SEPARATOR . $image['folder'] : $username;

            if (class_exists('Path')) {
                $uploadDir = Path::uploadsBaseFs();
            } else {
                $projectRoot = dirname(dirname(__DIR__));
                $uploadDir = $projectRoot . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . ltrim($this->config['upload_dir'], '/');
            }
            $originalDir = $uploadDir . DIRECTORY_SEPARATOR . $pathSegment . DIRECTORY_SEPARATOR . $this->config['original_dir'];
            $imagePath = $originalDir . DIRECTORY_SEPARATOR . $image['filename'];

            if (!file_exists($imagePath)) {
                $this->error('Image file not found', 404);
                return;
            }

            $manipulator = new ImageManipulator($imagePath, $this->config['image']['default_quality']);

            // Apply operations based on request
            switch ($operation) {
                case 'resize':
                    $manipulator->resize(
                        $data['width'] ?? 800,
                        $data['height'] ?? 600,
                        $data['maintain_aspect'] ?? true
                    );
                    break;

                case 'crop':
                    $manipulator->crop(
                        $data['width'] ?? 200,
                        $data['height'] ?? 200,
                        $data['x'] ?? null,
                        $data['y'] ?? null
                    );
                    break;

                case 'thumbnail':
                    $manipulator->thumbnail(
                        $data['width'] ?? 200,
                        $data['height'] ?? 200
                    );
                    break;

                case 'rotate':
                    $manipulator->rotate($data['degrees'] ?? 90);
                    break;

                case 'flip_horizontal':
                    $manipulator->flipHorizontal();
                    break;

                case 'flip_vertical':
                    $manipulator->flipVertical();
                    break;

                case 'grayscale':
                    $manipulator->grayscale();
                    break;

                case 'brightness':
                    $manipulator->brightness($data['level'] ?? 0);
                    break;

                case 'contrast':
                    $manipulator->contrast($data['level'] ?? 0);
                    break;

                case 'blur':
                    $manipulator->blur($data['radius'] ?? 2);
                    break;

                case 'sharpen':
                    $manipulator->sharpen();
                    break;

                case 'sepia':
                    $manipulator->sepia($data['intensity'] ?? 80);
                    break;

                case 'vignette':
                    $manipulator->vignette($data['strength'] ?? 50);
                    break;

                case 'color_overlay':
                    $manipulator->colorOverlay(
                        $data['red'] ?? 255,
                        $data['green'] ?? 0,
                        $data['blue'] ?? 0,
                        $data['opacity'] ?? 30
                    );
                    break;

                default:
                    $this->error('Unknown operation: ' . $operation, 400);
                    return;
            }

            // Save manipulated image back (verify save succeeded)
            $saved = false;
            try {
                $saved = $manipulator->save($imagePath);
            } catch (Exception $e) {
                $this->error('Failed to save manipulated image: ' . $e->getMessage(), 500);
                return;
            }

            if (!$saved) {
                $this->error('Failed to save manipulated image (permission or format issue)', 500);
                return;
            }

            // Regenerate thumbnail from the modified image so gallery reflects the changes
            $thumbDir = $uploadDir . DIRECTORY_SEPARATOR . $pathSegment . DIRECTORY_SEPARATOR . $this->config['thumb_dir'];
            $thumbPath = $thumbDir . DIRECTORY_SEPARATOR . $image['filename'];
            $thumbWidth = max(1, (int)($this->config['image']['thumb_width'] ?? ($this->config['image']['thumbnail_width'] ?? 200)));
            $thumbHeight = max(1, (int)($this->config['image']['thumb_height'] ?? ($this->config['image']['thumbnail_height'] ?? 200)));

            try {
                $thumbManipulator = new ImageManipulator($imagePath, $this->config['image']['default_quality']);
                $thumbManipulator->thumbnail($thumbWidth, $thumbHeight);
                $thumbManipulator->save($thumbPath);
            } catch (Exception $thumbError) {
                // Log but don't fail - original manipulation succeeded
                error_log("Thumbnail regeneration failed after manipulation: " . $thumbError->getMessage());
            }

            // Record history
            $this->imageModel->recordHistory($id, $operation, $data);

            $this->response([
                'success' => true,
                'message' => 'Image manipulated successfully',
                'data' => $this->imageModel->getById($id)
            ]);
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * POST /api/images/{id}/revert
     * Reverts an image to its original state from backup.
     */
    public function revert($id) {
        try {
            $image = $this->imageModel->getById($id);
            if (!$image) {
                $this->error('Image not found', 404);
                return;
            }

            // Security check: user can only revert their own images
            if ((int)$image['user_id'] !== (int)($_SESSION['user_id'] ?? -1)) {
                $this->error('Forbidden', 403);
                return;
            }

            $userModel = new User();
            $user = $userModel->findById($image['user_id']);
            $username = $user ? $user['username'] : null;
            $pathSegment = ($image['folder'] && $image['folder'] !== 'default') ? $username . DIRECTORY_SEPARATOR . $image['folder'] : $username;

            if (class_exists('Path')) {
                $uploadBasePath = Path::uploadsBaseFs();
            } else {
                $projectRoot = dirname(dirname(__DIR__));
                $uploadBasePath = $projectRoot . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . ltrim($this->config['upload_dir'], '/');
            }

            $pristinePath = $uploadBasePath . DIRECTORY_SEPARATOR . $pathSegment . DIRECTORY_SEPARATOR . 'pristine' . DIRECTORY_SEPARATOR . $image['filename'];
            $originalPath = $uploadBasePath . DIRECTORY_SEPARATOR . $pathSegment . DIRECTORY_SEPARATOR . $this->config['original_dir'] . DIRECTORY_SEPARATOR . $image['filename'];
            
            if (!file_exists($pristinePath)) {
                $this->error('Original image backup not found. This image cannot be reverted.', 404);
                return;
            }

            // Copy from pristine backup to overwrite the working original
            if (!copy($pristinePath, $originalPath)) {
                $this->error('Failed to copy backup file. Check server permissions.', 500);
                return;
            }

            // Regenerate the thumbnail from the now-reverted original image
            // to ensure consistency across the UI.
            $thumbDir = $uploadBasePath . DIRECTORY_SEPARATOR . $pathSegment . DIRECTORY_SEPARATOR . $this->config['thumb_dir'];
            $thumbPath = $thumbDir . DIRECTORY_SEPARATOR . $image['filename'];
            
            // Ensure thumbnail dimensions are valid, provide defaults if config is missing or invalid.
            // Use max(1, ...) to guarantee dimensions are always at least 1.
            $thumbWidth = max(1, (int)($this->config['image']['thumb_width'] ?? ($this->config['image']['thumbnail_width'] ?? 200)));
            $thumbHeight = max(1, (int)($this->config['image']['thumb_height'] ?? ($this->config['image']['thumbnail_height'] ?? 200)));

            try {
                $thumbnailer = new ImageManipulator($originalPath);
                $thumbnailer->thumbnail($thumbWidth, $thumbHeight);
                $thumbnailer->save($thumbPath);
            } catch (Exception $thumbError) {
                // Log thumbnail error but don't fail the revert (original is restored)
                // The thumbnail can be regenerated later if needed
            }

            // Record history
            $this->imageModel->recordHistory($id, 'revert', ['reverted_at' => date('Y-m-d H:i:s')]);

            $this->response([
                'success' => true,
                'message' => 'Image reverted to original successfully',
                'data' => $this->imageModel->getById($id)
            ]);

        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }


    /**
     * GET /api/images/{id}/download
     * Download image
     */
    public function download($id) {
        try {
            $image = $this->imageModel->getById($id);
            if (!$image) {
                $this->error('Image not found', 404);
                return;
            }

            $userModel = new User();
            $user = $userModel->findById($image['user_id']);
            $username = $user ? $user['username'] : null;
            $pathSegment = ($image['folder'] && $image['folder'] !== 'default') ? $username . DIRECTORY_SEPARATOR . $image['folder'] : $username;

            if (class_exists('Path')) {
                $uploadDir = Path::uploadsBaseFs();
            } else {
                $projectRoot = dirname(dirname(__DIR__));
                $uploadDir = $projectRoot . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . ltrim($this->config['upload_dir'], '/');
            }
            $originalDir = $uploadDir . DIRECTORY_SEPARATOR . $pathSegment . DIRECTORY_SEPARATOR . $this->config['original_dir'];
            $imagePath = $originalDir . DIRECTORY_SEPARATOR . $image['filename'];

            if (!file_exists($imagePath)) {
                $this->error('Image file not found', 404);
                return;
            }

            header('Content-Description: File Transfer');
            header('Content-Type: ' . $image['mime_type']);
            header('Content-Disposition: attachment; filename="' . $image['original_name'] . '"');
            header('Content-Length: ' . filesize($imagePath));

            readfile($imagePath);
            exit;
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
     * Reduce raw EXIF array to a concise associative array of human-readable fields.
     */
    private function filterExif(array $raw) {
        $out = [];
        $get = function($section, $key) use ($raw) {
            return isset($raw[$section][$key]) ? $raw[$section][$key] : null;
        };
        $assign = function($label, $value) use (&$out) {
            if ($value !== null && $value !== '') $out[$label] = $value;
        };
        $assign('CameraMake', $get('IFD0','Make'));
        $assign('CameraModel', $get('IFD0','Model'));
        $assign('Software', $get('IFD0','Software'));
        $assign('DateTimeOriginal', $get('EXIF','DateTimeOriginal') ?: $get('IFD0','DateTime'));
        $assign('ExposureTime', $get('EXIF','ExposureTime'));
        $assign('FNumber', $get('EXIF','FNumber'));
        $assign('ISOSpeedRatings', $get('EXIF','ISOSpeedRatings'));
        $assign('FocalLength', $get('EXIF','FocalLength'));
        $assign('Orientation', $get('IFD0','Orientation'));
        $assign('Flash', $get('EXIF','Flash'));
        // GPS values (optional, may be removed for shared):
        if (isset($raw['GPS'])) {
            $assign('GPSLatitude', isset($raw['GPS']['GPSLatitude']) ? implode(',', $raw['GPS']['GPSLatitude']) : null);
            $assign('GPSLongitude', isset($raw['GPS']['GPSLongitude']) ? implode(',', $raw['GPS']['GPSLongitude']) : null);
        }
        return $out ?: null;
    }
    
}
