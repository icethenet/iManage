<?php
require_once '../app/Database.php';

$token = $_GET['token'] ?? null;

if (!$token) {
    die('No page token provided');
}

// Load the landing page
$db = Database::getInstance();
$stmt = $db->prepare("
    SELECT html_content, css_content, page_title, user_id
    FROM landing_pages 
    WHERE share_token = ? AND is_active = 1
");
$stmt->execute([$token]);
$page = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$page) {
    die('Page not found or inactive');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page['page_title']) ?> - iManage</title>
    <style>
        <?= $page['css_content'] ?>
        
        /* Reset default margins */
        body {
            margin: 0;
            padding: 0;
        }
    </style>
</head>
<body>
    <?= $page['html_content'] ?>
</body>
</html>
