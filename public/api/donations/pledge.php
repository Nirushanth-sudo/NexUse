<?php
// public/api/donations/pledge.php
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../config/auth_helper.php';

$user = requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON(['success' => false, 'message' => 'Invalid request method.'], 405);
}

$data = json_decode(file_get_contents('php://input'), true);

$donationRequestId = (int)($data['donation_request_id'] ?? 0);
$note = trim($data['note'] ?? '');

if (!$donationRequestId) {
    sendJSON(['success' => false, 'message' => 'Donation Request ID is required.'], 400);
}

try {
    $stmt = $pdo->prepare('SELECT * FROM donation_requests WHERE id = ?');
    $stmt->execute([$donationRequestId]);
    $dr = $stmt->fetch();

    if (!$dr) {
        sendJSON(['success' => false, 'message' => 'Donation request not found.'], 404);
    }

    if ((int)$dr['requester_id'] === $user['id']) {
        sendJSON(['success' => false, 'message' => 'You cannot pledge to your own donation request.'], 400);
    }

    $insStmt = $pdo->prepare('INSERT INTO requests (listing_id, requester_id, requester_role, status, note) VALUES (?, ?, \'donor\', \'pending\', ?)');
    $insStmt->execute([$donationRequestId, $user['id'], $note]);
    $requestId = $pdo->lastInsertId();

    $notifTitle = 'New Donation Pledge 💝';
    $notifMsg = "{$user['name']} pledged to support your request: \"{$dr['title']}\".";
    $notifStmt = $pdo->prepare('INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)');
    $notifStmt->execute([$dr['requester_id'], $notifTitle, $notifMsg]);

    sendJSON([
        'success' => true,
        'message' => 'Pledge submitted successfully! The receiver has been notified.',
        'request_id' => (int)$requestId
    ]);

} catch (PDOException $e) {
    sendJSON(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
}
?>
