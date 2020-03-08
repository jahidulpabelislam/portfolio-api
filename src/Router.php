<?php
/**
 * A RESTful API router.
 *
 * PHP version 7.1+
 *
 * @author Jahidul Pabel Islam <me@jahidulpabelislam.com>
 * @since Class available since Release: v2.0.0
 * @copyright 2010-2020 JPI
 */

namespace App;

if (!defined("ROOT")) {
    die();
}

use App\Controller\Auth;
use App\Controller\Projects;

class Router {

    use Responder;

    private $api;

    public function __construct(Core $api) {
        $this->api = $api;
    }

    /**
     * Check that the requested API version is valid, if so return empty array
     * else return appropriate response (array)
     */
    private function checkAPIVersion(): ?array {
        $version = $this->api->uriParts[0] ?? "";

        $shouldBeVersion = "v" . Config::get()->api_version;
        if ($version !== $shouldBeVersion) {
            $response = $this->getUnrecognisedAPIVersionResponse();
        }

        return $response ?? null;
    }

    /**
     * @return array An appropriate response to auth request
     */
    private function executeAuthAction(): ?array {
        $uriParts = $this->api->uriParts;
        $method = $this->api->method;

        $authAction = $uriParts[2] ?? "";

        if ($method === "POST") {
            if ($authAction === "login" && !isset($uriParts[3])) {
                $response = (new Auth($this->api))->login();
            }
        }
        else if ($method === "DELETE") {
            if ($authAction === "logout" && !isset($uriParts[3])) {
                $response = Auth::logout();
            }
        }
        else if ($method === "GET") {
            if ($authAction === "session" && !isset($uriParts[3])) {
                $response = Auth::getAuthStatus();
            }
        }
        else {
            $response = $this->getMethodNotAllowedResponse();
        }

        return $response ?? null;
    }

    private function executeProjectsGetAction(): ?array {
        $uriParts = $this->api->uriParts;

        if (isset($uriParts[2]) && $uriParts[2] !== "") {
            $projectId = $uriParts[2];

            if (isset($uriParts[3]) && $uriParts[3] === "images") {
                if (isset($uriParts[4]) && $uriParts[4] !== "" && !isset($uriParts[5])) {
                    $response = Projects::getProjectImage($projectId, $uriParts[4]);
                }
                else if (!isset($uriParts[4])) {
                    $response = Projects::getProjectImages($projectId);
                }
            }
            else if (!isset($uriParts[3])) {
                $response = Projects::getProject($projectId);
            }
        }
        else if (!isset($uriParts[2])) {
            $response = (new Projects($this->api))->getProjects();
        }

        return $response ?? null;
    }

    private function executeProjectsPostAction(): ?array {
        $uriParts = $this->api->uriParts;

        if (
            isset($uriParts[2]) && $uriParts[2] !== ""
            && isset($uriParts[3]) && $uriParts[3] === "images"
            && !isset($uriParts[4])
        ) {
            $response = (new Projects($this->api))->addProjectImage($uriParts[2]);
        }
        else if (!isset($uriParts[2])) {
            $response = (new Projects($this->api))->addProject();
        }

        return $response ?? null;
    }

    private function executeProjectsPutAction(): ?array {
        $uriParts = $this->api->uriParts;

        if (isset($uriParts[2]) && $uriParts[2] !== "" && !isset($uriParts[3])) {
            $response = (new Projects($this->api))->updateProject($uriParts[2]);
        }

        return $response ?? null;
    }

    private function executeProjectsDeleteAction(): ?array {
        $uriParts = $this->api->uriParts;

        if (isset($uriParts[2]) && $uriParts[2] !== "") {
            if (
                isset($uriParts[3]) && $uriParts[3] === "images"
                && isset($uriParts[4]) && $uriParts[4] !== ""
                && !isset($uriParts[5])
            ) {
                $response = Projects::deleteProjectImage($uriParts[2], $uriParts[4]);
            }
            else if (!isset($uriParts[3])) {
                $response = Projects::deleteProject($uriParts[2]);
            }
        }

        return $response ?? null;
    }

    /**
     * @return array An appropriate response to projects request
     */
    private function executeProjectsAction(): ?array {
        $methodFormatted = ucfirst(strtolower($this->api->method));
        $functionName = "executeProjects{$methodFormatted}Action";
        if (method_exists($this, $functionName)) {
            return $this->{$functionName}();
        }

        return $this->getMethodNotAllowedResponse();
    }

    /**
     * Try and execute the requested action
     *
     * @return array An appropriate response to request
     */
    private function executeAction(): ?array {
       $entityName = $this->api->uriParts[1] ?? null;

        // Make sure value is the correct case
        if ($entityName && strtolower($entityName) === $entityName) {
            $entityNameFormatted = ucfirst($entityName);
            $functionName = "execute{$entityNameFormatted}Action";
            if (method_exists($this, $functionName)) {
                return $this->{$functionName}();
            }
        }

        return null;
    }

    /**
     * Try and perform the necessary actions needed to fulfil the request that a user made
     */
    public function performRequest() {
        // Here check the requested API version, if okay return empty array
        // else returns appropriate response
        $response = $this->checkAPIVersion();

        // Only try to perform the action if API version check above returned okay
        if ($response === null) {
            $response = $this->executeAction();

            // If at this point response is empty, we didn't recognise the action
            if ($response === null) {
                $response = $this->getUnrecognisedURIResponse();
            }
        }

        return $response;
    }
}
