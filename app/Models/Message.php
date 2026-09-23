<?php
/**
 * NexUse — Message model.
 *
 * MVC layer: Model. Owns the `messages` table.
 * Module owner: Member 2 (pairs with the requests module).
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class Message extends Model
{
    protected static string $table = 'messages';
    protected static string $key   = 'message_id';

    public const MAX_LENGTH = 2000;

    /**
     * Every message in a thread, oldest first, with the sender's name.
     *
     * @return list<array<string, mixed>>
     */
    public static function forConversation(int $conversationId): array
    {
        return Database::all(
            'SELECT m.*, u.name AS sender_name
             FROM messages m
             JOIN users u ON u.user_id = m.sender_id
             WHERE m.conversation_id = ?
             ORDER BY m.created_at ASC, m.message_id ASC',
            [$conversationId]
        );
    }

    /**
     * Post a message and bump its thread.
     */
    public static function post(int $conversationId, int $senderId, string $body): int
    {
        $messageId = self::create([
            'conversation_id' => $conversationId,
            'sender_id'       => $senderId,
            'body'            => $body,
        ]);

        Conversation::touch($conversationId);

        return $messageId;
    }

    /**
     * Mark everything the other person sent in this thread as read.
     */
    public static function markThreadRead(int $conversationId, int $readerId): int
    {
        return Database::run(
            'UPDATE messages SET is_read = 1
              WHERE conversation_id = ? AND sender_id <> ? AND is_read = 0',
            [$conversationId, $readerId]
        )->rowCount();
    }

    /**
     * How many messages exist in a thread.
     */
    public static function countForConversation(int $conversationId): int
    {
        return self::count('conversation_id = ?', [$conversationId]);
    }
}
