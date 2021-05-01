<?php

namespace App\HTTP;

use App\Core;
use App\Utils\StringHelper;

class Request {

    public $method;

    public $uri;
    public $uriParts;

    public $params;
    public $data;

    public $files;

    public $headers;

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

    public function __construct() {
        $this->method = strtoupper($_SERVER["REQUEST_METHOD"]);

        $this->uri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

        // Get the individual parts of the request URI as an array
        $uri = StringHelper::removeSlashes($this->uri);
        $this->uriParts = explode("/", $uri);

        $this->params = self::sanitizeData($_GET);

        if ($this->method === "POST") {
            $this->data = self::sanitizeData($_POST);
            $this->files = $_FILES;
        }

        $this->headers = new Headers(apache_request_headers());
    }

    /**
     * Generates a full URL of current request
     *
     * @return string The full URI user requested
     */
    public function getURL(): string {
        return Core::makeFullURL($this->uri);
    }

    public function getParam(string $key, $default = null) {
        return $this->params[$key] ?? $default;
    }

}
