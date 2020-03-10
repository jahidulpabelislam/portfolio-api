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

if (!defined("ROOT")) {
    die();
}

use DateTime;
use DateTimeZone;

class Core {

    use Responder;

    private const CACHE_TIMEZONE = "Europe/London";

    private $response = [];

    public $method = "GET";

    public $uri = "";
    public $uriParts = [];

    public $data = [];
    public $params = [];
    public $request = [];

    public $files = [];

    public static function removeLeadingSlash(string $url): string {
        if ($url[0] === "/") {
            $url = substr($url, 1);
        }

        return $url;
    }

    public static function removeTrailingSlash(string $url): string {
        if (substr($url, -1) === "/") {
            $url = substr($url, 0, -1);
        }

        return $url;
    }

    public static function removeSlashes(string $url): string {
        $url = self::removeLeadingSlash($url);
        $url = self::removeTrailingSlash($url);

        return $url;
    }

    private function extractMethodFromRequest() {
        $method = $_SERVER["REQUEST_METHOD"];

        $this->method = strtoupper($method);
    }

    private function extractURIFromRequest() {
        $uri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
        $this->uri = $uri;

        // Get the individual parts of the request URI as an array
        $uri = self::removeSlashes($uri);
        $uriParts = explode("/", $uri);
        $this->uriParts = $uriParts;
    }

    private function sanitizeData($value) {
        if (is_array($value)) {
            $newArrayValues = [];
            foreach ($value as $subKey => $subValue) {
                $newArrayValues[$subKey] = $this->sanitizeData($subValue);
            }
            $value = $newArrayValues;
        }
        else if (is_string($value)) {
            $value = urldecode(stripslashes(trim($value)));
        }

        return $value;
    }

