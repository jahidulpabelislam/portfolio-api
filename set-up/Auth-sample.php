<?php
/**
 * All the functions needed for this API's Authentication.
 *
 * PHP version 7.1+
 *
 * @version 2.2.1
 * @since Class available since Release: v2.0.0
 * @author Jahidul Pabel Islam <me@jahidulpabelislam.com>
 * @copyright 2010-2019 JPI
 */

namespace JPI\API;

if (!defined("ROOT")) {
    die();
}

use Exception;
use Firebase\JWT\JWT;

class Auth {

    private const JWT_ALG = "HS512";
    private const JWT_EXPIRATION_HOURS = 1;

    /**
     * Authenticate a user trying to login
     * If successful store status (e.g store in cookie, session or database etc.) and output appropriate success message
     * If failed output appropriate error message
     *
     * @param $data array The data provided when trying to login
     * @return array Response with meta data given to ajax call after trying to login
     */
    public static function login(array $data): array {
        $response = [];

        $api = Core::get();

        // Checks if data needed are present and not empty
        $requiredFields = ["username", "password"];
        if ($api->hasRequiredFields($requiredFields)) {

            $response["meta"]["status"] = 401;
            $response["meta"]["message"] = "Unauthorized";

            if (Hasher::check($data["username"], Config::PORTFOLIO_ADMIN_USERNAME)) {
                if (Hasher::check($data["password"], Config::PORTFOLIO_ADMIN_PASSWORD)) {

                    $response["meta"]["ok"] = true;
                    $response["meta"]["status"] = 200;
                    $response["meta"]["message"] = "OK";

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

                    $response["meta"]["jwt"] = $jwt;
                }
                else {
                    $response["meta"]["feedback"] = "Wrong password.";
                }
            }
            else {
                $response["meta"]["feedback"] = "Wrong username and/or password.";
            }
        }
        else {
            $response = Responder::get()->getInvalidFieldsResponse($requiredFields);
        }

        return $response;
    }

    /**
     * Do the log out here (e.g removing cookie, session or database etc.)
     * If successful output appropriate success message
     *
     * @return array
     */
    public static function logout(): array {
        // TODO: Actually do the log out here (e.g removing cookie, session or database etc.)

        return Responder::getLoggedOutResponse();
    }

    /**
     * Check whether the current user is logged in (e.g check against stored cookie, session or database etc.)
     *
     * @return bool Whether user is logged in or not
     */
    public static function isLoggedIn(): bool {
        // TODO: Actually do the check of logged in status (e.g check against stored cookie, session or database etc.)

        // SAMPLE!!
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
