<?php

class User {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Create a new user
     */
    public function create($username, $password) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $this->db->prepare(
            "INSERT INTO users (username, password_hash, created_at) VALUES (?, ?, NOW())"
        );
        $stmt->execute([$username, $passwordHash]);

        return $this->db->lastInsertId();
    }

    /**
     * Find a user by their username
     */
    public function findByUsername($username) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Find a user by their ID
     */
    public function findById($id) {
        $stmt = $this->db->prepare("SELECT id, username, created_at FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}