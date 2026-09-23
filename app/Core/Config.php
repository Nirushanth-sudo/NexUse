<?php
/**
 * NexUse — configuration access.
 *
 * MVC layer: Core. Loads config/config.local.php once and serves values from it.
 */

declare(strict_types=1);

namespace App\Core;

class Config
{
    /** @var array<string, mixed>|null */
    private static ?array $values = null;

    /**
     * Every configuration value.
     *
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        if (self::$values === null) {
            $file = BASE_PATH . '/config/config.local.php';

            if (!is_file($file)) {
                http_response_code(500);
                exit(
                    'Configuration missing. Copy config/config.local.example.php to '
                    . 'config/config.local.php and fill in your database details.'
                );
            }

            self::$values = require $file;
        }

        return self::$values;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::all()[$key] ?? $default;
    }
}
