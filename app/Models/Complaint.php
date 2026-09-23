<?php
/**
 * NexUse — Complaint model.
 *
 * MVC layer: Model. Owns the `complaints` table.
 * Module owner: Member 3 (reviews and complaints); resolved by Member 4's admin area.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class Complaint extends Model
{
    protected static string $table = 'complaints';
    protected static string $key   = 'complaint_id';

    public const STATUSES = ['open', 'reviewing', 'resolved', 'dismissed'];

    /**
     * One complaint with everyone involved.
     *
     * @return array<string, mixed>|null
     */
    public static function findDetailed(int $complaintId): ?array
    {
        return Database::one(
            'SELECT c.*,
                    complainant.name AS complainant_name, complainant.email AS complainant_email,
                    against.name AS against_name,
                    l.title AS listing_title
             FROM complaints c
             JOIN users complainant ON complainant.user_id = c.complainant_id
             LEFT JOIN users against ON against.user_id = c.against_user_id
             LEFT JOIN listings l ON l.listing_id = c.listing_id
             WHERE c.complaint_id = ?',
            [$complaintId]
        );
    }

    /**
     * Complaints this user filed.
     *
     * @return list<array<string, mixed>>
     */
    public static function filedBy(int $userId): array
    {
        return Database::all(
            'SELECT c.*, against.name AS against_name, l.title AS listing_title
             FROM complaints c
             LEFT JOIN users against ON against.user_id = c.against_user_id
             LEFT JOIN listings l ON l.listing_id = c.listing_id
             WHERE c.complainant_id = ?
             ORDER BY c.created_at DESC',
            [$userId]
        );
    }

    /**
     * Every complaint, for the admin queue, optionally filtered by status.
     *
     * @return list<array<string, mixed>>
     */
    public static function queue(string $status = ''): array
    {
        $where  = '1 = 1';
        $params = [];

        if ($status !== '' && in_array($status, self::STATUSES, true)) {
            $where    = 'c.status = ?';
            $params[] = $status;
        }

        return Database::all(
            "SELECT c.*,
                    complainant.name AS complainant_name,
                    against.name AS against_name,
                    l.title AS listing_title
             FROM complaints c
             JOIN users complainant ON complainant.user_id = c.complainant_id
             LEFT JOIN users against ON against.user_id = c.against_user_id
             LEFT JOIN listings l ON l.listing_id = c.listing_id
             WHERE {$where}
             ORDER BY
               CASE c.status WHEN 'open' THEN 0 WHEN 'reviewing' THEN 1 ELSE 2 END,
               c.created_at DESC",
            $params
        );
    }

    /**
     * How many complaints sit at each status.
     *
     * @return array<string, int>
     */
    public static function countsByStatus(): array
    {
        $counts = array_fill_keys(self::STATUSES, 0);

        foreach (Database::all('SELECT status, COUNT(*) AS total FROM complaints GROUP BY status') as $row) {
            $counts[(string) $row['status']] = (int) $row['total'];
        }

        return $counts;
    }

    /**
     * Complaints still needing attention.
     */
    public static function openCount(): int
    {
        return self::count("status IN ('open', 'reviewing')");
    }
}
