<?php
/**
 * OAuth Service
 * Handles OAuth 2.0 authentication flow for multiple providers
 * Cross-Platform Compatible
 */

class OAuthService {
    private $config;
    private $provider;
    
    public function __construct($provider = null) {
        // Load OAuth configuration
        $configPath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'oauth.php';
        
        if (!file_exists($configPath)) {
            throw new Exception('OAuth configuration file not found. Copy oauth.php.example to oauth.php and configure.');
        }
        
        $this->config = require $configPath;
        $this->provider = $provider;
    }
    
    /**
     * Get authorization URL for OAuth provider
     */
    public function getAuthorizationUrl($provider) {
        if (!isset($this->config[$provider])) {
            throw new Exception("Unsupported OAuth provider: {$provider}");
        }
        
        $providerConfig = $this->config[$provider];
        
        if (!$providerConfig['enabled']) {
            throw new Exception("OAuth provider {$provider} is not enabled");
        }
        
        // Generate and store state for CSRF protection
        $state = bin2hex(random_bytes(16));
        $_SESSION[$this->config['session']['state_key']] = $state;
        $_SESSION['oauth_provider'] = $provider;
        
        // Build authorization URL
        $params = [
            'client_id' => $providerConfig['client_id'],
            'redirect_uri' => $providerConfig['redirect_uri'],
            'response_type' => 'code',
            'state' => $state,
        ];
        
        // Add scopes
        if ($provider === 'google' || $provider === 'microsoft') {
            $params['scope'] = implode(' ', $providerConfig['scopes']);
        } elseif ($provider === 'facebook') {
            $params['scope'] = implode(',', $providerConfig['scopes']);
        } elseif ($provider === 'github') {
            $params['scope'] = implode(' ', $providerConfig['scopes']);
        }
        
        return $providerConfig['auth_url'] . '?' . http_build_query($params);
    }
    
    /**
     * Exchange authorization code for access token
     */
    public function getAccessToken($provider, $code) {
        $providerConfig = $this->config[$provider];
        
        $params = [
            'client_id' => $providerConfig['client_id'],
            'client_secret' => $providerConfig['client_secret'],
            'code' => $code,
            'redirect_uri' => $providerConfig['redirect_uri'],
            'grant_type' => 'authorization_code',
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $providerConfig['token_url']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Content-Type: application/x-www-form-urlencoded',
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception("Failed to get access token: HTTP {$httpCode}");
        }
        
        $data = json_decode($response, true);
        
        if (!isset($data['access_token'])) {
            throw new Exception('Access token not found in response');
        }
        
        return $data;
    }
    
    /**
     * Get user information from OAuth provider
     */
    public function getUserInfo($provider, $accessToken) {
        $providerConfig = $this->config[$provider];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $providerConfig['user_info_url']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        // Set authorization header
        if ($provider === 'facebook') {
            curl_setopt($ch, CURLOPT_URL, $providerConfig['user_info_url'] . '?fields=id,name,email,picture&access_token=' . $accessToken);
        } else {
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $accessToken,
                'Accept: application/json',
            ]);
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception("Failed to get user info: HTTP {$httpCode}");
        }
        
        $userData = json_decode($response, true);
        
        // Normalize user data across providers
        return $this->normalizeUserData($provider, $userData, $accessToken);
    }
    
    /**
     * Normalize user data from different providers to common format
     */
    private function normalizeUserData($provider, $data, $accessToken) {
        $normalized = [
            'provider' => $provider,
            'oauth_id' => null,
            'email' => null,
            'name' => null,
            'username' => null,
            'avatar_url' => null,
            'oauth_token' => $accessToken,
        ];
        
        switch ($provider) {
            case 'google':
                $normalized['oauth_id'] = $data['id'] ?? null;
                $normalized['email'] = $data['email'] ?? null;
                $normalized['name'] = $data['name'] ?? null;
                $normalized['username'] = $data['email'] ? explode('@', $data['email'])[0] : null;
                $normalized['avatar_url'] = $data['picture'] ?? null;
                break;
                
            case 'facebook':
                $normalized['oauth_id'] = $data['id'] ?? null;
                $normalized['email'] = $data['email'] ?? null;
                $normalized['name'] = $data['name'] ?? null;
                $normalized['username'] = $data['email'] ? explode('@', $data['email'])[0] : null;
                $normalized['avatar_url'] = $data['picture']['data']['url'] ?? null;
                break;
                
            case 'github':
                $normalized['oauth_id'] = $data['id'] ?? null;
                $normalized['email'] = $data['email'] ?? $this->getGitHubEmail($accessToken);
                $normalized['name'] = $data['name'] ?? $data['login'];
                $normalized['username'] = $data['login'] ?? null;
                $normalized['avatar_url'] = $data['avatar_url'] ?? null;
                break;
                
            case 'microsoft':
                $normalized['oauth_id'] = $data['id'] ?? null;
                $normalized['email'] = $data['mail'] ?? $data['userPrincipalName'] ?? null;
                $normalized['name'] = $data['displayName'] ?? null;
                $normalized['username'] = $data['mail'] ? explode('@', $data['mail'])[0] : null;
                $normalized['avatar_url'] = null; // Microsoft Graph doesn't provide avatar in basic call
                break;
        }
        
        return $normalized;
    }
    
    /**
     * Get GitHub user's primary email (GitHub requires separate API call)
     */
    private function getGitHubEmail($accessToken) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.github.com/user/emails');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
            'Accept: application/json',
            'User-Agent: iManage',
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $emails = json_decode($response, true);
        
        // Find primary verified email
        foreach ($emails as $email) {
            if ($email['primary'] && $email['verified']) {
                return $email['email'];
            }
        }
        
        // Fallback to first email
        return $emails[0]['email'] ?? null;
    }
    
    /**
     * Verify state parameter for CSRF protection
     */
    public function verifyState($state) {
        $sessionState = $_SESSION[$this->config['session']['state_key']] ?? null;
        
        if (!$sessionState || $sessionState !== $state) {
            return false;
        }
        
        // Clear state after verification
        unset($_SESSION[$this->config['session']['state_key']]);
        
        return true;
    }
    
    /**
     * Get list of enabled OAuth providers
     */
    public function getEnabledProviders() {
        $enabled = [];
        
        foreach (['google', 'facebook', 'github', 'microsoft'] as $provider) {
            if (isset($this->config[$provider]) && $this->config[$provider]['enabled']) {
                $enabled[] = $provider;
            }
        }
        
        return $enabled;
    }
}
