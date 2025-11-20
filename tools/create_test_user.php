<?php
/**
 * Create or find a test user in the database
 * Usage: php create_test_user.php
 */

$dbConfigPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';
$dbConfig = require $dbConfigPath;

try {
    $pdo = new PDO(
        'mysql:host=' . $dbConfig['host'] . ';dbname=' . $dbConfig['database'],
        $dbConfig['username'],
        $dbConfig['password']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if test user exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute(['integration_test_user']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo json_encode([
            'success' => true,
            'message' => 'Test user already exists',
            'user_id' => $user['id'],
            'username' => 'integration_test_user'
        ]);
    } else {
        // Create test user
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([
            'integration_test_user',
            'test@example.com',
            password_hash('test1234', PASSWORD_DEFAULT)
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Test user created',
            'user_id' => $pdo->lastInsertId(),
            'username' => 'integration_test_user'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    exit(1);
}
?>
