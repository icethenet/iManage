<?php
/**
 * Security Helper Functions
 */

class SecurityHelper {
    
    /**
     * Generate a CSRF token
     */
    public static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['csrf_token_time'] = time();
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Validate CSRF token
     */
    public static function validateCSRFToken($token) {
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        
        // Check token age (expire after 1 hour)
        if (isset($_SESSION['csrf_token_time']) && (time() - $_SESSION['csrf_token_time']) > 3600) {
            unset($_SESSION['csrf_token']);
            unset($_SESSION['csrf_token_time']);
            return false;
        }
        
        // Compare tokens using timing-safe comparison
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Sanitize HTML output
     */
    public static function escapeHtml($string) {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Sanitize for attribute value
     */
    public static function escapeAttr($string) {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Sanitize for JavaScript
     */
    public static function escapeJs($string) {
        return json_encode($string, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }
    
    /**
     * Validate and sanitize filename
     */
    public static function sanitizeFilename($filename) {
        // Remove any path separators
        $filename = basename($filename);
        
        // Remove any characters that aren't alphanumeric, dash, underscore, or dot
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        
        // Prevent double extensions (.php.jpg becomes .php_jpg)
        $parts = explode('.', $filename);
        if (count($parts) > 2) {
            $extension = array_pop($parts);
            $filename = implode('_', $parts) . '.' . $extension;
        }
        
        return $filename;
    }
    
    /**
     * Generate secure random string
     */
    public static function generateRandomString($length = 32) {
        return bin2hex(random_bytes($length / 2));
    }
    
    /**
     * Verify password strength
     */
    public static function isPasswordStrong($password) {
        $minLength = 8;
        $hasUppercase = preg_match('/[A-Z]/', $password);
        $hasLowercase = preg_match('/[a-z]/', $password);
        $hasNumber = preg_match('/[0-9]/', $password);
        
        return strlen($password) >= $minLength && $hasUppercase && $hasLowercase && $hasNumber;
    }
    
    /**
     * Check if IP is rate limited
     */
    public static function checkRateLimit($key, $maxAttempts = 5, $timeWindow = 300) {
        if (!isset($_SESSION['rate_limits'])) {
            $_SESSION['rate_limits'] = [];
        }
        
        if (!isset($_SESSION['rate_limits'][$key])) {
            $_SESSION['rate_limits'][$key] = [];
        }
        
        $now = time();
        
        // Clean old attempts
        $_SESSION['rate_limits'][$key] = array_filter(
            $_SESSION['rate_limits'][$key],
            function($timestamp) use ($now, $timeWindow) {
                return ($now - $timestamp) < $timeWindow;
            }
        );
        
        // Check if limit exceeded
        if (count($_SESSION['rate_limits'][$key]) >= $maxAttempts) {
            return false;
        }
        
        // Record this attempt
        $_SESSION['rate_limits'][$key][] = $now;
        
        return true;
    }
    
    /**
     * Validate session integrity
     */
    public static function validateSession() {
        // Check if session has fingerprint data
        if (!isset($_SESSION['ip_address']) || !isset($_SESSION['user_agent'])) {
            return true; // Legacy sessions still allowed
        }
        
        // Verify IP hasn't changed
        $currentIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        if ($_SESSION['ip_address'] !== $currentIP) {
            return false;
        }
        
        // Verify user agent hasn't changed
        $currentUA = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        if ($_SESSION['user_agent'] !== $currentUA) {
            return false;
        }
        
        return true;
    }
}
