<?php
/**
 * NexUse — ItemRequest model.
 *
 * MVC layer: Model. Owns the `requests` table — buy, rent, borrow and donation
 * requests, and the status flow they move through.
 * Module owner: Member 2 (requests and rentals).
 *
 * Named ItemRequest so it does not collide with App\Core\Request, which is the
 * HTTP request.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class ItemRequest extends Model
{
    protected static string $table = 'requests';
    protected static string $key   = 'request_id';

    public const TYPES    = ['buy', 'rent', 'borrow', 'donation'];
    public const STATUSES = ['pending', 'accepted', 'rejected', 'withdrawn', 'completed'];

    public const RETURN_CONDITIONS = ['as_given', 'minor_damage', 'major_damage', 'not_returned'];

    /**
     * One request with its listing and both parties.
     *
     * @return array<string, mixed>|null
     */
    public static function findDetailed(int $requestId): ?array
    {
        return Database::one(
            'SELECT r.*,
                    l.title AS listing_title, l.listing_type, l.price AS listing_price,
                    l.status AS listing_status, l.location AS listing_location,
                    req.name AS requester_name, req.email AS requester_email, req.phone AS requester_phone,
                    own.name AS owner_name, own.email AS owner_email, own.phone AS owner_phone
             FROM requests r
             JOIN listings l ON l.listing_id = r.listing_id
             JOIN users req ON req.user_id = r.requester_id
             JOIN users own ON own.user_id = r.owner_id
             WHERE r.request_id = ?',
            [$requestId]
        );
    }

    /**
     * Requests this user sent.
     *
     * @return list<array<string, mixed>>
     */
    public static function sentBy(int $userId, string $status = ''): array
    {
        $where  = 'r.requester_id = ?';
        $params = [$userId];

        if ($status !== '' && in_array($status, self::STATUSES, true)) {
            $where   .= ' AND r.status = ?';
            $params[] = $status;
        }

        return Database::all(
            "SELECT r.*, l.title AS listing_title, l.listing_type, l.price AS listing_price,
                    own.name AS owner_name, own.avatar_path AS owner_avatar
             FROM requests r
             JOIN listings l ON l.listing_id = r.listing_id
             JOIN users own ON own.user_id = r.owner_id
             WHERE {$where}
             ORDER BY r.created_at DESC",
            $params
        );
    }

    /**
     * Requests other people sent to this user.
     *
     * @return list<array<string, mixed>>
     */
    public static function receivedBy(int $userId, string $status = ''): array
    {
        $where  = 'r.owner_id = ?';
        $params = [$userId];

        if ($status !== '' && in_array($status, self::STATUSES, true)) {
            $where   .= ' AND r.status = ?';
            $params[] = $status;
        }

        return Database::all(
            "SELECT r.*, l.title AS listing_title, l.listing_type, l.price AS listing_price,
                    req.name AS requester_name, req.avatar_path AS requester_avatar
             FROM requests r
             JOIN listings l ON l.listing_id = r.listing_id
             JOIN users req ON req.user_id = r.requester_id
             WHERE {$where}
             ORDER BY
               CASE r.status WHEN 'pending' THEN 0 WHEN 'accepted' THEN 1 ELSE 2 END,
               r.created_at DESC",
            $params
        );
    }

    /**
     * A live (pending or accepted) request by this user on this listing.
     *
     * @return array<string, mixed>|null
     */
    public static function liveFor(int $listingId, int $requesterId): ?array
    {
        return Database::one(
            "SELECT * FROM requests
             WHERE listing_id = ? AND requester_id = ? AND status IN ('pending', 'accepted')
             ORDER BY created_at DESC
             LIMIT 1",
            [$listingId, $requesterId]
        );
    }

    /**
     * How many requests this user has at each status.
     *
     * @return array<string, int>
     */
    public static function statusCounts(int $userId, string $side = 'received'): array
    {
        $column = $side === 'sent' ? 'requester_id' : 'owner_id';

        $counts = array_fill_keys(self::STATUSES, 0);

        $rows = Database::all(
            "SELECT status, COUNT(*) AS total FROM requests WHERE {$column} = ? GROUP BY status",
            [$userId]
        );

        foreach ($rows as $row) {
            $counts[(string) $row['status']] = (int) $row['total'];
        }

        return $counts;
    }

    /**
     * Requests this user completed that they have not reviewed yet.
     *
     * @return list<array<string, mixed>>
     */
    public static function awaitingReviewBy(int $userId): array
    {
        return Database::all(
            "SELECT r.*, l.title AS listing_title, l.listing_type,
                    CASE WHEN r.requester_id = ? THEN own.name ELSE req.name END AS other_name,
                    CASE WHEN r.requester_id = ? THEN own.avatar_path ELSE req.avatar_path END AS other_avatar,
                    CASE WHEN r.requester_id = ? THEN r.owner_id ELSE r.requester_id END AS other_id
             FROM requests r
             JOIN listings l ON l.listing_id = r.listing_id
             JOIN users req ON req.user_id = r.requester_id
             JOIN users own ON own.user_id = r.owner_id
             WHERE r.status = 'completed'
               AND (r.requester_id = ? OR r.owner_id = ?)
               AND NOT EXISTS (
                     SELECT 1 FROM reviews rv
                      WHERE rv.request_id = r.request_id AND rv.reviewer_id = ?
                   )
             ORDER BY r.updated_at DESC",
            // Six placeholders now: the three CASE expressions, the two
            // party checks, and the reviewer check.
            [$userId, $userId, $userId, $userId, $userId, $userId]
        );
    }

    /**
     * Is this user one of the two parties to this request?
     *
     * @param array<string, mixed> $request
     */
    public static function involves(array $request, int $userId): bool
    {
        return (int) $request['requester_id'] === $userId || (int) $request['owner_id'] === $userId;
    }

    /**
     * The other party's user id.
     *
     * @param array<string, mixed> $request
     */
    public static function otherParty(array $request, int $userId): int
    {
        return (int) $request['requester_id'] === $userId
            ? (int) $request['owner_id']
            : (int) $request['requester_id'];
    }

    /**
     * Total completed exchanges across the platform.
     */
    public static function completedCount(): int
    {
        return self::count("status = 'completed'");
    }

    /**
     * How many requests exist at each status, across the platform.
     *
     * @return array<string, int>
     */
    public static function countsByStatus(): array
    {
        $counts = array_fill_keys(self::STATUSES, 0);

        foreach (Database::all('SELECT status, COUNT(*) AS total FROM requests GROUP BY status') as $row) {
            $counts[(string) $row['status']] = (int) $row['total'];
        }

        return $counts;
    }

    /**
     * The newest requests, for the admin dashboard.
     *
     * @return list<array<string, mixed>>
     */
    public static function latest(int $limit = 8): array
    {
        return Database::all(
            'SELECT r.*, l.title AS listing_title,
                    req.name AS requester_name, own.name AS owner_name
             FROM requests r
             JOIN listings l ON l.listing_id = r.listing_id
             JOIN users req ON req.user_id = r.requester_id
             JOIN users own ON own.user_id = r.owner_id
             ORDER BY r.created_at DESC
             LIMIT ' . (int) $limit
        );
    }
}
