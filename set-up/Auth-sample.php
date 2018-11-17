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

		//checks if data needed are present and not empty
		$dataNeeded = array("username", "password");
		if (Helper::checkData($data, $dataNeeded)) {

			$results["meta"]["ok"] = false;
			$results["meta"]["status"] = 401;
			$results["meta"]["message"] = "Unauthorized";

			if ($data["username"] === PORTFOLIO_ADMIN_USERNAME) {

				if (Hasher::check($data["password"], PORTFOLIO_ADMIN_PASSWORD)) {

					$results["meta"]["ok"] = true;
					$results["meta"]["status"] = 200;
					$results["meta"]["message"] = "OK";

					/*
					 * Actually do the logging in here (e.g store in cookie, session or database etc.)
					 */

				}
				else {
					$results["meta"]["feedback"] = "Wrong Password.";
				}
			}
			else {
				$results["meta"]["feedback"] = "Wrong Username and/or Password.";
			}

		}
		else {
			$results["meta"] = Helper::dataNotProvided($dataNeeded);
		}

		return $results;
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


		$results["meta"]["ok"] = true;
		$results["meta"]["feedback"] = "Successfully Logged Out.";

		return $results;
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
}