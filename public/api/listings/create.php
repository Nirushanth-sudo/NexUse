<?php
// public/api/listings/create.php
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../config/auth_helper.php';

$user = requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON(['success' => false, 'message' => 'Invalid request method.'], 405);
}

$data = json_decode(file_get_contents('php://input'), true);

$title = trim($data['title'] ?? '');
$category = trim($data['category'] ?? '');
$condition = trim($data['condition'] ?? '');
$type = trim($data['type'] ?? '');
$price = (float)($data['price'] ?? 0.00);
$location = trim($data['location'] ?? $user['location']);
$description = trim($data['description'] ?? '');

if (empty($title) || empty($category) || empty($condition) || empty($type) || empty($description)) {
    sendJSON(['success' => false, 'message' => 'Title, category, condition, type, and description are required.'], 400);
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

try {
    $stmt = $pdo->prepare('INSERT INTO listings (title, category, `condition`, type, price, availability, owner_id, location, description, emoji) VALUES (?, ?, ?, ?, ?, \'available\', ?, ?, ?, ?)');
    $stmt->execute([$title, $category, $condition, $type, $price, $user['id'], $location, $description, $emoji]);

    sendJSON([
        'success' => true,
        'message' => 'Listing created successfully.',
        'listing_id' => (int)$pdo->lastInsertId()
    ]);
} catch (PDOException $e) {
    sendJSON(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
}
?>
