<?php

/**
 * All the general functions for the API.
 *
 * Can be used by multiple API's.
 *
 * PHP version 7.1+
 *
 * @author Jahidul Pabel Islam <me@jahidulpabelislam.com>
 * @copyright 2010-2020 JPI
 */

namespace App;

use App\HTTP\Controller\Auth;
use App\HTTP\Controller\Projects;
use App\HTTP\Response;
use App\HTTP\Request;
use App\Utils\Singleton;
use App\Utils\StringHelper;
use DateTime;

class Core {

    use Singleton;

    /**
     * @var Request|null
     */
    protected $request = null;

    /**
     * @var Response|null
     */
    protected $response = null;

    /**
     * @var Router|null
     */
    protected $router = null;

    public function getRequest(): Request {
        if (is_null($this->request)) {
            $this->request = new Request();
        }

        return $this->request;
    }

    private function initRoutes(): void {
        $router = $this->router;

        $router->setBasePath("/v" . Config::get()->api_version);

        $projectsController = Projects::class;
        $authController = Auth::class;

        $router->addRoute("/projects/{projectId}/images/{id}/", "GET", [$projectsController, "getProjectImage"], "projectImage");
        $router->addRoute("/projects/{projectId}/images/{id}/", "DELETE", [$projectsController, "deleteProjectImage"]);

        $router->addRoute("/projects/{projectId}/images/", "GET", [$projectsController, "getProjectImages"], "projectImages");
        $router->addRoute("/projects/{projectId}/images/", "POST", [$projectsController, "addProjectImage"]);

        $router->addRoute("/projects/{id}/", "GET", [$projectsController, "getProject"], "project");
        $router->addRoute("/projects/{id}/", "PUT", [$projectsController, "updateProject"]);
        $router->addRoute("/projects/{id}/", "DELETE", [$projectsController, "deleteProject"]);

        $router->addRoute("/projects/", "GET", [$projectsController, "getProjects"]);
        $router->addRoute("/projects/", "POST", [$projectsController, "addProject"]);

        $router->addRoute("/auth/login/", "POST", [$authController, "login"]);
        $router->addRoute("/auth/logout/", "DELETE", [$authController, "logout"]);
        $router->addRoute("/auth/status/", "GET", [$authController, "getStatus"]);
    }

    public function getRouter(): Router {
        if (is_null($this->router)) {
            $this->router = new Router($this->getRequest());
            $this->initRoutes();
        }

        return $this->router;
    }

    /**
     * @param $uri string|string[]
     * @return string
     */
    public static function makeFullURL($uri): string {
        if (is_array($uri)) {
            $uri = implode("/", $uri);
        }

        $protocol = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") ? "https" : "http";
        $domain = StringHelper::removeTrailingSlash($_SERVER["SERVER_NAME"]);
        $uri = StringHelper::removeLeadingSlash($uri);

        return StringHelper::addTrailingSlash("$protocol://$domain/$uri");
    }

    public static function makeUrl(string $base, array $params): string {
        $fullURL = StringHelper::addTrailingSlash($base);

        if ($params && count($params)) {
            $fullURL .= "?" . http_build_query($params);
        }

        return $fullURL;
    }

    public static function isValueValid(array $data, string $key): bool {
        if (!isset($data[$key])) {
            return false;
        }

        $value = $data[$key];

        if (is_array($value)) {
            return (count($value) > 0);
        }

        if (is_string($value)) {
            return ($value !== "");
        }

        return false;
    }

    private function setCORSHeaders(): void {
        $origins = $this->getRequest()->headers->get("Origin");
        $originURL = $origins[0] ?? "";

        // Strip the protocol from domain
        $originDomain = str_replace(["https://", "http://"], "", $originURL);

        // If the domain if allowed send correct header response back
        if (in_array($originDomain, Config::get()->allowed_domains)) {
            $this->response->addHeader("Access-Control-Allow-Origin", $originURL);
            $this->response->addHeader("Access-Control-Allow-Methods", $this->getRouter()->getMethodsForPath());
            $this->response->addHeader("Access-Control-Allow-Headers", ["Authorization", "Content-Type"]);
            $this->response->addHeader("Vary", "Origin");
        }
    }

    public static function getDefaultCacheHeaders(): array {
        $secondsToCache = 2678400; // 31 days

        return [
            "Cache-Control" => ["max-age=$secondsToCache", "public"],
            "Expires" => new DateTime("+$secondsToCache seconds"),
            "Pragma" => "cache",
            "ETag" => true,
        ];
    }

    /**
     * Process the response.
     */
    public function processResponse(): void {
        $request = $this->getRequest();
        $response = $this->response;

        $content = $response->getContent();
        $defaults = [
            "meta" => [
                "status_code" => "",
                "status_message" => "",
                "method" => $request->method,
                "uri" => $request->uri,
                "params" => $request->params,
            ],
        ];
        $content = array_replace_recursive($defaults, $content);

        $this->setCORSHeaders();

        if ($response->headers->get("ETag", "") === $request->headers->get("If-None-Match")) {
            $response->setStatus(304);
        }

        $content["meta"]["status_code"] = $response->getStatusCode();
        $content["meta"]["status_message"] = $response->getStatusMessage();

        $response->setContent($content);

        $response->addHeader("Content-Type", "application/json");
    }

    public function handleRequest(): void {
        $this->response = $this->getRouter()->performRequest();

        $this->processResponse();

        $sendPretty = StringHelper::stringToBoolean($this->getRequest()->getParam("pretty"));
        $this->response->send($sendPretty);
    }

}
