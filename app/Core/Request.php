<?php
/**
 * NexUse — the incoming HTTP request.
 *
 * MVC layer: Core. Controllers read input through this rather than touching the
 * superglobals directly.
 *
 * Note: this is the HTTP request. The item-request entity lives in
 * App\Models\ItemRequest.
 */

declare(strict_types=1);

namespace App\Core;

class Request
{
    public static function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public static function isPost(): bool
    {
        return self::method() === 'POST';
    }

    /**
     * The path portion of the URL, without the query string, e.g. "/listings/create".
     */
    public static function path(): string
    {
        $uri  = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';

        $base = rtrim((string) Config::get('base_url', ''), '/');
        if ($base !== '' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base));
        }

        return '/' . trim($path, '/');
    }

    /**
     * The full request URI, used to send a user back where they came from.
     */
    public static function uri(): string
    {
        return $_SERVER['REQUEST_URI'] ?? '/';
    }

    /* --------------------------------------------------------- reading -- */

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    /**
     * A trimmed string from the query string.
     */
    public static function queryString(string $key, string $default = ''): string
    {
        $value = $_GET[$key] ?? $default;

        return is_string($value) ? trim($value) : $default;
    }

    /**
     * A positive integer from the query string, or null when absent or invalid.
     */
    public static function queryInt(string $key): ?int
    {
        $value = $_GET[$key] ?? null;

        if ($value === null || $value === '' || !is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }

    /**
     * A trimmed string from the POST body.
     */
    public static function post(string $key, string $default = ''): string
    {
        $value = $_POST[$key] ?? $default;

        return is_string($value) ? trim($value) : $default;
    }

    /**
     * A raw POST value, untrimmed — used for passwords.
     */
    public static function raw(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $default;
    }

    public static function postInt(string $key, ?int $default = null): ?int
    {
        $value = $_POST[$key] ?? null;

        if ($value === null || $value === '' || !is_numeric($value)) {
            return $default;
        }

        return (int) $value;
    }

    /**
     * Every POST value, for remembering a rejected form.
     *
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        return $_POST;
    }

    /**
     * A single uploaded file, normalised.
     *
     * @return array{name: string, type: string, tmp_name: string, error: int, size: int}|null
     */
    public static function file(string $key): ?array
    {
        if (!isset($_FILES[$key]) || is_array($_FILES[$key]['name'])) {
            return null;
        }

        return $_FILES[$key];
    }

    /**
     * A multi-file upload field, normalised into a list of single files.
     *
     * @return list<array{name: string, type: string, tmp_name: string, error: int, size: int}>
     */
    public static function files(string $key): array
    {
        if (!isset($_FILES[$key]) || !is_array($_FILES[$key]['name'])) {
            return [];
        }

        $files = [];
        $count = count($_FILES[$key]['name']);

        for ($i = 0; $i < $count; $i++) {
            if ((int) $_FILES[$key]['error'][$i] === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $files[] = [
                'name'     => (string) $_FILES[$key]['name'][$i],
                'type'     => (string) $_FILES[$key]['type'][$i],
                'tmp_name' => (string) $_FILES[$key]['tmp_name'][$i],
                'error'    => (int) $_FILES[$key]['error'][$i],
                'size'     => (int) $_FILES[$key]['size'][$i],
            ];
        }

        return $files;
    }
}
