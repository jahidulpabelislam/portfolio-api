<?php
/**
 * A RESTful API router.
 *
 * PHP version 7.1+
 *
 * @version 2.2.0
 * @since Class available since Release: v2.0.0
 * @author Jahidul Pabel Islam <me@jahidulpabelislam.com>
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

        $version = $uri[0] ?? "";

        $shouldBeVersion = "v" . Config::API_VERSION;
        if ($version !== $shouldBeVersion) {
            $response = Responder::get()->getUnrecognisedAPIVersionResponse();
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
                $response = Responder::getAuthStatusResponse();
            }
        }
        else {
            $response = Responder::get()->getMethodNotAllowedResponse();
        }

        return $response ?? [];
    }

    private function executeProjectsGetAction(array $uri, array $data): array {
        if (isset($uri[2]) && $uri[2] !== "") {
            $projectId = $uri[2];

            if (isset($uri[3]) && $uri[3] === "images") {
                if (isset($uri[4]) && $uri[4] !== "" && !isset($uri[5])) {
                    $response = Projects::getProjectImage($projectId, $uri[4]);
                }
                else if (!isset($uri[4])) {
                    $response = Projects::getProjectImages($projectId);
                }
            }
            else if (!isset($uri[3])) {
                $response = Projects::getProject($projectId, true);
            }
        }
        else {
            $response = Projects::getProjects($data);
        }

        return $response ?? [];
    }

    private function executeProjectsPostAction(array $uri, array $data): array {
        if (
            isset($uri[2]) && $uri[2] !== ""
            && isset($uri[3]) && $uri[3] === "images"
            && !isset($uri[4])
        ) {
            $data["project_id"] = $uri[2];
            $response = Projects::addProjectImage($data, $this->api->files);
        }
        else if (!isset($uri[2])) {
            $response = Projects::addProject($data);
        }

        return $response ?? [];
    }

    private function executeProjectsPutAction(array $uri, array $data): array {
        if (isset($uri[2]) && $uri[2] !== "" && !isset($uri[3])) {
            $data["id"] = $uri[2];
            $response = Projects::editProject($data);
        }

        return $response ?? [];
    }

    private function executeProjectsDeleteAction(array $uri, array $data): array {
        if (isset($uri[2]) && $uri[2] !== "") {
            if (
                isset($uri[3]) && $uri[3] === "images"
                && isset($uri[4]) && $uri[4] !== ""
                && !isset($uri[5])
            ) {
                $data["id"] = $uri[4];
                $data["project_id"] = $uri[2];
                $response = Projects::deleteProjectImage($data);
            }
            else if (!isset($uri[3])) {
                $data["id"] = $uri[2];
                $response = Projects::deleteProject($data);
            }
        }

        return $response ?? [];
    }

    /**
     * @return array An appropriate response to projects request
     */
    private function executeProjectsAction(array $uri, string $method, array $data): array {
        if ($method === "GET") {
            $response = $this->executeProjectsGetAction($uri, $data);
        }
        else if ($method === "POST") {
            $response = $this->executeProjectsPostAction($uri, $data);
        }
        else if ($method === "PUT") {
            $response = $this->executeProjectsPutAction($uri, $data);
        }
        else if ($method === "DELETE") {
            $response = $this->executeProjectsDeleteAction($uri, $data);
        }
        else {
            $response = Responder::get()->getMethodNotAllowedResponse();
        }

        return $response;
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
            $response = Responder::get()->getUnrecognisedURIResponse();
        }

        $this->api->sendResponse($response);
    }
}
