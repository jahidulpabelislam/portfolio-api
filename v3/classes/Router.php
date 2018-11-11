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

		// Figure out what action on what object request is for & perform necessary action(s)
		switch ($object) {
			case "login":
				switch ($method) {
					case "POST":
						$results = Auth::login($data);
						break;
					default:
						$results["meta"] = Helper::methodNotAllowed($method, $path);
				}
				break;
			case "logout":
				switch ($method) {
					case "GET":
						$results = Auth::logout();
						break;
					default:
						$results["meta"] = Helper::methodNotAllowed($method, $path);
				}
				break;
			case "session":
				switch ($method) {
					case "GET":
						$results = $api->getAuthStatus();
						break;
					default:
						$results["meta"] = Helper::methodNotAllowed($method, $path);
				}
				break;
			case "projects":
				switch ($method) {
					case "GET":
						if (isset($path[1]) && trim($path[1]) !== "") {
							$projectID = $path[1];
							if (isset($path[2]) && $path[2] === "images") {
								if (isset($path[3]) && $path[3] !== "") {
									$results = $api->getProjectImage($projectID, $path[3]);
								}
								else {
									$results = $api->getProjectImages($projectID);
								}
							}
							else {
								$results = $api->getProject($projectID, true);
							}
						}
						else {
							$results = $api->getProjects($data);
						}
						break;
					case "POST":
						if (isset($path[1]) && trim($path[1]) !== "" && isset($path[2]) && $path[2] === "images") {
							if (isset($_FILES["image"])) {
								$data["ProjectID"] = $path[1];
								$results = $api->addProjectImage($data);
							}
						}
						else {
							$results = $api->addProject($data);
						}
						break;
					case "PUT":
						if (isset($path[1]) && trim($path[1]) !== "") {
							$data["ID"] = $path[1];
							$results = $api->editProject($data);
						}
						break;
					case "DELETE":
						if (isset($path[1]) && trim($path[1]) !== "") {
							if (isset($path[2]) && $path[2] === "images" && isset($path[3]) && $path[3] !== "") {
								$data["ID"] = $path[3];
								$data["ProjectID"] = $path[1];
								$results = $api->deleteImage($data);
							}
							else {
								$data["ID"] = $path[1];
								$results = $api->deleteProject($data);
							}
						}
						break;
					default:
						$results["meta"] = Helper::methodNotAllowed($method, $path);
				}
				break;
			default:
				$results["meta"]["ok"] = false;
				$results["meta"]["status"] = 404;
				$results["meta"]["feedback"] = "Unrecognised URI (/api/v3/" . implode("/", $path) . ")";
				$results["meta"]["message"] = "Not Found";
		}

		if (empty($results)) {
			$results["meta"] = Helper::methodNotAllowed($method, $path);
		}

		Helper::sendData($results, $data, $method, $path);
	}
}