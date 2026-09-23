<?php
/**
 * NexUse — base controller.
 *
 * MVC layer: Core. Controllers take the request, ask Models for data, and hand
 * that data to a View. They hold no SQL and no HTML.
 */

declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    /**
     * Render a view inside the site layout.
     *
     * @param array<string, mixed> $data
     */
    protected function view(string $template, array $data = []): void
    {
        View::render($template, $data);
    }

    /**
     * Redirect to an application path and stop.
     */
    protected function redirect(string $path): never
    {
        $base = rtrim((string) Config::get('base_url', ''), '/');
        header('Location: ' . $base . '/' . ltrim($path, '/'));
        exit;
    }

    /**
     * Redirect back to the page the request came from.
     */
    protected function back(string $fallback = '/'): never
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '';

        if (is_string($referer) && $referer !== '') {
            $path = parse_url($referer, PHP_URL_PATH);
            $host = parse_url($referer, PHP_URL_HOST);

            // Only follow a referer pointing at this same site.
            if ($path !== null && ($host === null || $host === ($_SERVER['HTTP_HOST'] ?? ''))) {
                header('Location: ' . $referer);
                exit;
            }
        }

        $this->redirect($fallback);
    }

    /**
     * Stop with a 404 page.
     */
    protected function notFound(string $message = 'That page could not be found.'): never
    {
        http_response_code(404);
        View::render('errors.404', ['pageTitle' => 'Not found', 'message' => $message]);
        exit;
    }

    /**
     * Stop with a 403 page.
     */
    protected function forbidden(string $message = 'You do not have access to that.'): never
    {
        http_response_code(403);
        View::render('errors.403', ['pageTitle' => 'Not allowed', 'message' => $message]);
        exit;
    }

    /**
     * Verify the CSRF token on a POST request.
     */
    protected function verifyCsrf(): void
    {
        Session::verifyCsrf();
    }

    /**
     * Queue a flash message for the next page.
     */
    protected function flash(string $type, string $message): void
    {
        Session::flash($type, $message);
    }

    /**
     * Remember the submitted form and the errors it produced, then go back to it.
     *
     * @param array<string, string> $errors
     */
    protected function redirectWithErrors(string $path, array $errors): never
    {
        Session::remember(Request::all());
        Session::putErrors($errors);
        Session::flash('error', 'Please correct the highlighted fields.');

        $this->redirect($path);
    }

    /**
     * Reject anything that is not a POST request.
     */
    protected function requirePost(string $fallback = '/'): void
    {
        if (!Request::isPost()) {
            $this->redirect($fallback);
        }
    }
}
