<?php
/**
 * OAuth Callback Handler
 * Public endpoint for OAuth provider redirects
 */

session_start();

require_once __DIR__ . '/../app/Controllers/OAuthController.php';

$controller = new OAuthController();
$controller->callback();
