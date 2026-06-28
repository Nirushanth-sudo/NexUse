<?php
// public/api/requests/return.php
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../config/auth_helper.php';

$user = requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON(['success' => false, 'message' => 'Invalid request method.'], 405);
}

$data = json_decode(file_get_contents('php://input'), true);

$requestId = (int)($data['request_id'] ?? 0);
$type = trim($data['type'] ?? ''); 
$condition = trim($data['condition'] ?? 'Good');

if (!$requestId || !in_array($type, ['renter_return', 'lender_confirm'])) {
    sendJSON(['success' => false, 'message' => 'Request ID and transaction type (renter_return/lender_confirm) are required.'], 400);
}

try {
    $stmt = $pdo->prepare('SELECT r.*, l.owner_id, l.title AS listing_title 
                            FROM requests r 
                            JOIN listings l ON r.listing_id = l.id 
                            WHERE r.id = ?');
    $stmt->execute([$requestId]);
    $request = $stmt->fetch();

    if (!$request) {
        sendJSON(['success' => false, 'message' => 'Request not found.'], 404);
    }

    if ($type === 'renter_return') {
        if ((int)$request['requester_id'] !== $user['id']) {
            sendJSON(['success' => false, 'message' => 'Forbidden: Only the renter can log a return.'], 403);
        }

        $upStmt = $pdo->prepare('UPDATE requests SET actual_return = CURRENT_DATE() WHERE id = ?');
        $upStmt->execute([$requestId]);

        $notifStmt = $pdo->prepare('INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)');
        $notifStmt->execute([
            $request['owner_id'],
            'Item Returned 📦',
            "{$user['name']} has returned \"{$request['listing_title']}\". Please verify condition and log return."
        ]);

        sendJSON(['success' => true, 'message' => 'Return logged. Owner has been notified.']);
    } else {
        if ((int)$request['owner_id'] !== $user['id'] && $user['role'] !== 'admin') {
            sendJSON(['success' => false, 'message' => 'Forbidden: Only the owner can confirm a return.'], 403);
        }

        $penalty = 0.00;
        if (!empty($request['return_date'])) {
            $dueDate = new DateTime($request['return_date']);
            $today = new DateTime();
            if ($today > $dueDate) {
                $diff = $today->diff($dueDate)->days;
                $penalty = (float)($diff * 10.00); 
            }
        }

        $upStmt = $pdo->prepare('UPDATE requests SET status = \'returned\', actual_return = COALESCE(actual_return, CURRENT_DATE()), penalty = ?, condition_on_return = ? WHERE id = ?');
        $upStmt->execute([$penalty, $condition, $requestId]);

        $lstStmt = $pdo->prepare('UPDATE listings SET availability = \'available\' WHERE id = ?');
        $lstStmt->execute([$request['listing_id']]);

        $notifStmt = $pdo->prepare('INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)');
        $notifMsg = "Your return of \"{$request['listing_title']}\" has been confirmed. Condition logged: {$condition}.";
        if ($penalty > 0) {
            $notifMsg .= " A late penalty of \${$penalty} was applied.";
        }
        $notifStmt->execute([
            $request['requester_id'],
            'Return Confirmed ✅',
            $notifMsg
        ]);

        sendJSON(['success' => true, 'message' => 'Return logged successfully.', 'penalty' => $penalty]);
    }

} catch (PDOException $e) {
    sendJSON(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
}
?>
