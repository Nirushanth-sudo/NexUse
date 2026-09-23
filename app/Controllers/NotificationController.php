<?php
/**
 * NexUse — notifications.
 *
 * MVC layer: Controller.
 * Module owner: Member 4 · interim criterion 3.
 *
 * CRUD on the `notifications` entity:
 *   Create  → Notification::raise(), called by the request, review and complaint
 *             modules, and by the admin broadcast
 *   Read    → index(), plus the header bell in the layout
 *   Update  → markRead(), markUnread(), markAllRead()
 *   Delete  → dismiss(), clearRead()
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Models\Notification;

class NotificationController extends Controller
{
    /* ------------------------------------------------------------ READ -- */

    public function index(): void
    {
        $user   = Auth::requireLogin();
        $userId = (int) $user['user_id'];
        $filter = Request::queryString('filter');

        $this->view('notifications.index', [
            'pageTitle'     => 'Notifications',
            'navActive'     => 'notifications',
            'notifications' => Notification::forUser($userId, $filter),
            'filter'        => $filter,
            'unread'        => Notification::unreadCount($userId),
            'total'         => Notification::count('user_id = ?', [$userId]),
        ]);
    }

    /* ---------------------------------------------------------- UPDATE -- */

    public function markRead(int $id): void
    {
        $user = Auth::requireLogin();
        $this->requirePost('/notifications');
        $this->verifyCsrf();

        $notification = Notification::findForUser($id, (int) $user['user_id']);

        if ($notification === null) {
            $this->notFound('That notification could not be found.');
        }

        Notification::markRead($id, (int) $user['user_id']);

        // Following the notification's own link is the useful default.
        $link = $notification['link'];

        if (Request::post('follow') === '1' && !empty($link)) {
            $this->redirect((string) $link);
        }

        $this->back('/notifications');
    }

    public function markUnread(int $id): void
    {
        $user = Auth::requireLogin();
        $this->requirePost('/notifications');
        $this->verifyCsrf();

        Notification::markUnread($id, (int) $user['user_id']);

        $this->back('/notifications');
    }

    public function markAllRead(): void
    {
        $user = Auth::requireLogin();
        $this->requirePost('/notifications');
        $this->verifyCsrf();

        $count = Notification::markAllRead((int) $user['user_id']);

        $this->flash('success', $count === 0
            ? 'Nothing was unread.'
            : $count . ' notification' . ($count === 1 ? '' : 's') . ' marked read.');

        $this->back('/notifications');
    }

    /* ---------------------------------------------------------- DELETE -- */

    public function dismiss(int $id): void
    {
        $user = Auth::requireLogin();
        $this->requirePost('/notifications');
        $this->verifyCsrf();

        Notification::dismiss($id, (int) $user['user_id']);

        $this->flash('success', 'Notification dismissed.');
        $this->back('/notifications');
    }

    public function clearRead(): void
    {
        $user = Auth::requireLogin();
        $this->requirePost('/notifications');
        $this->verifyCsrf();

        $count = Notification::clearRead((int) $user['user_id']);

        $this->flash('success', $count === 0
            ? 'There was nothing read to clear.'
            : $count . ' read notification' . ($count === 1 ? '' : 's') . ' cleared.');

        $this->redirect('/notifications');
    }
}
