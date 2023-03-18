<?php

declare(strict_types=1);

/**
 * A RESTful API router.
 */

namespace App\HTTP;

use App\Core;
use JPI\HTTP\Router as PackageRouter;

class Router extends PackageRouter {

    public function addRoute(string $pattern, string $method, $callback, string $name = null): void {
        parent::addRoute("/v" . Core::VERSION . $pattern, $method, $callback, $name);
    }

    public function getMethodsForPath(): array {
        $path = $this->getRequest()->getPath();

        $methods = [];

        foreach ($this->routes as $route) {
            if (preg_match($route->getRegex(), $path)) {
                $methods[$route->getMethod()] = true;
            }
        }

        return array_keys($methods);
    }
}
