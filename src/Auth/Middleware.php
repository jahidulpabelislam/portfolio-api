<?php

declare(strict_types=1);

namespace App\Auth;

use App\Auth\Manager as AuthManager;
use JPI\HTTP\RequestAwareTrait;
use JPI\HTTP\RequestHandlerInterface;
use JPI\HTTP\RequestMiddlewareInterface;
use JPI\HTTP\Response;

class Middleware implements RequestMiddlewareInterface {

    use RequestAwareTrait;

    public function run(RequestHandlerInterface $next): Response {
        $request = $this->getRequest();
        $request->setAttribute("is_authenticated", AuthManager::isLoggedIn($request));

        return $next->handle();
    }
}
