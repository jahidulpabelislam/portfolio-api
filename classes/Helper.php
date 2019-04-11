<?php
/*
 * All the general functions for the API.
 *
 * Can be used by multiple API's.
 *
 * PHP version 7
 *
 * @author Jahidul Pabel Islam <me@jahidulpabelislam.com>
 * @version 3
 * @link https://github.com/jahidulpabelislam/portfolio-api/
 * @copyright 2010-2018 JPI
*/

namespace JPI\API;

if (!defined("ROOT")) {
    die();
}

class Helper {

    public $method = 'GET';
    public $path = [];
    public $data = [];

    private static $instance = null;

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

    public function extractFromRequest() {

        // Get the requested method
        $method = strtoupper($_SERVER["REQUEST_METHOD"]);

        $requestedPath = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

        $requestedURI = !empty($requestedPath) ? trim($requestedPath, "/") : "";

        $requestedURI = strtolower($requestedURI);

        // Get the individual parts of the request URI as an array
        $requestedURIArray = explode("/", $requestedURI);

        $data = [];
        foreach ($_REQUEST as $key => $value) {
            $data[$key] = stripslashes(urldecode($_REQUEST[$key]));
        }

        $this->method = $method;
        $this->path = $requestedURIArray;
        $this->data = $data;
    }

    /**
     * Check if all the required data is provided
     * And data provided is not empty
     *
     * @param $data array Array of the data provided for the request
     * @param $requiredData array Array of required data keys
     * @return bool Whether data required is provided & is valid or not
     */
    public static function hasRequiredData(array $data, array $requiredData): bool {

        // Loops through each data needed for the request
        foreach ($requiredData as $dataKey) {

            // Checks if the data needed is provided and is not empty
            if (!isset($data[$dataKey]) || trim($data[$dataKey]) === "") {
                // Return false as data needed is not provided or empty
                return false;
            }
        }

        // Otherwise data provided is ok and data required is provided
        return true;
    }

    /**
     * Send necessary meta data back when needed required data is not provided
     *
     * @param $requiredData array Array of the data required
     * @return array Array of meta data
     */
    public static function getDataNotProvidedResponse(array $requiredData): array {
        return [
            "meta" => [
                "status" => 400,
                "message" => "Bad Request",
                "required_data" => $requiredData,
                "feedback" => "The necessary data was not provided.",
            ],
        ];
    }

    /**
     * Generates a full url from the URI user requested
     *
     * @param array $path array The URI user request as an array
     * @return string The Full URI user requested
     */
    public function getAPIURL($path = null): string {
        if (!$path) {
            $path = $this->path;
        }

        $protocol = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] != "off") ? "https" : "http";
        $url = "{$protocol}://" . $_SERVER["SERVER_NAME"];

        $explodedPath = implode("/", $path);

        if ($explodedPath) {
            $explodedPath .= "/";
        }

        $url .= "/{$explodedPath}";

        return $url;
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
                "message" => "Method not allowed.",
                "feedback" => "{$this->method} Method Not Allowed on " . $this->getAPIURL(),
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
                "feedback" => "Unrecognised URI (" . $this->getAPIURL() . ")",
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

        $shouldBePath = $this->path;
        $shouldBePath[0] = $shouldBeVersion;
        $shouldBeURL = self::getAPIURL($shouldBePath);

        return [
            "meta" => [
                "status" => 404,
                "feedback" => "Unrecognised API Version. Current version is " . Config::API_VERSION . ", so please update requested URL to {$shouldBeURL}.",
                "message" => "Not Found",
            ],
        ];
    }

    /**
     * Send the response response back
     *
     * @param $response array The response generated from the request so far
     */
    public function sendResponse(array $response) {

        // Just remove any internal meta data
        unset($response["meta"]["affected_rows"]);

        $data = $this->data;
        $method = $this->method;
        $path = $this->path;

        // Send back the data provided
        $response["meta"]["data"] = $data;
        // Send back the method requested
        $response["meta"]["method"] = $method;
        // Send back the path they requested
        $response["meta"]["path"] = $path;

        $originURL = $_SERVER["HTTP_ORIGIN"] ?? "";

        // Strip the protocol from domain
        $originDomain = str_replace("http://", "", $originURL);
        $originDomain = str_replace("https://", "", $originDomain);

        // If the domain if allowed send correct header response back
        if (in_array($originDomain, Config::ALLOWED_DOMAINS)) {
            header("Access-Control-Allow-Origin: {$originURL}");
            header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
            header("Access-Control-Allow-Headers: Process-Data, Authorization");

            if ($method === "OPTIONS") {
                $response["meta"]["status"] = 200;
                $response["meta"]["message"] = "OK";
            }
        }

        $response["meta"]["ok"] = isset($response["meta"]["ok"]) ? $response["meta"]["ok"] : false;

        // Figure out the correct meta responses to return
        if ($response["meta"]["ok"] === true) {
            $status = isset($response["meta"]["status"]) ? $response["meta"]["status"] : 200;
            $message = isset($response["meta"]["message"]) ? $response["meta"]["message"] : "OK";
        }
        else {
            $status = isset($response["meta"]["status"]) ? $response["meta"]["status"] : 500;
            $message = isset($response["meta"]["message"]) ? $response["meta"]["message"] : "Internal Server Error";
        }

        $response["meta"]["status"] = $status;
        $response["meta"]["message"] = $message;

        header("HTTP/1.1 {$status} {$message}");

        $notCachedURLs = ["session/"];

        // Set cache for 31 days for some GET Requests
        if ($method == "GET" && !in_array(Config::API_VERSION . implode("/", $path), $notCachedURLs)) {
            $secondsToCache = 2678400;
            $expiresTime = gmdate("D, d M Y H:i:s", time() + $secondsToCache) . " GMT";
            header("Cache-Control: max-age={$secondsToCache}, public");
            header("Expires: {$expiresTime}");
            header("Pragma: cache");
        }

        // Check if requested to send json
        $json = (stripos($_SERVER["HTTP_ACCEPT"], "application/json") !== false);

        header("Content-Type: application/json");

        // Send the response, send by json if json was requested
        if ($json) {
            echo json_encode($response);
        } // Else send by plain text
        else {
            echo json_encode($response, JSON_PRETTY_PRINT);
        }
        die();
    }
}

Helper::get();
