<?php
/**
 * NexUse — routing.
 *
 * MVC layer: Core. Maps a URL path and HTTP method onto a controller method.
 * Routes are declared in /routes.php.
 *
 * Supports one dynamic segment style: {id}, which must be a positive integer and
 * is passed to the controller method as an argument.
 */

declare(strict_types=1);

namespace App\Core;

class Router
{
    /** @var array<string, array<string, array{0: class-string, 1: string}>> */
    private array $routes = ['GET' => [], 'POST' => []];

    /**
     * @param array{0: class-string, 1: string} $action
     */
    public function get(string $path, array $action): void
    {
        $this->routes['GET'][$this->normalise($path)] = $action;
    }

    /**
     * @param array{0: class-string, 1: string} $action
     */
    public function post(string $path, array $action): void
    {
        $this->routes['POST'][$this->normalise($path)] = $action;
    }

    /**
     * Register the same action for GET and POST — used by form pages that render
     * on GET and process on POST.
     *
     * @param array{0: class-string, 1: string} $action
     */
    public function form(string $path, array $action): void
    {
        $this->get($path, $action);
        $this->post($path, $action);
    }

    /**
     * Match the current request and run its controller.
     */
    public function dispatch(string $method, string $path): void
    {
        $method = strtoupper($method);
        $path   = $this->normalise($path);

        if (!isset($this->routes[$method])) {
            $this->fail(405, 'That request method is not supported here.');
        }

        // Exact match first — the common case.
        if (isset($this->routes[$method][$path])) {
            $this->run($this->routes[$method][$path], []);

            return;
        }

        // Then patterns carrying {id}.
        foreach ($this->routes[$method] as $route => $action) {
            if (!str_contains($route, '{')) {
                continue;
            }

            // Swap {id} for a placeholder BEFORE quoting: preg_quote would escape the
            // braces to \{id\} and the substitution would then never match.
            $token   = 'ROUTEPARAM';
            $stencil = preg_replace('#\{[a-z_]+\}#', $token, $route) ?? $route;
            $pattern = '#^' . str_replace($token, '([0-9]+)', preg_quote($stencil, '#')) . '$#';

            if (preg_match($pattern, $path, $matches)) {
                array_shift($matches);
                $this->run($action, array_map('intval', $matches));

                return;
            }
        }

        // A path that exists for the other method is a wrong-method, not a 404.
        $other = $method === 'GET' ? 'POST' : 'GET';
        if (isset($this->routes[$other][$path])) {
            $this->fail(405, 'That address does not accept this kind of request.');
        }

        $this->fail(404, 'That page could not be found.');
    }

    /**
     * @param array{0: class-string, 1: string} $action
     * @param list<int>                         $arguments
     */
    private function run(array $action, array $arguments): void
    {
        [$class, $method] = $action;

        if (!class_exists($class)) {
            $this->fail(500, 'Controller not found: ' . $class);
        }

        $controller = new $class();

        if (!method_exists($controller, $method)) {
            $this->fail(500, 'Action not found: ' . $class . '::' . $method);
        }

        $controller->$method(...$arguments);
    }

    private function normalise(string $path): string
    {
        $path = '/' . trim($path, '/');

        return $path === '/' ? '/' : rtrim($path, '/');
    }

    private function fail(int $status, string $message): never
    {
        http_response_code($status);

        View::render($status === 404 ? 'errors.404' : 'errors.500', [
            'pageTitle' => $status === 404 ? 'Not found' : 'Something went wrong',
            'message'   => $message,
            'status'    => $status,
        ]);

        exit;
    }
}
