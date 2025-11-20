# OAuth Setup Guide

## Overview

iManage now supports OAuth 2.0 authentication with Google, Facebook, GitHub, and Microsoft. Users can sign in with their existing social accounts instead of creating a new username/password.

## Features

- **Multiple Providers**: Google, Facebook, GitHub, Microsoft
- **Auto Account Creation**: New users are automatically created on first OAuth login
- **Account Linking**: Link OAuth accounts to existing email addresses
- **Secure Tokens**: Cryptographically secure state parameters for CSRF protection
- **Avatar Support**: Automatically imports user avatars from OAuth providers

## Setup Instructions

### 1. Configure OAuth Providers

Copy the example configuration file:

```bash
cp config/oauth.php.example config/oauth.php
```

Edit `config/oauth.php` and set your application URL:

```php
'app_url' => 'http://your-domain.com/imanage/public',
```

### 2. Register Your Application with OAuth Providers

#### Google OAuth Setup

1. Go to [Google Cloud Console](https://console.developers.google.com)
2. Create a new project or select existing
3. Enable "Google+ API"
4. Go to "Credentials" → "Create Credentials" → "OAuth client ID"
5. Application type: "Web application"
6. Authorized redirect URIs:
   ```
   http://your-domain.com/imanage/public/oauth-callback.php?provider=google
   ```
7. Copy your `Client ID` and `Client Secret` to `config/oauth.php`
8. Set `'enabled' => true` for Google

#### Facebook OAuth Setup

1. Go to [Facebook Developers](https://developers.facebook.com)
2. Create a new app or select existing
3. Add "Facebook Login" product
4. Settings → Basic → Copy App ID and App Secret
5. Facebook Login Settings → Valid OAuth Redirect URIs:
   ```
   http://your-domain.com/imanage/public/oauth-callback.php?provider=facebook
   ```
6. Copy your `App ID` and `App Secret` to `config/oauth.php`
7. Set `'enabled' => true` for Facebook

#### GitHub OAuth Setup

1. Go to [GitHub Developer Settings](https://github.com/settings/developers)
2. Click "New OAuth App"
3. Application name: "iManage"
4. Homepage URL: `http://your-domain.com`
5. Authorization callback URL:
   ```
   http://your-domain.com/imanage/public/oauth-callback.php?provider=github
   ```
6. Copy your `Client ID` and `Client Secret` to `config/oauth.php`
7. Set `'enabled' => true` for GitHub

#### Microsoft OAuth Setup

1. Go to [Azure Portal](https://portal.azure.com)
2. Navigate to "Azure Active Directory" → "App registrations"
3. Click "New registration"
4. Redirect URI: "Web" platform
   ```
   http://your-domain.com/imanage/public/oauth-callback.php?provider=microsoft
   ```
5. Copy `Application (client) ID`
6. Go to "Certificates & secrets" → Create new client secret
7. Copy your `Client ID` and `Client Secret` to `config/oauth.php`
8. Set `'enabled' => true` for Microsoft

### 3. Update Database Schema

Run the migration to add OAuth support to the users table:

```bash
# Using MySQL command line
mysql -u your_user -p your_database < database/migrations/add_oauth_support.sql

# Or using PHP
php tools/run_migration.php add_oauth_support
```

This adds the following columns:
- `email` - User's email address
- `oauth_provider` - Provider name (google, facebook, github, microsoft)
- `oauth_id` - User's ID from the OAuth provider
- `oauth_token` - Access token
- `oauth_refresh_token` - Refresh token (if provided)
- `avatar_url` - Profile picture URL
- `last_login` - Last login timestamp

### 4. Configure Web Server

Ensure your web server can handle the OAuth callback URL. If using Apache with mod_rewrite, the existing `.htaccess` should work.

For Nginx, add:

```nginx
location ~ ^/(oauth-login|oauth-callback)\.php$ {
    fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
    fastcgi_index index.php;
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
}
```

### 5. Test OAuth Flow

1. Navigate to your iManage login page
2. Click on an OAuth provider button (e.g., "Continue with Google")
3. You'll be redirected to the provider's login page
4. After authentication, you'll be redirected back to iManage
5. A new account will be created automatically if this is your first login

## Configuration Options

Edit `config/oauth.php` to customize behavior:

```php
// Automatically create accounts on first OAuth login
'auto_create_accounts' => true,

// Require email verification for new accounts
'require_email_verification' => false,

// Use OAuth provider's avatar as user profile picture
'default_avatar' => true,
```

## Security Features

- **CSRF Protection**: State parameter validation prevents cross-site request forgery
- **Secure Token Storage**: OAuth tokens stored encrypted in database
- **Provider Verification**: Only enabled providers can authenticate
- **Unique Constraints**: Each OAuth account can only link to one user

## Troubleshooting

### "OAuth not configured" error

- Ensure `config/oauth.php` exists (copy from `oauth.php.example`)
- Check file permissions on config directory

### "Invalid redirect URI" error

- Verify redirect URIs match exactly in provider settings
- Include protocol (http/https) and port if needed
- No trailing slashes

### "Access denied" error

- User canceled authentication
- Check provider app is published/live (not in development mode)

### Email already exists

- User already has an account with that email
- They should log in with username/password first
- Then link OAuth account from profile settings (future feature)

## Account Linking

When a user logs in with OAuth for the first time:

1. System checks if OAuth provider + ID combination exists
2. If not, checks if email address exists
3. If email exists, links OAuth to existing account
4. If email doesn't exist, creates new account

## Username Generation

OAuth usernames are generated from:
- GitHub: Uses GitHub username
- Google: Uses email prefix (before @)
- Facebook: Uses email prefix
- Microsoft: Uses email prefix

If username exists, appends number (e.g., `john`, `john1`, `john2`)

## Privacy Considerations

OAuth tokens are stored to:
- Refresh expired access tokens
- Keep user logged in across sessions
- Sync profile information

To revoke access:
1. Visit provider's app permissions page
2. Remove iManage app authorization
3. Delete user's OAuth tokens from database

## Production Checklist

- [ ] Change `app_url` to production domain
- [ ] Update all provider redirect URIs to HTTPS
- [ ] Enable only needed OAuth providers
- [ ] Set strong database password
- [ ] Enable HTTPS/SSL
- [ ] Review provider app permissions requested
- [ ] Test OAuth flow on production domain
- [ ] Monitor OAuth error logs

## API Reference

### Initiate OAuth Login

```
GET /oauth-login.php?provider={provider}&return_url={url}
```

Parameters:
- `provider`: google, facebook, github, microsoft
- `return_url`: Optional, where to redirect after login

### OAuth Callback

```
GET /oauth-callback.php?provider={provider}&code={code}&state={state}
```

This endpoint is called by the OAuth provider. Do not call directly.

## File Structure

```
app/
├── Controllers/
│   └── OAuthController.php    # OAuth request handler
├── Models/
│   └── User.php               # Extended with OAuth methods
└── Utils/
    └── OAuthService.php       # OAuth 2.0 flow implementation

config/
├── oauth.php.example          # Configuration template
└── oauth.php                  # Your configuration (gitignored)

database/
└── migrations/
    └── add_oauth_support.sql  # Database migration

public/
├── oauth-login.php           # OAuth initiation endpoint
└── oauth-callback.php        # OAuth callback handler
```

## Support

For issues:
- Check provider's OAuth documentation
- Review error messages in browser console
- Check PHP error logs
- Verify redirect URIs match exactly

Common provider docs:
- [Google OAuth 2.0](https://developers.google.com/identity/protocols/oauth2)
- [Facebook Login](https://developers.facebook.com/docs/facebook-login)
- [GitHub OAuth](https://docs.github.com/en/developers/apps/building-oauth-apps)
- [Microsoft Identity Platform](https://docs.microsoft.com/en-us/azure/active-directory/develop/)
