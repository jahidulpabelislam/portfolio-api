<?php
/**
 * The functions for the handling of authentication for this API.
 *
 * PHP version 7.1+
 *
 * @author Jahidul Pabel Islam <me@jahidulpabelislam.com>
 * @version 1.0.0
 * @since Class available since Release: v3.8.0
 * @copyright 2010-2020 JPI
 */

namespace App\Entity;

use Exception;
use Firebase\JWT\JWT;
use App\Config;
use App\Core as API;

class User extends Entity {

    private const JWT_ALG = "HS512";
    private const JWT_EXPIRATION_HOURS = 6;

    /**
     * Authenticate a user trying to login
     * If successful store generate JWT and return, else return null
     *
     * @return string|null
     */
    public static function login(): ?string {
        $api = API::get();

        $data = $api->data;

        // TODO: Actually do the logging in here (e.g store in cookie, session or database etc.)

        // SAMPLE!!
        $tokenId = 1; // JSON Token Id: an unique identifier for the token
        $issuedAt = time(); // Issued at: time when the token was generated
        $expire = $issuedAt + (self::JWT_EXPIRATION_HOURS * 60 * 60); // Token expiration time
        $serverName = "https://jahidulpabelislam.com/"; // Issuer

        $jwtData = [
            "jti" => $tokenId,
            "iss" => $serverName,
            "iat" => $issuedAt,
            "nbf" => $issuedAt,
            "exp" => $expire,
            "data" => [
                // Any extra API specific data
            ],
        ];

        $secretKey = Config::PORTFOLIO_ADMIN_SECRET_KEY;

        $jwt = JWT::encode($jwtData, $secretKey, self::JWT_ALG);
        return $jwt;
    }

    /**
     * Do the log out here (e.g removing cookie, session or database etc.)
     *
     * @return bool
     */
    public static function logout(): bool {
        // TODO: Actually do the log out here (e.g removing cookie, session or database etc.)
        return true;
    }

    /**
     * Check whether the current user is logged in (e.g check against stored cookie, session or database etc.)
     *
     * @return bool
     */
    public static function isLoggedIn(): bool {
        // SAMPLE!!
        // TODO: Actually do the check of logged in status (e.g check against stored cookie, session or database etc.)
        $headers = apache_request_headers();

        $auth = $headers["Authorization"] ?? "";
        [$jwt] = sscanf($auth, "Bearer %s");

        if (!empty($jwt)) {
            try {
                $secretKey = Config::PORTFOLIO_ADMIN_SECRET_KEY;

                $token = JWT::decode($jwt, $secretKey, [self::JWT_ALG]);

                // An exception is thrown if auth bearer token provided isn't valid
                // So assume all is valid here
                return true;
            }
            catch (Exception $e) {
                $errorMessage = $e->getMessage();
                error_log("Failed auth check with error: {$errorMessage}");
            }
        }

        return false;
    }

}
