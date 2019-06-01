<?php
/*
 * All the general functions for the API.
 *
 * Can be used by multiple API's.
 *
 * PHP version 7
 *
 * @author Jahidul Pabel Islam <me@jahidulpabelislam.com>
 * @version 3.1.0
 * @link https://github.com/jahidulpabelislam/portfolio-api/
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
     *
     * @return self
     */
    public static function get() {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function extractMethodFromRequest() {
        $method = $_SERVER["REQUEST_METHOD"];
        $method = strtoupper($method);

        $this->method = $method;
    }

    private function extractURIFromRequest() {
        $uriString = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
        $uriString = trim($uriString);
        $this->uriString = $uriString;

        $uriString = trim($uriString, " /");
        $uriString = strtolower($uriString);

        // Get the individual parts of the request URI as an array
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
            $value = stripslashes(urldecode(trim($value)));
        }

        return $value;
    }

    private function extractDataFromRequest() {
        $data = [];

        foreach ($_REQUEST as $field => $value) {
            $data[$field] = $this->sanitizeData($value);
        }

        $this->data = $data;
    }

    public function extractFromRequest() {
        $this->extractMethodFromRequest();
        $this->extractURIFromRequest();
        $this->extractDataFromRequest();
    }

    /**
     * Generates a full url from the URI user requested
     *
     * @param $uriArray array The URI user request as an array
     * @return string The full URI user requested
     */
    public function getAPIURL(array $uriArray = null): string {
        $uriString = $uriArray ? implode("/", $uriArray) : $this->uriString;

        if (!empty($uriString)) {
            $uriString = trim($uriString, "/");
            $uriString .= "/";
        }

        $protocol = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") ? "https" : "http";
        $url = "{$protocol}://" . $_SERVER["SERVER_NAME"];

        $url = rtrim($url, "/");
        $url .= "/{$uriString}";

        return $url;
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

        // Loops through each required data field for the request
        foreach ($requiredFields as $field) {

            // Checks if the required field is provided and is not empty
            if (!$this->isFieldValid($field)) {
                // Return false as required field is not provided or empty
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
    private function getInvalidFields(array $requiredFields): array {
        // Loops through each required data field for the request and only gets invalid fields
        $invalidFields = array_filter($requiredFields, function($field) {
            return !$this->isFieldValid($field);
        });

        return $invalidFields;
    }

    /**
     * Send necessary meta data back when required data/fields is not provided/valid
     *
     * @param $requiredFields array Array of the data required
     * @return array Array of meta data
     */
    public function getInvalidFieldsResponse(array $requiredFields): array {
        $invalidFields = $this->getInvalidFields($requiredFields);

        return [
            "meta" => [
                "status" => 400,
                "message" => "Bad Request",
                "required_fields" => $requiredFields,
                "invalid_fields" => $invalidFields,
                "feedback" => "The necessary data was not provided, missing/invalid fields: " . implode(", ", $invalidFields) . ".",
            ],
        ];
    }

    /**
     * Generate meta data to send back when the method provided is not allowed on the URI
     *
     * @return array Array of meta data
     */
    public function getMethodNotAllowedResponse(): array {
        return [
            "meta" => [
                "status" => 405,
                "message" => "Method Not Allowed.",
                "feedback" => "Method {$this->method} not allowed on " . $this->getAPIURL() . ".",
            ],
        ];
    }

    /**
     * Send necessary meta data back when user isn't logged in correctly
     *
     * @return array Array of meta data
     */
    public static function getNotAuthorisedResponse(): array {
        return [
            "meta" => [
                "status" => 401,
                "message" => "Unauthorized",
                "feedback" => "You need to be logged in!",
            ],
        ];
    }

    /**
     * Generate response data to send back when the URI provided is not recognised
     *
     * @return array Array of meta data
     */
    public function getUnrecognisedURIResponse(): array {
        return [
            "meta" => [
                "status" => 404,
                "feedback" => "Unrecognised URI (" . $this->getAPIURL() . ").",
                "message" => "Not Found",
            ],
        ];
    }

    /**
     * Generate response data to send back when the requested API version is not recognised
     *
     * @return array Array of meta data
     */
    public function getUnrecognisedAPIVersionResponse(): array {
        $shouldBeVersion = "v" . Config::API_VERSION;

        $shouldBeURI = $this->uriArray;
        $shouldBeURI[0] = $shouldBeVersion;
        $shouldBeURL = $this->getAPIURL($shouldBeURI);

        return [
            "meta" => [
                "status" => 404,
                "feedback" => "Unrecognised API version. Current version is " . Config::API_VERSION . ", so please update requested URL to {$shouldBeURL}.",
                "message" => "Not Found",
            ],
        ];
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
            $secondsToCache = 2678400;
            $expiresTime = gmdate("D, d M Y H:i:s", time() + $secondsToCache) . " GMT";
            header("Cache-Control: max-age={$secondsToCache}, public");
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

        // Just remove any internal meta data
        unset($response["meta"]["affected_rows"]);

        $this->setCORSHeaders($response);
        $this->setCacheHeaders();

        // Figure out the correct meta responses to return
        $isSuccessful = $response["meta"]["ok"] = $response["meta"]["ok"] ?? false;
        $status = $response["meta"]["status"] = $response["meta"]["status"] ?? ($isSuccessful ? 200 : 500);
        $message = $response["meta"]["message"] = $response["meta"]["message"] ?? ($isSuccessful ? "OK" : "Internal Server Error");

        // Send back all the data sent in request
        $response["meta"]["data"] = $this->data;
        $response["meta"]["method"] = $this->method;
        $response["meta"]["uri"] = $this->uriString;

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
