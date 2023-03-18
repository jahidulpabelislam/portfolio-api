<?php

/**
 * Stores settings for the application.
 */

namespace App;

class Config {

    protected array $values = [];

    public function __set(string $key, $value): void {
        $this->values[$key] = $value;
    }

    public function __get(string $key) {
        return $this->values[$key] ?? null;
    }
}
