<?php
/**
 * NexUse — view rendering.
 *
 * MVC layer: Core. Renders a template from app/Views with the data a controller
 * passes it. Views contain presentation only — no queries, no business rules.
 */

declare(strict_types=1);

namespace App\Core;

class View
{
    /**
     * Render a view inside the site layout.
     *
     * @param string               $template Dot or slash path, e.g. "listings.create"
     * @param array<string, mixed> $data     Variables made available to the template
     */
    public static function render(string $template, array $data = []): void
    {
        $content = self::capture($template, $data);

        $layoutData = array_merge($data, ['content' => $content]);

        self::output('layouts/app', $layoutData);
    }

    /**
     * Render a view with no layout — used for partials and fragments.
     *
     * @param array<string, mixed> $data
     */
    public static function partial(string $template, array $data = []): void
    {
        self::output($template, $data);
    }

    /**
     * Render a view to a string instead of sending it.
     *
     * @param array<string, mixed> $data
     */
    public static function capture(string $template, array $data = []): string
    {
        ob_start();
        self::output($template, $data);

        return (string) ob_get_clean();
    }

    /**
     * Include a template file with the given data in scope.
     *
     * @param array<string, mixed> $data
     */
    private static function output(string $template, array $data = []): void
    {
        $path = BASE_PATH . '/app/Views/' . str_replace('.', '/', $template) . '.php';

        if (!is_file($path)) {
            http_response_code(500);
            exit('View not found: ' . htmlspecialchars($template, ENT_QUOTES));
        }

        extract($data, EXTR_SKIP);

        require $path;
    }
}
