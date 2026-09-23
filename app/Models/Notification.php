<?php
/**
 * NexUse — Notification model.
 *
 * MVC layer: Model. Owns the `notifications` table.
 * Module owner: Member 4 (notifications and administration).
 *
 * Notification::raise() is the hook the request, review and complaint modules
 * call whenever something happens that the other party should hear about.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class Notification extends Model
{
    protected static string $table = 'notifications';
    protected static string $key   = 'notification_id';

    public const TYPES = [
        'request', 'accepted', 'rejected', 'withdrawn',
        'returned', 'review', 'complaint', 'broadcast', 'system',
    ];

    /**
     * Raise a notification for one user.
     */
    public static function raise(
        int $userId,
        string $type,
        string $title,
        ?string $message = null,
        ?string $link = null
    ): int {
        return self::create([
            'user_id' => $userId,
            'type'    => in_array($type, self::TYPES, true) ? $type : 'system',
            'title'   => $title,
            'message' => $message,
            'link'    => $link,
            'is_read' => 0,
        ]);
    }

    /**
     * Send the same notification to many users at once — used by admin broadcast.
     *
     * @param list<int> $userIds
     */
    public static function raiseMany(array $userIds, string $type, string $title, ?string $message = null): int
    {
        $sent = 0;

        foreach ($userIds as $userId) {
            self::raise((int) $userId, $type, $title, $message);
            $sent++;
        }

        return $sent;
    }

    /**
     * A user's notifications, newest first.
     *
     * @return list<array<string, mixed>>
     */
    public static function forUser(int $userId, string $filter = '', int $limit = 100): array
    {
        $where  = 'user_id = ?';
        $params = [$userId];

        if ($filter === 'unread') {
            $where .= ' AND is_read = 0';
        } elseif ($filter === 'read') {
            $where .= ' AND is_read = 1';
        }

        return Database::all(
            "SELECT * FROM notifications
             WHERE {$where}
             ORDER BY created_at DESC
             LIMIT " . (int) $limit,
            $params
        );
    }

    /**
     * The few most recent, for the header dropdown.
     *
     * @return list<array<string, mixed>>
     */
    public static function recentForUser(int $userId, int $limit = 6): array
    {
        return Database::all(
            'SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ' . (int) $limit,
            [$userId]
        );
    }

    public static function unreadCount(int $userId): int
    {
        return self::count('user_id = ? AND is_read = 0', [$userId]);
    }

    /**
     * Mark one notification read, but only if it belongs to this user.
     */
    public static function markRead(int $notificationId, int $userId): void
    {
        Database::run(
            'UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND user_id = ?',
            [$notificationId, $userId]
        );
    }

    public static function markUnread(int $notificationId, int $userId): void
    {
        Database::run(
            'UPDATE notifications SET is_read = 0 WHERE notification_id = ? AND user_id = ?',
            [$notificationId, $userId]
        );
    }

    /**
     * Mark every one of this user's notifications read.
     */
    public static function markAllRead(int $userId): int
    {
        return Database::run(
            'UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0',
            [$userId]
        )->rowCount();
    }

    /**
     * Delete one notification, but only if it belongs to this user.
     */
    public static function dismiss(int $notificationId, int $userId): void
    {
        Database::run(
            'DELETE FROM notifications WHERE notification_id = ? AND user_id = ?',
            [$notificationId, $userId]
        );
    }

    /**
     * Delete every notification this user has already read.
     */
    public static function clearRead(int $userId): int
    {
        return Database::run(
            'DELETE FROM notifications WHERE user_id = ? AND is_read = 1',
            [$userId]
        )->rowCount();
    }

    /**
     * Find one, but only if it belongs to this user.
     *
     * @return array<string, mixed>|null
     */
    public static function findForUser(int $notificationId, int $userId): ?array
    {
        return Database::one(
            'SELECT * FROM notifications WHERE notification_id = ? AND user_id = ?',
            [$notificationId, $userId]
        );
    }
}
