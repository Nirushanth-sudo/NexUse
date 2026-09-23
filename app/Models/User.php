<?php
/**
 * NexUse — User model.
 *
 * MVC layer: Model. Owns every query against the `users` table.
 * Module owner: Member 4 (authentication and administration).
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;
use App\Core\Upload;

class User extends Model
{
    protected static string $table = 'users';
    protected static string $key   = 'user_id';

    /**
     * Find an account by email address.
     *
     * @return array<string, mixed>|null
     */
    public static function findByEmail(string $email): ?array
    {
        return Database::one('SELECT * FROM users WHERE email = ?', [strtolower($email)]);
    }

    /**
     * Is this email address already taken?
     */
    public static function emailTaken(string $email, ?int $ignoreUserId = null): bool
    {
        if ($ignoreUserId === null) {
            return self::exists('email = ?', [strtolower($email)]);
        }

        return self::exists('email = ? AND user_id <> ?', [strtolower($email), $ignoreUserId]);
    }

    /**
     * Register a new member and return their id.
     */
    public static function register(string $name, string $email, string $password, ?string $phone, ?string $city): int
    {
        return self::create([
            'name'          => $name,
            'email'         => strtolower($email),
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'phone'         => $phone,
            'city'          => $city,
            'role'          => 'member',
            'status'        => 'active',
        ]);
    }

    /**
     * Replace a user's password.
     */
    public static function setPassword(int $userId, string $password): void
    {
        self::update($userId, ['password_hash' => password_hash($password, PASSWORD_DEFAULT)]);
    }

    /**
     * Replace a user's profile picture, deleting whatever it replaces.
     *
     * The old file is removed here rather than left behind, so the uploads
     * folder does not grow one dead image every time somebody changes theirs.
     */
    public static function setAvatar(int $userId, ?string $path): void
    {
        $current = self::find($userId);

        if ($current !== null && !empty($current['avatar_path']) && $current['avatar_path'] !== $path) {
            Upload::delete((string) $current['avatar_path']);
        }

        self::update($userId, ['avatar_path' => $path]);
    }

    /**
     * A user's average rating and how many reviews it came from.
     *
     * @return array{average: float|null, count: int}
     */
    public static function rating(int $userId): array
    {
        $row = Database::one(
            'SELECT AVG(rating) AS average, COUNT(*) AS total FROM reviews WHERE reviewee_id = ?',
            [$userId]
        );

        return [
            'average' => $row && $row['average'] !== null ? round((float) $row['average'], 1) : null,
            'count'   => (int) ($row['total'] ?? 0),
        ];
    }

    /**
     * Users for the admin list, filtered by role, status and a search term.
     *
     * @return list<array<string, mixed>>
     */
    public static function search(string $keyword = '', string $role = '', string $status = ''): array
    {
        $where  = ['1 = 1'];
        $params = [];

        if ($keyword !== '') {
            $where[]  = '(u.name LIKE ? OR u.email LIKE ?)';
            $params[] = '%' . $keyword . '%';
            $params[] = '%' . $keyword . '%';
        }

        if (in_array($role, ['admin', 'member'], true)) {
            $where[]  = 'u.role = ?';
            $params[] = $role;
        }

        if (in_array($status, ['active', 'suspended'], true)) {
            $where[]  = 'u.status = ?';
            $params[] = $status;
        }

        return Database::all(
            'SELECT u.*,
                    (SELECT COUNT(*) FROM listings l WHERE l.user_id = u.user_id) AS listing_count,
                    (SELECT COUNT(*) FROM requests r WHERE r.requester_id = u.user_id) AS request_count
             FROM users u
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY u.created_at DESC',
            $params
        );
    }

    /**
     * How many users hold each role.
     *
     * @return array<string, int>
     */
    public static function countsByRole(): array
    {
        $counts = [];

        foreach (Database::all('SELECT role, COUNT(*) AS total FROM users GROUP BY role') as $row) {
            $counts[(string) $row['role']] = (int) $row['total'];
        }

        return $counts;
    }
}
