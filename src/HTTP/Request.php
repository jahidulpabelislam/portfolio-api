<?php

namespace App\HTTP;

use App\Core;
use App\Utils\ArrayCollection;
use App\Utils\Collection;
use JPI\Utils\URL;

class Request {

    public $server;

    public $method;

    public $uri;
    public $uriParts;

    public $params;
    public $data;

    public $files;

    public $headers;

    public $identifiers;

    /**
     * @param $value array|string
     * @return Collection|string
     */
    private static function sanitizeData($value) {
        if (is_array($value)) {
            $newArrayValues = new Collection();
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
        $this->server = new ArrayCollection($_SERVER);

        $this->method = strtoupper($this->server->get("REQUEST_METHOD"));

        $this->uri = parse_url($this->server->get("REQUEST_URI"), PHP_URL_PATH);

        // Get the individual parts of the request URI as an array
        $uri = URL::removeSlashes($this->uri);
        $this->uriParts = explode("/", $uri);

        $this->params = self::sanitizeData($_GET);

        if (in_array($this->method, ["POST", "PUT"])) {
            $json = file_get_contents("php://input");
            $data = json_decode($json, true);
            $this->data = self::sanitizeData($data);

            $files = [];
            foreach ($_FILES as $key => $item) {
                $files[$key] = $this->normaliseFileItem($item);
            }
            $this->files = $files;
        }

        $this->headers = new Headers(apache_request_headers());

        $this->identifiers = new ArrayCollection();
    }

    private function normaliseFileItem(array $item): array {
        if (!is_array($item["tmp_name"])) {
            return $item;
        }

        $normalised = [];

        foreach (array_keys($item["tmp_name"]) as $key) {
            $normalised[$key] = $this->normaliseFileItem([
                "tmp_name" => $item["tmp_name"][$key],
                "size" => $item["size"][$key],
                "error" => $item["error"][$key],
                "name" => $item["name"][$key],
                "type" => $item["type"][$key],
            ]);
        }

        return $normalised;
    }

    /**
     * Generates a full URL of current request
     *
     * @return URL
     */
    public function getURL(): URL {
        return Core::get()->makeFullURL($this->uri);
    }

    public function hasParam(string $param): bool {
        return isset($this->params[$param]);
    }

    public function getParam(string $param, $default = null) {
        return $this->params->get($param, $default);
    }

    public function getIdentifier(string $identifier, $default = null) {
        return $this->identifiers->get($identifier, $default);
    }
}
