<?php
/**
 * NexUse — admin complaint handling.
 *
 * MVC layer: Controller.
 * Module owner: Member 4 (admin area), on Member 3's `complaints` table.
 *
 * The platform records and routes disputes. It does not enforce outcomes — an
 * administrator can mark a complaint reviewed, resolved or dismissed, and leave
 * a note the complainant can see.
 */

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Models\Complaint;
use App\Models\Notification;

class AdminComplaintController extends Controller
{
    public function index(): void
    {
        Auth::requireAdmin();

        $status = Request::queryString('status');

        $this->view('admin.complaints.index', [
            'pageTitle'  => 'Complaints',
            'navActive'  => 'admin',
            'adminNav'   => 'complaints',
            'complaints' => Complaint::queue($status),
            'status'     => $status,
            'counts'     => Complaint::countsByStatus(),
            'total'      => Complaint::count(),
        ]);
    }

    /**
     * One complaint, and the form that updates its status.
     */
    public function show(int $id): void
    {
        Auth::requireAdmin();

        $complaint = Complaint::findDetailed($id);

        if ($complaint === null) {
            $this->notFound('That complaint could not be found.');
        }

        if (!Request::isPost()) {
            $this->view('admin.complaints.show', [
                'pageTitle' => 'Complaint #' . $id,
                'navActive' => 'admin',
                'adminNav'  => 'complaints',
                'complaint' => $complaint,
            ]);

            return;
        }

        $this->verifyCsrf();

        $status = Request::post('status');
        $note   = Request::post('admin_note');

        if (!in_array($status, Complaint::STATUSES, true)) {
            $this->flash('error', 'Choose a valid status.');
            $this->redirect('/admin/complaints/' . $id);
        }

        if (mb_strlen($note) > 500) {
            $this->flash('error', 'Keep the note under 500 characters.');
            $this->redirect('/admin/complaints/' . $id);
        }

        $statusChanged = $complaint['status'] !== $status;

        Complaint::update($id, [
            'status'     => $status,
            'admin_note' => $note !== '' ? $note : null,
        ]);

        if ($statusChanged) {
            Notification::raise(
                (int) $complaint['complainant_id'],
                'complaint',
                'Your complaint was updated',
                'An administrator marked "' . excerpt((string) $complaint['subject'], 50)
                    . '" as ' . $status . '.',
                '/complaints'
            );
        }

        $this->flash('success', 'Complaint marked ' . $status . '.');
        $this->redirect('/admin/complaints');
    }
}
