<?php
/**
 * NexUse — reviews.
 *
 * MVC layer: Controller.
 * Module owner: Member 3 · interim criterion 3.
 *
 * CRUD on the `reviews` entity:
 *   Create  → write()
 *   Read    → mine()   (plus reviews shown on every public profile)
 *   Update  → edit()
 *   Delete  → destroy()
 *
 * A review can only be written against a request that reached `completed`, and
 * only by one of its two parties.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Models\ItemRequest;
use App\Models\Notification;
use App\Models\Review;
use App\Models\User;

class ReviewController extends Controller
{
    /* ------------------------------------------------------------ READ -- */

    /**
     * Reviews this user wrote, reviews about them, and exchanges still to review.
     */
    public function mine(): void
    {
        $user   = Auth::requireLogin();
        $userId = (int) $user['user_id'];

        $this->view('reviews.mine', [
            'pageTitle' => 'Reviews',
            'navActive' => 'reviews',
            'tab'       => Request::queryString('tab', 'received'),
            'received'  => Review::about($userId),
            'written'   => Review::writtenBy($userId),
            'pending'   => ItemRequest::awaitingReviewBy($userId),
            'rating'    => User::rating($userId),
        ]);
    }

    /* ---------------------------------------------------------- CREATE -- */

    /**
     * Write a review for a completed request.
     */
    public function write(int $requestId): void
    {
        $user    = Auth::requireLogin();
        $userId  = (int) $user['user_id'];
        $request = ItemRequest::findDetailed($requestId);

        if ($request === null) {
            $this->notFound('That exchange could not be found.');
        }

        if (!ItemRequest::involves($request, $userId)) {
            $this->forbidden('You can only review an exchange you took part in.');
        }

        if ($request['status'] !== 'completed') {
            $this->flash('error', 'You can review an exchange once it is complete.');
            $this->redirect('/requests/' . $requestId);
        }

        $existing = Review::byReviewerForRequest($requestId, $userId);

        if ($existing !== null) {
            $this->flash('info', 'You have already reviewed this exchange. You can edit it instead.');
            $this->redirect('/reviews/' . (int) $existing['review_id'] . '/edit');
        }

        $revieweeId   = ItemRequest::otherParty($request, $userId);
        $revieweeName = (int) $request['requester_id'] === $userId
            ? (string) $request['owner_name']
            : (string) $request['requester_name'];

        if (!Request::isPost()) {
            $this->view('reviews.write', [
                'pageTitle'    => 'Write a review',
                'navActive'    => 'reviews',
                'formWidth'    => true,
                'request'      => $request,
                'revieweeName' => $revieweeName,
                'errors'       => Session::takeErrors(),
            ]);

            return;
        }

        $this->verifyCsrf();

        $rating  = Request::postInt('rating', 0) ?? 0;
        $comment = Request::post('comment');
        $errors  = $this->validate($rating, $comment);

        if (!empty($errors)) {
            $this->redirectWithErrors('/reviews/write/' . $requestId, $errors);
        }

        $reviewId = Review::create([
            'request_id'  => $requestId,
            'reviewer_id' => $userId,
            'reviewee_id' => $revieweeId,
            'rating'      => $rating,
            'comment'     => $comment !== '' ? $comment : null,
        ]);

        Notification::raise(
            $revieweeId,
            'review',
            'You received a review',
            $user['name'] . ' left you a ' . $rating . '-star review.',
            '/users/' . $revieweeId
        );

        $this->flash('success', 'Thank you — your review is published.');
        $this->redirect('/reviews?tab=written#review-' . $reviewId);
    }

    /* ---------------------------------------------------------- UPDATE -- */

    /**
     * Edit a review you wrote.
     */
    public function edit(int $id): void
    {
        $user   = Auth::requireLogin();
        $review = Review::findDetailed($id);

        if ($review === null) {
            $this->notFound('That review could not be found.');
        }

        if ((int) $review['reviewer_id'] !== (int) $user['user_id']) {
            $this->forbidden('You can only edit reviews you wrote.');
        }

        if (!Request::isPost()) {
            $this->view('reviews.edit', [
                'pageTitle' => 'Edit review',
                'navActive' => 'reviews',
                'formWidth' => true,
                'review'    => $review,
                'errors'    => Session::takeErrors(),
            ]);

            return;
        }

        $this->verifyCsrf();

        $rating  = Request::postInt('rating', 0) ?? 0;
        $comment = Request::post('comment');
        $errors  = $this->validate($rating, $comment);

        if (!empty($errors)) {
            $this->redirectWithErrors('/reviews/' . $id . '/edit', $errors);
        }

        Review::update($id, [
            'rating'  => $rating,
            'comment' => $comment !== '' ? $comment : null,
        ]);

        $this->flash('success', 'Review updated.');
        $this->redirect('/reviews?tab=written');
    }

    /* ---------------------------------------------------------- DELETE -- */

    /**
     * Delete a review. The author may remove their own; an admin may remove any.
     */
    public function destroy(int $id): void
    {
        $user = Auth::requireLogin();
        $this->requirePost('/reviews');
        $this->verifyCsrf();

        $review = Review::find($id);

        if ($review === null) {
            $this->notFound('That review could not be found.');
        }

        $isAuthor = (int) $review['reviewer_id'] === (int) $user['user_id'];

        if (!$isAuthor && $user['role'] !== 'admin') {
            $this->forbidden('You can only delete reviews you wrote.');
        }

        Review::delete($id);

        // Tell the subject when an administrator removed a review about them.
        if (!$isAuthor) {
            Notification::raise(
                (int) $review['reviewee_id'],
                'system',
                'A review was removed',
                'An administrator removed a review from your profile.',
                '/profile'
            );
        }

        $this->flash('success', 'Review deleted.');
        $this->redirect($isAuthor ? '/reviews?tab=written' : '/admin');
    }

    /* ---------------------------------------------------------- helpers -- */

    /**
     * @return array<string, string>
     */
    private function validate(int $rating, string $comment): array
    {
        $errors = [];

        if ($rating < 1 || $rating > 5) {
            $errors['rating'] = 'Choose a rating from 1 to 5 stars.';
        }

        if (mb_strlen($comment) > 1000) {
            $errors['comment'] = 'Keep your review under 1000 characters.';
        }

        return $errors;
    }
}
