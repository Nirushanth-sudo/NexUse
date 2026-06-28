<?php
// public/api/donations/create.php
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../config/auth_helper.php';

$user = requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON(['success' => false, 'message' => 'Invalid request method.'], 405);
}

$data = json_decode(file_get_contents('php://input'), true);

$title = trim($data['title'] ?? '');
$category = trim($data['category'] ?? '');
$location = trim($data['location'] ?? $user['location']);
$itemType = trim($data['item_type'] ?? '');
$description = trim($data['description'] ?? '');

if (empty($title) || empty($category) || empty($location) || empty($itemType) || empty($description)) {
    sendJSON(['success' => false, 'message' => 'Title, category, location, item type, and description are required.'], 400);
}

try {
    $stmt = $pdo->prepare('INSERT INTO donation_requests (title, category, location, item_type, description, requester_id, rating) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$title, $category, $location, $itemType, $description, $user['id'], $user['rating']]);

    sendJSON([
        'success' => true,
        'message' => 'Donation request created successfully.',
        'donation_request_id' => (int)$pdo->lastInsertId()
    ]);
} catch (PDOException $e) {
    sendJSON(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
}
?>
