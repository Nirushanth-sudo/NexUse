<?php
/**
 * NexUse — authentication and access control.
 *
 * MVC layer: Core. Holds who is signed in and enforces who may see what.
 */

declare(strict_types=1);

namespace App\Core;

use App\Models\User;

class Auth
{
    /** @var array<string, mixed>|null */
    private static ?array $user = null;

    private static bool $loaded = false;

    /**
     * Sign a user in, rotating the session id to prevent fixation.
     */
    public static function login(int $userId): void
    {
        session_regenerate_id(true);
        Session::put('user_id', $userId);

        self::$user   = null;
        self::$loaded = false;
    }

    public static function logout(): void
    {
        Session::destroy();

        self::$user   = null;
        self::$loaded = false;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    /**
     * The signed-in user's row, or null.
     *
     * @return array<string, mixed>|null
     */
    public static function user(): ?array
    {
        if (self::$loaded) {
            return self::$user;
        }

        self::$loaded = true;

        $userId = Session::get('user_id');

        if (empty($userId)) {
            return null;
        }

        $user = User::find((int) $userId);

        // The account was deleted or suspended while the session was live.
        if ($user === null) {
            Session::destroy();

            return null;
        }

        if ($user['status'] === 'suspended') {
            Session::destroy();
            Session::flash('error', 'Your account has been suspended. Contact the administrator.');

            return null;
        }

        return self::$user = $user;
    }

    public static function id(): ?int
    {
        $user = self::user();

        return $user === null ? null : (int) $user['user_id'];
    }

    public static function isAdmin(): bool
    {
        $user = self::user();

        return $user !== null && $user['role'] === 'admin';
    }

    /**
     * Does the signed-in user own this record?
     */
    public static function owns(int $ownerId): bool
    {
        return self::id() !== null && self::id() === $ownerId;
    }

    /* ------------------------------------------------------------ guards -- */

    /**
     * Require a signed-in member, sending visitors to the login page first.
     *
     * @return array<string, mixed>
     */
    public static function requireLogin(): array
    {
        $user = self::user();

        if ($user === null) {
            Session::put('redirect_after_login', Request::uri());
            Session::flash('error', 'Please sign in to continue.');
            self::redirect('/login');
        }

        return $user;
    }

    /**
     * Require an administrator.
     *
     * @return array<string, mixed>
     */
    public static function requireAdmin(): array
    {
        $user = self::requireLogin();

        if ($user['role'] !== 'admin') {
            http_response_code(403);
            Session::flash('error', 'That area is for administrators only.');
            self::redirect('/');
        }

        return $user;
    }

    /**
     * Require that nobody is signed in — for the login and register pages.
     */
    public static function requireGuest(): void
    {
        if (self::check()) {
            self::redirect('/');
        }
    }

    private static function redirect(string $path): never
    {
        $base = rtrim((string) Config::get('base_url', ''), '/');
        header('Location: ' . $base . '/' . ltrim($path, '/'));
        exit;
    }
}
