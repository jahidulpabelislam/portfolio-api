<?php
/**
 * The controller for this API's Authentication.
 *
 * PHP version 7.1+
 *
 * @author Jahidul Pabel Islam <me@jahidulpabelislam.com>
 * @version 2.2.2
 * @since Class available since Release: v2.0.0
 * @copyright 2010-2019 JPI
*/

namespace App\Controller;

if (!defined("ROOT")) {
    die();
}

use App\Core as API;
use App\Responder;
use App\Entity\User;

class Auth {

    /**
     * Call to authenticate a user
     *
     * If successful return appropriate success message with JWT
     * else return appropriate error message
     *
     * @return array
     */
    public static function login(): array {
        if (API::get()->hasRequiredFields(User::class)) {

            $jwt = User::login();
            if ($jwt) {
                return [
                    "meta" => [
                        "ok" => true,
                        "status" => 200,
                        "message" => "OK",
                        "jwt" => $jwt,
                    ],
                ];
            }

            return [
                "meta" => [
                    "status" => 401,
                    "message" => "Unauthorized",
                    "feedback" => "Wrong username and/or password.",
                ],
            ];
        }

        return Responder::get()->getInvalidFieldsResponse(User::class);
    }

    /**
     * Call logout, then return appropriate success or error message
     *
     * @return array
     */
    public static function logout(): array {
        if (User::logout()) {
            return Responder::getLoggedOutResponse();
        }

        return Responder::getUnsuccessfulLogOutResponse();
    }

    /**
     * Check whether the current user is logged in
     * then return appropriate response depending on check
     *
     * @return array
     */
    public static function getAuthStatus(): array {
        if (User::isLoggedIn()) {
            return [
                "meta" => [
                    "ok" => true,
                    "status" => 200,
                    "message" => "OK",
                ],
            ];
        }

        return Responder::getNotAuthorisedResponse();
    }
}
