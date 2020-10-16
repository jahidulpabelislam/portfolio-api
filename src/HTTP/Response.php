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

    protected $statusCode;
    protected $statusMessage = null;
    protected $content;

    public $headers;

    public function __construct(int $statusCode = 500, array $content = [], array $headers = []) {
        $this->content = $content;
        $this->statusCode = $statusCode;
        $this->headers = new Headers($headers);
    }

    public function setCacheHeaders(array $headers) {
        $timeZone = static::getCacheTimeZone();

        if (isset($headers["Expires"]) && $headers["Expires"] instanceof DateTime) {
            $headers["Expires"]->setTimezone($timeZone);
            $headers["Expires"] = $headers["Expires"]->format("D, d M Y H:i:s") . " GMT";
        }

        if (isset($headers["ETag"]) && $headers["ETag"]) {
            $headers["ETag"] = $this->getETag();
        }

        foreach ($headers as $header => $value) {
            $this->headers->set($header, $value);
        }
    }

    public function setStatus(int $code, ?string $message = null) {
        $this->statusCode = $code;
        $this->statusMessage = $message;
    }

    public function getStatusCode(): int {
        return $this->statusCode;
    }

    public function getStatusMessage(): string {
        if ($this->statusMessage === null) {
            $this->statusMessage = Status::getMessageForCode($this->getStatusCode());
        }

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

    public function getETag(): string {
        return md5(json_encode($this->getContent()));
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
