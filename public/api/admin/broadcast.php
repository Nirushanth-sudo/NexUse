<?php
// public/api/admin/broadcast.php
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../config/auth_helper.php';

$user = requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON(['success' => false, 'message' => 'Invalid request method.'], 405);
}

$data = json_decode(file_get_contents('php://input'), true);

$target = trim($data['target'] ?? 'all'); 
$title = trim($data['title'] ?? '');
$message = trim($data['message'] ?? '');

if (empty($title) || empty($message)) {
    sendJSON(['success' => false, 'message' => 'Title and message are required.'], 400);
}

try {
    if ($target === 'all') {
        $stmt = $pdo->prepare('INSERT INTO notifications (user_id, title, message) 
                                SELECT id, ?, ? FROM users WHERE id != ?');
        $stmt->execute([$title, $message, $user['id']]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO notifications (user_id, title, message) 
                                SELECT id, ?, ? FROM users WHERE role = ? AND id != ?');
        $stmt->execute([$title, $message, $target, $user['id']]);
    }

    sendJSON(['success' => true, 'message' => 'System broadcast sent successfully.']);

} catch (PDOException $e) {
    sendJSON(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
}
?>
