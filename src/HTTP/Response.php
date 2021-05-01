<?php

namespace App\HTTP;

use DateTime;
use DateTimeZone;

class Response {

    private const CACHE_TIMEZONE = "Europe/London";

    /**
     * @var DateTimeZone|null
     */
    private static $cacheTimeZone = null;

    public static function getCacheTimeZone(): DateTimeZone {
        if (is_null(static::$cacheTimeZone)) {
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

    public function setCacheHeaders(array $headers): void {
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

    public function setStatus(int $code, ?string $message = null): void {
        $this->statusCode = $code;
        $this->statusMessage = $message;
    }

    public function getStatusCode(): int {
        return $this->statusCode;
    }

    public function getStatusMessage(): string {
        if (is_null($this->statusMessage)) {
            $this->statusMessage = Status::getMessageForCode($this->getStatusCode());
        }

        return $this->statusMessage;
    }

    public function addHeader(string $header, $value): void {
        $this->headers->set($header, $value);
    }

    public function getHeaders(): Headers {
        return $this->headers;
    }

    public function setContent(array $content): void {
        $this->content = $content;
    }

    public function getContent(): array {
        return $this->content;
    }

    public function getETag(): string {
        return md5(json_encode($this->getContent()));
    }

    protected function sendHeaders(): void {
        foreach ($this->headers as $name => $value) {
            if (is_array($value)) {
                $value = implode(", ", $value);
            }

            header("$name: $value");
        }

        header("HTTP/1.1 {$this->getStatusCode()} {$this->getStatusMessage()}");
    }

    protected function sendContent(bool $pretty): void {
        $encodeParams = $pretty ? JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES : 0;
        echo json_encode($this->getContent(), $encodeParams);
    }

    public function send(bool $pretty = false): void {
        $this->sendHeaders();
        $this->sendContent($pretty);
    }

}
