<?php
// public/api/auth/logout.php
require_once __DIR__ . '/../../../config/auth_helper.php';

$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

sendJSON(['success' => true, 'message' => 'Logged out successfully.']);
?>
