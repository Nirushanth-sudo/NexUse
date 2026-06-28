<?php
// public/api/auth/google.php
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../config/auth_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON(['success' => false, 'message' => 'Invalid request method.'], 405);
}

$data = json_decode(file_get_contents('php://input'), true);
$credential = $data['credential'] ?? '';

if (empty($credential)) {
    sendJSON(['success' => false, 'message' => 'Credential token is required.'], 400);
}

$url = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($credential);
$response = @file_get_contents($url);

if ($response === false) {
    sendJSON(['success' => false, 'message' => 'Failed to verify Google Identity Token.'], 400);
}

$payload = json_decode($response, true);
if (!isset($payload['sub']) || !isset($payload['email'])) {
    sendJSON(['success' => false, 'message' => 'Invalid Google Identity Token.'], 400);
}

$oauth_config_path = __DIR__ . '/../../../config/oauth.json';
if (file_exists($oauth_config_path)) {
    $oauth_config = json_decode(file_get_contents($oauth_config_path), true);
    if (!empty($oauth_config['client_id']) && $oauth_config['client_id'] !== 'YOUR_GOOGLE_CLIENT_ID.apps.googleusercontent.com') {
        if ($payload['aud'] !== $oauth_config['client_id']) {
            sendJSON(['success' => false, 'message' => 'Audience mismatch. Client ID validation failed.'], 400);
        }
    }
}

$googleId = $payload['sub'];
$email = $payload['email'];
$name = $payload['name'] ?? explode('@', $email)[0];

try {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE google_id = ?');
    $stmt->execute([$googleId]);
    $user = $stmt->fetch();

    if (!$user) {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $stmt = $pdo->prepare('UPDATE users SET google_id = ? WHERE id = ?');
            $stmt->execute([$googleId, $user['id']]);
            $user['google_id'] = $googleId;
        } else {
            $username = explode('@', $email)[0];
            $username = preg_replace('/[^a-zA-Z0-9_]/', '', $username);
            if (empty($username)) {
                $username = 'google_' . time();
            }

            $origUsername = $username;
            $suffix = 1;
            while (true) {
                $checkStmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = ?');
                $checkStmt->execute([$username]);
                if ($checkStmt->fetchColumn() == 0) {
                    break;
                }
                $username = $origUsername . $suffix;
                $suffix++;
            }

            $stmt = $pdo->prepare('INSERT INTO users (username, name, email, google_id, role, rating, verified) VALUES (?, ?, ?, ?, \'buyer\', 5.00, 1)');
            $stmt->execute([$username, $name, $email, $googleId]);
            
            $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
            $stmt->execute([$pdo->lastInsertId()]);
            $user = $stmt->fetch();
        }
    }

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
        'message' => 'Logged in successfully with Google.',
        'user' => getLoggedInUser()
    ]);

} catch (PDOException $e) {
    sendJSON(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
}
?>
