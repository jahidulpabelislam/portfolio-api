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

use App\Database\Exception as DBException;
use App\Entity\User;
use App\HTTP\Controller\AuthGuarded;
use App\HTTP\Responder;
use App\HTTP\Response;
use App\Utils\StringHelper;
use Exception;

class Router {

    use Responder;

    protected $basePath = "";

    protected $routes = [];
    protected $namedRoutes = [];

    public function setBasePath(string $basePath): void {
        $this->basePath = $basePath;
    }

    public function getBasePath(): string {
        return $this->basePath;
    }

    /**
     * @param $path string
     * @param $method string
     * @param $callback Closure|array
     * @param $name string|null
     */
    public function addRoute(string $path, string $method, $callback, string $name = null): void {
        if (!isset($this->routes[$path])) {
            $this->routes[$path] = [];
        }

        $route = [];

        if (is_array($callback)) {
            $route["controller"] = $callback[0];
            $route["function"] = $callback[1];
        }
        else if (is_callable($callback)) {
            $route["callable"] = $callback;
        }

        $this->routes[$path][$method] = $route;

        if ($name) {
            $this->namedRoutes[$name] = $path;
        }
    }

    protected function getFullPath(string $path): string {
        $basePath = $this->getBasePath();
        if ($basePath !== "") {
            $path = StringHelper::addTrailingSlash($basePath) . StringHelper::removeLeadingSlash($path);
        }

        return $path;
    }

    /**
     * @param $name string
     * @param $params array
     * @return string
     * @throws Exception
     */
    public function makePath(string $name, array $params): string {
        if (!isset($this->namedRoutes[$name])) {
            throw new Exception("Named route $name not defined");
        }

        $path = $this->namedRoutes[$name];
        $url = $this->getFullPath($path);

        foreach ($params as $identifier => $value) {
            $url = str_replace("/{{$identifier}}/", "/$value/", $url);
        }

        return $url;
    }

    public function makeUrl(string $name, array $params): string {
        $path = $this->makePath($name, $params);
        return Core::makeFullURL($path);
    }

    private function getIdentifiersFromMatches(array $matches): array {
        $identifiers = [];

        foreach ($matches as $key => $match) {
            if (is_numeric($key)) {
                $identifiers[$key] = $match;
            }
        }

        return $identifiers;
    }

    private function pathToRegex(string $path): string {
        $path = $this->getFullPath($path);

        $regex = preg_replace("/\/{([A-Za-z]*?)}\//", "/(?<$1>[^/]*)/", $path);
        $regex = str_replace("/", "\/", $regex);
        return "/^{$regex}$/";
    }

    /**
     * Try and execute the requested action
     *
     * @return Response An appropriate response to request
     * @throws DBException
     */
    private function executeAction(): Response {
        $uri = $this->request->uri;
        $method = $this->request->method;

        foreach ($this->routes as $path => $routes) {
            $pathRegex = $this->pathToRegex($path);
            if (preg_match($pathRegex, $uri, $matches)) {
                if (isset($routes[$method])) {
                    $route = $routes[$method];
                    array_shift($matches);
                    $identifiers = $this->getIdentifiersFromMatches($matches);

                    if (isset($route["callable"])) {
                        return $route["callable"](...$identifiers);
                    }

                    $controllerClass = $route["controller"];
                    $controller = new $controllerClass($this->request);

                    if (
                        $controller instanceof AuthGuarded
                        && !in_array($route["function"], $controller->publicFunctions)
                        && !User::isLoggedIn()
                    ) {
                        return static::getNotAuthorisedResponse();
                    }

                    return call_user_func_array([$controller, $route["function"]], $identifiers);
                }

                if ($method === "OPTIONS") {
                    return new Response(200);
                }

                return $this->getMethodNotAllowedResponse();
            }
        }

        return $this->getUnrecognisedURIResponse();
    }

    public function getMethodsForPath(): array {
        $uri = $this->request->uri;
        foreach ($this->routes as $path => $routes) {
            $pathRegex = $this->pathToRegex($path);
            if (preg_match($pathRegex, $uri, $matches)) {
                return array_keys($routes);
            }
        }

        return [];
    }

    /**
     * Check that the requested API version is valid, if so return empty array
     * else return appropriate response (array)
     */
    private function checkAPIVersion(): ?Response {
        $version = $this->request->uriParts[0] ?? null;

        $shouldBeVersion = "v" . Config::get()->api_version;
        if ($version !== $shouldBeVersion) {
            return $this->getUnrecognisedAPIVersionResponse();
        }

        return null;
    }

    /**
     * Try and perform the necessary actions needed to fulfil the request that a user made
     */
    public function performRequest(): Response {
        // Here check the requested API version, if okay return empty array
        // else returns appropriate response
        $response = $this->checkAPIVersion();

        // Only try to perform the action if API version check above returned okay
        if (is_null($response)) {
            try {
                $response = $this->executeAction();
            }
            catch (DBException $exception) {
                error_log($exception->getMessage() . ". Full error: $exception");
                $response = new Response();
            }
        }

        return $response;
    }

}
