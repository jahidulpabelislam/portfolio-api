<?php

declare(strict_types=1);

/**
 * The base endpoint for all requests for this API.
 */

require_once __DIR__ . "/../bootstrap.php";

use App\Core;

$app = Core::get();
$response = $app->handle();

if ($response->hasHeader("ETag") && $response->getHeaderString("ETag") === $app->getRequest()->getHeaderString("If-None-Match")) {
    $response->withStatus(304)
        ->withBody("")
        ->removeHeader("Content-Type")
    ;
}
else {
    $response->setHeader("Content-Type", "application/json");
}

$response->send();
