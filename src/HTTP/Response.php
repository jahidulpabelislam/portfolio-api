<?php

namespace App\HTTP;

class Response {

    protected $statusCode = 500;
    protected $statusMessage = "Internal Server Error";
    protected $body = [];
    protected $headers = [];

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

    public function addHeader(string $header, string $value) {
        $this->headers[$header] = $value;
    }

    public function getHeaders(): array {
        return $this->headers;
    }

    public function setBody(array $body) {
        $this->body = $body;
    }

    public function getBody(): array {
        return $this->body;
    }

    protected function sendHeaders() {
        foreach ($this->getHeaders() as $name => $value) {
            header("{$name}: {$value}");
        }

        header("HTTP/1.1 {$this->getStatusCode()} {$this->getStatusMessage()}");
    }

    protected function sendBody(bool $pretty) {
        $encodeParams = $pretty ? JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES : 0;
        echo json_encode($this->getBody(), $encodeParams);
    }

    public function send(bool $pretty = false) {
        $this->sendHeaders();
        $this->sendBody($pretty);
    }

}
