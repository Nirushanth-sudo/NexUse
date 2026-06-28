<?php
// public/api/user/wishlist.php
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../config/auth_helper.php';

$user = requireLogin();

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $stmt = $pdo->prepare('SELECT l.* 
                                FROM wishlist w 
                                JOIN listings l ON w.listing_id = l.id 
                                WHERE w.user_id = ?');
        $stmt->execute([$user['id']]);
        $wishlist = $stmt->fetchAll();

        foreach ($wishlist as &$w) {
            $w['id'] = (int)$w['id'];
            $w['price'] = (float)$w['price'];
            $w['owner_id'] = (int)$w['owner_id'];
        }

        sendJSON(['success' => true, 'wishlist' => $wishlist]);
    } 
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $listingId = (int)($data['listing_id'] ?? 0);
        $action = trim($data['action'] ?? ''); 

        if (!$listingId || !in_array($action, ['add', 'remove'])) {
            sendJSON(['success' => false, 'message' => 'Listing ID and action (add/remove) are required.'], 400);
        }

        if ($action === 'add') {
            $stmt = $pdo->prepare('INSERT IGNORE INTO wishlist (user_id, listing_id) VALUES (?, ?)');
            $stmt->execute([$user['id'], $listingId]);
            sendJSON(['success' => true, 'message' => 'Added to wishlist.']);
        } else {
            $stmt = $pdo->prepare('DELETE FROM wishlist WHERE user_id = ? AND listing_id = ?');
            $stmt->execute([$user['id'], $listingId]);
            sendJSON(['success' => true, 'message' => 'Removed from wishlist.']);
        }
    } else {
        sendJSON(['success' => false, 'message' => 'Invalid request method.'], 405);
    }
} catch (PDOException $e) {
    sendJSON(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
}
?>
