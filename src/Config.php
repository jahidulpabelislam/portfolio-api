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

if (!defined("ROOT")) {
    die();
}

class Config {

    private static $instance;

    public $debug = false;

    public function __construct() {
        $environment = getenv("APPLICATION_ENV") ?? "production";

        // Only want debugging on development site
        if ($environment === "development") {
            $this->debug = true;
        }
    }

    /**
     * Singleton getter
     */
    public static function get(): Config {
        if (!self::$instance) {
            self::$instance = new Config();
        }

        return self::$instance;
    }

    public function __get(string $name) {
        $name = strtoupper($name);
        if (defined($name)) {
            return constant($name);
        }

        return null;
    }
}
