<?php
// public/api/listings/update.php
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../config/auth_helper.php';

$user = requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON(['success' => false, 'message' => 'Invalid request method.'], 405);
}

$data = json_decode(file_get_contents('php://input'), true);

$id = (int)($data['id'] ?? 0);
$title = trim($data['title'] ?? '');
$category = trim($data['category'] ?? '');
$condition = trim($data['condition'] ?? '');
$type = trim($data['type'] ?? '');
$price = (float)($data['price'] ?? 0.00);
$location = trim($data['location'] ?? '');
$description = trim($data['description'] ?? '');

if (!$id || empty($title) || empty($category) || empty($condition) || empty($type) || empty($description)) {
    sendJSON(['success' => false, 'message' => 'Listing ID, title, category, condition, type, and description are required.'], 400);
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

    $emojis = [
        'Electronics' => '💻',
        'Tools' => '🔧',
        'Books' => '📚',
        'Clothing' => '🧥',
        'Furniture' => '🛋️',
        'Sports' => '⛺',
        'Other' => '📦'
    ];
    $emoji = $emojis[$category] ?? '📦';

    $stmt = $pdo->prepare('UPDATE listings SET title = ?, category = ?, `condition` = ?, type = ?, price = ?, location = ?, description = ?, emoji = ? WHERE id = ?');
    $stmt->execute([$title, $category, $condition, $type, $price, $location, $description, $emoji, $id]);

    sendJSON([
        'success' => true,
        'message' => 'Listing updated successfully.'
    ]);
} catch (PDOException $e) {
    sendJSON(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
}
?>
