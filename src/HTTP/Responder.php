<?php

/**
 * Class for storing any helper functions which constructs responses.
 */

namespace App\HTTP;

trait Responder {

    protected $request;

    public function __construct(Request $request) {
        $this->request = $request;
    }

    /**
     * Response when user isn't logged in correctly
     */
    public static function getNotAuthorisedResponse(): Response {
        return new Response(401, [
            "message" => "You need to be logged in!",
        ]);
    }

    public function getInvalidInputResponse(array $errors): Response {
        return new Response(400, [
            "message" => "The necessary data was not provided and/or invalid.",
            "errors" => $errors,
        ]);
    }
}
