<?php
/*
 * A RESTful API router.
 * @author Jahidul Pabel Islam
*/

namespace JPI\API;

class Router {
	
	/**
	 * Try and perform the necessary actions needed to fulfil the request that a user made
	 */
	public static function performRequest() {

		list($method, $path, $data) = Helper::extractFromRequest();

		$api = new API();

		$object = !empty($path[0]) ? $path[0] : '';

		$result = [];

		// Figure out what action on what object request is for & perform necessary action(s)
		switch ($object) {
			case "login":
				switch ($method) {
					case "POST":
						$result = Auth::login($data);
						break;
					default:
						$result = Helper::getMethodNotAllowedResult($method, $path);
				}
				break;
			case "logout":
				switch ($method) {
					case "GET":
						$result = Auth::logout();
						break;
					default:
						$result = Helper::getMethodNotAllowedResult($method, $path);
				}
				break;
			case "session":
				switch ($method) {
					case "GET":
						$result = Auth::getAuthStatus();
						break;
					default:
						$result = Helper::getMethodNotAllowedResult($method, $path);
				}
				break;
			case "projects":
				switch ($method) {
					case "GET":
						if (isset($path[1]) && trim($path[1]) !== "") {
							$projectID = $path[1];
							if (isset($path[2]) && $path[2] === "images") {
								if (isset($path[3]) && $path[3] !== "") {
									$result = $api->getProjectImage($projectID, $path[3]);
								}
								else {
									$result = $api->getProjectImages($projectID);
								}
							}
							else {
								$result = $api->getProject($projectID, true);
							}
						}
						else {
							$result = $api->getProjects($data);
						}
						break;
					case "POST":
						if (isset($path[1]) && trim($path[1]) !== "" && isset($path[2]) && $path[2] === "images") {
							if (isset($_FILES["image"])) {
								$data["ProjectID"] = $path[1];
								$result = $api->addProjectImage($data);
							}
						}
						else {
							$result = $api->addProject($data);
						}
						break;
					case "PUT":
						if (isset($path[1]) && trim($path[1]) !== "") {
							$data["ID"] = $path[1];
							$result = $api->editProject($data);
						}
						break;
					case "DELETE":
						if (isset($path[1]) && trim($path[1]) !== "") {
							if (isset($path[2]) && $path[2] === "images" && isset($path[3]) && $path[3] !== "") {
								$data["ID"] = $path[3];
								$data["ProjectID"] = $path[1];
								$result = $api->deleteImage($data);
							}
							else {
								$data["ID"] = $path[1];
								$result = $api->deleteProject($data);
							}
						}
						break;
					default:
						$result = Helper::getMethodNotAllowedResult($method, $path);
				}
				break;
			default:
				$result = [
					'meta' => [
						'ok' => false,
						'status' => 404,
						'feedback' => 'Unrecognised URI (/api/v3/' . implode('/', $path) . ')',
						'message' => 'Not Found',
					],
				];
		}

		if (empty($result)) {
			$result = Helper::getMethodNotAllowedResult($method, $path);
		}

		Helper::sendResponse($result, $data, $method, $path);
	}
}