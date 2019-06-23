<?php
/**
 * All the general functions for the API.
 *
 * Can be used by multiple API's.
 *
 * PHP version 7.1+
 *
 * @version 4.0.0
 * @author Jahidul Pabel Islam <me@jahidulpabelislam.com>
 * @copyright 2010-2019 JPI
 */

namespace JPI\API;

if (!defined("ROOT")) {
    die();
}

class Core {

    public $method = "GET";
    public $uriArray = [];
    public $uriString = "";
    public $data = [];

    private static $instance;

    /**
     * Singleton getter
     */
    public static function get(): Core {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function extractMethodFromRequest() {
        $method = $_SERVER["REQUEST_METHOD"];

        $this->method = strtoupper($method);
    }

    private function extractURIFromRequest() {
        $uriString = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
        $uriString = strtolower(trim($uriString));
        $this->uriString = $uriString;

        // Get the individual parts of the request URI as an array
        $uriString = trim($uriString, "/");
        $uriArray = explode("/", $uriString);
        $this->uriArray = $uriArray;
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
        $this->data = $this->sanitizeData($_REQUEST);
    }

    public function extractFromRequest() {
        $this->extractMethodFromRequest();
        $this->extractURIFromRequest();
        $this->extractDataFromRequest();
    }

    public static function addTrailingSlash(string $url): string {
        $url = rtrim($url, " /");

        return "{$url}/";
    }

    /**
     * Generates a full URL from the URI user requested
     *
     * @param $uriArray array The URI user request as an array
     * @return string The full URI user requested
     */
    public function getAPIURL(array $uriArray = null): string {
        $uri = $uriArray ? implode("/", $uriArray) : $this->uriString;

        $protocol = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") ? "https" : "http";
        $domain = rtrim($_SERVER["SERVER_NAME"], "/");

        $fullURL = "{$protocol}://{$domain}/{$uri}";
        $fullURL = self::addTrailingSlash($fullURL);

        return $fullURL;
    }

    private function isFieldValid(string $field): bool {
        $data = $this->data;

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
     * @param $requiredFields array Array of required data keys
     * @return bool Whether data required is provided & is valid or not
     */
    public function hasRequiredFields(array $requiredFields): bool {

        // Loops through each required field, and bails early with false if at least one is invalid
        foreach ($requiredFields as $field) {
            if (!$this->isFieldValid($field)) {
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
    public function getInvalidFields(array $requiredFields): array {
        $invalidFields = array_filter($requiredFields, function($field) {
            return !$this->isFieldValid($field);
        });

        return $invalidFields;
    }

    private function setCORSHeaders(array &$response) {
        $originURL = $_SERVER["HTTP_ORIGIN"] ?? "";

        // Strip the protocol from domain
        $originDomain = str_replace(["http://", "https://"], "", $originURL);

        // If the domain if allowed send correct header response back
        if (in_array($originDomain, Config::ALLOWED_DOMAINS)) {
            header("Access-Control-Allow-Origin: {$originURL}");
            header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
            header("Access-Control-Allow-Headers: Process-Data, Authorization");

            // Override meta data, and respond with all endpoints available
            if ($this->method === "OPTIONS") {
                $response["meta"]["ok"] = true;
                $response["meta"]["status"] = 200;
                $response["meta"]["message"] = "OK";
            }
        }
    }

    private function setCacheHeaders() {
        $notCachedURLs = [
            "/v" . Config::API_VERSION . "/auth/session/",
        ];

        // Set cache for 31 days for some GET Requests
        if ($this->method === "GET" && !in_array($this->uriString, $notCachedURLs)) {
            $secondsToCache = 2678400; // 31 days

            header("Cache-Control: max-age={$secondsToCache}, public");

            $expiryTimestamp = time() + $secondsToCache;
            $expiresTime = gmdate("D, d M Y H:i:s e", $expiryTimestamp);
            header("Expires: {$expiresTime}");

            header("Pragma: cache");
        }
    }

    /**
     * Send the response response back
     *
     * @param $response array The response generated from the request so far
     */
    public function sendResponse(array $response) {
        $this->setCORSHeaders($response);
        $this->setCacheHeaders();

        // Set defaults 'ok', 'status' & 'message' if not set
        $response["meta"]["ok"] = $response["meta"]["ok"] ?? false;

        $isSuccessful = $response["meta"]["ok"];

        $response["meta"]["status"] = $response["meta"]["status"] ?? ($isSuccessful ? 200 : 500);
        $response["meta"]["message"] = $response["meta"]["message"] ?? ($isSuccessful ? "OK" : "Internal Server Error");

        // Send back all the data sent in request
        $response["meta"]["data"] = $this->data;
        $response["meta"]["method"] = $this->method;
        $response["meta"]["uri"] = $this->uriString;

        $status = $response["meta"]["status"];
        $message = $response["meta"]["message"];

        header("HTTP/1.1 {$status} {$message}");
        header("Content-Type: application/json");

        // Check if requested to send json
        $isSendingJson = (stripos($_SERVER["HTTP_ACCEPT"], "application/json") !== false);

        $encodeParams = $isSendingJson ? 0 : JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES;
        echo json_encode($response, $encodeParams);
        die();
    }
}

Core::get();
