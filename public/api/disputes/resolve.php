<?php
// public/api/disputes/resolve.php
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../config/auth_helper.php';

$user = requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON(['success' => false, 'message' => 'Invalid request method.'], 405);
}

$data = json_decode(file_get_contents('php://input'), true);

$disputeId = (int)($data['dispute_id'] ?? 0);
$resolution = trim($data['resolution'] ?? '');

if (!$disputeId || empty($resolution)) {
    sendJSON(['success' => false, 'message' => 'Dispute ID and resolution details are required.'], 400);
}

try {
    $stmt = $pdo->prepare('SELECT * FROM disputes WHERE id = ?');
    $stmt->execute([$disputeId]);
    $dispute = $stmt->fetch();

    if (!$dispute) {
        sendJSON(['success' => false, 'message' => 'Dispute not found.'], 404);
    }

    $upStmt = $pdo->prepare('UPDATE disputes SET status = \'resolved\', resolution = ? WHERE id = ?');
    $upStmt->execute([$resolution, $disputeId]);

    $notifStmt = $pdo->prepare('INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)');
    $notifStmt->execute([
        $dispute['reporter_id'],
        'Dispute Resolved ⚖️',
        "Your filed dispute (ID: {$disputeId}) has been resolved: {$resolution}."
    ]);

    $notifStmt->execute([
        $dispute['reported_user_id'],
        'Dispute Resolution Notification ⚖️',
        "A dispute filed against you (ID: {$disputeId}) has been resolved by an administrator: {$resolution}."
    ]);

    sendJSON(['success' => true, 'message' => 'Dispute resolved successfully.']);

} catch (PDOException $e) {
    sendJSON(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
}
?>
