<?php
session_start();

// Require authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require_once '../app/Database.php';

// Get user's pages
$db = Database::getInstance();
$stmt = $db->prepare("
    SELECT id, page_title, share_token, is_active, created_at, updated_at
    FROM landing_pages 
    WHERE user_id = ? 
    ORDER BY updated_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$pages = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Pages - iManage</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            margin: 0;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        h1 {
            margin: 0;
            color: #2c3e50;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .pages-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }
        
        .page-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }
        
        .page-card:hover {
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
            transform: translateY(-2px);
        }
        
        .page-title {
            font-size: 1.3em;
            font-weight: 600;
            color: #2c3e50;
            margin: 0 0 10px 0;
        }
        
        .page-meta {
            color: #6c757d;
            font-size: 0.9em;
            margin-bottom: 15px;
        }
        
        .page-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .btn-sm {
            padding: 8px 16px;
            font-size: 0.9em;
        }
        
        .btn-view {
            background: #28a745;
            color: white;
        }
        
        .btn-view:hover {
            background: #218838;
        }
        
        .btn-edit {
            background: #17a2b8;
            color: white;
        }
        
        .btn-edit:hover {
            background: #138496;
        }
        
        .btn-delete {
            background: #dc3545;
            color: white;
        }
        
        .btn-delete:hover {
            background: #c82333;
        }
        
        .share-link {
            background: #f8f9fa;
            padding: 8px;
            border-radius: 4px;
            font-size: 0.85em;
            word-break: break-all;
            margin-top: 10px;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .empty-state h2 {
            color: #6c757d;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📄 My Landing Pages</h1>
            <div>
                <a href="page-designer.php" class="btn btn-primary">➕ Create New Page</a>
                <a href="index.php" class="btn btn-secondary">← Back to Gallery</a>
            </div>
        </div>
        
        <?php if (empty($pages)): ?>
            <div class="empty-state">
                <h2>No pages yet</h2>
                <p>Create your first custom landing page with the GrapesJS visual editor!</p>
                <a href="page-designer.php" class="btn btn-primary">Create Your First Page</a>
            </div>
        <?php else: ?>
            <div class="pages-grid">
                <?php foreach ($pages as $page): ?>
                    <div class="page-card">
                        <h2 class="page-title"><?= htmlspecialchars($page['page_title']) ?></h2>
                        <div class="page-meta">
                            <div>Created: <?= date('M j, Y g:i A', strtotime($page['created_at'])) ?></div>
                            <div>Updated: <?= date('M j, Y g:i A', strtotime($page['updated_at'])) ?></div>
                            <div>Status: <?= $page['is_active'] ? '✅ Active' : '❌ Inactive' ?></div>
                        </div>
                        
                        <div class="page-actions">
                            <a href="landing-page.php?token=<?= htmlspecialchars($page['share_token']) ?>" 
                               target="_blank" 
                               class="btn btn-sm btn-view">
                                👁️ View Page
                            </a>
                            <a href="page-designer.php?id=<?= $page['id'] ?>" 
                               class="btn btn-sm btn-edit">
                                ✏️ Edit
                            </a>
                            <button onclick="deletePage(<?= $page['id'] ?>, '<?= htmlspecialchars($page['page_title']) ?>')" 
                                    class="btn btn-sm btn-delete">
                                🗑️ Delete
                            </button>
                        </div>
                        
                        <div class="share-link">
                            <strong>Share URL:</strong><br>
                            <code><?= htmlspecialchars($_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . '/landing-page.php?token=' . $page['share_token']) ?></code>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        async function deletePage(pageId, pageTitle) {
            if (!confirm(`Delete page "${pageTitle}"?\n\nThis action cannot be undone.`)) {
                return;
            }
            
            try {
                const response = await fetch(`api.php?action=deletecustompage`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: pageId })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('Page deleted successfully!');
                    location.reload();
                } else {
                    alert('Failed to delete: ' + (data.message || 'Unknown error'));
                }
            } catch (error) {
                alert('Error: ' + error.message);
            }
        }
    </script>
</body>
</html>
