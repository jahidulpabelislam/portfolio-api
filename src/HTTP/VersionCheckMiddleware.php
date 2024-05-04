<?php

declare(strict_types=1);

namespace App\HTTP;

use App\Core;
use JPI\HTTP\RequestAwareTrait;
use JPI\HTTP\RequestHandlerInterface;
use JPI\HTTP\RequestMiddlewareInterface;
use JPI\HTTP\Response;

final class VersionCheckMiddleware implements RequestMiddlewareInterface {

    use RequestAwareTrait;

    public function run(RequestHandlerInterface $next): Response {
        $request = $this->getRequest();

        $parts = $request->getPathParts();

        $shouldBeVersionPart = "v" . Core::VERSION;
        if ($parts[0] === $shouldBeVersionPart) {
            return $next->handle();
        }

        $parts[0] = $shouldBeVersionPart;

        $shouldBeURL = $request->getURL();
        $shouldBeURL->setPath(implode("/", $parts));

        return Response::json(404, [
            "message" => "Unrecognised API version. Current version is " . Core::VERSION . ", so please update requested URL to $shouldBeURL.",
        ]);
    }
}
