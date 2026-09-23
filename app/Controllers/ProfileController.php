<?php
/**
 * NexUse — profiles.
 *
 * MVC layer: Controller.
 * Module owner: Member 3 (supporting the reviews module).
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Core\Upload;
use App\Models\Complaint;
use App\Models\ItemRequest;
use App\Models\Listing;
use App\Models\Review;
use App\Models\User;

class ProfileController extends Controller
{
    /**
     * The signed-in user's own profile and activity summary.
     */
    public function show(): void
    {
        $user   = Auth::requireLogin();
        $userId = (int) $user['user_id'];

        $this->view('profile.show', [
            'pageTitle' => 'My profile',
            'navActive' => 'profile',
            'user'      => $user,
            'rating'    => User::rating($userId),
            'reviews'   => Review::about($userId),
            'summary'   => [
                'listings'   => Listing::count('user_id = ?', [$userId]),
                'available'  => Listing::count("user_id = ? AND status = 'available'", [$userId]),
                'sent'       => ItemRequest::count('requester_id = ?', [$userId]),
                'received'   => ItemRequest::count('owner_id = ?', [$userId]),
                'completed'  => ItemRequest::count(
                    "status = 'completed' AND (requester_id = ? OR owner_id = ?)",
                    [$userId, $userId]
                ),
                'complaints' => Complaint::count('complainant_id = ?', [$userId]),
            ],
        ]);
    }

    /**
     * Edit your own details.
     */
    public function edit(): void
    {
        $user = Auth::requireLogin();

        if (!Request::isPost()) {
            $this->view('profile.edit', [
                'pageTitle' => 'Edit profile',
                'navActive' => 'profile',
                'formWidth' => true,
                'user'      => $user,
                'errors'    => Session::takeErrors(),
            ]);

            return;
        }

        $this->verifyCsrf();

        $name  = Request::post('name');
        $email = strtolower(Request::post('email'));
        $phone = Request::post('phone');
        $city  = Request::post('city');
        $bio   = Request::post('bio');

        $errors = [];

        if ($name === '') {
            $errors['name'] = 'Enter your name.';
        } elseif (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
            $errors['name'] = 'Your name must be between 2 and 100 characters.';
        }

        if ($email === '') {
            $errors['email'] = 'Enter your email address.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'That does not look like a valid email address.';
        } elseif (User::emailTaken($email, (int) $user['user_id'])) {
            $errors['email'] = 'Another account already uses that email address.';
        }

        if ($phone !== '' && !preg_match('/^[0-9+\-\s()]{7,20}$/', $phone)) {
            $errors['phone'] = 'Enter a valid phone number, or leave it blank.';
        }

        if (mb_strlen($bio) > 500) {
            $errors['bio'] = 'Keep your description under 500 characters.';
        }

        /* Profile picture. Validated by Core\Upload exactly like a listing
           photo — same 2 MB cap, same getimagesize() check, same random name. */
        $avatarPath   = null;
        $removeAvatar = Request::post('remove_avatar') !== '';
        $avatarFile   = Request::file('avatar');

        if ($avatarFile !== null && ($avatarFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $result = Upload::image($avatarFile);

            if (!$result['ok']) {
                $errors['avatar'] = (string) $result['error'];
            } else {
                $avatarPath = (string) $result['path'];
            }
        }

        if (!empty($errors)) {
            // A picture that passed validation but cannot be saved alongside the
            // rest of the form would otherwise be orphaned on disk.
            if ($avatarPath !== null) {
                Upload::delete($avatarPath);
            }

            $this->redirectWithErrors('/profile/edit', $errors);
        }

        User::update((int) $user['user_id'], [
            'name'  => $name,
            'email' => $email,
            'phone' => $phone !== '' ? $phone : null,
            'city'  => $city !== '' ? $city : null,
            'bio'   => $bio !== '' ? $bio : null,
        ]);

        if ($avatarPath !== null) {
            User::setAvatar((int) $user['user_id'], $avatarPath);
        } elseif ($removeAvatar) {
            User::setAvatar((int) $user['user_id'], null);
        }

        $this->flash('success', 'Profile updated.');
        $this->redirect('/profile');
    }

    /**
     * Change your own password.
     */
    public function password(): void
    {
        $user = Auth::requireLogin();

        if (!Request::isPost()) {
            $this->view('profile.password', [
                'pageTitle' => 'Change password',
                'navActive' => 'profile',
                'formWidth' => true,
                'errors'    => Session::takeErrors(),
            ]);

            return;
        }

        $this->verifyCsrf();

        $current = (string) Request::raw('current_password', '');
        $new     = (string) Request::raw('password', '');
        $confirm = (string) Request::raw('password_confirm', '');

        $errors = [];

        if (!password_verify($current, (string) $user['password_hash'])) {
            $errors['current_password'] = 'That is not your current password.';
        }

        if (mb_strlen($new) < 8) {
            $errors['password'] = 'Your new password must be at least 8 characters.';
        } elseif ($new !== $confirm) {
            $errors['password_confirm'] = 'The two passwords do not match.';
        }

        if (!empty($errors)) {
            $this->redirectWithErrors('/profile/password', $errors);
        }

        User::setPassword((int) $user['user_id'], $new);

        $this->flash('success', 'Password changed.');
        $this->redirect('/profile');
    }

    /**
     * Somebody else's public profile: their listings and their reviews.
     */
    public function publicProfile(int $id): void
    {
        /* Members-only. A member's profile carries their city, their whole
           catalogue and every review about them — enough to identify a real
           person — so a guest is sent to sign in first and returned here after. */
        Auth::requireLogin();

        $profile = User::find($id);

        if ($profile === null || $profile['status'] === 'suspended') {
            $this->notFound('That member could not be found.');
        }

        // Your own public profile is just your profile page.
        if (Auth::id() === $id) {
            $this->redirect('/profile');
        }

        $this->view('profile.public', [
            'pageTitle'    => (string) $profile['name'],
            'navActive'    => 'browse',
            'profile'      => $profile,
            'rating'       => User::rating($id),
            'distribution' => Review::distributionFor($id),
            'reviews'      => Review::about($id),
            'listings'     => Listing::forUser($id, 'available'),
            'completed'    => ItemRequest::count(
                "status = 'completed' AND (requester_id = ? OR owner_id = ?)",
                [$id, $id]
            ),
        ]);
    }
}
