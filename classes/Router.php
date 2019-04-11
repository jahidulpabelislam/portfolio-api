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

    private $helper;

    public function __construct() {
        $this->helper = Helper::get();
    }

    /**
     * Check that the requested API version is valid, if so return empty array
     * else return appropriate response (array)
     *
     * @return array
     */
    private function checkAPIVersion() {
        $response = [];

        $path = $this->helper->path;

        $version = !empty($path[0]) ? $path[0] : "";

        $shouldBeVersion = "v" . Config::API_VERSION;
        if ($version !== $shouldBeVersion) {
            $response = $this->helper->getUnrecognisedAPIVersionResponse();
        }

        return $response;
    }

    /**
     * Try and execute the requested action
     *
     * @return array An appropriate response to request
     */
    private function executeAction() {
        $response = [];

        $method = $this->helper->method;
        $path = $this->helper->path;
        $data = $this->helper->data;

        $entity = !empty($path[1]) ? $path[1] : "";

        // Figure out what action on what object request is for & perform necessary action(s)
        switch ($entity) {
            case "login":
                switch ($method) {
                    case "POST":
                        $response = Auth::login($data);
                        break;
                    default:
                        $response = $this->helper->getMethodNotAllowedResponse();
                }
                break;
            case "logout":
                switch ($method) {
                    case "DELETE":
                        $response = Auth::logout();
                        break;
                    default:
                        $response = $this->helper->getMethodNotAllowedResponse();
                }
                break;
            case "session":
                switch ($method) {
                    case "GET":
                        $response = Auth::getAuthStatus();
                        break;
                    default:
                        $response = $this->helper->getMethodNotAllowedResponse();
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
                        $response = $this->helper->getMethodNotAllowedResponse();
                        break;
                }
                break;
        }

        return $response;
    }

    /**
     * Try and perform the necessary actions needed to fulfil the request that a user made
     */
    public function performRequest() {
        $this->helper->extractFromRequest();

        // Here check the requested API version, if okay return empty array
        // else returns appropriate response
        $response = $this->checkAPIVersion();

        // Only try to perform the action if API version check above returned okay
        if (empty($response)) {
            $response = $this->executeAction();
        }

        // If at this point response is empty, we didn't recognise the action
        if (empty($response)) {
            $response = $this->helper->getUnrecognisedURIResponse();
        }

        $this->helper->sendResponse($response);
    }
}
