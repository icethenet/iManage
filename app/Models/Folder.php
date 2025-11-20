<?php
/**
 * Folder Model
 * Handles database operations for folders
 */

class Folder {
    private $db;
    private $table = 'folders';

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Get all folders
     */
    public function getByUserId($userId) {
        $query = "SELECT * FROM {$this->table} WHERE user_id = ? ORDER BY name ASC";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /**
     * Get folder by name
     */
    public function getByNameForUser($name, $userId) {
        $query = "SELECT * FROM {$this->table} WHERE name = ? AND user_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$name, $userId]);
        return $stmt->fetch();
    }

    /**
     * Create new folder
     */
    public function create($name, $userId, $description = '', $parentId = null) {
        // Check if a folder with the same name already exists for this user under the same parent.
        $existing = $this->getByNameForUser($name, $userId);
        if ($existing && ($existing['parent_id'] == $parentId)) {
            // A folder with this name already exists in this location for this user.
            return false;
        }

        $insertQuery = "INSERT INTO {$this->table} (name, user_id, description, parent_id) VALUES (?, ?, ?, ?)";
        $stmt = $this->db->prepare($insertQuery);
        $stmt->execute([$name, $userId, $description, $parentId]);

        $lastId = $this->db->lastInsertId();
        $selectQuery = "SELECT * FROM {$this->table} WHERE id = ?";
        $stmt = $this->db->prepare($selectQuery);
        $stmt->execute([$lastId]);
        return $stmt->fetch();
    }

    /**
     * Update folder
     */
    public function update($name, $userId, $data) {
        $query = "UPDATE {$this->table} SET description = ? WHERE name = ? AND user_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$data['description'] ?? '', $name, $userId]);
        return true;
    }

    /**
     * Delete folder
     */
    public function delete($name, $userId) {
        if ($name === 'default') {
            return false; // Cannot delete default folder
        }

        $query = "DELETE FROM {$this->table} WHERE name = ? AND user_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$name, $userId]);
        return true;
    }

    /**
     * Count folders by user
     */
    public function countByUser($userId) {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM {$this->table} WHERE user_id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['count'];
    }
}

