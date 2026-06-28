<?php
// public/api/disputes/create.php
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../config/auth_helper.php';

$user = requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON(['success' => false, 'message' => 'Invalid request method.'], 405);
}

$data = json_decode(file_get_contents('php://input'), true);

$reportedUserId = (int)($data['reported_user_id'] ?? 0);
$listingId = (int)($data['listing_id'] ?? 0);
$reason = trim($data['reason'] ?? '');
$description = trim($data['description'] ?? '');

if (!$reportedUserId || empty($reason) || empty($description)) {
    sendJSON(['success' => false, 'message' => 'Reported user, reason, and description are required.'], 400);
}

try {
    $stmt = $pdo->prepare('INSERT INTO disputes (reporter_id, reported_user_id, listing_id, reason, description, status) VALUES (?, ?, ?, ?, ?, \'pending\')');
    $stmt->execute([$user['id'], $reportedUserId, $listingId ?: null, $reason, $description]);

    sendJSON([
        'success' => true,
        'message' => 'Dispute reported successfully. Administrators have been notified.',
        'dispute_id' => (int)$pdo->lastInsertId()
    ]);
} catch (PDOException $e) {
    sendJSON(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
}
?>
