<?php
/**
 * Image Management System - Database Installation Script
 * Cross-Platform Edition (Windows/Linux)
 * 
 * This script provides an interactive web-based installer for setting up
 * the database, creating tables, and populating sample data.
 * Compatible with Windows, Linux, and macOS.
 */

// Start session first
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define base paths with cross-platform compatibility
define('BASE_DIR', dirname(dirname(__FILE__)));
define('DS', DIRECTORY_SEPARATOR); // Auto-detects OS separator
define('CONFIG_DIR', BASE_DIR . DS . 'config');
define('DB_CONFIG_FILE', CONFIG_DIR . DS . 'database.php');

// Track installation status
$step = 1;  // Current step to display
$errors = [];
$success = [];
$dbConnected = false;
$configExists = false;
$nextStep = 1;  // Next step after processing

// Check if database config already exists
$configExists = file_exists(DB_CONFIG_FILE);

// Check installation complete
$installComplete = isset($_SESSION['install_complete']) && $_SESSION['install_complete'];
$dbName = $_SESSION['db_name'] ?? 'image_gallery';

// Determine which step to show
if ($installComplete) {
    $step = 'complete';
} else {
    // Process form submissions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = isset($_POST['action']) ? $_POST['action'] : '';
        
        // Step 1: Validate connection
        if ($action === 'test_connection') {
            $host = trim($_POST['db_host'] ?? '');
            $username = trim($_POST['db_username'] ?? '');
            $password = trim($_POST['db_password'] ?? '');
            
            if (empty($host) || empty($username)) {
                $errors[] = 'Host and username are required';
                $step = 1;
            } else {
                try {
                    $dsn = "mysql:host=$host";
                    $pdo = new PDO($dsn, $username, $password);
                    $dbConnected = true;
                    $success[] = 'Database connection successful!';
                    
                    // Store in session for next steps
                    $_SESSION['db_config'] = [
                        'host' => $host,
                        'username' => $username,
                        'password' => $password
                    ];
                    
                    // Move to step 2
                    $step = 2;
                } catch (PDOException $e) {
                    $errors[] = 'Connection failed: ' . $e->getMessage();
                    $dbConnected = false;
                    $step = 1;
                }
            }
        }
        
        // Step 2: Create database and tables
        elseif ($action === 'create_database') {
            $dbName = trim($_POST['db_name'] ?? '');
            $host = trim($_POST['db_host'] ?? '');
            $username = trim($_POST['db_username'] ?? '');
            $password = trim($_POST['db_password'] ?? '');
            $sampleData = isset($_POST['sample_data']) ? true : false;
            
            if (empty($dbName)) {
                $errors[] = 'Database name is required';
                $step = 2;
            } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $dbName)) {
                $errors[] = 'Database name can only contain letters, numbers, and underscores';
                $step = 2;
            } else {
                try {
                    // Connect to MySQL server
                    $dsn = "mysql:host=$host";
                    $pdo = new PDO($dsn, $username, $password);
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    
                    // Create database
                    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                    $success[] = "Database '$dbName' created successfully!";
                    
                    // Connect to the new database
                    $dsn = "mysql:host=$host;dbname=$dbName";
                    $pdo = new PDO($dsn, $username, $password);
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    
                    // Create tables
                    $schema = file_get_contents(BASE_DIR . DS . 'database' . DS . 'schema.sql');
                    if ($schema) {
                        // Split SQL statements
                        $statements = array_filter(array_map('trim', explode(';', $schema)));
                        
                        foreach ($statements as $statement) {
                            if (!empty($statement)) {
                                $pdo->exec($statement);
                            }
                        }
                        $success[] = 'Database tables created successfully!';
                    }
                    
                    // Insert sample data if requested
                    if ($sampleData) {
                        insertSampleData($pdo);
                        $success[] = 'Sample data inserted successfully!';
                    }
                    
                    // Save configuration file
                    saveConfigFile($host, $username, $password, $dbName);
                    $success[] = 'Configuration file saved!';
                    
                    // Store info for verification step
                    $_SESSION['db_config'] = [
                        'host' => $host,
                        'username' => $username,
                        'password' => $password,
                        'database' => $dbName
                    ];
                    
                    // Move to step 3 for verification
                    $step = 3;
                    
                } catch (PDOException $e) {
                    $errors[] = 'Database creation failed: ' . $e->getMessage();
                    $step = 2;
                } catch (Exception $e) {
                    $errors[] = 'Error: ' . $e->getMessage();
                    $step = 2;
                }
            }
        }
        
        // Step 3: Verify installation
        elseif ($action === 'verify_installation') {
            $host = trim($_POST['db_host'] ?? '');
            $username = trim($_POST['db_username'] ?? '');
            $password = trim($_POST['db_password'] ?? '');
            $dbName = trim($_POST['db_name'] ?? '');
            
            try {
                $dsn = "mysql:host=$host;dbname=$dbName";
                $pdo = new PDO($dsn, $username, $password);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Check if tables exist
                $result = $pdo->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = '$dbName'");
                $tableCount = $result->fetchColumn();
                
                if ($tableCount > 0) {
                    $success[] = "Installation verified! Found $tableCount tables.";
                    
                    // List tables
                    $result = $pdo->query("SHOW TABLES");
                    $tables = $result->fetchAll(PDO::FETCH_COLUMN);
                    $success[] = 'Tables: ' . implode(', ', $tables);
                    
                    // Check for sample data
                    $result = $pdo->query("SELECT COUNT(*) FROM folders");
                    $folderCount = $result->fetchColumn();
                    
                    $result = $pdo->query("SELECT COUNT(*) FROM images");
                    $imageCount = $result->fetchColumn();
                    
                    $success[] = "Folders: $folderCount | Images: $imageCount";
                    
                    // Mark installation complete
                    $_SESSION['install_complete'] = true;
                    $_SESSION['db_name'] = $dbName;
                    
                    $step = 'complete';
                } else {
                    $errors[] = 'No tables found. Installation may have failed.';
                    $step = 3;
                }
            } catch (PDOException $e) {
                $errors[] = 'Verification failed: ' . $e->getMessage();
                $step = 3;
            }
        }
    } else {
        // GET request - show appropriate step
        $step = 1;  // Always start at step 1 for new page load
    }
}

