<?php

/*
 * All the functions for this API Authentication
 * @author Jahidul Pabel Islam
*/

namespace JPI\API;

class Auth {

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

			if ($data["username"] === Config::PORTFOLIO_ADMIN_USERNAME) {

				if (Hasher::check($data["password"], Config::PORTFOLIO_ADMIN_PASSWORD)) {

					$result["meta"]["ok"] = true;
					$result["meta"]["status"] = 200;
					$result["meta"]["message"] = "OK";

					/*
					 * Actually do the logging in here (e.g store in cookie, session or database etc.)
					 */

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
		 * Actually do the log out here (e.g removing cookie, session or database etc.)
		 */

		$result = [
			'meta' => [
				'ok' => true,
				'feedback' => 'Successfully Logged Out.',
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
		 * Actually do the check of logged in status (e.g check against stored cookie, session or database etc.)
		 */

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
				'meta' => [
					'ok' => true,
					'status' => 200,
					'message' => 'OK',
				],
			];
		}
		else {
			$result = Helper::getNotAuthorisedResult();
		}

		return $result;
	}
}