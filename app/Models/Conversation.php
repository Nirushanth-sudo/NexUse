<?php
/**
 * NexUse — Conversation model.
 *
 * MVC layer: Model. Owns the `conversations` table.
 * Module owner: Member 2 (pairs with the requests module).
 *
 * One thread per (listing, interested member). The item owner is the other
 * party, whichever of the four exchange types the listing is — so the same
 * thread serves a buyer talking to a seller, a renter to a lender, or a
 * receiver to a donor.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class Conversation extends Model
{
    protected static string $table = 'conversations';
    protected static string $key   = 'conversation_id';

    /**
     * Find the thread for this listing and member, or start one.
     */
    public static function findOrCreate(int $listingId, int $buyerId, int $ownerId): int
    {
        $existing = Database::value(
            'SELECT conversation_id FROM conversations WHERE listing_id = ? AND buyer_id = ?',
            [$listingId, $buyerId]
        );

        if ($existing !== null) {
            return (int) $existing;
        }

        return self::create([
            'listing_id' => $listingId,
            'buyer_id'   => $buyerId,
            'owner_id'   => $ownerId,
        ]);
    }

    /**
     * One thread with the listing and both parties attached.
     *
     * @return array<string, mixed>|null
     */
    public static function findDetailed(int $conversationId): ?array
    {
        return Database::one(
            'SELECT c.*,
                    l.title AS listing_title, l.listing_type, l.price AS listing_price,
                    l.status AS listing_status,
                    b.name AS buyer_name, o.name AS owner_name,
                    b.avatar_path AS buyer_avatar, o.avatar_path AS owner_avatar
             FROM conversations c
             JOIN listings l ON l.listing_id = c.listing_id
             JOIN users b ON b.user_id = c.buyer_id
             JOIN users o ON o.user_id = c.owner_id
             WHERE c.conversation_id = ?',
            [$conversationId]
        );
    }

    /**
     * Every thread this user is part of, most recent first, with the last line
     * of each and how many messages are waiting for them.
     *
     * @return list<array<string, mixed>>
     */
    public static function inboxFor(int $userId): array
    {
        return Database::all(
            'SELECT c.*,
                    l.title AS listing_title, l.listing_type,
                    b.name AS buyer_name, o.name AS owner_name,
                    b.avatar_path AS buyer_avatar, o.avatar_path AS owner_avatar,
                    (SELECT m.body FROM messages m
                      WHERE m.conversation_id = c.conversation_id
                      ORDER BY m.created_at DESC LIMIT 1) AS last_body,
                    (SELECT m.sender_id FROM messages m
                      WHERE m.conversation_id = c.conversation_id
                      ORDER BY m.created_at DESC LIMIT 1) AS last_sender_id,
                    (SELECT COUNT(*) FROM messages m
                      WHERE m.conversation_id = c.conversation_id
                        AND m.sender_id <> ? AND m.is_read = 0) AS unread_count
             FROM conversations c
             JOIN listings l ON l.listing_id = c.listing_id
             JOIN users b ON b.user_id = c.buyer_id
             JOIN users o ON o.user_id = c.owner_id
             WHERE c.buyer_id = ? OR c.owner_id = ?
             ORDER BY c.last_message_at DESC',
            [$userId, $userId, $userId]
        );
    }

    /**
     * Is this user one of the two parties?
     *
     * @param array<string, mixed> $conversation
     */
    public static function involves(array $conversation, int $userId): bool
    {
        return (int) $conversation['buyer_id'] === $userId
            || (int) $conversation['owner_id'] === $userId;
    }

    /**
     * The other party's user id.
     *
     * @param array<string, mixed> $conversation
     */
    public static function otherParty(array $conversation, int $userId): int
    {
        return (int) $conversation['buyer_id'] === $userId
            ? (int) $conversation['owner_id']
            : (int) $conversation['buyer_id'];
    }

    /**
     * Bump the thread so it sorts to the top of both inboxes.
     */
    public static function touch(int $conversationId): void
    {
        Database::run(
            'UPDATE conversations SET last_message_at = NOW() WHERE conversation_id = ?',
            [$conversationId]
        );
    }

    /**
     * Total unread messages across every thread this user is in — for the header badge.
     */
    public static function unreadTotalFor(int $userId): int
    {
        return (int) Database::value(
            'SELECT COUNT(*)
             FROM messages m
             JOIN conversations c ON c.conversation_id = m.conversation_id
             WHERE (c.buyer_id = ? OR c.owner_id = ?)
               AND m.sender_id <> ?
               AND m.is_read = 0',
            [$userId, $userId, $userId],
            0
        );
    }
}
