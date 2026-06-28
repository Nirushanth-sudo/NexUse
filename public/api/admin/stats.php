<?php
// public/api/admin/stats.php
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../config/auth_helper.php';

$user = requireRole('admin');

try {
    $stmt = $pdo->query('SELECT COUNT(*) FROM users');
    $totalUsers = (int)$stmt->fetchColumn();

    $stmt = $pdo->query('SELECT COUNT(*) FROM listings WHERE availability = \'available\'');
    $activeListings = (int)$stmt->fetchColumn();

    $stmt = $pdo->query('SELECT COUNT(*) FROM requests');
    $totalTransactions = (int)$stmt->fetchColumn();

    $stmt = $pdo->query('SELECT COUNT(*) FROM disputes WHERE status = \'pending\'');
    $openDisputes = (int)$stmt->fetchColumn();

    $stmt = $pdo->query('SELECT type, COUNT(*) as count FROM listings GROUP BY type');
    $distribution = $stmt->fetchAll();

    $distributionMap = [
        'buy' => 0,
        'rent' => 0,
        'donate' => 0,
        'share' => 0,
        'disposal' => 0
    ];
    foreach ($distribution as $row) {
        if (isset($distributionMap[$row['type']])) {
            $distributionMap[$row['type']] = (int)$row['count'];
        }
    }

    sendJSON([
        'success' => true,
        'stats' => [
            'total_users' => $totalUsers,
            'active_listings' => $activeListings,
            'total_transactions' => $totalTransactions,
            'open_disputes' => $openDisputes,
            'distribution' => $distributionMap
        ]
    ]);

} catch (PDOException $e) {
    sendJSON(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
}
?>
