<?php
require_once __DIR__ . '/../app/Models/Image.php';
require_once __DIR__ . '/../app/Utils/ImageManipulator.php';
require_once __DIR__ . '/../app/Models/User.php';
require_once __DIR__ . '/../app/Utils/Path.php';
// Debug endpoint: /public/debug_save_test.php?id=51
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
header('Content-Type: application/json');
if (!$id) { echo json_encode(['success'=>false,'error'=>'no id']); exit; }
try {
    $imgModel = new Image();
    $image = $imgModel->getById($id);
    if (!$image) { echo json_encode(['success'=>false,'error'=>'not found']); exit; }
    $userModel = new User();
    $user = $userModel->findById($image['user_id']);
    $username = $user['username'] ?? 'unknown';
    $pathSegment = ($image['folder'] && $image['folder'] !== 'default') ? $username . DIRECTORY_SEPARATOR . $image['folder'] : $username;
    $uploadDir = Path::uploadsBaseFs();
    $originalDir = $uploadDir . DIRECTORY_SEPARATOR . $pathSegment . DIRECTORY_SEPARATOR . 'original';
    $imagePath = $originalDir . DIRECTORY_SEPARATOR . $image['filename'];
    $tmpDst = $originalDir . DIRECTORY_SEPARATOR . $image['filename'] . '.debugsave';
    $info = [
        'imagePath'=>$imagePath,
        'exists'=>file_exists($imagePath),
        'is_writable_dir'=>is_writable(dirname($imagePath)),
        'is_writable_file'=>is_writable($imagePath),
        'perms_dir'=>substr(sprintf('%o', fileperms(dirname($imagePath))), -4),
        'perms_file'=>file_exists($imagePath) ? substr(sprintf('%o', fileperms($imagePath)), -4) : null
    ];
    $m = new ImageManipulator($imagePath);
    $ok = $m->save($tmpDst);
    $info['save_result'] = $ok;
    $info['last_error'] = error_get_last();
    echo json_encode(['success'=>true,'info'=>$info]);
} catch (Exception $e) {
    echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}
