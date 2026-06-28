<?php
// public/api/auth/login.php
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../config/auth_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON(['success' => false, 'message' => 'Invalid request method.'], 405);
}

$data = json_decode(file_get_contents('php://input'), true);
$usernameOrEmail = trim($data['username'] ?? '');
$password = $data['password'] ?? '';

if (empty($usernameOrEmail) || empty($password)) {
    sendJSON(['success' => false, 'message' => 'Username/Email and password are required.'], 400);
}

try {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ? OR email = ?');
    $stmt->execute([$usernameOrEmail, $usernameOrEmail]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['rating'] = $user['rating'];
        $_SESSION['verified'] = $user['verified'];
        $_SESSION['phone'] = $user['phone'];
        $_SESSION['location'] = $user['location'];
        $_SESSION['address'] = $user['address'];

        sendJSON([
            'success' => true,
            'message' => 'Login successful.',
            'user' => getLoggedInUser()
        ]);
    } else {
        sendJSON(['success' => false, 'message' => 'Invalid username/email or password.'], 401);
    }
} catch (PDOException $e) {
    sendJSON(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
}
?>
