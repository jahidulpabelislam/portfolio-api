<?php

/**
 * Sets Constants for Connection to the Database & API's Auth
 * as well as other settings.
 *
 * PHP version 7.1+
 *
 * @author Jahidul Pabel Islam <me@jahidulpabelislam.com>
 * @since Class available since Release: v3.0.0
 * @copyright 2010-2020 JPI
 */

namespace App;

use App\Utils\Singleton;

class Config {

    use Singleton;

    public $debug = false;

    private function __construct() {
        $environment = getenv("APPLICATION_ENV") ?? "production";

        // Only want debugging on development site
        if ($environment === "development") {
            $this->debug = true;
        }
    }

    public function __get(string $name) {
        $name = strtoupper($name);
        if (defined($name)) {
            return constant($name);
        }

        return null;
    }

}
