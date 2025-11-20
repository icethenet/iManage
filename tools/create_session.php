<?php
// Usage: php create_session.php <user_id> <username>
if ($argc < 3) {
    fwrite(STDERR, "Usage: php create_session.php <user_id> <username>\n");
    exit(2);
}
$userId = $argv[1];
$username = $argv[2];

// Use a generated session id so we can set and return it
session_start();
// regenerate a new session id to avoid clashing with any existing session
session_regenerate_id(true);
$_SESSION['user_id'] = $userId;
$_SESSION['username'] = $username;
session_write_close();

// Output the session id so the test harness can use it
echo session_id();
?>
