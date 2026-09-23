<?php
/**
 * NexUse — router script for PHP's built-in development server.
 *
 * Used by start.bat:
 *   php -S localhost:8000 -t public public/router.php
 *
 * Returning false lets the server deliver a real file (CSS, JS, an upload)
 * directly; everything else is handed to the front controller.
 *
 * Apache does the same job through public/.htaccess, so the application runs
 * unchanged under XAMPP.
 */

declare(strict_types=1);

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$file = __DIR__ . $path;

if ($path !== '/' && is_file($file)) {
    return false;
}

require __DIR__ . '/index.php';
