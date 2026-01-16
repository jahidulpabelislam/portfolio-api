<?php

declare(strict_types=1);

namespace App\HTTP;

use App\Core;
use JPI\App;
use JPI\HTTP\RequestAwareTrait;
use JPI\HTTP\RequestHandlerInterface;
use JPI\HTTP\RequestMiddlewareInterface;
use JPI\HTTP\Response;

final class CORSMiddleware implements RequestMiddlewareInterface {

    use RequestAwareTrait;

    public function run(RequestHandlerInterface $next): Response {
        $response = $next->handle();

        $originURL = $this->getRequest()->getHeaderString("Origin");

        // Strip the protocol from domain
        $originDomain = str_replace(["https://", "http://"], "", $originURL);

        // If the domain is allowed send set some headers for CORS
        $app = Core::get();
        if (in_array($originDomain, App::get()->config()->allowed_domains->toArray())) {
            $response->withHeader("Access-Control-Allow-Origin", $originURL)
                ->withHeader("Access-Control-Allow-Methods", $app->getRouter()->getMethodsForPath())
                ->withHeader("Access-Control-Allow-Headers", ["Authorization", "Content-Type", "Process-Data", "Cache-Control"])
                ->withHeader("Vary", "Origin")
            ;
        }

        return $response;
    }
}
