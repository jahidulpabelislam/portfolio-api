<?php

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
        $path = $this->request->getPath();
        foreach ($this->routes as $routePath => $routes) {
            $pathRegex = $this->pathToRegex($routePath);
            if (preg_match($pathRegex, $path)) {
                return array_keys($routes);
            }
        }

        return [];
    }
}
