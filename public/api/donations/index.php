<?php
// public/api/donations/index.php
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../config/auth_helper.php';

try {
    $sql = "SELECT d.*, u.name AS requester_name 
            FROM donation_requests d 
            JOIN users u ON d.requester_id = u.id 
            WHERE 1=1";
    
    $params = [];

    if (!empty($_GET['category'])) {
        $categories = explode(',', $_GET['category']);
        $inParams = [];
        foreach ($categories as $index => $cat) {
            $key = "cat_" . $index;
            $inParams[] = ":" . $key;
            $params[$key] = trim($cat);
        }
        $sql .= " AND d.category IN (" . implode(',', $inParams) . ")";
    }

    if (!empty($_GET['location'])) {
        $sql .= " AND d.location = :location";
        $params['location'] = $_GET['location'];
    }

    $sql .= " ORDER BY d.id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $requests = $stmt->fetchAll();

    foreach ($requests as &$r) {
        $r['id'] = (int)$r['id'];
        $r['requester_id'] = (int)$r['requester_id'];
        $r['rating'] = (float)$r['rating'];
    }

    sendJSON(['success' => true, 'donation_requests' => $requests]);

} catch (PDOException $e) {
    sendJSON(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
}
?>
