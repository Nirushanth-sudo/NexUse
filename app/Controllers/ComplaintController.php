<?php
/**
 * NexUse — complaints and disputes.
 *
 * MVC layer: Controller.
 * Module owner: Member 3 (second CRUD set); resolved in Member 4's admin area.
 *
 * CRUD on the `complaints` entity:
 *   Create  → report()
 *   Read    → mine()
 *   Update  → edit()
 *   Delete  → destroy()
 *
 * The platform records disputes and routes them to an administrator. It does not
 * mediate or enforce outcomes — that is out of scope by the proposal.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Models\Complaint;
use App\Models\Listing;
use App\Models\Notification;
use App\Models\User;

class ComplaintController extends Controller
{
    /* ------------------------------------------------------------ READ -- */

    /**
     * Complaints this user has filed.
     */
    public function mine(): void
    {
        $user = Auth::requireLogin();

        $this->view('complaints.mine', [
            'pageTitle'  => 'My complaints',
            'navActive'  => 'complaints',
            'complaints' => Complaint::filedBy((int) $user['user_id']),
        ]);
    }

    /* ---------------------------------------------------------- CREATE -- */

    /**
     * Report a problem with a user or a listing.
     */
    public function report(): void
    {
        $user = Auth::requireLogin();

        if (!Request::isPost()) {
            $againstId = Request::queryInt('user');
            $listingId = Request::queryInt('listing');

            $this->view('complaints.report', [
                'pageTitle' => 'Report a problem',
                'navActive' => 'complaints',
                'formWidth' => true,
                'against'   => $againstId !== null ? User::find($againstId) : null,
                'listing'   => $listingId !== null ? Listing::find($listingId) : null,
                'errors'    => Session::takeErrors(),
            ]);

            return;
        }

        $this->verifyCsrf();

        $subject     = Request::post('subject');
        $description = Request::post('description');
        $againstId   = Request::postInt('against_user_id');
        $listingId   = Request::postInt('listing_id');

        $errors = $this->validate($subject, $description);

        if ($againstId !== null && $againstId === (int) $user['user_id']) {
            $errors['subject'] = 'You cannot file a complaint against yourself.';
        }

        if ($againstId !== null && User::find($againstId) === null) {
            $againstId = null;
        }

        if ($listingId !== null && Listing::find($listingId) === null) {
            $listingId = null;
        }

        if (!empty($errors)) {
            $this->redirectWithErrors('/complaints/report', $errors);
        }

        $complaintId = Complaint::create([
            'complainant_id'  => $user['user_id'],
            'against_user_id' => $againstId,
            'listing_id'      => $listingId,
            'subject'         => $subject,
            'description'     => $description,
            'status'          => 'open',
        ]);

        // Every administrator hears about it.
        foreach (User::search('', 'admin', 'active') as $admin) {
            Notification::raise(
                (int) $admin['user_id'],
                'complaint',
                'New complaint filed',
                $user['name'] . ' filed a complaint: ' . excerpt($subject, 60),
                '/admin/complaints/' . $complaintId
            );
        }

        $this->flash('success', 'Your report has been sent to an administrator.');
        $this->redirect('/complaints');
    }

    /* ---------------------------------------------------------- UPDATE -- */

    /**
     * Edit a complaint you filed, while it is still open.
     */
    public function edit(int $id): void
    {
        $user      = Auth::requireLogin();
        $complaint = Complaint::findDetailed($id);

        if ($complaint === null) {
            $this->notFound('That complaint could not be found.');
        }

        if ((int) $complaint['complainant_id'] !== (int) $user['user_id']) {
            $this->forbidden('You can only edit complaints you filed.');
        }

        if (in_array($complaint['status'], ['resolved', 'dismissed'], true)) {
            $this->flash('error', 'That complaint has been closed and can no longer be edited.');
            $this->redirect('/complaints');
        }

        if (!Request::isPost()) {
            $this->view('complaints.edit', [
                'pageTitle' => 'Edit complaint',
                'navActive' => 'complaints',
                'formWidth' => true,
                'complaint' => $complaint,
                'errors'    => Session::takeErrors(),
            ]);

            return;
        }

        $this->verifyCsrf();

        $subject     = Request::post('subject');
        $description = Request::post('description');
        $errors      = $this->validate($subject, $description);

        if (!empty($errors)) {
            $this->redirectWithErrors('/complaints/' . $id . '/edit', $errors);
        }

        Complaint::update($id, [
            'subject'     => $subject,
            'description' => $description,
        ]);

        $this->flash('success', 'Complaint updated.');
        $this->redirect('/complaints');
    }

    /* ---------------------------------------------------------- DELETE -- */

    /**
     * Withdraw a complaint you filed.
     */
    public function destroy(int $id): void
    {
        $user = Auth::requireLogin();
        $this->requirePost('/complaints');
        $this->verifyCsrf();

        $complaint = Complaint::find($id);

        if ($complaint === null) {
            $this->notFound('That complaint could not be found.');
        }

        $isAuthor = (int) $complaint['complainant_id'] === (int) $user['user_id'];

        if (!$isAuthor && $user['role'] !== 'admin') {
            $this->forbidden('You can only withdraw complaints you filed.');
        }

        Complaint::delete($id);

        $this->flash('success', 'Complaint withdrawn.');
        $this->redirect($isAuthor ? '/complaints' : '/admin/complaints');
    }

    /* ---------------------------------------------------------- helpers -- */

    /**
     * @return array<string, string>
     */
    private function validate(string $subject, string $description): array
    {
        $errors = [];

        if ($subject === '') {
            $errors['subject'] = 'Give your report a short subject.';
        } elseif (mb_strlen($subject) > 150) {
            $errors['subject'] = 'Keep the subject under 150 characters.';
        }

        if ($description === '') {
            $errors['description'] = 'Describe what happened.';
        } elseif (mb_strlen($description) < 20) {
            $errors['description'] = 'Please give a little more detail — at least 20 characters.';
        }

        return $errors;
    }
}
