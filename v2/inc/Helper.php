<?php
/*
 * All the general functions for API
 * Used by multiple API's
 * @author Jahidul Pabel Islam
*/

class Helper {

	public static function extractFromRequest() {

		// Get the method
		$method = strtoupper($_SERVER['REQUEST_METHOD']);
		
		$requested_path = !empty($_SERVER['PATH_INFO']) ? ltrim($_SERVER['PATH_INFO'], "/") : '';

		// Get the path to decide what happens
		$path = explode('/', $requested_path);

		$data = [];
		foreach ($_REQUEST as $key => $value) {
			$data[$key] = stripslashes(urldecode($_REQUEST[$key]));
		}

		return [$method, $path, $data];
	}

	/**
	 * Check if all data needed is provided
	 * And data provided is not empty
	 * @param $data array array of data provided
	 * @param $dataNeeded array array of data needed
	 * @return bool whether data provided is valid and data needed is provided
	 */
	public static function checkData($data, $dataNeeded) {

		//loops through each request needed
		foreach ($dataNeeded as $aData) {

			//checks if data needed is provided and is not empty
			if (!isset($data[$aData]) || trim($data[$aData]) === "") {
				//return false as data needed is not provided or empty
				return false;
			}

		}

		//otherwise data provided are ok and data needed are provided
		return true;
	}

	/**
	 * When the method provided is not allowed
	 * @param $method string the method tried
	 * @param $path array the path tried
	 * @return array array of meta data
	 */
	public static function methodNotAllowed($method, $path) {

		$meta["ok"] = false;
		$meta["status"] = 405;
		$meta["message"] = "Method not allowed.";
		$meta["feedback"] = "${method} Method Not Allowed on /api/v2/" . implode("/", $path);

		return $meta;
	}

	/**
	 * Send necessary meta data back when needed data is not provided
	 * @param $dataNeeded array array of data needed
	 * @return array array of meta data
	 */
	public static function dataNotProvided($dataNeeded) {

		$meta["ok"] = false;
		$meta["status"] = 400;
		$meta["message"] = "Bad Request";
		$meta["requestsNeeded"] = $dataNeeded;
		$meta["feedback"] = "The necessary data was not provided.";

		return $meta;
	}

	/**
	 * Send necessary meta data back when user isn't logged in correctly
	 * @return array array of meta data
	 */
	public static function notAuthorised() {

		$results = [];
		$results["meta"]["ok"] = false;
		$results["meta"]["status"] = 401;
		$results["meta"]["message"] = "Unauthorized";
		$results["meta"]["feedback"] = "You need to be logged in!";

		return $results;
	}

	public static function sendData($results, $data, $method, $path) {

		// Send back the data provided
		$results['meta']["data"] = $data;
		// Send back the method requested
		$results['meta']["method"] = $method;
		// Send back the path they requested
		$results['meta']["path"] = $path;

		// Figure out the correct meta responses to return
		if (isset($results["meta"]["ok"]) && $results["meta"]["ok"] !== false) {
			$status = isset($results["meta"]["status"]) ? $results["meta"]["status"] : 200;
			$message = isset($results["meta"]["message"]) ? $results["meta"]["message"] : "OK";
		}
		else {
			$status = isset($results["meta"]["status"]) ? $results["meta"]["status"] : 500;
			$message = isset($results["meta"]["message"]) ? $results["meta"]["message"] : "Internal Server Error";
		}

		$results["meta"]["status"] = $status;
		$results["meta"]["message"] = $message;

		header("HTTP/1.1 $status $message");
		
		$origin_domain = $_SERVER["HTTP_ORIGIN"];

		// Strip the protocol from domain
		$stripped_domain = str_replace("http://", "", $origin_domain);
		$stripped_domain = str_replace("https://", "", $stripped_domain);

		$allowed_domains = [
			"jahidulpabelislam.com",
			"cms.jahidulpabelislam.com",
			"staging.jahidulpabelislam.com",
			"staging.cms.jahidulpabelislam.com",
			"portfolio.local",
			"portfolio-cms.local",
		];

		// If the domain if allowed send correct header response back
		if (in_array($stripped_domain, $allowed_domains)) {
			header("Access-Control-Allow-Origin: $origin_domain");
		}

		// Set cache for 31 days for all GET Requests
		if ($method == "GET") {
			$seconds_to_cache = 2678400;
			$expires_time = gmdate("D, d M Y H:i:s", time() + $seconds_to_cache) . " GMT";
			header("Cache-Control: max-age=$seconds_to_cache, public");
			header("Expires: $expires_time");
			header("Pragma: cache");
		}
		
		// Check if requested to send json
		$json = (stripos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

		// Send the results, send by json if json was requested
		if ($json) {
			header("Content-Type: application/json");
			echo json_encode($results);
		} // Else send by plain text
		else {
			header("Content-Type: text/plain");
			echo("results: ");
			var_dump($results);
		}
	}
}