<?php
// public/api/auth/register.php
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../config/auth_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON(['success' => false, 'message' => 'Invalid request method.'], 405);
}

$data = json_decode(file_get_contents('php://input'), true);

$name = trim($data['name'] ?? '');
$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';
$phone = trim($data['phone'] ?? '');
$location = trim($data['location'] ?? '');
$address = trim($data['address'] ?? '');

if (empty($name) || empty($email) || empty($password) || empty($location)) {
    sendJSON(['success' => false, 'message' => 'Name, email, password, and location are required.'], 400);
}

if (strlen($password) < 6) {
    sendJSON(['success' => false, 'message' => 'Password must be at least 6 characters long.'], 400);
}

$username = explode('@', $email)[0];
$username = preg_replace('/[^a-zA-Z0-9_]/', '', $username);
if (empty($username)) {
    $username = 'user_' . time();
}

try {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetchColumn() > 0) {
        sendJSON(['success' => false, 'message' => 'Email address is already registered.'], 409);
    }

    $origUsername = $username;
    $suffix = 1;
    while (true) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = ?');
        $stmt->execute([$username]);
        if ($stmt->fetchColumn() == 0) {
            break;
        }
        $username = $origUsername . $suffix;
        $suffix++;
    }

    $passwordHash = password_hash($password, PASSWORD_BCRYPT);

    $stmt = $pdo->prepare('INSERT INTO users (username, name, email, password, role, rating, verified, phone, location, address) VALUES (?, ?, ?, ?, \'buyer\', 5.00, 0, ?, ?, ?)');
    $stmt->execute([$username, $name, $email, $passwordHash, $phone, $location, $address]);

    sendJSON([
        'success' => true,
        'message' => 'Registration successful. You can now log in.'
    ]);
} catch (PDOException $e) {
    sendJSON(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
}
?>
