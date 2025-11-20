<?php
// Usage: php call_manipulate.php <image_id> <session_id>
$php_errormsg = null;
if ($argc < 3) { echo "Usage: php call_manipulate.php <image_id> <session_id>\n"; exit(2); }
$id = $argv[1];
$sess = $argv[2];
$payload = json_encode(['operation'=>'crop','x'=>0,'y'=>0,'width'=>32,'height'=>32]);
$opts = [
  'http' => [
    'method' => 'POST',
    'header' => "Content-Type: application/json\r\nCookie: PHPSESSID={$sess}\r\n",
    'content' => $payload,
    'ignore_errors' => true
  ]
];
$ctx = stream_context_create($opts);
$url = "http://localhost/imanage/public/api.php?action=manipulate&id={$id}";
$res = file_get_contents($url, false, $ctx);
echo $res . PHP_EOL;
