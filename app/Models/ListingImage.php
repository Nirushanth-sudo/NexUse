<?php
/**
 * NexUse — ListingImage model.
 *
 * MVC layer: Model. Owns the `listing_images` table.
 * Module owner: Member 1 (listings).
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class ListingImage extends Model
{
    protected static string $table = 'listing_images';
    protected static string $key   = 'image_id';

    public const MAX_PER_LISTING = 5;

    /**
     * Every image for a listing, main one first.
     *
     * @return list<array<string, mixed>>
     */
    public static function forListing(int $listingId): array
    {
        return Database::all(
            'SELECT * FROM listing_images
             WHERE listing_id = ?
             ORDER BY is_primary DESC, image_id ASC',
            [$listingId]
        );
    }

    /**
     * The main image path for a listing, or null when it has none.
     */
    public static function primaryPath(int $listingId): ?string
    {
        $path = Database::value(
            'SELECT image_path FROM listing_images
             WHERE listing_id = ?
             ORDER BY is_primary DESC, image_id ASC
             LIMIT 1',
            [$listingId]
        );

        return $path ? (string) $path : null;
    }

    public static function countForListing(int $listingId): int
    {
        return self::count('listing_id = ?', [$listingId]);
    }

    /**
     * Attach an image to a listing.
     */
    public static function attach(int $listingId, string $path, bool $isPrimary = false): int
    {
        return self::create([
            'listing_id' => $listingId,
            'image_path' => $path,
            'is_primary' => $isPrimary ? 1 : 0,
        ]);
    }

    /**
     * Find an image, but only if it belongs to this listing.
     *
     * @return array<string, mixed>|null
     */
    public static function findForListing(int $imageId, int $listingId): ?array
    {
        return Database::one(
            'SELECT * FROM listing_images WHERE image_id = ? AND listing_id = ?',
            [$imageId, $listingId]
        );
    }

    /**
     * Make one image the main one, demoting the rest.
     */
    public static function makePrimary(int $imageId, int $listingId): void
    {
        Database::run('UPDATE listing_images SET is_primary = 0 WHERE listing_id = ?', [$listingId]);
        Database::run('UPDATE listing_images SET is_primary = 1 WHERE image_id = ?', [$imageId]);
    }

    /**
     * After removing the main image, promote whichever is left.
     */
    public static function promoteFirst(int $listingId): void
    {
        $next = Database::one(
            'SELECT image_id FROM listing_images WHERE listing_id = ? ORDER BY image_id ASC LIMIT 1',
            [$listingId]
        );

        if ($next !== null) {
            Database::run('UPDATE listing_images SET is_primary = 1 WHERE image_id = ?', [$next['image_id']]);
        }
    }

    /**
     * Every stored file path for a listing, so the files can be deleted with it.
     *
     * @return list<string>
     */
    public static function pathsForListing(int $listingId): array
    {
        $rows = Database::all('SELECT image_path FROM listing_images WHERE listing_id = ?', [$listingId]);

        return array_map(static fn(array $row): string => (string) $row['image_path'], $rows);
    }
}
