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

    /**
     * Find a user by email
     */
    public function findByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Find a user by OAuth provider and ID
     */
    public function findByOAuth($provider, $oauthId) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE oauth_provider = ? AND oauth_id = ?");
        $stmt->execute([$provider, $oauthId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Create a new user from OAuth
     */
    public function createOAuthUser($username, $email, $provider, $oauthId, $oauthToken, $refreshToken, $avatarUrl) {
        $stmt = $this->db->prepare(
            "INSERT INTO users (username, email, oauth_provider, oauth_id, oauth_token, oauth_refresh_token, avatar_url, created_at) 
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW())"
        );
        $stmt->execute([$username, $email, $provider, $oauthId, $oauthToken, $refreshToken, $avatarUrl]);

        return $this->db->lastInsertId();
    }

    /**
     * Link OAuth account to existing user
     */
    public function linkOAuthAccount($userId, $provider, $oauthId, $oauthToken, $refreshToken, $avatarUrl) {
        $stmt = $this->db->prepare(
            "UPDATE users SET oauth_provider = ?, oauth_id = ?, oauth_token = ?, oauth_refresh_token = ?, avatar_url = ? 
             WHERE id = ?"
        );
        return $stmt->execute([$provider, $oauthId, $oauthToken, $refreshToken, $avatarUrl, $userId]);
    }

    /**
     * Update OAuth tokens for existing user
     */
    public function updateOAuthTokens($userId, $oauthToken, $refreshToken, $avatarUrl) {
        $stmt = $this->db->prepare(
            "UPDATE users SET oauth_token = ?, oauth_refresh_token = ?, avatar_url = ? WHERE id = ?"
        );
        return $stmt->execute([$oauthToken, $refreshToken, $avatarUrl, $userId]);
    }

    /**
     * Update last login timestamp
     */
    public function updateLastLogin($userId) {
        $stmt = $this->db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        return $stmt->execute([$userId]);
    }
}