<?php
/*
 * A RESTful API router.
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

if (!defined("ROOT")) {
	die();
}

class Router {

	/**
	 * Try and perform the necessary actions needed to fulfil the request that a user made
	 */
	public static function performRequest() {

		list($method, $path, $data) = Helper::extractFromRequest();

		$api = new Core();

		$entity = !empty($path[0]) ? $path[0] : "";

		$response = [];

		// Figure out what action on what object request is for & perform necessary action(s)
		switch ($entity) {
			case "login":
				switch ($method) {
					case "POST":
						$response = Auth::login($data);
						break;
					default:
						$response = Helper::getMethodNotAllowedResponse($method, $path);
				}
				break;
			case "logout":
				switch ($method) {
					case "DELETE":
						$response = Auth::logout();
						break;
					default:
						$response = Helper::getMethodNotAllowedResponse($method, $path);
				}
				break;
			case "session":
				switch ($method) {
					case "GET":
						$response = Auth::getAuthStatus();
						break;
					default:
						$response = Helper::getMethodNotAllowedResponse($method, $path);
				}
				break;
			case "projects":
				switch ($method) {
					case "GET":
						if (isset($path[1]) && trim($path[1]) !== "") {
							$projectID = $path[1];
							if (isset($path[2]) && $path[2] === "images") {
								if (isset($path[3]) && $path[3] !== "" && !isset($path[4])) {
									$response = $api->getProjectImage($projectID, $path[3]);
								}
								else if (!isset($path[3])) {
									$response = $api->getProjectImages($projectID);
								}
							}
							else if (!isset($path[2])) {
								$response = $api->getProject($projectID, true);
							}
						}
						else if (!isset($path[1])) {
							$response = $api->getProjects($data);
						}
						break;
					case "POST":
						if (isset($path[1]) && trim($path[1]) !== "" &&
							isset($path[2]) && $path[2] === "images" && !isset($path[3])) {
								$data["ProjectID"] = $path[1];
								$response = $api->addProjectImage($data);
						}
						else if (!isset($path[1])) {
							$response = $api->addProject($data);
						}
						break;
					case "PUT":
						if (isset($path[1]) && trim($path[1]) !== "" && !isset($path[2])) {
							$data["ID"] = $path[1];
							$response = $api->editProject($data);
						}
						break;
					case "DELETE":
						if (isset($path[1]) && trim($path[1]) !== "") {
							if (isset($path[2]) && $path[2] === "images"
								&& isset($path[3]) && $path[3] !== "" && !isset($path[4])) {
								$data["ID"] = $path[3];
								$data["ProjectID"] = $path[1];
								$response = $api->deleteImage($data);
							}
							else if (!isset($path[2])) {
								$data["ID"] = $path[1];
								$response = $api->deleteProject($data);
							}
						}
						break;
					default:
						$response = Helper::getMethodNotAllowedResponse($method, $path);
						break;
				}
				break;
		}

		if (empty($response)) {
			$response = Helper::getUnrecognisedURIResponse($path);
		}

		Helper::sendResponse($response, $data, $method, $path);
	}
}