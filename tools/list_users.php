<?php
require __DIR__ . '/../app/Database.php';

$db = Database::getInstance();
$stmt = $db->query('SELECT id, username, email FROM users LIMIT 5');

echo "Users in database:\n";
while($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo sprintf("ID: %d - Username: %s - Email: %s\n", 
        $user['id'], 
        $user['username'], 
        $user['email'] ?? 'N/A'
    );
}

