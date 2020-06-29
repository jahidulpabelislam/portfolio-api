<?php
/**
 * The controller for this API's Authentication.
 *
 * PHP version 7.1+
 *
 * @author Jahidul Pabel Islam <me@jahidulpabelislam.com>
 * @since Class available since Release: v2.0.0
 * @copyright 2010-2020 JPI
 */

namespace App\Controller;

use App\Controller;
use App\Core;
use App\Entity\User;

class Auth extends Controller {

    /**
     * Call to authenticate a user
     *
     * If successful return appropriate success message with JWT
     * else return appropriate error message
     *
     * @return array The request response to send back
     */
    public function login(): array {
        $data = $this->core->data;
        if (Core::hasRequiredFields(User::class, $data)) {

            $jwt = User::login($data);
            if ($jwt) {
                return [
                    "meta" => [
                        "ok" => true,
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

        return $this->getInvalidFieldsResponse(User::class, $data);
    }

    /**
     * Call logout, then return appropriate success or error message
     *
     * @return array The request response to send back
     */
    public static function logout(): array {
        if (User::logout()) {
            return self::getLoggedOutResponse();
        }

        return self::getUnsuccessfulLogOutResponse();
    }

    /**
     * Check whether the current user is logged in
     * then return appropriate response depending on check
     *
     * @return array The request response to send back
     */
    public static function getAuthStatus(): array {
        if (User::isLoggedIn()) {
            return [
                "meta" => [
                    "ok" => true,
                ],
            ];
        }

        return self::getNotAuthorisedResponse();
    }

}
