<?php
// public/api/auth/google_client_id.php
require_once __DIR__ . '/../../../config/auth_helper.php';

$configPath = __DIR__ . '/../../../config/oauth.json';
$clientId = 'YOUR_GOOGLE_CLIENT_ID.apps.googleusercontent.com';

if (file_exists($configPath)) {
    $config = json_decode(file_get_contents($configPath), true);
    if (!empty($config['client_id'])) {
        $clientId = $config['client_id'];
    }
}

sendJSON(['success' => true, 'client_id' => $clientId]);
?>
