<?php

declare(strict_types=1);

/**
 * The controller for this API's authentication.
 */

namespace App\Auth;

use App\Auth\Manager as AuthManager;
use App\HTTP\AbstractController;
use JPI\HTTP\Response;

class Controller extends AbstractController {

    /**
     * Call to authenticate a user.
     *
     * If successful return appropriate success message with JWT else return appropriate error message.
     */
    public function login(): Response {
        $errors = AuthManager::getErrors($this->getRequest());
        if ($errors) {
            return $this->getInvalidInputResponse($errors);
        }

        $jwt = AuthManager::login($this->getRequest());
        if ($jwt) {
            return Response::json(200, [
                "data" => $jwt,
            ]);
        }

        return Response::json(401, [
            "error" => "Wrong username and/or password.",
        ]);
    }

    /**
     * Call logout, then return appropriate success or error message.
     */
    public function logout(): Response {
        if (AuthManager::logout($this->getRequest())) {
            return Response::json(204, [
                "message" => "Successfully logged out.",
            ]);
        }

        return Response::json(500, [
            "message" => "Couldn't successfully process your logout request!",
        ]);
    }

    /**
     * Check whether the current user is logged in then return appropriate response depending on check.
     */
    public function status(): Response {
        return Response::json(200, [
            "data" => $this->getRequest()->getAttribute("is_authenticated"),
        ]);
    }
}
