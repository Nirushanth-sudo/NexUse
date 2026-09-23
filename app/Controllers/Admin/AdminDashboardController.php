<?php
/**
 * NexUse — admin dashboard and broadcast.
 *
 * MVC layer: Controller.
 * Module owner: Member 4.
 */

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Models\Complaint;
use App\Models\ItemRequest;
use App\Models\Listing;
use App\Models\Notification;
use App\Models\Review;
use App\Models\User;

class AdminDashboardController extends Controller
{
    /**
     * Platform overview: totals, breakdowns and the newest activity.
     */
    public function index(): void
    {
        Auth::requireAdmin();

        $this->view('admin.dashboard', [
            'pageTitle'      => 'Admin dashboard',
            'navActive'      => 'admin',
            'adminNav'       => 'dashboard',
            'stats'          => [
                'users'      => User::count(),
                'members'    => User::count("role = 'member'"),
                'suspended'  => User::count("status = 'suspended'"),
                'listings'   => Listing::count(),
                'available'  => Listing::count("status = 'available'"),
                'requests'   => ItemRequest::count(),
                'completed'  => ItemRequest::completedCount(),
                'reviews'    => Review::count(),
                'complaints' => Complaint::openCount(),
            ],
            'listingTypes'   => Listing::countsByType(),
            'requestStatus'  => ItemRequest::countsByStatus(),
            'averageRating'  => Review::platformAverage(),
            'latestListings' => Listing::latest(6),
            'latestRequests' => ItemRequest::latest(6),
            'openComplaints' => Complaint::queue('open'),
        ]);
    }

    /**
     * Send a notification to a role, or to everyone.
     */
    public function broadcast(): void
    {
        $admin = Auth::requireAdmin();

        if (!Request::isPost()) {
            $this->view('admin.broadcast', [
                'pageTitle'   => 'Send a notification',
                'navActive'   => 'admin',
                'adminNav'    => 'broadcast',
                'errors'      => Session::takeErrors(),
                'memberCount' => User::count("role = 'member' AND status = 'active'"),
                'adminCount'  => User::count("role = 'admin' AND status = 'active'"),
            ]);

            return;
        }

        $this->verifyCsrf();

        $audience = Request::post('audience', 'all');
        $title    = Request::post('title');
        $message  = Request::post('message');

        $errors = [];

        if (!in_array($audience, ['all', 'member', 'admin'], true)) {
            $errors['audience'] = 'Choose who should receive this.';
        }

        if ($title === '') {
            $errors['title'] = 'Give the notification a title.';
        } elseif (mb_strlen($title) > 150) {
            $errors['title'] = 'Keep the title under 150 characters.';
        }

        if (mb_strlen($message) > 500) {
            $errors['message'] = 'Keep the message under 500 characters.';
        }

        if (!empty($errors)) {
            $this->redirectWithErrors('/admin/broadcast', $errors);
        }

        $recipients = User::search('', $audience === 'all' ? '' : $audience, 'active');
        $userIds    = array_map(static fn(array $u): int => (int) $u['user_id'], $recipients);

        $sent = Notification::raiseMany(
            $userIds,
            'broadcast',
            $title,
            $message !== '' ? $message : null
        );

        $this->flash('success', 'Sent to ' . $sent . ' ' . ($sent === 1 ? 'person' : 'people') . '.');
        $this->redirect('/admin');
    }
}
