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

use DateTime;
use DateTimeZone;

class Core {

    private const CACHE_TIMEZONE = "Europe/London";

    private static $cacheTimeZone = null;
    private static $rowDateTimeFormat = "Y-m-d H:i:s e";

    private $response = [];

    public $method = "GET";

    public $uri = "";
    public $uriParts = [];

    public $data = [];
    public $params = [];
    public $request = [];

    public $files = [];

    protected $etag = null;
    protected $lastModified = null;

    private function extractMethodFromRequest() {
        $this->method = strtoupper($_SERVER["REQUEST_METHOD"]);
    }

    private function extractURIFromRequest() {
        $this->uri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

        // Get the individual parts of the request URI as an array
        $uri = Utilities::removeSlashes($this->uri);
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
     * Generates a full URL from the URI user requested
     *
     * @param $uriParts string[]|null The URI user request as an array
     * @return string The full URI user requested
     */
    public function getAPIURL(array $uriParts = null): string {
        $uri = $this->uri;
        if ($uriParts !== null) {
            $uri = implode("/", $uriParts);
            $uri = Utilities::addLeadingSlash($uri);
        }

        $protocol = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") ? "https" : "http";
        $domain = Utilities::removeTrailingSlash($_SERVER["SERVER_NAME"]);

        $fullURL = "{$protocol}://{$domain}{$uri}";

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

    protected function getLastModifiedFromRequest(): ?string {
        return $_SERVER["HTTP_IF_MODIFIED_SINCE"] ?? null;
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
            self::setHeader("Vary", "Origin");

            // Override meta data, and respond with all endpoints available
            if ($this->method === "OPTIONS") {
                $this->response["meta"]["ok"] = true;
                $this->response["meta"]["status"] = 200;
                $this->response["meta"]["message"] = "OK";
            }
        }
    }

    private static function getCacheTimeZone(): DateTimeZone {
        if (static::$cacheTimeZone === null) {
            static::$cacheTimeZone = new DateTimeZone(self::CACHE_TIMEZONE);
        }

        return static::$cacheTimeZone;
    }

    private static function createDateTimeFromRow(array $row): DateTime {
        $dateTime = DateTime::createFromFormat(static::$rowDateTimeFormat, $row["updated_at"]);
        $dateTime->setTimezone(static::getCacheTimeZone());

        return $dateTime;
    }

    public function getLastModified(): string {
        if ($this->lastModified === null) {
            $response = $this->response;

            $lastModified = "";

            if (!empty($response["row"])) {
                $latestRow = $response["row"];
                if (!empty($latestRow["updated_at"])) {
                    $latestDate = self::createDateTimeFromRow($latestRow);
                    $lastModified = $latestDate->format("D, j M Y H:i:s") . " GMT";
                }
            }

            $this->lastModified = $lastModified;
        }

        return $this->lastModified;
    }

    private function setLastModifiedHeaders() {
        $lastModified = $this->getLastModified();
        if ($lastModified) {
            self::setHeader("Last-Modified", $lastModified);
        }
    }

    public function getETagFromRequest(): ?string {
        return $_SERVER["HTTP_IF_NONE_MATCH"] ?? null;
    }

    public function getETag(): string {
        if ($this->etag === null) {
            $this->etag = md5(json_encode($this->response));
        }

        return $this->etag;
    }

    private function setCacheHeaders() {
        $notCachedURLs = [
            "/v" . Config::get()->api_version . "/auth/session/",
        ];

        // Set cache for 31 days for some GET Requests
        if ($this->method === "GET" && !in_array($this->uri, $notCachedURLs)) {
            $secondsToCache = 2678400; // 31 days

            self::setHeader("Cache-Control", "max-age={$secondsToCache}, public");

            $gmtTimeZone = static::getCacheTimeZone();
            $nowDate = new DateTime("+{$secondsToCache} seconds");
            $nowDate = $nowDate->setTimezone($gmtTimeZone);
            $expiresTime = $nowDate->format("D, d M Y H:i:s");
            self::setHeader("Expires", "{$expiresTime} GMT");

            self::setHeader("Pragma", "cache");

            $this->setLastModifiedHeaders();

            self::setHeader("ETag", $this->getETag());
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

        if (
            $this->getETag() === $this->getETagFromRequest() ||
            $this->getLastModified() === $this->getLastModifiedFromRequest()
        ) {
            header("HTTP/1.1 304 Not Modified");
            die();
        }

        $status = $this->response["meta"]["status"];
        $message = $this->response["meta"]["message"];

        header("HTTP/1.1 {$status} {$message}");
        self::setHeader("Content-Type", "application/json");

        // Check if requested to send json
        $accepts = explode(", ", $_SERVER["HTTP_ACCEPT"]);
        $isSendingJson = in_array("application/json", $accepts);

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
