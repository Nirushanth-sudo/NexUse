<?php
/**
 * NexUse — Review model.
 *
 * MVC layer: Model. Owns the `reviews` table.
 * Module owner: Member 3 (reviews and complaints).
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class Review extends Model
{
    protected static string $table = 'reviews';
    protected static string $key   = 'review_id';

    /**
     * One review with both parties and the item it concerns.
     *
     * @return array<string, mixed>|null
     */
    public static function findDetailed(int $reviewId): ?array
    {
        return Database::one(
            'SELECT rv.*,
                    reviewer.name AS reviewer_name,
                    reviewee.name AS reviewee_name,
                    l.title AS listing_title, l.listing_id
             FROM reviews rv
             JOIN users reviewer ON reviewer.user_id = rv.reviewer_id
             JOIN users reviewee ON reviewee.user_id = rv.reviewee_id
             JOIN requests r ON r.request_id = rv.request_id
             JOIN listings l ON l.listing_id = r.listing_id
             WHERE rv.review_id = ?',
            [$reviewId]
        );
    }

    /**
     * Reviews written about a user, newest first.
     *
     * @return list<array<string, mixed>>
     */
    public static function about(int $userId): array
    {
        return Database::all(
            'SELECT rv.*, reviewer.name AS reviewer_name, reviewer.avatar_path AS reviewer_avatar,
                    l.title AS listing_title, l.listing_id
             FROM reviews rv
             JOIN users reviewer ON reviewer.user_id = rv.reviewer_id
             JOIN requests r ON r.request_id = rv.request_id
             JOIN listings l ON l.listing_id = r.listing_id
             WHERE rv.reviewee_id = ?
             ORDER BY rv.created_at DESC',
            [$userId]
        );
    }

    /**
     * Reviews this user wrote about other people.
     *
     * @return list<array<string, mixed>>
     */
    public static function writtenBy(int $userId): array
    {
        return Database::all(
            'SELECT rv.*, reviewee.name AS reviewee_name, reviewee.avatar_path AS reviewee_avatar,
                    l.title AS listing_title, l.listing_id
             FROM reviews rv
             JOIN users reviewee ON reviewee.user_id = rv.reviewee_id
             JOIN requests r ON r.request_id = rv.request_id
             JOIN listings l ON l.listing_id = r.listing_id
             WHERE rv.reviewer_id = ?
             ORDER BY rv.created_at DESC',
            [$userId]
        );
    }

    /**
     * The review this user already wrote for this request, if any.
     *
     * @return array<string, mixed>|null
     */
    public static function byReviewerForRequest(int $requestId, int $reviewerId): ?array
    {
        return Database::one(
            'SELECT * FROM reviews WHERE request_id = ? AND reviewer_id = ?',
            [$requestId, $reviewerId]
        );
    }

    /**
     * How many stars each rating value has received, for a rating breakdown.
     *
     * @return array<int, int>
     */
    public static function distributionFor(int $userId): array
    {
        $distribution = array_fill_keys([1, 2, 3, 4, 5], 0);

        $rows = Database::all(
            'SELECT rating, COUNT(*) AS total FROM reviews WHERE reviewee_id = ? GROUP BY rating',
            [$userId]
        );

        foreach ($rows as $row) {
            $distribution[(int) $row['rating']] = (int) $row['total'];
        }

        return $distribution;
    }

    /**
     * The newest reviews, for the admin dashboard.
     *
     * @return list<array<string, mixed>>
     */
    public static function latest(int $limit = 6): array
    {
        return Database::all(
            'SELECT rv.*, reviewer.name AS reviewer_name, reviewee.name AS reviewee_name
             FROM reviews rv
             JOIN users reviewer ON reviewer.user_id = rv.reviewer_id
             JOIN users reviewee ON reviewee.user_id = rv.reviewee_id
             ORDER BY rv.created_at DESC
             LIMIT ' . (int) $limit
        );
    }

    /**
     * The average rating across the whole platform.
     */
    public static function platformAverage(): ?float
    {
        $value = Database::value('SELECT AVG(rating) FROM reviews');

        return $value === null ? null : round((float) $value, 2);
    }
}
