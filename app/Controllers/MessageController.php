<?php
/**
 * NexUse — direct messaging.
 *
 * MVC layer: Controller.
 * Module owner: Member 2 (pairs with the requests module).
 *
 * Lets an interested member talk to whoever listed an item — seller, lender or
 * donor — before or after sending a formal request. The proposal listed in-app
 * chat as a differentiator against ikman.lk; this is that feature.
 *
 * Deliberately simple: a plain request/response thread, no polling and no
 * WebSockets, because the proposal commits to vanilla PHP and JavaScript with no
 * external libraries.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Models\Conversation;
use App\Models\Listing;
use App\Models\Message;
use App\Models\Notification;

class MessageController extends Controller
{
    /**
     * Every thread this member is part of.
     */
    public function inbox(): void
    {
        $user = Auth::requireLogin();

        $this->view('messages.inbox', [
            'pageTitle'     => 'Messages',
            'navActive'     => 'messages',
            'conversations' => Conversation::inboxFor((int) $user['user_id']),
            'me'            => (int) $user['user_id'],
        ]);
    }

    /**
     * Open a thread from a listing page — creating it on first contact.
     */
    public function start(int $listingId): void
    {
        $user    = Auth::requireLogin();
        $listing = Listing::find($listingId);

        if ($listing === null || $listing['status'] === 'removed') {
            $this->notFound('That listing is no longer available.');
        }

        $ownerId = (int) $listing['user_id'];
        $userId  = (int) $user['user_id'];

        if ($ownerId === $userId) {
            $this->flash('info', 'That is your own listing — replies from interested members arrive in your inbox.');
            $this->redirect('/messages');
        }

        $conversationId = Conversation::findOrCreate($listingId, $userId, $ownerId);

        $this->redirect('/messages/' . $conversationId);
    }

    /**
     * Read one thread, and send into it.
     */
    public function thread(int $conversationId): void
    {
        $user         = Auth::requireLogin();
        $userId       = (int) $user['user_id'];
        $conversation = Conversation::findDetailed($conversationId);

        if ($conversation === null) {
            $this->notFound('That conversation could not be found.');
        }

        if (!Conversation::involves($conversation, $userId) && $user['role'] !== 'admin') {
            $this->forbidden('That conversation is between two other people.');
        }

        if (Request::isPost()) {
            $this->verifyCsrf();

            $body = Request::post('body');

            if ($body === '') {
                $this->flash('error', 'Write something before sending.');
                $this->redirect('/messages/' . $conversationId);
            }

            if (mb_strlen($body) > Message::MAX_LENGTH) {
                $this->flash('error', 'Messages are limited to ' . Message::MAX_LENGTH . ' characters.');
                $this->redirect('/messages/' . $conversationId);
            }

            Message::post($conversationId, $userId, $body);

            $otherId = Conversation::otherParty($conversation, $userId);

            Notification::raise(
                $otherId,
                'message',
                'New message from ' . $user['name'],
                excerpt($body, 90),
                '/messages/' . $conversationId
            );

            $this->redirect('/messages/' . $conversationId);
        }

        // Opening the thread clears its unread badge for whoever is reading.
        Message::markThreadRead($conversationId, $userId);

        $this->view('messages.thread', [
            'pageTitle'    => 'Messages · ' . $conversation['listing_title'],
            'navActive'    => 'messages',
            'conversation' => $conversation,
            'messages'     => Message::forConversation($conversationId),
            'me'           => $userId,
            'otherName'    => (int) $conversation['buyer_id'] === $userId
                ? $conversation['owner_name']
                : $conversation['buyer_name'],
            'otherId'      => Conversation::otherParty($conversation, $userId),
        ]);
    }
}
