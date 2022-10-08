<?php

/**
 * The controller for this API's authentication.
 */

namespace App\Auth;

use App\Auth\Manager as AuthManager;
use App\HTTP\AbstractController;
use App\HTTP\Response;

class Controller extends AbstractController {

    /**
     * Call to authenticate a user
     *
     * If successful return appropriate success message with JWT
     * else return appropriate error message
     *
     * @return Response
     */
    public function login(): Response {
        $errors = AuthManager::getErrors($this->request);
        if (!$errors) {
            $jwt = AuthManager::login($this->request);
            if ($jwt) {
                return new Response(200, [
                    "data" => $jwt,
                ]);
            }

            return new Response(401, [
                "error" => "Wrong username and/or password.",
            ]);
        }

        return $this->getInvalidInputResponse($errors);
    }

    /**
     * Call logout, then return appropriate success or error message
     *
     * @return Response
     */
    public function logout(): Response {
        if (AuthManager::logout($this->request)) {
            return new Response(204, [
                "message" => "Successfully logged out.",
            ]);
        }

        return new Response(500, [
            "message" => "Couldn't successfully process your logout request!",
        ]);
    }

    /**
     * Check whether the current user is logged in
     * then return appropriate response depending on check
     *
     * @return Response
     */
    public function getStatus(): Response {
        return new Response(200, [
            "data" => AuthManager::isLoggedIn($this->request),
        ]);
    }
}