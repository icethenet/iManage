<?php
/**
 * Image Model
 * Handles database operations for images
 */

class Image {
    private $db;
    private $table = 'images';

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Get all images with pagination
     */
    public function getAll($page = 1, $limit = 12, $folder = null, $userId = null) {
        $offset = ($page - 1) * $limit;
        $query = "SELECT * FROM {$this->table} WHERE 1=1";
        $params = [];

        if (!is_null($userId)) {
            // When user is logged in, show their own images OR shared images from others
            $query .= " AND (user_id = ? OR shared = 1)";
            $params[] = $userId;
        } else {
            // For public view, only show shared images
            $query .= " AND shared = 1";
        }

        if ($folder) {
            $query .= " AND folder = ?";
            $params[] = $folder;
        }

        $query .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Get image by ID
     */
    public function getById($id) {
        $query = "SELECT * FROM {$this->table} WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Get image by share token
     */
    public function getByShareToken($token) {
        $query = "SELECT * FROM {$this->table} WHERE share_token = ? AND shared = 1";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$token]);
        return $stmt->fetch();
    }

    /**
     * Generate and set share token for an image
     */
    public function generateShareToken($id) {
        $token = bin2hex(random_bytes(16));
        $query = "UPDATE {$this->table} SET share_token = ? WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$token, $id]);
        return $token;
    }

    /**
     * Get images by folder
     */
    public function getByFolder($folder, $page = 1, $limit = 12, $userId = null) {
        return $this->getAll($page, $limit, $folder, $userId);
    }

    /**
     * Search images
     */
    public function search($query, $page = 1, $limit = 12, $userId = null) {
        $offset = ($page - 1) * $limit;
        $params = [];
        $searchQuery = "SELECT * FROM {$this->table} 
                       WHERE MATCH(title, description) AGAINST(? IN BOOLEAN MODE)";
        $params[] = $query;
        
        if (!is_null($userId)) {
            $searchQuery .= " AND user_id = ?";
            $params[] = $userId;
        } else {
            // For public view, only search shared images
            $searchQuery .= " AND shared = 1";
        }

        $searchQuery .= " ORDER BY created_at DESC 
                       LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->db->prepare($searchQuery);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Get total count
     */
    public function getCount($folder = null, $userId = null) {
        $query = "SELECT COUNT(*) as count FROM {$this->table} WHERE 1=1";
        $params = [];

        if (!is_null($userId)) {
            // When user is logged in, count their own images OR shared images from others
            $query .= " AND (user_id = ? OR shared = 1)";
            $params[] = $userId;
        } else {
            $query .= " AND shared = 1";
        }

        if ($folder) {
            $query .= " AND folder = ?";
            $params[] = $folder;
        }

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result['count'];
    }

    /**
     * Create new image record
     */
    public function create($data) {
        $query = "INSERT INTO {$this->table} 
                  (user_id, title, description, filename, original_name, mime_type, file_size, width, height, folder, tags, file_type)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $data['user_id'],
            $data['title'] ?? pathinfo($data['original_name'], PATHINFO_FILENAME),
            $data['description'] ?? '',
            $data['filename'],
            $data['original_name'],
            $data['mime_type'],
            $data['file_size'],
            $data['width'] ?? null,
            $data['height'] ?? null,
            $data['folder'] ?? 'default',
            $data['tags'] ?? '',
            $data['file_type'] ?? 'image'
        ]);

        return $this->db->lastInsertId();
    }

    /**
     * Update image record
     */
    public function update($id, $data) {
        $fields = [];
        $params = [];
        
        foreach (['title', 'description', 'tags', 'folder'] as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $params[] = $data[$field];
            }
        }

       if (isset($data['shared'])) {
            $fields[] = "shared = ?";
            $params[] = $data['shared'] ? 1 : 0; // Ensure it's 1 or 0
            
            // Generate share token if sharing is enabled and token doesn't exist
            if ($data['shared'] && !isset($data['share_token'])) {
                $image = $this->getById($id);
                if ($image && empty($image['share_token'])) {
                    $data['share_token'] = $this->generateShareToken($id);
                }
            }
        }

        if (empty($fields)) {
            return false;
        }

        $params[] = $id;
        $query = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = ?";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return true;
    }

    /**
     * Delete image record
     */
    public function delete($id) {
        $image = $this->getById($id);
        if (!$image) {
            return false;
        }

        $query = "DELETE FROM {$this->table} WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);
        return true;
    }

    /**
     * Get image metadata
     */
    public function getMetadata($imageId) {
        $query = "SELECT key_name, value FROM image_metadata WHERE image_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$imageId]);
        return $stmt->fetchAll();
    }

    /**
     * Set image metadata
     */
    public function setMetadata($imageId, $key, $value) {
        $query = "INSERT INTO image_metadata (image_id, key_name, value) 
                  VALUES (?, ?, ?) 
                  ON DUPLICATE KEY UPDATE value = ?";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$imageId, $key, $value, $value]);
    }

    /**
     * Record image processing history
     */
    public function recordHistory($imageId, $operation, $parameters, $resultFilename = null) {
        $query = "INSERT INTO image_history (image_id, operation, parameters, result_filename)
                  VALUES (?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $imageId,
            $operation,
            json_encode($parameters),
            $resultFilename
        ]);
    }

    /**
     * Get image processing history
     */
    public function getHistory($imageId) {
        $query = "SELECT * FROM image_history WHERE image_id = ? ORDER BY created_at DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$imageId]);
        return $stmt->fetchAll();
    }

    /**
     * Count images by user
     */
    public function countByUser($userId) {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM {$this->table} WHERE user_id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['count'];
    }

    /**
     * Count shared images by user
     */
    public function countSharedByUser($userId) {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM {$this->table} WHERE user_id = ? AND shared = 1");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['count'];
    }

    /**
     * Get total storage used by user (in bytes)
     */
    public function getTotalSizeByUser($userId) {
        $stmt = $this->db->prepare("SELECT SUM(file_size) as total FROM {$this->table} WHERE user_id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['total'] ?? 0);
    }

    /**
     * Get total count of all images
     */
    public function getTotalCount() {
        $stmt = $this->db->query("SELECT COUNT(*) FROM {$this->table}");
        return $stmt->fetchColumn();
    }

    /**
     * Get total storage used by all users (in bytes)
     */
    public function getTotalStorage() {
        $stmt = $this->db->query("SELECT SUM(file_size) as total FROM {$this->table}");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['total'] ?? 0);
    }
}

