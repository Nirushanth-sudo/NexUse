<?php
// public/api/auth/session.php
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../config/auth_helper.php';

$user = getLoggedInUser();
if ($user) {
    sendJSON(['success' => true, 'user' => $user]);
} else {
    sendJSON(['success' => false, 'user' => null]);
}
?>
