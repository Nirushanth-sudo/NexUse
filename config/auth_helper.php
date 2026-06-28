<?php
// config/auth_helper.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function getLoggedInUser() {
    if (isset($_SESSION['user_id'])) {
        return [
            'id' => (int)$_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'name' => $_SESSION['name'],
            'email' => $_SESSION['email'],
            'role' => $_SESSION['role'],
            'rating' => (float)$_SESSION['rating'],
            'verified' => (bool)$_SESSION['verified'],
            'phone' => $_SESSION['phone'],
            'location' => $_SESSION['location'],
            'address' => $_SESSION['address']
        ];
    }
    return null;
}

function requireLogin() {
    $user = getLoggedInUser();
    if (!$user) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized: Please log in first.']);
        exit;
    }
    return $user;
}

function requireRole($allowedRoles) {
    $user = requireLogin();
    if (!in_array($user['role'], (array)$allowedRoles)) {
        header('Content-Type: application/json');
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Forbidden: You do not have permission.']);
        exit;
    }
    return $user;
}

function sendJSON($data, $statusCode = 200) {
    header('Content-Type: application/json');
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}
?>
