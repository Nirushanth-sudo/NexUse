<?php
// public/api/user/list.php
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../config/auth_helper.php';

$user = requireLogin();

try {
    $stmt = $pdo->prepare('SELECT id, name FROM users WHERE id != ? ORDER BY name ASC');
    $stmt->execute([$user['id']]);
    $users = $stmt->fetchAll();

    foreach ($users as &$u) {
        $u['id'] = (int)$u['id'];
    }

    sendJSON(['success' => true, 'users' => $users]);
} catch (PDOException $e) {
    sendJSON(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
}
?>
