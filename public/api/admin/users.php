<?php
// public/api/admin/users.php
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../config/auth_helper.php';

$user = requireRole('admin');

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $type = $_GET['type'] ?? 'users';

        if ($type === 'disputes') {
            $stmt = $pdo->query('SELECT d.*, u1.name AS reporter_name, u2.name AS reported_user_name 
                                 FROM disputes d 
                                 LEFT JOIN users u1 ON d.reporter_id = u1.id 
                                 LEFT JOIN users u2 ON d.reported_user_id = u2.id 
                                 ORDER BY d.id DESC');
            $disputes = $stmt->fetchAll();
            foreach ($disputes as &$d) {
                $d['id'] = (int)$d['id'];
                $d['reporter_id'] = (int)$d['reporter_id'];
                $d['reported_user_id'] = (int)$d['reported_user_id'];
                $d['listing_id'] = $d['listing_id'] ? (int)$d['listing_id'] : null;
            }
            sendJSON(['success' => true, 'disputes' => $disputes]);
        } else {
            $stmt = $pdo->query('SELECT id, username, name, email, role, rating, verified, phone, location, address FROM users ORDER BY id ASC');
            $users = $stmt->fetchAll();
            foreach ($users as &$u) {
                $u['id'] = (int)$u['id'];
                $u['rating'] = (float)$u['rating'];
                $u['verified'] = (bool)$u['verified'];
            }
            sendJSON(['success' => true, 'users' => $users]);
        }
    } 
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $action = trim($data['action'] ?? '');

        if ($action === 'edit_user') {
            $userId = (int)($data['id'] ?? 0);
            $name = trim($data['name'] ?? '');
            $email = trim($data['email'] ?? '');
            $rating = (float)($data['rating'] ?? 5.00);
            $verified = (int)($data['verified'] ?? 0);

            if (!$userId || empty($name) || empty($email)) {
                sendJSON(['success' => false, 'message' => 'User ID, name, and email are required.'], 400);
            }

            $stmt = $pdo->prepare('UPDATE users SET name = ?, email = ?, rating = ?, verified = ? WHERE id = ?');
            $stmt->execute([$name, $email, $rating, $verified, $userId]);

            sendJSON(['success' => true, 'message' => 'User moderated successfully.']);
        } 
        elseif ($action === 'delete_user') {
            $userId = (int)($data['id'] ?? 0);

            if (!$userId) {
                sendJSON(['success' => false, 'message' => 'User ID is required.'], 400);
            }

            if ($userId === 1 || $userId === $user['id']) {
                sendJSON(['success' => false, 'message' => 'Cannot delete system administrator account.'], 400);
            }

            $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
            $stmt->execute([$userId]);

            sendJSON(['success' => true, 'message' => 'User deleted successfully.']);
        } else {
            sendJSON(['success' => false, 'message' => 'Invalid action.'], 400);
        }
    } else {
        sendJSON(['success' => false, 'message' => 'Invalid request method.'], 405);
    }
} catch (PDOException $e) {
    sendJSON(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
}
?>
