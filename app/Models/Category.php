<?php
/**
 * NexUse — Category model.
 *
 * MVC layer: Model. Owns the `categories` table.
 * Module owner: Member 1 (listings); maintained through Member 4's admin area.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class Category extends Model
{
    protected static string $table = 'categories';
    protected static string $key   = 'category_id';

    /**
     * Every category, alphabetically.
     *
     * @return list<array<string, mixed>>
     */
    public static function ordered(): array
    {
        return self::all('name ASC');
    }

    /**
     * Every category with how many listings use it.
     *
     * @return list<array<string, mixed>>
     */
    public static function withCounts(): array
    {
        return Database::all(
            'SELECT c.*,
                    (SELECT COUNT(*) FROM listings l WHERE l.category_id = c.category_id) AS listing_count
             FROM categories c
             ORDER BY c.name ASC'
        );
    }

    /**
     * Turn a name into a URL-safe slug.
     */
    public static function slugify(string $name): string
    {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';

        return trim($slug, '-');
    }

    /**
     * Is this slug already used by another category?
     */
    public static function slugTaken(string $slug, ?int $ignoreId = null): bool
    {
        if ($ignoreId === null) {
            return self::exists('slug = ?', [$slug]);
        }

        return self::exists('slug = ? AND category_id <> ?', [$slug, $ignoreId]);
    }
}
