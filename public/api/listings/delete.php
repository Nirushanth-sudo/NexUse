<?php
// public/api/listings/delete.php
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../config/auth_helper.php';

$user = requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON(['success' => false, 'message' => 'Invalid request method.'], 405);
}

$data = json_decode(file_get_contents('php://input'), true);
$id = (int)($data['id'] ?? 0);

if (!$id) {
    sendJSON(['success' => false, 'message' => 'Listing ID is required.'], 400);
}

try {
    $stmt = $pdo->prepare('SELECT owner_id FROM listings WHERE id = ?');
    $stmt->execute([$id]);
    $listing = $stmt->fetch();

    if (!$listing) {
        sendJSON(['success' => false, 'message' => 'Listing not found.'], 404);
    }

    if ((int)$listing['owner_id'] !== $user['id'] && $user['role'] !== 'admin') {
        sendJSON(['success' => false, 'message' => 'Forbidden: You do not own this listing.'], 403);
    }

    $stmt = $pdo->prepare('DELETE FROM listings WHERE id = ?');
    $stmt->execute([$id]);

    sendJSON([
        'success' => true,
        'message' => 'Listing deleted successfully.'
    ]);
} catch (PDOException $e) {
    sendJSON(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
}
?>