    private function extractDataFromRequest() {
        $this->data = $this->sanitizeData($_POST);
        $this->params = $this->sanitizeData($_GET);
        $this->request = $this->sanitizeData($_REQUEST);
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

    public static function addTrailingSlash(string $url): string {
        $url = self::removeTrailingSlash($url);

        return "{$url}/";
    }

    /**
     * Generates a full URL from the URI user requested
     *
     * @param $uriParts array The URI user request as an array
     * @return string The full URI user requested
     */
    public function getAPIURL(array $uriParts = null): string {
        $uri = $uriParts ? implode("/", $uriParts) : $this->uri;

        $protocol = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") ? "https" : "http";
        $domain = self::removeTrailingSlash($_SERVER["SERVER_NAME"]);

        $uri = self::removeSlashes($uri);
        $fullURL = "{$protocol}://{$domain}/{$uri}";
        $fullURL = self::addTrailingSlash($fullURL);

        return $fullURL;
    }

    private function isFieldValid(array $data, string $field): bool {
        if (!isset($data[$field])) {
            return false;
        }

        $value = $data[$field];

        if (is_array($value)) {
            return (count($value) > 0);
        }
        else if (is_string($value)) {
            return ($value !== "");
        }

        return false;
    }

    /**
     * Check if all the required data is provided
     * And data provided is not empty
     *
     * @param $entityClass string the Entity class
     * @return bool Whether data required is provided & is valid or not
     */
    public function hasRequiredFields(string $entityClass, array $data): bool {

        // Loops through each required field, and bails early with false if at least one is invalid
        foreach ($entityClass::getRequiredFields() as $field) {
            if (!$this->isFieldValid($data, $field)) {
                return false;
            }
        }

        // Otherwise data provided is ok and data required is provided
        return true;
    }

    /**
     * Get all invalid required data fields
     *
     * @param $requiredFields array Array of required data keys
     * @return array An array of invalid data fields
     */
    public function getInvalidFields(array $data, array $requiredFields): array {
        $invalidFields = array_filter($requiredFields, function(string $field) use ($data) {
            return !$this->isFieldValid($data, $field);
        });

        return $invalidFields;
    }

    private static function setHeader(string $header, string $value) {
        header("{$header}: {$value}");
    }

    private function setCORSHeaders() {
        $originURL = $_SERVER["HTTP_ORIGIN"] ?? "";

        // Strip the protocol from domain
        $originDomain = str_replace(["http://", "https://"], "", $originURL);

        // If the domain if allowed send correct header response back
        if (in_array($originDomain, Config::get()->allowed_domains)) {
            self::setHeader("Access-Control-Allow-Origin", $originURL);
            self::setHeader("Access-Control-Allow-Methods", "GET, POST, PUT, DELETE, OPTIONS");
            self::setHeader("Access-Control-Allow-Headers", "Process-Data, Authorization");

            // Override meta data, and respond with all endpoints available
            if ($this->method === "OPTIONS") {
                $this->response["meta"]["ok"] = true;
                $this->response["meta"]["status"] = 200;
                $this->response["meta"]["message"] = "OK";
            }
        }
    }

    private function setLastModifiedHeaders() {
        $response = $this->response;

        if (empty($response["rows"]) && empty($response["row"])) {
            return;
        }

        $gmtTimeZone = new DateTimeZone(self::CACHE_TIMEZONE);
        $rowDateFormat = "Y-m-d H:i:s e";

        $latestRow = $response["row"] ?? $response["rows"][0] ?? null;

        if (!empty($response["rows"]) && count($response["rows"]) > 1) {
            $latestDate = 0;
            foreach ($response["rows"] as $row) {
                if (empty($row["updated_at"])) {
                    continue;
                }

                $updatedAtDate = DateTime::createFromFormat($rowDateFormat, $row["updated_at"]);
                $updatedAtDate = $updatedAtDate->setTimezone($gmtTimeZone);
                $updatedAt = $updatedAtDate->getTimestamp();

                if ($updatedAt > $latestDate) {
                    $latestDate = $updatedAt;
                    $latestRow = $row;
                }
            }
        }

        if (!empty($latestRow["updated_at"])) {
            $updatedAt = DateTime::createFromFormat($rowDateFormat, $latestRow["updated_at"]);
            $updatedAt = $updatedAt->setTimezone($gmtTimeZone);
            $lastModified = $updatedAt->format("D, j M Y H:i:s");

            self::setHeader("Last-Modified", $lastModified . " GMT");
            self::setHeader("ETag", md5($latestRow["id"] . $latestRow["updated_at"]));
        }
    }

    private function setCacheHeaders() {
        $notCachedURLs = [
            "/v" . Config::get()->api_version . "/auth/session/",
        ];

        // Set cache for 31 days for some GET Requests
        if ($this->method === "GET" && !in_array($this->uri, $notCachedURLs)) {
            $secondsToCache = 2678400; // 31 days

            self::setHeader("Cache-Control", "max-age={$secondsToCache}, public");

            $gmtTimeZone = new DateTimeZone(self::CACHE_TIMEZONE);
            $nowDate = new DateTime("+{$secondsToCache} seconds");
            $nowDate = $nowDate->setTimezone($gmtTimeZone);
            $expiresTime = $nowDate->format("D, d M Y H:i:s");
            self::setHeader("Expires", "{$expiresTime} GMT");

            self::setHeader("Pragma", "cache");

            $this->setLastModifiedHeaders();
        }
    }

    /**
     * Send the response response back
     *
     * @param $response array The response generated from the request so far
     */
    public function sendResponse(array $response) {
        $isSuccessful = $response["meta"]["ok"] ?? false;
        $defaults = [
            "meta" => [
                "ok" => false,
                "status" => ($isSuccessful ? 200 : 500),
                "message" => ($isSuccessful ? "OK" : "Internal Server Error"),
                "method" => $this->method,
                "uri" => $this->uri,
                "params" => $this->params,
                "data" => $this->data,
                "files" => $this->files,
            ],
        ];
        $this->response = array_replace_recursive($defaults, $response);

        $this->setCORSHeaders();
        $this->setCacheHeaders();

        $status = $this->response["meta"]["status"];
        $message = $this->response["meta"]["message"];

        header("HTTP/1.1 {$status} {$message}");
        self::setHeader("Content-Type", "application/json");

        // Check if requested to send json
        $isSendingJson = $_SERVER["HTTP_ACCEPT"] === "application/json";

        $encodeParams = $isSendingJson ? 0 : JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES;
        echo json_encode($this->response, $encodeParams);
        die();
    }

    public function handleRequest() {
        $this->extractFromRequest();

        $router = new Router($this);
        $response = $router->performRequest();

        $this->sendResponse($response);
    }
}
