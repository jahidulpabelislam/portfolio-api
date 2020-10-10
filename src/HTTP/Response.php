<?php

namespace App\HTTP;

use DateTime;
use DateTimeZone;

class Response {

    private const CACHE_TIMEZONE = "Europe/London";

    private static $cacheTimeZone = null;

    public static function getCacheTimeZone(): DateTimeZone {
        if (static::$cacheTimeZone === null) {
            static::$cacheTimeZone = new DateTimeZone(self::CACHE_TIMEZONE);
        }

        return static::$cacheTimeZone;
    }

    protected $statusCode = 500;
    protected $statusMessage = "Internal Server Error";
    protected $content = [];

    public $headers = null;

    public function __construct() {
        $this->headers = new Headers();
    }

    public function setStatus(int $code, string $message) {
        $this->statusCode = $code;
        $this->statusMessage = $message;
    }

    public function getStatusCode(): int {
        return $this->statusCode;
    }

    public function getStatusMessage(): string {
        return $this->statusMessage;
    }

    public function addHeader(string $header, $value) {
        $this->headers->set($header, $value);
    }

    public function getHeaders(): Headers {
        return $this->headers;
    }

    public function setContent(array $content) {
        $this->content = $content;
    }

    public function getContent(): array {
        return $this->content;
    }

    protected function sendHeaders() {
        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }

        header("HTTP/1.1 {$this->getStatusCode()} {$this->getStatusMessage()}");
    }

    protected function sendContent(bool $pretty) {
        $encodeParams = $pretty ? JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES : 0;
        echo json_encode($this->getContent(), $encodeParams);
    }

    public function send(bool $pretty = false) {
        $this->sendHeaders();
        $this->sendContent($pretty);
    }

}
