<?php
/*
 * A RESTful API router.
 *
 * PHP version 7
 *
 * @author Jahidul Pabel Islam <me@jahidulpabelislam.com>
 * @version 2.1.0
 * @link https://github.com/jahidulpabelislam/portfolio-api/
 * @since Class available since Release: v2.0.0
 * @copyright 2010-2019 JPI
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
    private function checkAPIVersion(): array {
        $response = [];

        $uri = $this->helper->uriArray;

        $version = !empty($uri[0]) ? $uri[0] : "";

        $shouldBeVersion = "v" . Config::API_VERSION;
        if ($version !== $shouldBeVersion) {
            $response = $this->helper->getUnrecognisedAPIVersionResponse();
        }

        return $response;
    }

    /**
     * @return array An appropriate response to request
     */
    private function executeAuthAction(string $entity, string $method, array $data): array{
        $response = [];

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
        }

        return $response;
    }

    /**
     * @return array An appropriate response to request
     */
    private function executeProjectsAction(array $uri, string $method, array $data): array {
        $api = new Core();

        $response = [];

        switch ($method) {
            case "GET":
                if (isset($uri[2]) && trim($uri[2]) !== "") {
                    $projectId = $uri[2];
                    if (isset($uri[3]) && $uri[3] === "images") {
                        if (isset($uri[4]) && $uri[4] !== "" && !isset($uri[5])) {
                            $response = $api->getProjectImage($projectId, $uri[4]);
                        }
                        else if (!isset($uri[4])) {
                            $response = $api->getProjectImages($projectId);
                        }
                    }
                    else if (!isset($uri[3])) {
                        $response = $api->getProject($projectId, true);
                    }
                }
                else if (!isset($uri[2])) {
                    $response = $api->getProjects($data);
                }
                break;
            case "POST":
                if (
                    isset($uri[2]) && trim($uri[2]) !== ""
                    && isset($uri[3]) && $uri[3] === "images"
                    && !isset($uri[4])
                ) {
                    $data["project_id"] = $uri[2];
                    $response = $api->addProjectImage($data);
                }
                else if (!isset($uri[2])) {
                    $response = $api->addProject($data);
                }
                break;
            case "PUT":
                if (isset($uri[2]) && trim($uri[2]) !== "" && !isset($uri[3])) {
                    $data["id"] = $uri[2];
                    $response = $api->editProject($data);
                }
                break;
            case "DELETE":
                if (isset($uri[2]) && trim($uri[2]) !== "") {
                    if (
                        isset($uri[3]) && $uri[3] === "images"
                        && isset($uri[4]) && $uri[4] !== ""
                        && !isset($uri[5])
                    ) {
                        $data["id"] = $uri[4];
                        $data["project_id"] = $uri[2];
                        $response = $api->deleteImage($data);
                    }
                    else if (!isset($uri[3])) {
                        $data["id"] = $uri[2];
                        $response = $api->deleteProject($data);
                    }
                }
                break;
            default:
                $response = $this->helper->getMethodNotAllowedResponse();
                break;
        }

        return $response;
    }

    /**
     * Try and execute the requested action
     *
     * @return array An appropriate response to request
     */
    private function executeAction(): array {
        $response = [];

        $method = $this->helper->method;
        $uri = $this->helper->uriArray;
        $data = $this->helper->data;

        $entity = !empty($uri[1]) ? $uri[1] : "";

        $authEntities = ["login", "logout", "session"];

        if ($entity === "projects") {
            $response = $this->executeProjectsAction($uri, $method, $data);
        } else if (in_array($entity, $authEntities)) {
            $response = $this->executeAuthAction($entity, $method, $data);
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
