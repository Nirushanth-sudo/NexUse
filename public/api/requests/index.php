<?php
// public/api/requests/index.php
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../config/auth_helper.php';

$user = requireLogin();

$type = $_GET['type'] ?? 'outgoing'; // 'outgoing', 'incoming', 'pledges'

try {
    if ($type === 'incoming') {
        // Requests made by others for the logged-in user's listings
        $stmt = $pdo->prepare('SELECT r.*, l.title AS listing_title, l.type AS listing_type, l.price AS listing_price, u.name AS requester_name 
                                FROM requests r 
                                JOIN listings l ON r.listing_id = l.id 
                                JOIN users u ON r.requester_id = u.id 
                                WHERE l.owner_id = ? AND r.requester_role != \'donor\'
                                ORDER BY r.id DESC');
        $stmt->execute([$user['id']]);
        $requests = $stmt->fetchAll();
    } 
    elseif ($type === 'pledges') {
        // Donation pledges made by donors on the logged-in user's donation requests
        $stmt = $pdo->prepare('SELECT r.*, dr.title AS request_title, u.name AS donor_name 
                                FROM requests r 
                                JOIN donation_requests dr ON r.listing_id = dr.id 
                                JOIN users u ON r.requester_id = u.id 
                                WHERE dr.requester_id = ? AND r.requester_role = \'donor\'
                                ORDER BY r.id DESC');
        $stmt->execute([$user['id']]);
        $requests = $stmt->fetchAll();
    } 
    else {
        // Requests made by the logged-in user to others (purchases, rentals, or pledges made by this user)
        $stmt = $pdo->prepare('SELECT r.*, l.title AS listing_title, l.type AS listing_type, l.price AS listing_price, l.owner_id 
                                FROM requests r 
                                JOIN listings l ON r.listing_id = l.id 
                                WHERE r.requester_id = ? AND r.requester_role != \'donor\'
                                ORDER BY r.id DESC');
        $stmt->execute([$user['id']]);
        $requests = $stmt->fetchAll();
    }

    foreach ($requests as &$r) {
        $r['id'] = (int)$r['id'];
        $r['listing_id'] = (int)$r['listing_id'];
        $r['requester_id'] = (int)$r['requester_id'];
        $r['penalty'] = (float)$r['penalty'];
        if (isset($r['listing_price'])) {
            $r['listing_price'] = (float)$r['listing_price'];
        }
    }

    sendJSON(['success' => true, 'requests' => $requests]);

} catch (PDOException $e) {
    sendJSON(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
}
?>
