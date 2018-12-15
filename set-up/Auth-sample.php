<?php
/*
 * All the functions needed for this API's Authentication.
 *
 * PHP version 7
 *
 * @author Jahidul Pabel Islam <me@jahidulpabelislam.com>
 * @version 2
 * @link https://github.com/jahidulpabelislam/portfolio-api/
 * @since Class available since Release: v2
 * @copyright 2014-2018 JPI
*/

namespace JPI\API;

use Firebase\JWT\JWT;

class Auth {

	private static $JWT_ALG = "HS512";
	private static $JWT_EXPIRATION_HOURS = 1;

	/**
	 * Authenticate a user trying to login
	 * If successful store status (e.g store in cookie, session or database etc.) and output appropriate success message
	 * If failed output appropriate error message
	 *
	 * @param $data array The data provided when trying to login
	 * @return array Response with meta data given to ajax call after trying to login
	 */
	public static function login($data) {

		$result = [];

		// Checks if data needed are present and not empty
		$dataNeeded = array("username", "password");
		if (Helper::checkData($data, $dataNeeded)) {

			$result["meta"]["ok"] = false;
			$result["meta"]["status"] = 401;
			$result["meta"]["message"] = "Unauthorized";

			if (Hasher::check($data["username"], Config::PORTFOLIO_ADMIN_USERNAME)) {

				if (Hasher::check($data["password"], Config::PORTFOLIO_ADMIN_PASSWORD)) {

					$result["meta"]["ok"] = true;
					$result["meta"]["status"] = 200;
					$result["meta"]["message"] = "OK";

					/*
					 * TODO Actually do the logging in here (e.g store in cookie, session or database etc.)
					 */

					/*
					 * SAMPLE!!
					 */
					$tokenId = 1; // Json Token Id: an unique identifier for the token
					$issuedAt = time(); // Issued at: time when the token was generated
					$expire = $issuedAt + (60 * 60 * 1000) * self::$JWT_EXPIRATION_HOURS; // Token expiration time
					$serverName = "https://jahidulpabelislam.com/"; // Issuer

					$jwtData = [
						"jti" => $tokenId,
						"iss" => $serverName,
						"iat" => $issuedAt,
						"nbf" => $issuedAt,
						"exp" => $expire,
						"data" => [
							// Any extra API secific data
						]
					];

					$secretKey = Config::PORTFOLIO_ADMIN_SECRET_KEY;

					$jwt = JWT::encode($jwtData, $secretKey, self::$JWT_ALG);
					
					$result["meta"]["jwt"] = $jwt;
				}
				else {
					$result["meta"]["feedback"] = "Wrong Password.";
				}
			}
			else {
				$result["meta"]["feedback"] = "Wrong Username and/or Password.";
			}

		}
		else {
			$result = Helper::getDataNotProvidedResult($dataNeeded);
		}

		return $result;
	}

	/**
	 * Do the log out here (e.g removing cookie, session or database etc.)
	 * If successful output appropriate success message
	 *
	 * @return mixed
	 */
	public static function logout() {

		/*
		 * TODO Actually do the log out here (e.g removing cookie, session or database etc.)
		 */

		$result = [
			"meta" => [
				"ok" => true,
				"feedback" => "Successfully Logged Out.",
			],
		];

		return $result;
	}

	/**
	 * Check whether the current user is logged in (e.g check against stored cookie, session or database etc.)
	 *
	 * @return bool Whether user is logged in or not
	 */
	public static function isLoggedIn() {

		/*
		 * TODO Actually do the check of logged in status (e.g check against stored cookie, session or database etc.)
		 */

		/*
		 * SAMPLE!!
		 */
		$headers = apache_request_headers();

		$auth = $headers["Authorization"] ?? "";
		list($jwt) = sscanf($auth, "Bearer %s");

		if (!empty($jwt)) {
			
			try {
				$secretKey = Config::PORTFOLIO_ADMIN_SECRET_KEY;

				$token = JWT::decode($jwt, $secretKey, array(self::$JWT_ALG));

				// An exception is thrown if auth bearer token provided isn't valid
				// So assume all is valid here
				return true;
			}
			catch (\Exception $e) {
				error_log("Failed auth check with error: " . $e->getMessage());
			}
		}

		return false;
	}

	/**
	 * Check whether the user is logged or not
	 *
	 * @return array The request response to send back
	 */
	public static function getAuthStatus() {

		if (self::isLoggedIn()) {
			$result = [
				"meta" => [
					"ok" => true,
					"status" => 200,
					"message" => "OK",
				],
			];
		}
		else {
			$result = Helper::getNotAuthorisedResult();
		}

		return $result;
	}
}