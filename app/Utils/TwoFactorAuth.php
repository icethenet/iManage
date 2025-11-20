<?php
/**
 * Two-Factor Authentication Utility
 * Handles TOTP and Email-based 2FA
 */

class TwoFactorAuth {
    
    /**
     * Generate a random secret for TOTP
     */
    public static function generateSecret($length = 32) {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; // Base32 characters
        $secret = '';
        for ($i = 0; $i < $length; $i++) {
            $secret .= $characters[random_int(0, strlen($characters) - 1)];
        }
        return $secret;
    }
    
    /**
     * Generate TOTP code from secret
     */
    public static function generateTOTP($secret, $timeSlice = null) {
        if ($timeSlice === null) {
            $timeSlice = floor(time() / 30);
        }
        
        $secret = self::base32Decode($secret);
        $time = pack('N*', 0, $timeSlice);
        $hash = hash_hmac('sha1', $time, $secret, true);
        $offset = ord($hash[19]) & 0xf;
        $code = (
            ((ord($hash[$offset]) & 0x7f) << 24) |
            ((ord($hash[$offset + 1]) & 0xff) << 16) |
            ((ord($hash[$offset + 2]) & 0xff) << 8) |
            (ord($hash[$offset + 3]) & 0xff)
        ) % 1000000;
        
        return str_pad($code, 6, '0', STR_PAD_LEFT);
    }
    
    /**
     * Verify TOTP code
     */
    public static function verifyTOTP($secret, $code, $window = 1) {
        $timeSlice = floor(time() / 30);
        
        // Check current time slice and adjacent ones (to account for clock drift)
        for ($i = -$window; $i <= $window; $i++) {
            if (self::generateTOTP($secret, $timeSlice + $i) === $code) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Generate QR code URL for authenticator apps
     */
    public static function getQRCodeUrl($username, $secret, $issuer = 'iManage') {
        $label = urlencode($issuer . ':' . $username);
        $params = http_build_query([
            'secret' => $secret,
            'issuer' => $issuer,
            'algorithm' => 'SHA1',
            'digits' => 6,
            'period' => 30
        ]);
        
        $otpauthUrl = "otpauth://totp/{$label}?{$params}";
        return "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($otpauthUrl);
    }
    
    /**
     * Generate backup codes
     */
    public static function generateBackupCodes($count = 8) {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $code = '';
            for ($j = 0; $j < 8; $j++) {
                $code .= random_int(0, 9);
            }
            $codes[] = substr($code, 0, 4) . '-' . substr($code, 4, 4);
        }
        return $codes;
    }
    
    /**
     * Generate and send email code
     */
    public static function sendEmailCode($email, $username) {
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Store code in session with expiration
        $_SESSION['email_2fa_code'] = $code;
        $_SESSION['email_2fa_expires'] = time() + 300; // 5 minutes
        
        // Send email
        $subject = 'Your iManage 2FA Code';
        $message = "Hello {$username},\n\n";
        $message .= "Your two-factor authentication code is: {$code}\n\n";
        $message .= "This code will expire in 5 minutes.\n\n";
        $message .= "If you didn't request this code, please ignore this email.\n";
        
        $headers = 'From: noreply@imanage.local' . "\r\n" .
                   'Reply-To: noreply@imanage.local' . "\r\n" .
                   'X-Mailer: PHP/' . phpversion();
        
        mail($email, $subject, $message, $headers);
        
        return $code; // Return for testing purposes
    }
    
    /**
     * Verify email code
     */
    public static function verifyEmailCode($code) {
        if (!isset($_SESSION['email_2fa_code']) || !isset($_SESSION['email_2fa_expires'])) {
            return false;
        }
        
        if (time() > $_SESSION['email_2fa_expires']) {
            unset($_SESSION['email_2fa_code']);
            unset($_SESSION['email_2fa_expires']);
            return false;
        }
        
        if ($_SESSION['email_2fa_code'] === $code) {
            unset($_SESSION['email_2fa_code']);
            unset($_SESSION['email_2fa_expires']);
            return true;
        }
        
        return false;
    }
    
    /**
     * Base32 decode
     */
    private static function base32Decode($secret) {
        $base32chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $base32charsFlipped = array_flip(str_split($base32chars));
        
        $paddingCharCount = substr_count($secret, '=');
        $allowedValues = [6, 4, 3, 1, 0];
        
        if (!in_array($paddingCharCount, $allowedValues)) {
            return false;
        }
        
        for ($i = 0; $i < 4; $i++) {
            if ($paddingCharCount == $allowedValues[$i] &&
                substr($secret, -($allowedValues[$i])) != str_repeat('=', $allowedValues[$i])) {
                return false;
            }
        }
        
        $secret = str_replace('=', '', $secret);
        $secret = str_split($secret);
        $binaryString = '';
        
        for ($i = 0; $i < count($secret); $i = $i + 8) {
            $x = '';
            if (!in_array($secret[$i], $base32charsFlipped)) {
                return false;
            }
            for ($j = 0; $j < 8; $j++) {
                $x .= str_pad(base_convert(@$base32charsFlipped[@$secret[$i + $j]], 10, 2), 5, '0', STR_PAD_LEFT);
            }
            $eightBits = str_split($x, 8);
            for ($z = 0; $z < count($eightBits); $z++) {
                $binaryString .= (($y = chr(base_convert($eightBits[$z], 2, 10))) || ord($y) == 48) ? $y : '';
            }
        }
        
        return $binaryString;
    }
}
