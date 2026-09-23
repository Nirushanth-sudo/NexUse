<?php
/**
 * NexUse — admin user management.
 *
 * MVC layer: Controller.
 * Module owner: Member 4 (supporting CRUD set, distinct from self-service sign-up).
 *
 * CRUD on the `users` entity from the administrator's side:
 *   Create  → create()   an admin adds an account
 *   Read    → index()    users by role and status, with search
 *   Update  → edit()     details, role, suspend or reactivate
 *   Delete  → destroy()
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

class AdminUserController extends Controller
{
    /* ------------------------------------------------------------ READ -- */

    public function index(): void
    {
        Auth::requireAdmin();

        $keyword = Request::queryString('q');
        $role    = Request::queryString('role');
        $status  = Request::queryString('status');

        $this->view('admin.users.index', [
            'pageTitle' => 'Users',
            'navActive' => 'admin',
            'adminNav'  => 'users',
            'users'     => User::search($keyword, $role, $status),
            'keyword'   => $keyword,
            'role'      => $role,
            'status'    => $status,
            'counts'    => User::countsByRole(),
            'total'     => User::count(),
        ]);
    }

    /* ---------------------------------------------------------- CREATE -- */

    public function create(): void
    {
        Auth::requireAdmin();

        if (!Request::isPost()) {
            $this->view('admin.users.create', [
                'pageTitle' => 'Add a user',
                'navActive' => 'admin',
                'adminNav'  => 'users',
                'errors'    => Session::takeErrors(),
            ]);

            return;
        }

        $this->verifyCsrf();

        $name     = Request::post('name');
        $email    = strtolower(Request::post('email'));
        $phone    = Request::post('phone');
        $city     = Request::post('city');
        $role     = Request::post('role', 'member');
        $password = (string) Request::raw('password', '');

        $errors = [];

        if ($name === '') {
            $errors['name'] = 'Enter a name.';
        }

        if ($email === '') {
            $errors['email'] = 'Enter an email address.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'That is not a valid email address.';
        } elseif (User::emailTaken($email)) {
            $errors['email'] = 'An account already uses that address.';
        }

        if (mb_strlen($password) < 8) {
            $errors['password'] = 'The password must be at least 8 characters.';
        }

        if (!in_array($role, ['member', 'admin'], true)) {
            $errors['role'] = 'Choose a valid role.';
        }

        if (!empty($errors)) {
            $this->redirectWithErrors('/admin/users/create', $errors);
        }

        $userId = User::create([
            'name'          => $name,
            'email'         => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'phone'         => $phone !== '' ? $phone : null,
            'city'          => $city !== '' ? $city : null,
            'role'          => $role,
            'status'        => 'active',
        ]);

        Notification::raise(
            $userId,
            'system',
            'Your NexUse account was created',
            'An administrator created this account for you. Change your password from your profile.',
            '/profile/password'
        );

        $this->flash('success', 'Account created for ' . $name . '.');
        $this->redirect('/admin/users');
    }

    /* ---------------------------------------------------------- UPDATE -- */

    public function edit(int $id): void
    {
        $admin  = Auth::requireAdmin();
        $target = User::find($id);

        if ($target === null) {
            $this->notFound('That user could not be found.');
        }

        if (!Request::isPost()) {
            $this->view('admin.users.edit', [
                'pageTitle' => 'Edit ' . $target['name'],
                'navActive' => 'admin',
                'adminNav'  => 'users',
                'target'    => $target,
                'isSelf'    => (int) $target['user_id'] === (int) $admin['user_id'],
                'errors'    => Session::takeErrors(),
                'activity'  => [
                    'listings'   => Listing::count('user_id = ?', [$id]),
                    'requests'   => ItemRequest::count('requester_id = ? OR owner_id = ?', [$id, $id]),
                    'reviews'    => Review::count('reviewee_id = ?', [$id]),
                    'complaints' => Complaint::count('against_user_id = ?', [$id]),
                ],
            ]);

            return;
        }

        $this->verifyCsrf();

        $isSelf = (int) $target['user_id'] === (int) $admin['user_id'];

        $name     = Request::post('name');
        $email    = strtolower(Request::post('email'));
        $phone    = Request::post('phone');
        $city     = Request::post('city');
        $role     = Request::post('role', (string) $target['role']);
        $status   = Request::post('status', (string) $target['status']);
        $password = (string) Request::raw('password', '');

        $errors = [];

        if ($name === '') {
            $errors['name'] = 'Enter a name.';
        }

        if ($email === '') {
            $errors['email'] = 'Enter an email address.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'That is not a valid email address.';
        } elseif (User::emailTaken($email, $id)) {
            $errors['email'] = 'Another account already uses that address.';
        }

        if (!in_array($role, ['member', 'admin'], true)) {
            $errors['role'] = 'Choose a valid role.';
        }

        if (!in_array($status, ['active', 'suspended'], true)) {
            $errors['status'] = 'Choose a valid status.';
        }

        if ($password !== '' && mb_strlen($password) < 8) {
            $errors['password'] = 'A new password must be at least 8 characters.';
        }

        // An administrator must not lock themselves out.
        if ($isSelf && ($role !== 'admin' || $status !== 'active')) {
            $errors['role'] = 'You cannot remove your own administrator access.';
        }

        if (!empty($errors)) {
            $this->redirectWithErrors('/admin/users/' . $id . '/edit', $errors);
        }

        $wasSuspended = $target['status'] === 'suspended';

        User::update($id, [
            'name'   => $name,
            'email'  => $email,
            'phone'  => $phone !== '' ? $phone : null,
            'city'   => $city !== '' ? $city : null,
            'role'   => $role,
            'status' => $status,
        ]);

        if ($password !== '') {
            User::setPassword($id, $password);
        }

        // Tell the member when their access changes.
        if ($status === 'suspended' && !$wasSuspended) {
            Notification::raise($id, 'system', 'Your account has been suspended',
                'Contact the administrator if you believe this is a mistake.');
        } elseif ($status === 'active' && $wasSuspended) {
            Notification::raise($id, 'system', 'Your account has been reactivated',
                'You can sign in and use NexUse again.', '/');
        }

        $this->flash('success', 'Account updated.');
        $this->redirect('/admin/users');
    }

    /* ---------------------------------------------------------- DELETE -- */

    public function destroy(int $id): void
    {
        $admin = Auth::requireAdmin();
        $this->requirePost('/admin/users');
        $this->verifyCsrf();

        $target = User::find($id);

        if ($target === null) {
            $this->notFound('That user could not be found.');
        }

        if ((int) $target['user_id'] === (int) $admin['user_id']) {
            $this->flash('error', 'You cannot delete your own account.');
            $this->redirect('/admin/users');
        }

        // The last administrator must not be removed.
        if ($target['role'] === 'admin' && User::count("role = 'admin'") <= 1) {
            $this->flash('error', 'That is the only administrator account. Promote another first.');
            $this->redirect('/admin/users');
        }

        // Listings, requests, reviews and notifications go with them by cascade.
        User::delete($id);

        $this->flash('success', $target['name'] . "'s account and all their content were deleted.");
        $this->redirect('/admin/users');
    }
}
