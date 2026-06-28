<?php
// public/api/user/profile.php
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../config/auth_helper.php';

$user = requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON(['success' => false, 'message' => 'Invalid request method.'], 405);
}

$data = json_decode(file_get_contents('php://input'), true);

$name = trim($data['name'] ?? '');
$email = trim($data['email'] ?? '');
$location = trim($data['location'] ?? '');
$phone = trim($data['phone'] ?? '');

if (empty($name) || empty($email) || empty($location)) {
    sendJSON(['success' => false, 'message' => 'Name, email, and location are required.'], 400);
}

try {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ? AND id != ?');
    $stmt->execute([$email, $user['id']]);
    if ($stmt->fetchColumn() > 0) {
        sendJSON(['success' => false, 'message' => 'Email address is already taken by another account.'], 409);
    }

    $stmt = $pdo->prepare('UPDATE users SET name = ?, email = ?, location = ?, phone = ? WHERE id = ?');
    $stmt->execute([$name, $email, $location, $phone, $user['id']]);

    $_SESSION['name'] = $name;
    $_SESSION['email'] = $email;
    $_SESSION['location'] = $location;
    $_SESSION['phone'] = $phone;

    sendJSON([
        'success' => true,
        'message' => 'Profile updated successfully.',
        'user' => getLoggedInUser()
    ]);

} catch (PDOException $e) {
    sendJSON(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
}
?>
