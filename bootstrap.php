<?php
/**
 * NexUse — application bootstrap.
 *
 * Loaded by the front controller before anything else. Defines the base path,
 * registers the class autoloader, starts the session and loads view helpers.
 */

declare(strict_types=1);

define('BASE_PATH', __DIR__);

/**
 * PSR-4 style autoloader: App\Core\Router → app/Core/Router.php
 */
spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $path     = BASE_PATH . '/app/' . str_replace('\\', '/', $relative) . '.php';

    if (is_file($path)) {
        require $path;
    }
});

require_once BASE_PATH . '/app/Helpers/functions.php';

use App\Core\Config;
use App\Core\Session;

if (Config::get('debug')) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
}

date_default_timezone_set('Asia/Colombo');

Session::start();
