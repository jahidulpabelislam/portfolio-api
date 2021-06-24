<?php

/**
 * The functions for the handling of authentication for this API.
 */

namespace App\Auth;

use App\Config;
use App\HTTP\Request;
use Exception;
use Firebase\JWT\JWT;

class Manager extends AbstractManager {

    private const JWT_ALG = "HS512";
    private const JWT_EXPIRATION_HOURS = 6;

    public static function login(Request $request): ?string {
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

        $secretKey = Config::get()->portfolio_admin_secret_key;

        $jwt = JWT::encode($jwtData, $secretKey, self::JWT_ALG);
        return $jwt;
    }

    public static function logout(Request $request): bool {
        // TODO: Actually do the log out here (e.g removing cookie, session or database etc.)
        return true;
    }

    public static function isLoggedIn(Request $request): bool {
        // SAMPLE!!
        // TODO: Actually do the check of logged in status (e.g check against stored cookie, session or database etc.)

        $auth = $request->headers->get("Authorization", []);
        [$jwt] = sscanf($auth[0] ?? "", "Bearer %s");

        if (!empty($jwt)) {
            try {
                $secretKey = Config::get()->portfolio_admin_secret_key;

                JWT::decode($jwt, $secretKey, [self::JWT_ALG]);

                // An exception is thrown if auth bearer token provided isn't valid
                // So assume all is valid here
                return true;
            }
            catch (Exception $e) {
                $errorMessage = $e->getMessage();
                error_log("Failed auth check with error: $errorMessage");
            }
        }

        return false;
    }
}
