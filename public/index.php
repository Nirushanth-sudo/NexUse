<?php
/**
 * NexUse — front controller.
 *
 * Every request enters here. Nothing else in the application is reachable
 * directly from the web, which is the point of keeping only this file and the
 * assets folder inside public/.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

use App\Core\Request;
use App\Core\Router;

$router = new Router();

require BASE_PATH . '/routes.php';

$router->dispatch(Request::method(), Request::path());
