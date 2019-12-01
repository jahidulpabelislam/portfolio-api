<?php
/**
 * A RESTful API router.
 *
 * PHP version 7.1+
 *
 * @version 2.2.1
 * @since Class available since Release: v2.0.0
 * @author Jahidul Pabel Islam <me@jahidulpabelislam.com>
 * @copyright 2010-2019 JPI
 */

namespace JPI\API;

use JPI\API\Controller\Auth;
use JPI\API\Controller\Projects;

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
    private function checkAPIVersion(): ?array {
        $uri = $this->api->uriArray;

        $version = $uri[0] ?? "";

        $shouldBeVersion = "v" . Config::API_VERSION;
        if ($version !== $shouldBeVersion) {
            $response = Responder::get()->getUnrecognisedAPIVersionResponse();
        }

        return $response ?? null;
    }

    /**
     * @return array An appropriate response to auth request
     */
    private function executeAuthAction(array $uri, string $method): ?array {
        $authAction = $uri[2] ?? "";

        if ($method === "POST") {
            if ($authAction === "login" && (!isset($uri[3]) || $uri[3] === "")) {
                $response = Auth::login();
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

        return $response ?? null;
    }

    private function executeProjectsGetAction(array $uri): ?array {
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
            $response = Projects::getProjects();
        }

        return $response ?? null;
    }

    private function executeProjectsPostAction(array $uri): ?array {
        if (
            isset($uri[2]) && $uri[2] !== ""
            && isset($uri[3]) && $uri[3] === "images"
            && !isset($uri[4])
        ) {
            $response = Projects::addProjectImage($uri[2]);
        }
        else if (!isset($uri[2])) {
            $response = Projects::addProject();
        }

        return $response ?? null;
    }

    private function executeProjectsPutAction(array $uri): ?array {
        if (isset($uri[2]) && $uri[2] !== "" && !isset($uri[3])) {
            $response = Projects::updateProject($uri[2]);
        }

        return $response ?? null;
    }

    private function executeProjectsDeleteAction(array $uri): ?array {
        if (isset($uri[2]) && $uri[2] !== "") {
            if (
                isset($uri[3]) && $uri[3] === "images"
                && isset($uri[4]) && $uri[4] !== ""
                && !isset($uri[5])
            ) {
                $response = Projects::deleteProjectImage($uri[2], $uri[4]);
            }
            else if (!isset($uri[3])) {
                $response = Projects::deleteProject($uri[2]);
            }
        }

        return $response ?? null;
    }

    /**
     * @return array An appropriate response to projects request
     */
    private function executeProjectsAction(array $uri, string $method): ?array {
        $methodFormatted = ucfirst(strtolower($method));
        $functionName = "executeProjects{$methodFormatted}Action";
        if (method_exists($this, $functionName)) {
            return $this->{$functionName}($uri);
        }

        return Responder::get()->getMethodNotAllowedResponse();
    }

    /**
     * Try and execute the requested action
     *
     * @return array An appropriate response to request
     */
    private function executeAction(): ?array {
        $method = $this->api->method;
        $uri = $this->api->uriArray;

        $entity = $uri[1] ?? "";

        $entityFormatted = ucfirst(strtolower($entity));
        $functionName = "execute{$entityFormatted}Action";
        if (method_exists($this, $functionName)) {
            return $this->{$functionName}($uri, $method);
        }

        return null;
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
