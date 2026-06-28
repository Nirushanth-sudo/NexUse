<?php
// public/api/requests/create.php
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../config/auth_helper.php';

$user = requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON(['success' => false, 'message' => 'Invalid request method.'], 405);
}

$data = json_decode(file_get_contents('php://input'), true);

$listingId = (int)($data['listing_id'] ?? 0);
$startDate = !empty($data['start_date']) ? $data['start_date'] : null;
$returnDate = !empty($data['return_date']) ? $data['return_date'] : null;

if (!$listingId) {
    sendJSON(['success' => false, 'message' => 'Listing ID is required.'], 400);
}

try {
    $stmt = $pdo->prepare('SELECT * FROM listings WHERE id = ?');
    $stmt->execute([$listingId]);
    $listing = $stmt->fetch();

    if (!$listing) {
        sendJSON(['success' => false, 'message' => 'Listing not found.'], 404);
    }

    if ($listing['availability'] !== 'available') {
        sendJSON(['success' => false, 'message' => 'Item is not currently available for request.'], 409);
    }

    if ((int)$listing['owner_id'] === $user['id']) {
        sendJSON(['success' => false, 'message' => 'You cannot request your own listing.'], 400);
    }

    $requesterRole = 'buyer';
    if ($listing['type'] === 'rent') {
        $requesterRole = 'renter';
        if (empty($startDate) || empty($returnDate)) {
            sendJSON(['success' => false, 'message' => 'Start date and Return date are required for rentals.'], 400);
        }
    } elseif ($listing['type'] === 'share') {
        $requesterRole = 'renter';
    }

    $stmt = $pdo->prepare('INSERT INTO requests (listing_id, requester_id, requester_role, status, start_date, return_date) VALUES (?, ?, ?, \'pending\', ?, ?)');
    $stmt->execute([$listingId, $user['id'], $requesterRole, $startDate, $returnDate]);
    $requestId = $pdo->lastInsertId();

    $upStmt = $pdo->prepare('UPDATE listings SET availability = \'requested\' WHERE id = ?');
    $upStmt->execute([$listingId]);

    $notifTitle = $listing['type'] === 'buy' ? 'New Purchase Request' : 'New Rental Request';
    $notifMsg = $listing['type'] === 'buy'
        ? "{$user['name']} wants to buy your \"{$listing['title']}\"."
        : "{$user['name']} wants to borrow your \"{$listing['title']}\" from {$startDate} to {$returnDate}.";

    $notifStmt = $pdo->prepare('INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)');
    $notifStmt->execute([$listing['owner_id'], $notifTitle, $notifMsg]);

    sendJSON([
        'success' => true,
        'message' => 'Request placed successfully. The owner has been notified.',
        'request_id' => (int)$requestId
    ]);

} catch (PDOException $e) {
    sendJSON(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
}
?>
