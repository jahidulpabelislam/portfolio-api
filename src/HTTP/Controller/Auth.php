<?php

/**
 * The controller for this API's authentication.
 */

namespace App\HTTP\Controller;

use App\Auth\Manager as AuthManager;
use App\HTTP\Controller;
use App\HTTP\Response;

class Auth extends Controller {

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
            return self::getLoggedOutResponse();
        }

        return self::getUnsuccessfulLogOutResponse();
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
