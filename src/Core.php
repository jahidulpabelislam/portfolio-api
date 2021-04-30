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
use App\Utils\Singleton;
use App\Utils\StringHelper;
use DateTime;

class Core {

    use Singleton;

    /**
     * @var Response|null
     */
    private $response = null;

    public $method = "GET";

    public $uri = "";
    public $uriParts = [];

    public $data = [];
    public $params = [];
    public $request = [];

    public $files = [];

    /**
     * @var Router|null
     */
    protected $router = null;

    private function initRoutes(): void {
        $router = $this->router;

        $router->setBasePath("/v" . Config::get()->api_version);

        $projectsController = Projects::class;
        $authController = Auth::class;

        $successResponseCallback = static function () {
            return new Response(200);
        };

        $router->addRoute("/projects/{projectId}/images/{id}/", "GET", [$projectsController, "getProjectImage"], "projectImage");
        $router->addRoute("/projects/{projectId}/images/{id}/", "DELETE", [$projectsController, "deleteProjectImage"]);
        $router->addRoute("/projects/{projectId}/images/{id}/", "OPTIONS", $successResponseCallback);

        $router->addRoute("/projects/{projectId}/images/", "GET", [$projectsController, "getProjectImages"], "projectImages");
        $router->addRoute("/projects/{projectId}/images/", "POST", [$projectsController, "addProjectImage"]);
        $router->addRoute("/projects/{projectId}/images/", "OPTIONS", $successResponseCallback);

        $router->addRoute("/projects/{id}/", "GET", [$projectsController, "getProject"], "project");
        $router->addRoute("/projects/{id}/", "PUT", [$projectsController, "updateProject"]);
        $router->addRoute("/projects/{id}/", "DELETE", [$projectsController, "deleteProject"]);
        $router->addRoute("/projects/{id}/", "OPTIONS", $successResponseCallback);

        $router->addRoute("/projects/", "GET", [$projectsController, "getProjects"]);
        $router->addRoute("/projects/", "POST", [$projectsController, "addProject"]);
        $router->addRoute("/projects/", "OPTIONS", $successResponseCallback);

        $router->addRoute("/auth/login/", "POST", [$authController, "login"]);
        $router->addRoute("/auth/login/", "OPTIONS", $successResponseCallback);

        $router->addRoute("/auth/logout/", "DELETE", [$authController, "logout"]);
        $router->addRoute("/auth/logout/", "OPTIONS", $successResponseCallback);

        $router->addRoute("/auth/status/", "GET", [$authController, "getStatus"]);
        $router->addRoute("/auth/status/", "OPTIONS", $successResponseCallback);
    }

    public function getRouter(): Router {
        if ($this->router === null) {
            $this->router = new Router($this);
            $this->initRoutes();
        }
        return $this->router;
    }

    private function extractMethodFromRequest(): void {
        $this->method = strtoupper($_SERVER["REQUEST_METHOD"]);
    }

    private function extractURIFromRequest(): void {
        $this->uri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

        // Get the individual parts of the request URI as an array
        $uri = StringHelper::removeSlashes($this->uri);
        $this->uriParts = explode("/", $uri);
    }

    /**
     * @param $value array|string
     * @return array|string
     */
    private static function sanitizeData($value) {
        if (is_array($value)) {
            $newArrayValues = [];
            foreach ($value as $subKey => $subValue) {
                $newArrayValues[$subKey] = self::sanitizeData($subValue);
            }
            $value = $newArrayValues;
        }
        else if (is_string($value)) {
            $value = urldecode(stripslashes(trim($value)));
        }

        return $value;
    }

    private function extractDataFromRequest(): void {
        $this->data = self::sanitizeData($_POST);
        $this->params = self::sanitizeData($_GET);
        $this->request = self::sanitizeData($_REQUEST);
    }

    private function extractFilesFromRequest(): void {
        $this->files = $_FILES;
    }

    public function extractFromRequest(): void {
        $this->extractMethodFromRequest();
        $this->extractURIFromRequest();
        $this->extractDataFromRequest();
        $this->extractFilesFromRequest();
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

    /**
     * Generates a full URL of current request
     *
     * @return string The full URI user requested
     */
    public function getRequestedURL(): string {
         return static::makeFullURL($this->uri);
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
        $originURL = $_SERVER["HTTP_ORIGIN"] ?? "";

        // Strip the protocol from domain
        $originDomain = str_replace(["http://", "https://"], "", $originURL);

        // If the domain if allowed send correct header response back
        if (in_array($originDomain, Config::get()->allowed_domains)) {
            $this->response->addHeader("Access-Control-Allow-Origin", $originURL);
            $this->response->addHeader("Access-Control-Allow-Methods", "GET, POST, PUT, DELETE, OPTIONS");
            $this->response->addHeader("Access-Control-Allow-Headers", "Process-Data, Authorization");
            $this->response->addHeader("Vary", "Origin");
        }
    }

    public function getETagFromRequest(): ?string {
        return $_SERVER["HTTP_IF_NONE_MATCH"] ?? null;
    }

    public static function getDefaultCacheHeaders(): array {
        $secondsToCache = 2678400; // 31 days

        return [
            "Cache-Control" => "max-age=$secondsToCache, public",
            "Expires" => new DateTime("+$secondsToCache seconds"),
            "Pragma" => "cache",
            "ETag" => true,
        ];
    }

    /**
     * Process the response.
     */
    public function processResponse(): void {
        $response = $this->response;

        $content = $response->getContent();
        $defaults = [
            "meta" => [
                "status_code" => "",
                "status_message" => "",
                "method" => $this->method,
                "uri" => $this->uri,
                "params" => $this->params,
            ],
        ];
        $content = array_replace_recursive($defaults, $content);

        $this->setCORSHeaders();

        if ($response->headers->get("ETag", "") === $this->getETagFromRequest()) {
            $response->setStatus(304);
        }

        $content["meta"]["status_code"] = $response->getStatusCode();
        $content["meta"]["status_message"] = $response->getStatusMessage();

        $response->setContent($content);

        $response->addHeader("Content-Type", "application/json");
    }

    public function handleRequest(): void {
        $this->extractFromRequest();

        $this->response = $this->getRouter()->performRequest();

        $this->processResponse();

        $sendPretty = StringHelper::stringToBoolean($this->params["pretty"] ?? null);
        $this->response->send($sendPretty);
    }

}
