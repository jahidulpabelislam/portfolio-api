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

    protected $routes = [];

    public function __construct(Core $core) {
        $this->core = $core;

        $projectsController = Projects::class;
        $authController = Auth::class;

        $this->addRoute("/projects/(?<projectId>[^/]*)/images/(?<id>[^/]*)/", "GET", $projectsController, "getProjectImage");
        $this->addRoute("/projects/(?<projectId>[^/]*)/images/(?<id>[^/]*)/", "DELETE", $projectsController, "deleteProjectImage");
        $this->addRoute("/projects/(?<projectId>[^/]*)/images/", "GET", $projectsController, "getProjectImages");
        $this->addRoute("/projects/(?<projectId>[^/]*)/images/", "POST", $projectsController, "addProjectImage");
        $this->addRoute("/projects/(?<id>[^/]*)/", "GET", $projectsController, "getProject");
        $this->addRoute("/projects/(?<id>[^/]*)/", "PUT", $projectsController, "updateProject");
        $this->addRoute("/projects/(?<id>[^/]*)/", "DELETE", $projectsController, "deleteProject");
        $this->addRoute("/projects/", "GET", $projectsController, "getProjects");
        $this->addRoute("/projects/", "POST", $projectsController, "addProject");
        $this->addRoute("/auth/login/", "POST", $authController, "login");
        $this->addRoute("/auth/logout/", "DELETE", $authController, "logout");
        $this->addRoute("/auth/session/", "GET", $authController, "getStatus");
    }

    public function addRoute(string $path, string $method, string $controller, string $function) {
        if (!isset($this->routes[$path])) {
            $this->routes[$path] = [];
        }

        $this->routes[$path][$method] = [
            "controller" => $controller,
            "function" => $function,
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
        foreach ($this->routes as $route => $routeData) {
            $routeRegex = str_replace("/", "\/", $route);
            $regex = "/^\/v" . Config::get()->api_version . "{$routeRegex}$/";
            if (preg_match_all($regex, $uri, $matches)) {
                if (isset($routeData[$this->core->method])) {
                    $action = $routeData[$this->core->method];
                    $controllerClass = $action["controller"];
                    $controller = new $controllerClass($this->core);
                    $identifiers = $this->getIdentifiersFromMatches($matches);
                    return call_user_func_array([$controller, $action["function"]], $identifiers);
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