// Function to insert sample data
function insertSampleData($pdo) {
    // Note: Schema already inserts default admin user and folders
    // This function adds additional sample data if needed
    
    echo "<div class='alert alert-info'>Sample data (admin user and folders) created by schema.sql</div>";
}

// Function to save configuration file
function saveConfigFile($host, $username, $password, $dbName) {
    $config = <<<PHP
<?php
/**
 * Database Configuration
 * Auto-generated by Installation Script
 */

return [
    'driver' => 'mysql',
    'host' => '{$host}',
    'port' => 3306,
    'username' => '{$username}',
    'password' => '{$password}',
    'database' => '{$dbName}',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
];
PHP;

    if (!is_dir(CONFIG_DIR)) {
        mkdir(CONFIG_DIR, 0755, true);
    }
    
    file_put_contents(DB_CONFIG_FILE, $config);
    chmod(DB_CONFIG_FILE, 0644);
}

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if installation is complete
$installComplete = isset($_SESSION['install_complete']) && $_SESSION['install_complete'];
$dbName = $_SESSION['db_name'] ?? 'image_gallery';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Image Management System - Database Installation</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            max-width: 600px;
            width: 100%;
            padding: 40px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .header h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .header p {
            color: #666;
            font-size: 14px;
        }
        
        .progress-steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            position: relative;
        }
        
        .progress-steps::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 0;
            right: 0;
            height: 2px;
            background: #ddd;
            z-index: -1;
        }
        
        .step {
            text-align: center;
            flex: 1;
        }
        
        .step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #ddd;
            color: #666;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-weight: bold;
            position: relative;
            z-index: 1;
        }
        
        .step.active .step-number {
            background: #667eea;
            color: white;
        }
        
        .step.completed .step-number {
            background: #28a745;
            color: white;
        }
        
        .step-title {
            font-size: 12px;
            color: #666;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            font-family: inherit;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-group small {
            display: block;
            margin-top: 5px;
            color: #999;
            font-size: 12px;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: auto;
            margin-right: 10px;
        }
        
        .checkbox-group label {
            margin: 0;
            flex: 1;
        }
        
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .alert-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .alert-info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
        }
        
        .alert ul {
            margin-left: 20px;
            margin-top: 10px;
        }
        
        .alert li {
            margin-bottom: 5px;
        }
        
        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }
        
        button {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-success:hover {
            background: #218838;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
        }
        
        .completion-message {
            text-align: center;
            padding: 20px;
        }
        
        .completion-icon {
            font-size: 60px;
            margin-bottom: 20px;
        }
        
        .completion-message h2 {
            color: #28a745;
            margin-bottom: 10px;
        }
        
        .completion-message p {
            color: #666;
            margin-bottom: 20px;
        }
        
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #999;
            font-size: 12px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        @media (max-width: 600px) {
            .container {
                padding: 20px;
            }
            
            .progress-steps {
                flex-direction: column;
                gap: 15px;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .button-group {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🖼️ Image Management System</h1>
            <p>Database Installation Wizard</p>
        </div>
        
        <!-- Progress Steps -->
        <div class="progress-steps">
            <div class="step <?php echo ($step === 1 || $step === 2 || $step === 3) ? 'active' : ($step === 'complete' ? 'completed' : ''); ?>">
                <div class="step-number">1</div>
                <div class="step-title">Database Connection</div>
            </div>
            <div class="step <?php echo ($step === 2 || $step === 3) ? 'active' : ($step === 'complete' ? 'completed' : ''); ?>">
                <div class="step-number">2</div>
                <div class="step-title">Create Database</div>
            </div>
            <div class="step <?php echo ($step === 3) ? 'active' : ($step === 'complete' ? 'completed' : ''); ?>">
                <div class="step-number">3</div>
                <div class="step-title">Verify & Complete</div>
            </div>
        </div>
        
        <!-- Alert Messages -->
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <strong>⚠️ Errors:</strong>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <strong>✅ Success:</strong>
                <ul>
                    <?php foreach ($success as $msg): ?>
                        <li><?php echo htmlspecialchars($msg); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <!-- Installation Complete -->
        <?php if ($step === 'complete'): ?>
            <div class="completion-message">
                <div class="completion-icon">✨</div>
                <h2>Installation Complete!</h2>
                <p>Your database has been successfully configured and populated.</p>
                
                <div class="alert alert-info">
                    <strong>Database Details:</strong><br>
                    Database Name: <strong><?php echo htmlspecialchars($dbName); ?></strong><br>
                    Configuration File: <strong>config/database.php</strong><br>
                    Sample Data: <strong>Included</strong>
                </div>
                
                <p style="font-size: 12px; color: #999; margin-top: 15px;">
                    You can now close this page and access the application at:<br>
                    <strong>http://imanage.local</strong>
                </p>
                
                <button type="button" class="btn-success" onclick="window.location.href='/index.html'">
                    Go to Application
                </button>
            </div>
        <?php elseif ($step === 1): ?>
            <!-- Step 1: Database Connection -->
                <form method="POST">
                    <input type="hidden" name="action" value="test_connection">
                    
                    <h3 style="margin-bottom: 20px; color: #333;">Step 1: Database Connection</h3>
                    
                    <div class="alert alert-info">
                        Enter your MySQL server credentials. The installer will test the connection.
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="db_host">MySQL Host</label>
                            <input type="text" id="db_host" name="db_host" value="localhost" required>
                            <small>Usually 'localhost' for local development</small>
                        </div>
                        <div class="form-group">
                            <label for="db_port">Port (Optional)</label>
                            <input type="text" id="db_port" name="db_port" value="3306" disabled>
                            <small>Standard port is 3306</small>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="db_username">MySQL Username</label>
                        <input type="text" id="db_username" name="db_username" value="root" required>
                        <small>Your MySQL root or admin username</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="db_password">MySQL Password</label>
                        <input type="password" id="db_password" name="db_password">
                        <small>Leave empty if no password</small>
                    </div>
                    
                    <div class="button-group">
                        <button type="submit" class="btn-primary">Test Connection & Continue</button>
                    </div>
                </form>
            
            <!-- Step 2: Create Database -->
            <?php elseif ($step === 2): ?>
                <form method="POST">
                    <input type="hidden" name="action" value="create_database">
                    
                    <h3 style="margin-bottom: 20px; color: #333;">Step 2: Create Database & Tables</h3>
                    
                    <div class="alert alert-info">
                        Enter a name for your image gallery database. This will be created with all necessary tables.
                    </div>
                    
                    <div class="form-group">
                        <label for="db_name">Database Name</label>
                        <input type="text" id="db_name" name="db_name" value="image_gallery" required>
                        <small>Use letters, numbers, and underscores only</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="db_host">MySQL Host</label>
                        <input type="text" id="db_host" name="db_host" value="<?php echo htmlspecialchars($host ?? 'localhost'); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="db_username">MySQL Username</label>
                        <input type="text" id="db_username" name="db_username" value="<?php echo htmlspecialchars($username ?? 'root'); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="db_password">MySQL Password</label>
                        <input type="password" id="db_password" name="db_password" value="<?php echo htmlspecialchars($password ?? ''); ?>">
                    </div>
                    
                    <div class="form-group checkbox-group">
                        <input type="checkbox" id="sample_data" name="sample_data" checked>
                        <label for="sample_data">Include sample data (folders and images for demo)</label>
                    </div>
                    
                    <div class="button-group">
                        <button type="button" class="btn-secondary" onclick="history.back()">← Back</button>
                        <button type="submit" class="btn-primary">Create Database & Continue</button>
                    </div>
                </form>
            
            <!-- Step 3: Verify Installation -->
            <?php elseif ($step === 3): ?>
                <form method="POST">
                    <input type="hidden" name="action" value="verify_installation">
                    
                    <h3 style="margin-bottom: 20px; color: #333;">Step 3: Verify Installation</h3>
                    
                    <div class="alert alert-info">
                        Verify that your database was created successfully with all tables.
                    </div>
                    
                    <div class="form-group">
                        <label for="db_host">MySQL Host</label>
                        <input type="text" id="db_host" name="db_host" value="<?php echo htmlspecialchars($_POST['db_host'] ?? 'localhost'); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="db_username">MySQL Username</label>
                        <input type="text" id="db_username" name="db_username" value="<?php echo htmlspecialchars($_POST['db_username'] ?? 'root'); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="db_password">MySQL Password</label>
                        <input type="password" id="db_password" name="db_password" value="<?php echo htmlspecialchars($_POST['db_password'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="db_name">Database Name</label>
                        <input type="text" id="db_name" name="db_name" value="<?php echo htmlspecialchars($_POST['db_name'] ?? 'image_gallery'); ?>" required>
                    </div>
                    
                    <div class="button-group">
                        <button type="button" class="btn-secondary" onclick="history.back()">← Back</button>
                        <button type="submit" class="btn-primary">Verify Installation</button>
                    </div>
                </form>
            <?php endif; ?>
        
        <div class="footer">
            <p>Image Management System v1.0 - Database Installation Wizard</p>
            <p>For support, refer to the documentation files included with the system.</p>
        </div>
    </div>
</body>
</html>
