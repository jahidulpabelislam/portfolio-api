<?php

/**
 * A RESTful API router.
 */

namespace App\HTTP;

use App\Auth\GuardedControllerInterface;
use App\Auth\Manager as AuthManager;
use App\Core;
use JPI\Utils\Collection;
use Exception;
use JPI\Utils\URL;

class Router {

    use Responder;

    protected $routes = [];
    protected $namedRoutes = [];

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

    protected function getFullPath(string $path): URL {
        return (new URL("/v" . Core::VERSION . "/"))
            ->addPath($path)
        ;
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

    public function makeUrl(string $name, array $params): URL {
        $path = $this->makePath($name, $params);
        return Core::get()->makeFullURL($path);
    }

    private function getIdentifiersFromMatches(array $matches): array {
        $identifiers = [];

        foreach ($matches as $key => $match) {
            if (!is_numeric($key)) {
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

                    $this->request->identifiers = new Collection($identifiers);

                    if (isset($route["callable"])) {
                        return $route["callable"](...$identifiers);
                    }

                    $controllerClass = $route["controller"];
                    $controller = new $controllerClass($this->request);

                    if (
                        $controller instanceof GuardedControllerInterface
                        && !in_array($route["function"], $controller->getPublicFunctions())
                        && !AuthManager::isLoggedIn($this->request)
                    ) {
                        return static::getNotAuthorisedResponse();
                    }

                    return call_user_func_array([$controller, $route["function"]], array_values($identifiers));
                }

                if ($method === "OPTIONS") {
                    return new Response(200);
                }

                return new Response(405, [
                    "message" => "Method {$this->request->method} not allowed on " . $this->request->getURL() . ".",
                ]);
            }
        }

        return new Response(404, [
            "message" => "Unrecognised URI (" . $this->request->getURL() . ").",
        ]);
    }

    public function getMethodsForPath(): array {
        $uri = $this->request->uri;
        foreach ($this->routes as $path => $routes) {
            $pathRegex = $this->pathToRegex($path);
            if (preg_match($pathRegex, $uri)) {
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

        $shouldBeVersionPart = "v" . Core::VERSION;
        if ($version === $shouldBeVersionPart) {
            return null;
        }

        $shouldBeURIParts = $this->request->uriParts;
        $shouldBeURIParts[0] = $shouldBeVersionPart;

        $shouldBeURL = Core::get()->makeFullURL(implode("/", $shouldBeURIParts));

        return new Response(404, [
            "message" => "Unrecognised API version. Current version is " . Core::VERSION . ", so please update requested URL to $shouldBeURL.",
        ]);
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
            $response = $this->executeAction();
        }

        return $response;
    }
}
