# Database Configuration - Production Deployment Guide

## Overview

This directory contains database configuration. **DO NOT commit production credentials to version control.**

## File Structure

- `database.php` — Database connection parameters
- `app.php` — Application settings (paths, upload sizes, etc.)

## Development (Current Setup)

```php
// config/database.php
return [
    'host' => 'localhost',
    'port' => 3306,
    'database' => 'image_gallery',
    'username' => 'root',
    'password' => '',  // Empty password for local dev
    'charset' => 'utf8mb4',
];
```

## Production Deployment

### Option 1: Environment Variables (RECOMMENDED)

1. **Generate strong MySQL password** (e.g., `openssl rand -base64 32`)

2. **Set environment variables** on your server:
   ```bash
   # Linux/Mac (.bashrc or server environment)
   export DB_HOST="prod-db.example.com"
   export DB_USER="app_user"
   export DB_PASS="<strong-random-password>"
   export DB_NAME="image_gallery_prod"
   ```

3. **Update `database.php`** to read from environment:
   ```php
   return [
       'host' => getenv('DB_HOST') ?: 'localhost',
       'port' => 3306,
       'database' => getenv('DB_NAME') ?: 'image_gallery',
       'username' => getenv('DB_USER') ?: 'root',
       'password' => getenv('DB_PASS') ?: '',
       'charset' => 'utf8mb4',
   ];
   ```

4. **Add `.gitignore` entry**:
   ```
   config/database.php
   .env
   ```

### Option 2: Separate Production Config

1. **Store production config outside web root**:
   ```
   /var/www/html/imanage/          (web root)
   /var/private/config/database.php (production config)
   ```

2. **Symlink to production config**:
   ```bash
   ln -s /var/private/config/database.php /var/www/html/imanage/config/database.php
   ```

3. **Restrict file permissions**:
   ```bash
   chmod 600 /var/private/config/database.php
   chown www-data:www-data /var/private/config/database.php
   ```

### Option 3: Docker/Containerized Deployment

Use environment variables via Docker environment or `.env` file:

```dockerfile
# Dockerfile
ENV DB_HOST="mysql"
ENV DB_USER="app_user"
ENV DB_PASS="<strong-password>"
```

## MySQL User Setup (Production)

### Create dedicated application user (DO NOT use root)

```sql
-- Connect as root or admin user
CREATE USER 'app_user'@'localhost' IDENTIFIED BY '<strong-random-password>';

-- Grant only necessary permissions to image_gallery database
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE ON image_gallery.* TO 'app_user'@'localhost';

-- Remove unnecessary privileges
REVOKE CREATE, ALTER, DROP ON image_gallery.* FROM 'app_user'@'localhost';

-- Apply changes
FLUSH PRIVILEGES;
```

## Security Checklist

- [ ] Store credentials in environment variables or `.env` file
- [ ] Use strong, randomly-generated MySQL password (min 32 chars)
- [ ] Create dedicated MySQL user (not root)
- [ ] Restrict MySQL user permissions to specific database
- [ ] Set `.gitignore` to exclude `config/database.php` from version control
- [ ] Restrict file permissions on config file to `0600`
- [ ] Use HTTPS for all connections to the app
- [ ] Enable SSL/TLS for MySQL connections (in production)

## Database Connection via SSL/TLS (Optional, Advanced)

```php
// config/database.php - with SSL
$dsn = 'mysql:host=' . getenv('DB_HOST') . ';dbname=' . getenv('DB_NAME');
$pdo = new PDO($dsn, getenv('DB_USER'), getenv('DB_PASS'), [
    PDO::MYSQL_ATTR_SSL_KEY    => '/path/to/client-key.pem',
    PDO::MYSQL_ATTR_SSL_CERT   => '/path/to/client-cert.pem',
    PDO::MYSQL_ATTR_SSL_CA     => '/path/to/ca-cert.pem',
    PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,  // true in production
]);
```

## Troubleshooting

### "Access denied for user 'root'@'localhost'"
- Verify MySQL service is running
- Check credentials in `database.php`
- Ensure MySQL user exists and password is correct

### "Unknown database 'image_gallery'"
- Verify database exists: `SHOW DATABASES;`
- Run installer at `public/install.php` if needed

### "Connection refused"
- Check if MySQL is listening on the configured host/port
- Verify firewall rules allow connection
- Check MySQL `bind-address` in `/etc/mysql/mysql.conf.d/mysqld.cnf`

## References

- [MySQL User Management](https://dev.mysql.com/doc/refman/8.0/en/user-names.html)
- [PDO Connection Strings](https://www.php.net/manual/en/ref.pdo-mysql.connection.php)
- [Environment Variables in PHP](https://www.php.net/manual/en/function.getenv.php)
