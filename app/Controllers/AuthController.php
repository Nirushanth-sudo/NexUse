<?php
/**
 * NexUse — authentication.
 *
 * MVC layer: Controller.
 * Module owner: Member 4 · interim criterion 1.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Models\Notification;
use App\Models\User;

class AuthController extends Controller
{
    /**
     * Create an account. Renders on GET, processes on POST.
     */
    public function register(): void
    {
        Auth::requireGuest();

        if (!Request::isPost()) {
            $this->view('auth.register', [
                'pageTitle'  => 'Create account',
                'formWidth'  => true,
                'hideFooter' => true,
                'errors'    => Session::takeErrors(),
            ]);

            return;
        }

        $this->verifyCsrf();

        $name     = Request::post('name');
        $email    = strtolower(Request::post('email'));
        $phone    = Request::post('phone');
        $city     = Request::post('city');
        $password = (string) Request::raw('password', '');
        $confirm  = (string) Request::raw('password_confirm', '');

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
        } elseif (User::emailTaken($email)) {
            $errors['email'] = 'An account already uses that email address. Sign in instead.';
        }

        if ($phone !== '' && !preg_match('/^[0-9+\-\s()]{7,20}$/', $phone)) {
            $errors['phone'] = 'Enter a valid phone number, or leave it blank.';
        }

        if ($password === '') {
            $errors['password'] = 'Choose a password.';
        } elseif (mb_strlen($password) < 8) {
            $errors['password'] = 'Your password must be at least 8 characters.';
        } elseif ($password !== $confirm) {
            $errors['password_confirm'] = 'The two passwords do not match.';
        }

        if (!empty($errors)) {
            $this->redirectWithErrors('/register', $errors);
        }

        $userId = User::register(
            $name,
            $email,
            $password,
            $phone !== '' ? $phone : null,
            $city !== '' ? $city : null
        );

        // Signed in straight away — there is no email verification step.
        Auth::login($userId);

        Notification::raise(
            $userId,
            'system',
            'Welcome to NexUse',
            'Post something you no longer use, or browse what your neighbours have offered.',
            '/browse'
        );

        $this->flash('success', 'Account created. Welcome to NexUse, ' . explode(' ', $name)[0] . '.');
        $this->redirect('/');
    }

    /**
     * Sign in. Renders on GET, processes on POST.
     */
    public function login(): void
    {
        Auth::requireGuest();

        if (!Request::isPost()) {
            $this->view('auth.login', [
                'pageTitle'  => 'Sign in',
                'formWidth'  => true,
                'hideFooter' => true,
                'errors'    => Session::takeErrors(),
            ]);

            return;
        }

        $this->verifyCsrf();

        $email    = strtolower(Request::post('email'));
        $password = (string) Request::raw('password', '');

        if ($email === '' || $password === '') {
            $this->redirectWithErrors('/login', ['form' => 'Enter your email address and password.']);
        }

        $user = User::findByEmail($email);

        // The same message either way, so the form cannot be used to discover
        // which email addresses have accounts.
        if ($user === null || !password_verify($password, (string) $user['password_hash'])) {
            $this->redirectWithErrors('/login', ['form' => 'Those details do not match an account.']);
        }

        if ($user['status'] === 'suspended') {
            $this->redirectWithErrors('/login', [
                'form' => 'That account has been suspended. Contact the administrator.',
            ]);
        }

        // Upgrade the stored hash if PHP's default cost has moved on.
        if (password_needs_rehash((string) $user['password_hash'], PASSWORD_DEFAULT)) {
            User::setPassword((int) $user['user_id'], $password);
        }

        Auth::login((int) $user['user_id']);

        $this->flash('success', 'Signed in as ' . $user['name'] . '.');

        $target = Session::get('redirect_after_login');
        Session::forget('redirect_after_login');

        // Only follow a same-site path, never an absolute URL.
        if (is_string($target) && str_starts_with($target, '/') && !str_starts_with($target, '//')) {
            header('Location: ' . $target);
            exit;
        }

        $this->redirect($user['role'] === 'admin' ? '/admin' : '/');
    }

    /**
     * Sign out. POST only, with a CSRF check, so a stray link cannot do it.
     */
    public function logout(): void
    {
        $this->requirePost();
        $this->verifyCsrf();

        Auth::logout();

        $this->flash('success', 'You have been signed out.');
        $this->redirect('/');
    }
}
