<?php
// public/api/listings/index.php
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../config/auth_helper.php';

try {
    $sql = "SELECT l.*, u.name AS owner_name, u.rating AS owner_rating 
            FROM listings l 
            JOIN users u ON l.owner_id = u.id 
            WHERE 1=1";
    
    $params = [];

    if (!empty($_GET['type'])) {
        $sql .= " AND l.type = :type";
        $params['type'] = $_GET['type'];
    } else {
        $sql .= " AND l.type IN ('buy', 'rent', 'share') AND l.availability != 'rented'";
    }

    if (!empty($_GET['category'])) {
        $categories = explode(',', $_GET['category']);
        $inParams = [];
        foreach ($categories as $index => $cat) {
            $key = "cat_" . $index;
            $inParams[] = ":" . $key;
            $params[$key] = trim($cat);
        }
        $sql .= " AND l.category IN (" . implode(',', $inParams) . ")";
    }

    if (!empty($_GET['condition'])) {
        $sql .= " AND l.condition = :condition";
        $params['condition'] = $_GET['condition'];
    }

    if (!empty($_GET['location'])) {
        $sql .= " AND l.location = :location";
        $params['location'] = $_GET['location'];
    }

    if (!empty($_GET['search'])) {
        $sql .= " AND (l.title LIKE :search OR l.description LIKE :search OR l.category LIKE :search)";
        $params['search'] = '%' . $_GET['search'] . '%';
    }

    $sql .= " ORDER BY l.id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $listings = $stmt->fetchAll();

    $user = getLoggedInUser();
    $wishlistIds = [];
    $cartIds = [];
    if ($user) {
        $wishStmt = $pdo->prepare("SELECT listing_id FROM wishlist WHERE user_id = ?");
        $wishStmt->execute([$user['id']]);
        $wishlistIds = $wishStmt->fetchAll(PDO::FETCH_COLUMN);

        $cartStmt = $pdo->prepare("SELECT listing_id FROM cart WHERE user_id = ?");
        $cartStmt->execute([$user['id']]);
        $cartIds = $cartStmt->fetchAll(PDO::FETCH_COLUMN);
    }

    foreach ($listings as &$l) {
        $l['id'] = (int)$l['id'];
        $l['price'] = (float)$l['price'];
        $l['owner_id'] = (int)$l['owner_id'];
        $l['owner_rating'] = (float)$l['owner_rating'];
        $l['in_wishlist'] = in_array($l['id'], $wishlistIds);
        $l['in_cart'] = in_array($l['id'], $cartIds);
    }

    sendJSON(['success' => true, 'listings' => $listings]);

} catch (PDOException $e) {
    sendJSON(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
}
?>
