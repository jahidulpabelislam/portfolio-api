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
 * @copyright 2010-2018 JPI
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

        $version = !empty($path[0]) ? $path[0] : "";

        $shouldBeVersion = "v" . Config::API_VERSION;
        if ($version !== $shouldBeVersion) {
            $shouldBePath = $path;
            $shouldBePath[0] = $shouldBeVersion;
            $shouldBeURL = Helper::getAPIURL($shouldBePath);
            $response = [
                "meta" => [
                    "status" => 404,
                    "feedback" => "Unrecognised API Version. Current Version is v" . Config::API_VERSION . ". So update requested URL to $shouldBeURL.",
                    "message" => "Not Found",
                ],
            ];
            Helper::sendResponse($response, $data, $method, $path);

            return;
        }

        $entity = !empty($path[1]) ? $path[1] : "";

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
                $api = new Core();

                switch ($method) {
                    case "GET":
                        if (isset($path[2]) && trim($path[2]) !== "") {
                            $projectId = $path[2];
                            if (isset($path[3]) && $path[3] === "images") {
                                if (isset($path[4]) && $path[4] !== "" && !isset($path[5])) {
                                    $response = $api->getProjectImage($projectId, $path[4]);
                                }
                                else if (!isset($path[4])) {
                                    $response = $api->getProjectImages($projectId);
                                }
                            }
                            else if (!isset($path[3])) {
                                $response = $api->getProject($projectId, true);
                            }
                        }
                        else if (!isset($path[2])) {
                            $response = $api->getProjects($data);
                        }
                        break;
                    case "POST":
                        if (
                            isset($path[2]) && trim($path[2]) !== ""
                            && isset($path[3]) && $path[3] === "images"
                            && !isset($path[4])
                        ) {
                            $data["project_id"] = $path[2];
                            $response = $api->addProjectImage($data);
                        }
                        else if (!isset($path[2])) {
                            $response = $api->addProject($data);
                        }
                        break;
                    case "PUT":
                        if (isset($path[2]) && trim($path[2]) !== "" && !isset($path[3])) {
                            $data["id"] = $path[2];
                            $response = $api->editProject($data);
                        }
                        break;
                    case "DELETE":
                        if (isset($path[2]) && trim($path[2]) !== "") {
                            if (
                                isset($path[3]) && $path[3] === "images"
                                && isset($path[4]) && $path[4] !== ""
                                && !isset($path[5])
                            ) {
                                $data["id"] = $path[4];
                                $data["project_id"] = $path[2];
                                $response = $api->deleteImage($data);
                            }
                            else if (!isset($path[3])) {
                                $data["id"] = $path[2];
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