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

    private $api;

    public function __construct() {
        $this->api = Core::get();
    }

    /**
     * Check that the requested API version is valid, if so return empty array
     * else return appropriate response (array)
     */
    private function checkAPIVersion(): array {
        $uri = $this->api->uriArray;

        $version = !empty($uri[0]) ? $uri[0] : "";

        $shouldBeVersion = "v" . Config::API_VERSION;
        if ($version !== $shouldBeVersion) {
            $response = $this->api->getUnrecognisedAPIVersionResponse();
        }

        return $response ?? [];
    }

    /**
     * @return array An appropriate response to auth request
     */
    private function executeAuthAction(array $uri, string $method, array $data): array {
        $authAction = $uri[2] ?? "";

        if ($method === "POST") {
            if ($authAction === "login" && (!isset($uri[3]) || $uri[3] === "")) {
                $response = Auth::login($data);
            }
        }
        else if ($method === "DELETE") {
            if ($authAction === "logout" && (!isset($uri[3]) || $uri[3] === "")) {
                $response = Auth::logout();
            }
        }
        else if ($method === "GET") {
            if ($authAction === "session" && (!isset($uri[3]) || $uri[3] === "")) {
                $response = Auth::getAuthStatus();
            }
        }
        else {
            $response = $this->api->getMethodNotAllowedResponse();
        }

        return $response ?? [];
    }

    /**
     * @return array An appropriate response to projects request
     */
    private function executeProjectsAction(array $uri, string $method, array $data): array {
        $api = new Projects();

        if ($method === "GET") {
            if (isset($uri[2]) && $uri[2] !== "") {

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
            else {
                $response = $api->getProjects($data);
            }
        }
        else if ($method === "POST") {
            if (
                isset($uri[2]) && $uri[2] !== ""
                && isset($uri[3]) && $uri[3] === "images"
                && !isset($uri[4])
            ) {
                $data["project_id"] = $uri[2];
                $response = $api->addProjectImage($data);
            }
            else if (!isset($uri[2])) {
                $response = $api->addProject($data);
            }
        }
        else if ($method === "PUT") {
            if (isset($uri[2]) && $uri[2] !== "" && !isset($uri[3])) {
                $data["id"] = $uri[2];
                $response = $api->editProject($data);
            }
        }
        else if ($method === "DELETE") {
            if (isset($uri[2]) && $uri[2] !== "") {
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
        }
        else {
            $response = $this->api->getMethodNotAllowedResponse();
        }

        return $response ?? [];
    }

    /**
     * Try and execute the requested action
     *
     * @return array An appropriate response to request
     */
    private function executeAction(): array {
        $method = $this->api->method;
        $uri = $this->api->uriArray;
        $data = $this->api->data;

        $entity = $uri[1] ?? "";

        if ($entity === "auth") {
            $response = $this->executeAuthAction($uri, $method, $data);
        }
        else if ($entity === "projects") {
            $response = $this->executeProjectsAction($uri, $method, $data);
        }

        return $response ?? [];
    }

    /**
     * Try and perform the necessary actions needed to fulfil the request that a user made
     */
    public function performRequest() {
        $this->api->extractFromRequest();

        // Here check the requested API version, if okay return empty array
        // else returns appropriate response
        $response = $this->checkAPIVersion();

        // Only try to perform the action if API version check above returned okay
        if (empty($response)) {
            $response = $this->executeAction();
        }

        // If at this point response is empty, we didn't recognise the action
        if (empty($response)) {
            $response = $this->api->getUnrecognisedURIResponse();
        }

        $this->api->sendResponse($response);
    }
}
