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

    protected $router = null;

    private function initRoutes() {
        $router = $this->router;

        $router->setBasePath("/v" . Config::get()->api_version);

        $projectsController = Projects::class;
        $authController = Auth::class;

        $router->addRoute("/projects/{projectId}/images/{id}/", "GET", $projectsController, "getProjectImage", "projectImage");
        $router->addRoute("/projects/{projectId}/images/{id}/", "DELETE", $projectsController, "deleteProjectImage");
        $router->addRoute("/projects/{projectId}/images/", "GET", $projectsController, "getProjectImages", "projectImages");
        $router->addRoute("/projects/{projectId}/images/", "POST", $projectsController, "addProjectImage");
        $router->addRoute("/projects/{id}/", "GET", $projectsController, "getProject", "project");
        $router->addRoute("/projects/{id}/", "PUT", $projectsController, "updateProject");
        $router->addRoute("/projects/{id}/", "DELETE", $projectsController, "deleteProject");
        $router->addRoute("/projects/", "GET", $projectsController, "getProjects");
        $router->addRoute("/projects/", "POST", $projectsController, "addProject");
        $router->addRoute("/auth/login/", "POST", $authController, "login");
        $router->addRoute("/auth/logout/", "DELETE", $authController, "logout");
        $router->addRoute("/auth/session/", "GET", $authController, "getStatus");
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
        $uri = StringHelper::addLeadingSlash($uri);

        return StringHelper::addTrailingSlash("{$protocol}://{$domain}{$uri}");
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

            // Override meta data, and respond with all endpoints available
            if ($this->method === "OPTIONS") {
                $content = $this->response->getContent();
                $content["meta"]["status"] = 200;
                $content["meta"]["message"] = "OK";
                $this->response->setContent($content);
            }
        }
    }

    protected function getLastModifiedFromRequest(): ?string {
        return $_SERVER["HTTP_IF_MODIFIED_SINCE"] ?? null;
    }

    public function getLastModified() {
        return $this->response->headers->get("Last-Modified", "");
    }

    public function getETagFromRequest(): ?string {
        return $_SERVER["HTTP_IF_NONE_MATCH"] ?? null;
    }

    public function getETag(): string {
        return $this->response->headers->get("ETag", "");
    }

    /**
     * Process the response.
     */
    public function processResponse() {
        $content = $this->response->getContent();
        $isSuccessful = $content["ok"] ?? false;
        $defaults = [
            "meta" => [
                "status" => ($isSuccessful ? 200 : 500),
                "message" => ($isSuccessful ? "OK" : "Internal Server Error"),
                "method" => $this->method,
                "uri" => $this->uri,
                "params" => $this->params,
                "data" => $this->data,
                "files" => $this->files,
            ],
        ];
        unset($content["ok"]);
        $content = array_replace_recursive($defaults, $content);
        $this->response->setContent($content);

        $this->setCORSHeaders();

        $content = $this->response->getContent();

        if (
            $this->getETag() === $this->getETagFromRequest() ||
            $this->getLastModified() === $this->getLastModifiedFromRequest()
        ) {
            $content["meta"]["status"] = 304;
            $content["meta"]["message"] = "Not Modified";
        }

        $status = $content["meta"]["status"];
        $message = $content["meta"]["message"];
        $this->response->setStatus($status, $message);

        $this->response->addHeader("Content-Type", "application/json");
    }

    public function handleRequest() {
        $this->extractFromRequest();

        $this->response = $this->getRouter()->performRequest();

        $this->processResponse();

        $sendPretty = StringHelper::stringToBoolean($this->params["pretty"] ?? null);
        $this->response->send($sendPretty);
    }

}
