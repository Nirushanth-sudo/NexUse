<?php
/**
 * NexUse — session, flash messages and CSRF tokens.
 *
 * MVC layer: Core.
 */

declare(strict_types=1);

namespace App\Core;

class Session
{
    public static function start(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
            session_start();
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function put(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    /**
     * Empty the session and issue a fresh, empty one.
     */
    public static function destroy(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires'  => time() - 42000,
                'path'     => $params['path'],
                'domain'   => $params['domain'],
                'secure'   => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Lax',
            ]);
        }

        session_destroy();
        session_start();
        session_regenerate_id(true);
    }

    /* ------------------------------------------------------------ flash -- */

    /**
     * Queue a message for the next page render.
     *
     * @param string $type success | error | info | warning
     */
    public static function flash(string $type, string $message): void
    {
        $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
    }

    /**
     * Take every queued message, clearing the queue.
     *
     * @return list<array{type: string, message: string}>
     */
    public static function takeFlash(): array
    {
        $messages = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);

        return $messages;
    }

    /* ------------------------------------------------------------- CSRF -- */

    public static function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    /**
     * Check a submitted token, stopping the request when it does not match.
     */
    public static function verifyCsrf(): void
    {
        $submitted = $_POST['csrf_token'] ?? '';

        if (!is_string($submitted) || !hash_equals(self::csrfToken(), $submitted)) {
            http_response_code(419);
            exit('Your session expired, or the form was not submitted from NexUse. Go back and try again.');
        }
    }

    /* -------------------------------------------------- remembered input -- */

    /**
     * Remember submitted values so a rejected form can be redrawn filled in.
     *
     * @param array<string, mixed> $data
     */
    public static function remember(array $data): void
    {
        unset($data['password'], $data['password_confirm'], $data['csrf_token']);
        $_SESSION['old_input'] = $data;
    }

    public static function old(string $field, mixed $default = ''): mixed
    {
        return $_SESSION['old_input'][$field] ?? $default;
    }

    public static function forgetOld(): void
    {
        unset($_SESSION['old_input']);
    }

    /**
     * Remember validation errors across a redirect.
     *
     * @param array<string, string> $errors
     */
    public static function putErrors(array $errors): void
    {
        $_SESSION['errors'] = $errors;
    }

    /**
     * @return array<string, string>
     */
    public static function takeErrors(): array
    {
        $errors = $_SESSION['errors'] ?? [];
        unset($_SESSION['errors']);

        return $errors;
    }
}
