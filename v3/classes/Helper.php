<?php
/*
 * All the general functions for API
 * Used by multiple API's
 * @author Jahidul Pabel Islam
*/

namespace JPI\API;

class Helper {

	/**
	 * @return array Return an array with data extracted from the request
	 */
	public static function extractFromRequest() : array {

		// Get the requested method
		$method = strtoupper($_SERVER['REQUEST_METHOD']);
		
		$requestedURI = !empty($_SERVER['PATH_INFO']) ? trim($_SERVER['PATH_INFO'], "/") : '';

		// Get the individual parts of the request URI as an array
		$requestedURIArray = explode('/', $requestedURI);

		$data = [];
		foreach ($_REQUEST as $key => $value) {
			$data[$key] = stripslashes(urldecode($_REQUEST[$key]));
		}

		return [$method, $requestedURIArray, $data];
	}

	/**
	 * Check if all data needed is provided
	 * And data provided is not empty
	 *
	 * @param $data array Array of the data provided for the request
	 * @param $dataNeeded array Array of data needed
	 * @return bool Whether data provided is valid and data needed is provided or not
	 */
	public static function checkData(array $data, array $dataNeeded) : bool {

		// Loops through each data needed for the request
		foreach ($dataNeeded as $aData) {

			// Checks if the data needed is provided and is not empty
			if (!isset($data[$aData]) || trim($data[$aData]) === "") {
				// Return false as data needed is not provided or empty
				return false;
			}
		}

		// Otherwise data provided are ok and data needed are provided
		return true;
	}

	/**
	 * Generate meta data to send back when the method provided is not allowed on the URI
	 *
	 * @param $method string The method tried
	 * @param $path array The path (relative) tried
	 * @return array Array of meta data
	 */
	public static function getMethodNotAllowedResult($method, array $path) : array {

		$result = [
			'meta' => [
				'ok'       => false,
				'status'   => 405,
				'message'  => 'Method not allowed.',
				'feedback' => "$method Method Not Allowed on /api/v3/" . implode("/", $path),
			],
		];

		return $result;
	}

	/**
	 * Send necessary meta data back when needed data is not provided
	 *
	 * @param $dataNeeded array Array of the data needed
	 * @return array Array of meta data
	 */
	public static function getDataNotProvidedResult(array $dataNeeded) : array {

		$result = [
			'meta' => [
				'ok' => false,
				'status' => 400,
				'message' => 'Bad Request',
				'dataNeeded' => $dataNeeded,
				'feedback' => 'The necessary data was not provided.',
			],
		];

		return $result;
	}

	/**
	 * Send necessary meta data back when user isn't logged in correctly
	 *
	 * @return array Array of meta data
	 */
	public static function getNotAuthorisedResult() : array {

		$result = [
			'meta' => [
				'ok' => false,
				'status' => 401,
				'message' => 'Unauthorized',
				'feedback' => 'You need to be logged in!',
			],
		];

		return $result;
	}
	
	/**
	 * Send the result response back
	 *
	 * @param $result array The result generated from the request so far
	 * @param $data array The data sent with the request
	 * @param $method string The request method made
	 * @param $path array The URI (Relative) the request was made to
	 */
	public static function sendResponse(array $result, array $data, $method, array $path) {

		// Send back the data provided
		$result['meta']["data"] = $data;
		// Send back the method requested
		$result['meta']["method"] = $method;
		// Send back the path they requested
		$result['meta']["path"] = $path;
		
		$originURL = $_SERVER["HTTP_ORIGIN"] ?? "";
		
		// Strip the protocol from domain
		$originDomain = str_replace("http://", "", $originURL);
		$originDomain = str_replace("https://", "", $originDomain);
		
		$allowedDomains = [
			"jahidulpabelislam.com",
			"cms.jahidulpabelislam.com",
			"staging.jahidulpabelislam.com",
			"staging.cms.jahidulpabelislam.com",
			"portfolio.local",
			"portfolio-cms.local",
		];
		
		// If the domain if allowed send correct header response back
		if (in_array($originDomain, $allowedDomains)) {
			header("Access-Control-Allow-Origin: $originURL");
			header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
			header("Access-Control-Allow-Headers: Process-Data");
			
			if ($method === "OPTIONS") {
				$result["meta"]["status"] = 200;
				$result["meta"]["message"] = "OK";
			}
		}

		// Figure out the correct meta responses to return
		if (isset($result["meta"]["ok"]) && $result["meta"]["ok"] !== false) {
			$status = isset($result["meta"]["status"]) ? $result["meta"]["status"] : 200;
			$message = isset($result["meta"]["message"]) ? $result["meta"]["message"] : "OK";
		}
		else {
			$status = isset($result["meta"]["status"]) ? $result["meta"]["status"] : 500;
			$message = isset($result["meta"]["message"]) ? $result["meta"]["message"] : "Internal Server Error";
		}

		$result["meta"]["status"] = $status;
		$result["meta"]["message"] = $message;

		header("HTTP/1.1 $status $message");

		$notCachedURLs = array(
			"session/",
			"logout/",
		);

		// Set cache for 31 days for some GET Requests
		if ($method == "GET" && !in_array(implode("/", $path), $notCachedURLs)) {
			$secondsToCache = 2678400;
			$expiresTime = gmdate("D, d M Y H:i:s", time() + $secondsToCache) . " GMT";
			header("Cache-Control: max-age=$secondsToCache, public");
			header("Expires: $expiresTime");
			header("Pragma: cache");
		}
		
		// Check if requested to send json
		$json = (stripos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

		// Send the result, send by json if json was requested
		if ($json) {
			header("Content-Type: application/json");
			echo json_encode($result);
		} // Else send by plain text
		else {
			header("Content-Type: text/plain");
			echo("result: ");
			var_dump($result);
		}
	}
}