<?php
/**
 * NexUse — requests and rentals.
 *
 * MVC layer: Controller.
 * Module owner: Member 2 · interim criterion 3.
 *
 * CRUD on the `requests` entity:
 *   Create  → send()
 *   Read    → sent(), received(), show()
 *   Update  → respond(), markReturned()
 *   Delete  → withdraw()
 *
 * Status flow enforced here:
 *   pending → accepted → completed
 *   pending → rejected | withdrawn
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Models\ItemRequest;
use App\Models\Listing;
use App\Models\Notification;

class RequestController extends Controller
{
    /* ---------------------------------------------------------- CREATE -- */

    /**
     * Send a request against a listing.
     */
    public function send(int $listingId): void
    {
        $user    = Auth::requireLogin();
        $listing = Listing::findWithOwner($listingId);

        if ($listing === null || $listing['status'] === 'removed') {
            $this->notFound('That listing is no longer available.');
        }

        $ownerId = (int) $listing['user_id'];
        $userId  = (int) $user['user_id'];

        if ($ownerId === $userId) {
            $this->flash('error', 'You cannot request your own listing.');
            $this->redirect('/listings/' . $listingId);
        }

        if ($listing['status'] !== 'available') {
            $this->flash('error', 'That item is not currently available.');
            $this->redirect('/listings/' . $listingId);
        }

        $existing = ItemRequest::liveFor($listingId, $userId);

        if ($existing !== null) {
            $this->flash('info', 'You already have a request on this item.');
            $this->redirect('/requests/' . (int) $existing['request_id']);
        }

        $type       = request_type_for((string) $listing['listing_type']);
        $returnable = is_returnable((string) $listing['listing_type']);

        if (!Request::isPost()) {
            $this->view('requests.send', [
                'pageTitle'  => 'Request this item',
                'navActive'  => 'requests',
                'narrow'     => true,
                'listing'    => $listing,
                'type'       => $type,
                'returnable' => $returnable,
                'errors'     => Session::takeErrors(),
            ]);

            return;
        }

        $this->verifyCsrf();

        $message   = Request::post('message');
        $startDate = Request::post('start_date');
        $endDate   = Request::post('return_date');
        $errors    = [];

        if ($message !== '' && mb_strlen($message) > 1000) {
            $errors['message'] = 'Keep your message under 1000 characters.';
        }

        if ($returnable) {
            $today = date('Y-m-d');

            if ($startDate === '') {
                $errors['start_date'] = 'Choose the date you need it from.';
            } elseif (!$this->isValidDate($startDate)) {
                $errors['start_date'] = 'Enter a valid date.';
            } elseif ($startDate < $today) {
                $errors['start_date'] = 'The start date cannot be in the past.';
            }

            if ($endDate === '') {
                $errors['return_date'] = 'Choose the date you will return it.';
            } elseif (!$this->isValidDate($endDate)) {
                $errors['return_date'] = 'Enter a valid date.';
            } elseif ($startDate !== '' && $endDate < $startDate) {
                $errors['return_date'] = 'The return date must be on or after the start date.';
            }
        }

        if (!empty($errors)) {
            $this->redirectWithErrors('/requests/send/' . $listingId, $errors);
        }

        $requestId = ItemRequest::create([
            'listing_id'   => $listingId,
            'requester_id' => $userId,
            'owner_id'     => $ownerId,
            'request_type' => $type,
            'message'      => $message !== '' ? $message : null,
            'status'       => 'pending',
            'start_date'   => $returnable && $startDate !== '' ? $startDate : null,
            'return_date'  => $returnable && $endDate !== '' ? $endDate : null,
        ]);

        Notification::raise(
            $ownerId,
            'request',
            'New request on your listing',
            $user['name'] . ' requested "' . $listing['title'] . '".',
            '/requests/' . $requestId
        );

        $this->flash('success', 'Request sent. You will be notified when the owner responds.');
        $this->redirect('/requests/' . $requestId);
    }

    /* ------------------------------------------------------------ READ -- */

    /**
     * Requests this user sent to other people.
     */
    public function sent(): void
    {
        $user   = Auth::requireLogin();
        $status = Request::queryString('status');

        $this->view('requests.index', [
            'pageTitle' => 'Requests I sent',
            'navActive' => 'requests',
            'side'      => 'sent',
            'requests'  => ItemRequest::sentBy((int) $user['user_id'], $status),
            'counts'    => ItemRequest::statusCounts((int) $user['user_id'], 'sent'),
            'status'    => $status,
        ]);
    }

    /**
     * Requests other people sent to this user.
     */
    public function received(): void
    {
        $user   = Auth::requireLogin();
        $status = Request::queryString('status');

        $this->view('requests.index', [
            'pageTitle' => 'Requests received',
            'navActive' => 'requests',
            'side'      => 'received',
            'requests'  => ItemRequest::receivedBy((int) $user['user_id'], $status),
            'counts'    => ItemRequest::statusCounts((int) $user['user_id'], 'received'),
            'status'    => $status,
        ]);
    }

    /**
     * One request in full, from either party's point of view.
     */
    public function show(int $id): void
    {
        $user    = Auth::requireLogin();
        $request = ItemRequest::findDetailed($id);

        if ($request === null) {
            $this->notFound('That request could not be found.');
        }

        $userId = (int) $user['user_id'];

        if (!ItemRequest::involves($request, $userId) && $user['role'] !== 'admin') {
            $this->forbidden('That request is between two other people.');
        }

        $isOwner = (int) $request['owner_id'] === $userId;

        $this->view('requests.show', [
            'pageTitle'  => 'Request · ' . $request['listing_title'],
            'navActive'  => 'requests',
            'request'    => $request,
            'isOwner'    => $isOwner,
            'returnable' => is_returnable((string) $request['listing_type']),
            'myReview'   => \App\Models\Review::byReviewerForRequest($id, $userId),
            'errors'     => Session::takeErrors(),
        ]);
    }

    /* ---------------------------------------------------------- UPDATE -- */

    /**
     * The owner accepts or rejects a pending request.
     */
    public function respond(int $id): void
    {
        $user = Auth::requireLogin();
        $this->requirePost('/requests/' . $id);
        $this->verifyCsrf();

        $request = ItemRequest::findDetailed($id);

        if ($request === null) {
            $this->notFound('That request could not be found.');
        }

        if ((int) $request['owner_id'] !== (int) $user['user_id']) {
            $this->forbidden('Only the item owner can respond to this request.');
        }

        if ($request['status'] !== 'pending') {
            $this->flash('error', 'That request has already been answered.');
            $this->redirect('/requests/' . $id);
        }

        $decision = Request::post('decision');
        $note     = Request::post('owner_note');

        if (!in_array($decision, ['accept', 'reject'], true)) {
            $this->flash('error', 'Choose whether to accept or decline.');
            $this->redirect('/requests/' . $id);
        }

        $listingId = (int) $request['listing_id'];

        if ($decision === 'accept') {
            $update = [
                'status'     => 'accepted',
                'owner_note' => $note !== '' ? $note : null,
            ];

            // The owner may adjust the dates while accepting a rental or loan.
            if (is_returnable((string) $request['listing_type'])) {
                $start = Request::post('start_date');
                $end   = Request::post('return_date');

                if ($start !== '' && $this->isValidDate($start)) {
                    $update['start_date'] = $start;
                }
                if ($end !== '' && $this->isValidDate($end)) {
                    $update['return_date'] = $end;
                }
            }

            ItemRequest::update($id, $update);

            // The item is now promised to someone.
            Listing::setStatus($listingId, 'reserved');

            Notification::raise(
                (int) $request['requester_id'],
                'accepted',
                'Your request was accepted',
                $user['name'] . ' accepted your request for "' . $request['listing_title'] . '".',
                '/requests/' . $id
            );

            $this->flash('success', 'Request accepted. Arrange the handover between yourselves.');
        } else {
            ItemRequest::update($id, [
                'status'     => 'rejected',
                'owner_note' => $note !== '' ? $note : null,
            ]);

            Notification::raise(
                (int) $request['requester_id'],
                'rejected',
                'Your request was declined',
                $user['name'] . ' declined your request for "' . $request['listing_title'] . '".',
                '/requests/' . $id
            );

            $this->flash('success', 'Request declined.');
        }

        $this->redirect('/requests/' . $id);
    }

    /**
     * The owner records that a rented or borrowed item has come back, which
     * completes the exchange and unlocks reviews for both parties.
     */
    public function markReturned(int $id): void
    {
        $user    = Auth::requireLogin();
        $request = ItemRequest::findDetailed($id);

        if ($request === null) {
            $this->notFound('That request could not be found.');
        }

        if ((int) $request['owner_id'] !== (int) $user['user_id']) {
            $this->forbidden('Only the item owner can close this request.');
        }

        if ($request['status'] !== 'accepted') {
            $this->flash('error', 'Only an accepted request can be completed.');
            $this->redirect('/requests/' . $id);
        }

        $returnable = is_returnable((string) $request['listing_type']);

        if (!Request::isPost()) {
            $this->view('requests.complete', [
                'pageTitle'  => 'Complete this exchange',
                'navActive'  => 'requests',
                'formWidth'  => true,
                'request'    => $request,
                'returnable' => $returnable,
                'errors'     => Session::takeErrors(),
            ]);

            return;
        }

        $this->verifyCsrf();

        $errors = [];
        $update = ['status' => 'completed'];

        if ($returnable) {
            $returnedOn = Request::post('actual_return_date', date('Y-m-d'));
            $condition  = Request::post('return_condition', 'as_given');

            if (!$this->isValidDate($returnedOn)) {
                $errors['actual_return_date'] = 'Enter a valid date.';
            }

            if (!in_array($condition, ItemRequest::RETURN_CONDITIONS, true)) {
                $errors['return_condition'] = 'Choose the condition it came back in.';
            }

            $update['actual_return_date'] = $returnedOn;
            $update['return_condition']   = $condition;
        }

        $note = Request::post('owner_note');
        if ($note !== '') {
            $update['owner_note'] = $note;
        }

        if (!empty($errors)) {
            $this->redirectWithErrors('/requests/' . $id . '/return', $errors);
        }

        ItemRequest::update($id, $update);

        // A sold or donated item is gone; a returned one is available again.
        Listing::setStatus(
            (int) $request['listing_id'],
            $returnable ? 'available' : 'completed'
        );

        Notification::raise(
            (int) $request['requester_id'],
            'returned',
            $returnable ? 'Return confirmed' : 'Exchange completed',
            $user['name'] . ' completed the exchange for "' . $request['listing_title']
                . '". You can now leave a review.',
            '/reviews/write/' . $id
        );

        $this->flash('success', 'Exchange completed. You can now review the other person.');
        $this->redirect('/requests/' . $id);
    }

    /* ---------------------------------------------------------- DELETE -- */

    /**
     * The requester withdraws a request they sent.
     */
    public function withdraw(int $id): void
    {
        $user = Auth::requireLogin();
        $this->requirePost('/requests/' . $id);
        $this->verifyCsrf();

        $request = ItemRequest::findDetailed($id);

        if ($request === null) {
            $this->notFound('That request could not be found.');
        }

        if ((int) $request['requester_id'] !== (int) $user['user_id']) {
            $this->forbidden('You can only withdraw your own requests.');
        }

        if (!in_array($request['status'], ['pending', 'accepted'], true)) {
            $this->flash('error', 'That request can no longer be withdrawn.');
            $this->redirect('/requests/' . $id);
        }

        $wasAccepted = $request['status'] === 'accepted';

        ItemRequest::update($id, ['status' => 'withdrawn']);

        // Releasing an accepted request puts the item back on the market.
        if ($wasAccepted) {
            Listing::setStatus((int) $request['listing_id'], 'available');
        }

        Notification::raise(
            (int) $request['owner_id'],
            'withdrawn',
            'A request was withdrawn',
            $user['name'] . ' withdrew their request for "' . $request['listing_title'] . '".',
            '/requests/' . $id
        );

        $this->flash('success', 'Request withdrawn.');
        $this->redirect('/requests/sent');
    }

    /* ---------------------------------------------------------- helpers -- */

    /**
     * Is this a real calendar date in YYYY-MM-DD form?
     */
    private function isValidDate(string $date): bool
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return false;
        }

        [$y, $m, $d] = array_map('intval', explode('-', $date));

        return checkdate($m, $d, $y);
    }
}
