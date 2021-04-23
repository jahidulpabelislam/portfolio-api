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

use App\Controller\Auth;
use App\Controller\Projects;
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

    private function initRoutes() {
        $router = $this->router;

        $router->setBasePath("/v" . Config::get()->api_version);

        $projectsController = Projects::class;
        $authController = Auth::class;

        $successResponseCallback = static function () {
            return new Response(200);
        };

        $router->addRoute("/projects/{projectId}/images/{id}/", "GET", [$projectsController, "getProjectImage", "projectImage"]);
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

        $router->addRoute("/auth/session/", "GET", [$authController, "getStatus"]);
        $router->addRoute("/auth/session/", "OPTIONS", $successResponseCallback);
    }

    public function getRouter(): Router {
        if ($this->router === null) {
            $this->router = new Router($this);
            $this->initRoutes();
        }
        return $this->router;
    }

    private function extractMethodFromRequest() {
        $this->method = strtoupper($_SERVER["REQUEST_METHOD"]);
    }

    private function extractURIFromRequest() {
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

    private function extractDataFromRequest() {
        $this->data = self::sanitizeData($_POST);
        $this->params = self::sanitizeData($_GET);
        $this->request = self::sanitizeData($_REQUEST);
    }

    private function extractFilesFromRequest() {
        $this->files = $_FILES;
    }

    public function extractFromRequest() {
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

    private static function isFieldValid(array $data, string $field): bool {
        if (!isset($data[$field])) {
            return false;
        }

        $value = $data[$field];

        if (is_array($value)) {
            return (count($value) > 0);
        }

        if (is_string($value)) {
            return ($value !== "");
        }

        return false;
    }

    /**
     * Check if all the required data is provided
     * And data provided is not empty
     *
     * @param $entityClass string the Entity class
     * @param $data array Array of required data keys
     * @return bool Whether data required is provided & is valid or not
     */
    public static function hasRequiredFields(string $entityClass, array $data): bool {

        // Loops through each required field, and bails early with false if at least one is invalid
        foreach ($entityClass::getRequiredFields() as $field) {
            if (!self::isFieldValid($data, $field)) {
                return false;
            }
        }

        // Otherwise data provided is ok and data required is provided
        return true;
    }

    /**
     * Get all invalid required data fields
     *
     * @param $data array Data/values to check fields against
     * @param $requiredFields string[] Array of required data keys
     * @return string[] An array of invalid data fields
     */
    public static function getInvalidFields(array $data, array $requiredFields): array {
        return array_filter($requiredFields, static function(string $field) use ($data) {
            return !self::isFieldValid($data, $field);
        });
    }

    private function setCORSHeaders() {
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
            "Cache-Control" => "max-age={$secondsToCache}, public",
            "Expires" => new DateTime("+{$secondsToCache} seconds"),
            "Pragma" => "cache",
            "ETag" => true,
        ];
    }

    /**
     * Process the response.
     */
    public function processResponse() {
        $response = $this->response;

        $content = $response->getContent();
        $defaults = [
            "meta" => [
                "status" => "",
                "message" => "",
                "method" => $this->method,
                "uri" => $this->uri,
                "params" => $this->params,
            ],
        ];
        if ($this->method === "POST") {
            $defaults["meta"]["data"] = $this->data;
            $defaults["meta"]["files"] = $this->files;
        }
        $content = array_replace_recursive($defaults, $content);

        $this->setCORSHeaders();

        if ($response->headers->get("ETag", "") === $this->getETagFromRequest()) {
            $response->setStatus(304);
        }

        $content["meta"]["status"] = $response->getStatusCode();
        $content["meta"]["message"] = $response->getStatusMessage();

        $response->setContent($content);

        $response->addHeader("Content-Type", "application/json");
    }

    public function handleRequest() {
        $this->extractFromRequest();

        $this->response = $this->getRouter()->performRequest();

        $this->processResponse();

        $sendPretty = StringHelper::stringToBoolean($this->params["pretty"] ?? null);
        $this->response->send($sendPretty);
    }

}
