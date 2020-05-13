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

use App\Controller\Auth;
use App\Controller\Projects;
use App\Database\Exception;

class Router {

    use Responder;

    protected $routes;

    public function __construct(Core $core) {
        $this->core = $core;

        $this->routes = [
            "\/projects\/(?<projectId>[0-9]*)\/images\/(?<id>[0-9]*)\/" => [
                "GET" => [
                    "controller" => Projects::class,
                    "method" => "getProjectImage",
                ],
                "DELETE" => [
                    "controller" => Projects::class,
                    "method" => "deleteProjectImage",
                ],
            ],
            "\/projects\/(?<projectId>[0-9]*)\/images\/" => [
                "GET" => [
                    "controller" => Projects::class,
                    "method" => "getProjectImages",
                ],
                "POST" => [
                    "controller" => Projects::class,
                    "method" => "addProjectImage",
                ],
            ],
            "\/projects\/(?<id>[0-9]*)\/" => [
                "GET" => [
                    "controller" => Projects::class,
                    "method" => "getProject",
                ],
                "PUT" => [
                    "controller" => Projects::class,
                    "method" => "updateProject",
                ],
                "DELETE" => [
                    "controller" => Projects::class,
                    "method" => "deleteProject",
                ],
            ],
            "\/projects\/" => [
                "GET" => [
                    "controller" => Projects::class,
                    "method" => "getProjects",
                ],
                "POST" => [
                    "controller" => Projects::class,
                    "method" => "addProject",
                ],
            ],
            "\/auth\/login\/" => [
                "POST" => [
                    "controller" => Auth::class,
                    "method" => "login",
                ],
            ],
            "\/auth\/logout\/" => [
                "DELETE" => [
                    "controller" => Auth::class,
                    "method" => "logout",
                ],
            ],
            "\/auth\/session\/" => [
                "GET" => [
                    "controller" => Auth::class,
                    "method" => "getStatus",
                ],
            ],
        ];
    }

    /**
     * Check that the requested API version is valid, if so return empty array
     * else return appropriate response (array)
     */
    private function checkAPIVersion(): ?array {
        $version = $this->core->uriParts[0] ?? null;

        $shouldBeVersion = "v" . Config::get()->api_version;
        if ($version !== $shouldBeVersion) {
            $response = $this->getUnrecognisedAPIVersionResponse();
        }

        return $response ?? null;
    }

    private function getIdentifiersFromMatches(array $matches): array {
        $identifiers = [];

        foreach ($matches as $key => $match) {
            if (!is_numeric($key)) {
                $identifiers[$key] = $match[0];
            }
        }

        return $identifiers;
    }

    /**
     * Try and execute the requested action
     *
     * @return array An appropriate response to request
     */
    private function executeAction(): ?array {
        $uri = $this->core->uri;
        foreach ($this->routes as $regex => $routeData) {
            $regex = "/^\/v" . Config::get()->api_version . "{$regex}$/";
            if (preg_match_all($regex, $uri, $matches)) {
                if (isset($routeData[$this->core->method])) {
                    $action = $routeData[$this->core->method];
                    $controllerClass = $action["controller"];
                    $controller = new $controllerClass($this->core);
                    $identifiers = $this->getIdentifiersFromMatches($matches);
                    return call_user_func_array([$controller, $action["method"]], $identifiers);
                }

                return $this->getMethodNotAllowedResponse();
            }
        }

        return $this->getUnrecognisedURIResponse();
    }

    /**
     * Try and perform the necessary actions needed to fulfil the request that a user made
     */
    public function performRequest(): ?array {
        // Here check the requested API version, if okay return empty array
        // else returns appropriate response
        $response = $this->checkAPIVersion();

        // Only try to perform the action if API version check above returned okay
        if ($response === null) {
            try {
                $response = $this->executeAction();
            }
            catch (Exception $exception) {
                error_log($exception->getMessage() . ". Full error: {$exception}");
                $response = [
                    "ok" => false,
                ];
            }
        }

        return $response;
    }

}
