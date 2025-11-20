<?php
/**
 * OAuth Login Initiator
 * Redirects to OAuth provider for authentication
 */

session_start();

require_once __DIR__ . '/../app/Controllers/OAuthController.php';

$controller = new OAuthController();
$controller->login();
