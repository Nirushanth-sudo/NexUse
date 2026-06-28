<?php
// public/api/requests/respond.php
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../config/auth_helper.php';

$user = requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON(['success' => false, 'message' => 'Invalid request method.'], 405);
}

$data = json_decode(file_get_contents('php://input'), true);

$requestId = (int)($data['request_id'] ?? 0);
$action = trim($data['action'] ?? ''); // 'accept' or 'reject'

if (!$requestId || !in_array($action, ['accept', 'reject'])) {
    sendJSON(['success' => false, 'message' => 'Request ID and action (accept/reject) are required.'], 400);
}

try {
    $stmt = $pdo->prepare('SELECT r.*, l.owner_id, l.title AS listing_title, l.type AS listing_type 
                            FROM requests r 
                            JOIN listings l ON r.listing_id = l.id 
                            WHERE r.id = ?');
    $stmt->execute([$requestId]);
    $request = $stmt->fetch();

    if (!$request) {
        sendJSON(['success' => false, 'message' => 'Request not found.'], 404);
    }

    if ((int)$request['owner_id'] !== $user['id'] && $user['role'] !== 'admin') {
        sendJSON(['success' => false, 'message' => 'Forbidden: You do not own this listing.'], 403);
    }

    if ($action === 'accept') {
        $upStmt = $pdo->prepare('UPDATE requests SET status = \'accepted\' WHERE id = ?');
        $upStmt->execute([$requestId]);

        $newAvailability = $request['requester_role'] === 'buyer' ? 'completed' : 'rented';
        $lstStmt = $pdo->prepare('UPDATE listings SET availability = ? WHERE id = ?');
        $lstStmt->execute([$newAvailability, $request['listing_id']]);

        $notifStmt = $pdo->prepare('INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)');
        $notifStmt->execute([
            $request['requester_id'],
            'Request Approved! ✅',
            "Your request for \"{$request['listing_title']}\" has been approved."
        ]);

        sendJSON(['success' => true, 'message' => 'Request accepted successfully.']);
    } else {
        $upStmt = $pdo->prepare('UPDATE requests SET status = \'rejected\' WHERE id = ?');
        $upStmt->execute([$requestId]);

        $lstStmt = $pdo->prepare('UPDATE listings SET availability = \'available\' WHERE id = ?');
        $lstStmt->execute([$request['listing_id']]);

        $notifStmt = $pdo->prepare('INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)');
        $notifStmt->execute([
            $request['requester_id'],
            'Request Declined ❌',
            "Your request for \"{$request['listing_title']}\" has been declined."
        ]);

        sendJSON(['success' => true, 'message' => 'Request declined successfully.']);
    }

} catch (PDOException $e) {
    sendJSON(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
}
?>
