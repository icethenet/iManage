<?php
/**
 * OAuth Controller
 * Handles OAuth authentication requests
 */

require_once __DIR__ . '/../Utils/OAuthService.php';
require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Database.php';

class OAuthController {
    private $oauthService;
    private $userModel;
    
    public function __construct() {
        try {
            $this->oauthService = new OAuthService();
            $db = Database::getInstance()->getConnection();
            $this->userModel = new User($db);
        } catch (Exception $e) {
            $this->sendError('OAuth not configured', 500);
            exit;
        }
    }
    
    /**
     * Initiate OAuth login flow
     */
    public function login() {
        $provider = $_GET['provider'] ?? null;
        
        if (!$provider) {
            $this->sendError('Provider not specified', 400);
            return;
        }
        
        try {
            // Get authorization URL
            $authUrl = $this->oauthService->getAuthorizationUrl($provider);
            
            // Store return URL for after authentication
            $_SESSION['oauth_return_url'] = $_GET['return_url'] ?? '/';
            
            // Redirect to OAuth provider
            header('Location: ' . $authUrl);
            exit;
            
        } catch (Exception $e) {
            $this->sendError($e->getMessage(), 500);
        }
    }
    
    /**
     * Handle OAuth callback
     */
    public function callback() {
        $provider = $_GET['provider'] ?? $_SESSION['oauth_provider'] ?? null;
        $code = $_GET['code'] ?? null;
        $state = $_GET['state'] ?? null;
        $error = $_GET['error'] ?? null;
        
        // Check for OAuth errors
        if ($error) {
            $errorDescription = $_GET['error_description'] ?? 'OAuth authentication failed';
            $this->redirectWithError($errorDescription);
            return;
        }
        
        // Validate required parameters
        if (!$provider || !$code || !$state) {
            $this->redirectWithError('Invalid OAuth callback parameters');
            return;
        }
        
        // Verify state for CSRF protection
        if (!$this->oauthService->verifyState($state)) {
            $this->redirectWithError('Invalid state parameter. Possible CSRF attack.');
            return;
        }
        
        try {
            // Exchange code for access token
            $tokenData = $this->oauthService->getAccessToken($provider, $code);
            $accessToken = $tokenData['access_token'];
            $refreshToken = $tokenData['refresh_token'] ?? null;
            
            // Get user info from provider
            $oauthUser = $this->oauthService->getUserInfo($provider, $accessToken);
            
            // Find or create user
            $user = $this->findOrCreateUser($oauthUser, $refreshToken);
            
            if (!$user) {
                $this->redirectWithError('Failed to create or find user account');
                return;
            }
            
            // Create session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['login_time'] = time();
            
            // Update last login
            $this->userModel->updateLastLogin($user['id']);
            
            // Get return URL and redirect
            $returnUrl = $_SESSION['oauth_return_url'] ?? '/';
            unset($_SESSION['oauth_return_url']);
            unset($_SESSION['oauth_provider']);
            
            header('Location: ' . $returnUrl);
            exit;
            
        } catch (Exception $e) {
            $this->redirectWithError('OAuth error: ' . $e->getMessage());
        }
    }
    
    /**
     * Find existing user or create new one from OAuth data
     */
    private function findOrCreateUser($oauthUser, $refreshToken) {
        // Try to find user by OAuth provider and ID
        $user = $this->userModel->findByOAuth($oauthUser['provider'], $oauthUser['oauth_id']);
        
        if ($user) {
            // Update OAuth tokens
            $this->userModel->updateOAuthTokens(
                $user['id'],
                $oauthUser['oauth_token'],
                $refreshToken,
                $oauthUser['avatar_url']
            );
            return $user;
        }
        
        // Try to find by email if provided
        if ($oauthUser['email']) {
            $user = $this->userModel->findByEmail($oauthUser['email']);
            
            if ($user) {
                // Link OAuth account to existing user
                $this->userModel->linkOAuthAccount(
                    $user['id'],
                    $oauthUser['provider'],
                    $oauthUser['oauth_id'],
                    $oauthUser['oauth_token'],
                    $refreshToken,
                    $oauthUser['avatar_url']
                );
                return $user;
            }
        }
        
        // Create new user
        $username = $this->generateUniqueUsername($oauthUser['username'] ?? $oauthUser['name']);
        
        $userId = $this->userModel->createOAuthUser(
            $username,
            $oauthUser['email'],
            $oauthUser['provider'],
            $oauthUser['oauth_id'],
            $oauthUser['oauth_token'],
            $refreshToken,
            $oauthUser['avatar_url']
        );
        
        if ($userId) {
            return $this->userModel->findById($userId);
        }
        
        return null;
    }
    
    /**
     * Generate unique username from OAuth data
     */
    private function generateUniqueUsername($baseUsername) {
        // Clean username
        $username = preg_replace('/[^a-zA-Z0-9_]/', '', $baseUsername);
        $username = substr($username, 0, 50);
        
        // Check if username exists
        $originalUsername = $username;
        $counter = 1;
        
        while ($this->userModel->findByUsername($username)) {
            $username = $originalUsername . $counter;
            $counter++;
        }
        
        return $username;
    }
    
    /**
     * Redirect with error message
     */
    private function redirectWithError($message) {
        $_SESSION['oauth_error'] = $message;
        $returnUrl = $_SESSION['oauth_return_url'] ?? '/';
        unset($_SESSION['oauth_return_url']);
        unset($_SESSION['oauth_provider']);
        header('Location: ' . $returnUrl);
        exit;
    }
    
    /**
     * Send JSON error response
     */
    private function sendError($message, $code = 400) {
        http_response_code($code);
        echo json_encode(['error' => $message]);
    }
}
