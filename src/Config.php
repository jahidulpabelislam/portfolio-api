<?php

/**
 * Stores settings for the application.
 */

namespace App;

use App\Utils\Singleton;

class Config {

    use Singleton;

    protected $values = [];

    public function __set(string $key, $value) {
        $this->values[$key] = $value;
    }

    public function __get(string $key) {
        return $this->values[$key] ?? null;
    }
}
