<?php

declare(strict_types=1);

/**
 * Class for storing any helper functions which constructs responses.
 */

namespace App\HTTP;

use JPI\HTTP\Response;

trait Responder {

    /**
     * Response when user isn't logged in correctly
     */
    public static function getNotAuthorisedResponse(): Response {
        return Response::json(401, [
            "message" => "You need to be logged in!",
        ]);
    }

    public function getInvalidInputResponse(array $errors): Response {
        return Response::json(400, [
            "message" => "The necessary data was not provided and/or invalid.",
            "errors" => $errors,
        ]);
    }
}
