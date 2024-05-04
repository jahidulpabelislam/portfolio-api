<?php

declare(strict_types=1);

/**
 * Stores settings for the application.
 */

namespace App;

final class Config {

    use \JPI\Utils\Singleton;

    protected array $values = [];

    protected function __construct() {
        $config = $this;

        include_once __DIR__ . "/../config.php";

        if (file_exists(__DIR__ . "/../config.local.php")) {
            include_once __DIR__ . "/../config.local.php";
        }
    }

    public function __set(string $key, $value): void {
        $this->values[$key] = $value;
    }

    public function __get(string $key) {
        return $this->values[$key] ?? null;
    }
}
