<?php
// public/api/donations/pledge_respond.php
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../config/auth_helper.php';

$user = requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON(['success' => false, 'message' => 'Invalid request method.'], 405);
}

$data = json_decode(file_get_contents('php://input'), true);

$requestId = (int)($data['request_id'] ?? 0);
$action = trim($data['action'] ?? ''); 

if (!$requestId || !in_array($action, ['accept', 'reject'])) {
    sendJSON(['success' => false, 'message' => 'Request ID and action (accept/reject) are required.'], 400);
}

try {
    $stmt = $pdo->prepare('SELECT r.*, d.requester_id AS receiver_id, d.title AS request_title 
                            FROM requests r 
                            JOIN donation_requests d ON r.listing_id = d.id 
                            WHERE r.id = ? AND r.requester_role = \'donor\'');
    $stmt->execute([$requestId]);
    $request = $stmt->fetch();

    if (!$request) {
        sendJSON(['success' => false, 'message' => 'Pledge request not found.'], 404);
    }

    if ((int)$request['receiver_id'] !== $user['id'] && $user['role'] !== 'admin') {
        sendJSON(['success' => false, 'message' => 'Forbidden: You do not own this donation request.'], 403);
    }

    if ($action === 'accept') {
        $upStmt = $pdo->prepare('UPDATE requests SET status = \'accepted\' WHERE id = ?');
        $upStmt->execute([$requestId]);

        $notifStmt = $pdo->prepare('INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)');
        $notifStmt->execute([
            $request['requester_id'],
            'Pledge Accepted! ✅',
            "{$user['name']} has accepted your donation pledge for \"{$request['request_title']}\"."
        ]);

        sendJSON(['success' => true, 'message' => 'Pledge accepted successfully.']);
    } else {
        $upStmt = $pdo->prepare('UPDATE requests SET status = \'rejected\' WHERE id = ?');
        $upStmt->execute([$requestId]);

        $notifStmt = $pdo->prepare('INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)');
        $notifStmt->execute([
            $request['requester_id'],
            'Pledge Declined ❌',
            "{$user['name']} has declined your donation pledge for \"{$request['request_title']}\"."
        ]);

        sendJSON(['success' => true, 'message' => 'Pledge declined successfully.']);
    }

} catch (PDOException $e) {
    sendJSON(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
}
?>
