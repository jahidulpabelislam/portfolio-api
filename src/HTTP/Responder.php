<?php

/**
 * Class for storing any helper functions which constructs responses.
 */

namespace App\HTTP;

use JPI\HTTP\Response;

trait Responder {

    protected $request;

    public function __construct(Request $request) {
        $this->request = $request;
    }

    public function getRequest(): Request {
        return $this->request;
    }

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
