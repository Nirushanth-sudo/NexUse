<?php
// public/api/notifications/index.php
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../config/auth_helper.php';

$user = requireLogin();

try {
    $stmt = $pdo->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY id DESC LIMIT 50');
    $stmt->execute([$user['id']]);
    $notifications = $stmt->fetchAll();

    foreach ($notifications as &$n) {
        $n['id'] = (int)$n['id'];
        $n['user_id'] = (int)$n['user_id'];
        $n['is_read'] = (bool)$n['is_read'];
    }

    sendJSON(['success' => true, 'notifications' => $notifications]);
} catch (PDOException $e) {
    sendJSON(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
}
?>
