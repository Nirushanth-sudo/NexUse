<?php
/**
 * NexUse — Listing model.
 *
 * MVC layer: Model. Owns every query against the `listings` table.
 * Module owner: Member 1 (listings).
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class Listing extends Model
{
    protected static string $table = 'listings';
    protected static string $key   = 'listing_id';

    public const TYPES      = ['sell', 'rent', 'share', 'donate'];
    public const CONDITIONS = ['new', 'like_new', 'good', 'fair', 'poor'];
    public const STATUSES   = ['available', 'reserved', 'completed', 'removed'];

    /**
     * One listing joined with its owner and category.
     *
     * @return array<string, mixed>|null
     */
    public static function findWithOwner(int $listingId): ?array
    {
        return Database::one(
            'SELECT l.*,
                    u.name AS owner_name, u.city AS owner_city, u.created_at AS owner_since,
                    u.avatar_path AS owner_avatar,
                    c.name AS category_name
             FROM listings l
             JOIN users u ON u.user_id = l.user_id
             LEFT JOIN categories c ON c.category_id = l.category_id
             WHERE l.listing_id = ?',
            [$listingId]
        );
    }

    /**
     * Build the WHERE clause shared by search() and searchCount().
     *
     * @param  array<string, mixed> $filters
     * @return array{0: string, 1: list<mixed>}
     */
    private static function buildFilters(array $filters): array
    {
        $where  = ["l.status IN ('available', 'reserved')"];
        $params = [];

        if (!empty($filters['keyword'])) {
            // Category name is included so "electronics" finds the category as
            // well as the word in a title — the search people actually expect.
            $where[]  = '(l.title LIKE ? OR l.description LIKE ? OR c.name LIKE ?)';
            $params[] = '%' . $filters['keyword'] . '%';
            $params[] = '%' . $filters['keyword'] . '%';
            $params[] = '%' . $filters['keyword'] . '%';
        }

        if (!empty($filters['type']) && in_array($filters['type'], self::TYPES, true)) {
            $where[]  = 'l.listing_type = ?';
            $params[] = $filters['type'];
        }

        if (!empty($filters['category'])) {
            $where[]  = 'l.category_id = ?';
            $params[] = (int) $filters['category'];
        }

        if (!empty($filters['condition']) && in_array($filters['condition'], self::CONDITIONS, true)) {
            $where[]  = 'l.item_condition = ?';
            $params[] = $filters['condition'];
        }

        if (!empty($filters['location'])) {
            $where[]  = 'l.location LIKE ?';
            $params[] = '%' . $filters['location'] . '%';
        }

        if (!empty($filters['min_price'])) {
            $where[]  = 'l.price >= ?';
            $params[] = (float) $filters['min_price'];
        }

        if (!empty($filters['max_price'])) {
            $where[]  = '(l.price IS NULL OR l.price <= ?)';
            $params[] = (float) $filters['max_price'];
        }

        // "New or used" — the coarse version of item_condition that buyers ask for.
        if (($filters['newness'] ?? '') === 'new') {
            $where[] = "l.item_condition = 'new'";
        } elseif (($filters['newness'] ?? '') === 'used') {
            $where[] = "l.item_condition <> 'new'";
        }

        return [implode(' AND ', $where), $params];
    }

    /**
     * Search listings with filters, sorting and pagination.
     *
     * @param  array<string, mixed> $filters
     * @return list<array<string, mixed>>
     */
    public static function search(array $filters, string $sort = 'newest', int $limit = 12, int $offset = 0): array
    {
        [$where, $params] = self::buildFilters($filters);

        $order = match ($sort) {
            'price_low'  => 'l.price IS NULL, l.price ASC',
            'price_high' => 'l.price DESC',
            'title'      => 'l.title ASC',
            'oldest'     => 'l.created_at ASC',
            default      => 'l.created_at DESC',
        };

        // With a keyword, a title hit should outrank a description hit.
        if (!empty($filters['keyword']) && $sort === 'relevance') {
            $order  = 'title_hit DESC, l.created_at DESC';
        }

        // Only bind the relevance expression when it is actually ordered on,
        // so the ordinary sorts keep their simple parameter list.
        $select = 'l.*, u.name AS owner_name, c.name AS category_name';

        if (str_starts_with($order, 'title_hit')) {
            $select   = "l.*, u.name AS owner_name, c.name AS category_name,
                         (l.title LIKE ?) AS title_hit";
            $params   = array_merge(['%' . $filters['keyword'] . '%'], $params);
        }

        return Database::all(
            "SELECT {$select}
             FROM listings l
             JOIN users u ON u.user_id = l.user_id
             LEFT JOIN categories c ON c.category_id = l.category_id
             WHERE {$where}
             ORDER BY {$order}
             LIMIT " . (int) $limit . ' OFFSET ' . (int) $offset,
            $params
        );
    }

    /**
     * How many listings match these filters.
     *
     * @param array<string, mixed> $filters
     */
    public static function searchCount(array $filters): int
    {
        [$where, $params] = self::buildFilters($filters);

        return (int) Database::value(
            "SELECT COUNT(*)
             FROM listings l
             LEFT JOIN categories c ON c.category_id = l.category_id
             WHERE {$where}",
            $params,
            0
        );
    }

    /**
     * The most recent available listings of one type.
     *
     * @return list<array<string, mixed>>
     */
    public static function recentByType(string $type, int $limit = 4): array
    {
        return Database::all(
            "SELECT l.*, u.name AS owner_name
             FROM listings l
             JOIN users u ON u.user_id = l.user_id
             WHERE l.listing_type = ? AND l.status = 'available'
             ORDER BY l.created_at DESC
             LIMIT " . (int) $limit,
            [$type]
        );
    }

    /**
     * Every listing belonging to one user.
     *
     * @return list<array<string, mixed>>
     */
    public static function forUser(int $userId, string $status = ''): array
    {
        $where  = 'l.user_id = ?';
        $params = [$userId];

        if ($status !== '' && in_array($status, self::STATUSES, true)) {
            $where   .= ' AND l.status = ?';
            $params[] = $status;
        }

        return Database::all(
            "SELECT l.*, c.name AS category_name,
                    (SELECT COUNT(*) FROM requests r
                      WHERE r.listing_id = l.listing_id AND r.status = 'pending') AS pending_requests,
                    (SELECT COUNT(*) FROM requests r WHERE r.listing_id = l.listing_id) AS total_requests
             FROM listings l
             LEFT JOIN categories c ON c.category_id = l.category_id
             WHERE {$where}
             ORDER BY l.created_at DESC",
            $params
        );
    }

    /**
     * Other available listings from the same owner.
     *
     * @return list<array<string, mixed>>
     */
    public static function othersFromOwner(int $ownerId, int $excludeListingId, int $limit = 4): array
    {
        return Database::all(
            "SELECT l.* FROM listings l
             WHERE l.user_id = ? AND l.listing_id <> ? AND l.status = 'available'
             ORDER BY l.created_at DESC
             LIMIT " . (int) $limit,
            [$ownerId, $excludeListingId]
        );
    }

    /**
     * Items related to this one, for the "you might also like" strip on a
     * listing page.
     *
     * Ranked rather than filtered, so the strip is never empty while anything
     * relevant exists: same category scores highest, then same exchange type,
     * then same location. The listing itself is always excluded.
     *
     * @return list<array<string, mixed>>
     */
    public static function related(int $listingId, int $limit = 4): array
    {
        $listing = self::find($listingId);

        if ($listing === null) {
            return [];
        }

        return Database::all(
            "SELECT l.*, u.name AS owner_name, c.name AS category_name,
                    (
                      (CASE WHEN l.category_id <=> ?  THEN 4 ELSE 0 END) +
                      (CASE WHEN l.listing_type = ?   THEN 2 ELSE 0 END) +
                      (CASE WHEN l.location = ?       THEN 1 ELSE 0 END)
                    ) AS relevance
             FROM listings l
             JOIN users u ON u.user_id = l.user_id
             LEFT JOIN categories c ON c.category_id = l.category_id
             WHERE l.listing_id <> ?
               AND l.status = 'available'
             ORDER BY relevance DESC, l.created_at DESC
             LIMIT " . (int) $limit,
            [
                $listing['category_id'],
                $listing['listing_type'],
                $listing['location'],
                $listingId,
            ]
        );
    }

    /**
     * Change only the availability status.
     */
    public static function setStatus(int $listingId, string $status): void
    {
        if (in_array($status, self::STATUSES, true)) {
            self::update($listingId, ['status' => $status]);
        }
    }

    /**
     * How many listings exist of each type.
     *
     * @return array<string, int>
     */
    public static function countsByType(): array
    {
        $counts = [];

        foreach (Database::all('SELECT listing_type, COUNT(*) AS total FROM listings GROUP BY listing_type') as $row) {
            $counts[(string) $row['listing_type']] = (int) $row['total'];
        }

        return $counts;
    }

    /**
     * The newest listings, for the admin dashboard.
     *
     * @return list<array<string, mixed>>
     */
    public static function latest(int $limit = 8): array
    {
        return Database::all(
            'SELECT l.*, u.name AS owner_name
             FROM listings l
             JOIN users u ON u.user_id = l.user_id
             ORDER BY l.created_at DESC
             LIMIT ' . (int) $limit
        );
    }
}
