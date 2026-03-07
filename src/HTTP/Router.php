<?php

declare(strict_types=1);

namespace App\HTTP;

use JPI\CRUD\API\Router as PackageRouter;

final class Router extends PackageRouter {

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
