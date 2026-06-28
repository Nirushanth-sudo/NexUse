<?php
// public/api/user/cart.php
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../config/auth_helper.php';

$user = requireLogin();

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $stmt = $pdo->prepare('SELECT l.* 
                                FROM cart c 
                                JOIN listings l ON c.listing_id = l.id 
                                WHERE c.user_id = ?');
        $stmt->execute([$user['id']]);
        $cart = $stmt->fetchAll();

        foreach ($cart as &$item) {
            $item['id'] = (int)$item['id'];
            $item['price'] = (float)$item['price'];
            $item['owner_id'] = (int)$item['owner_id'];
        }

        sendJSON(['success' => true, 'cart' => $cart]);
    } 
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $listingId = (int)($data['listing_id'] ?? 0);
        $action = trim($data['action'] ?? ''); 

        if (empty($action) || (!in_array($action, ['add', 'remove', 'checkout']))) {
            sendJSON(['success' => false, 'message' => 'Action (add/remove/checkout) is required.'], 400);
        }

        if ($action === 'add') {
            if (!$listingId) {
                sendJSON(['success' => false, 'message' => 'Listing ID is required.'], 400);
            }
            
            // Check availability
            $lstStmt = $pdo->prepare('SELECT owner_id, availability FROM listings WHERE id = ?');
            $lstStmt->execute([$listingId]);
            $listing = $lstStmt->fetch();
            
            if (!$listing) {
                sendJSON(['success' => false, 'message' => 'Listing not found.'], 404);
            }
            if ($listing['availability'] !== 'available') {
                sendJSON(['success' => false, 'message' => 'Item is not available.'], 409);
            }
            if ((int)$listing['owner_id'] === $user['id']) {
                sendJSON(['success' => false, 'message' => 'You cannot buy your own item.'], 400);
            }

            $stmt = $pdo->prepare('INSERT IGNORE INTO cart (user_id, listing_id) VALUES (?, ?)');
            $stmt->execute([$user['id'], $listingId]);
            sendJSON(['success' => true, 'message' => 'Added to cart.']);
        } 
        elseif ($action === 'remove') {
            if (!$listingId) {
                sendJSON(['success' => false, 'message' => 'Listing ID is required.'], 400);
            }
            $stmt = $pdo->prepare('DELETE FROM cart WHERE user_id = ? AND listing_id = ?');
            $stmt->execute([$user['id'], $listingId]);
            sendJSON(['success' => true, 'message' => 'Removed from cart.']);
        } 
        elseif ($action === 'checkout') {
            // Fetch all items in user's cart
            $stmt = $pdo->prepare('SELECT c.listing_id, l.title, l.owner_id, l.availability 
                                    FROM cart c 
                                    JOIN listings l ON c.listing_id = l.id 
                                    WHERE c.user_id = ?');
            $stmt->execute([$user['id']]);
            $items = $stmt->fetchAll();

            if (empty($items)) {
                sendJSON(['success' => false, 'message' => 'Your cart is empty.'], 400);
            }

            // Begin transaction to ensure consistency
            $pdo->beginTransaction();

            $reqStmt = $pdo->prepare('INSERT INTO requests (listing_id, requester_id, requester_role, status) VALUES (?, ?, \'buyer\', \'pending\')');
            $lstStmt = $pdo->prepare('UPDATE listings SET availability = \'requested\' WHERE id = ?');
            $notifStmt = $pdo->prepare('INSERT INTO notifications (user_id, title, message) VALUES (?, \'New Purchase Request\', ?)');

            foreach ($items as $item) {
                if ($item['availability'] !== 'available') {
                    // Rollback if any item was taken in the meantime
                    $pdo->rollBack();
                    sendJSON(['success' => false, 'message' => "Item \"{$item['title']}\" is no longer available."], 409);
                }

                $reqStmt->execute([$item['listing_id'], $user['id']]);
                $lstStmt->execute([$item['listing_id']]);
                
                $msg = "{$user['name']} wants to buy your \"{$item['title']}\".";
                $notifStmt->execute([$item['owner_id'], $msg]);
            }

            // Clear cart
            $clearStmt = $pdo->prepare('DELETE FROM cart WHERE user_id = ?');
            $clearStmt->execute([$user['id']]);

            $pdo->commit();

            sendJSON(['success' => true, 'message' => 'Checkout complete. Sellers have been notified.']);
        }
    } else {
        sendJSON(['success' => false, 'message' => 'Invalid request method.'], 405);
    }
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    sendJSON(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
}
?>
