<?php
/**
 * Sets Constants for Connection to the Database & API's Auth
 * as well as other settings.
 *
 * PHP version 7.1+
 *
 * @version 1.1.3
 * @since Class available since Release: v3.0.0
 * @author Jahidul Pabel Islam <me@jahidulpabelislam.com>
 * @copyright 2010-2019 JPI
 */

namespace JPI\API;

if (!defined("ROOT")) {
    die();
}

class Config {

    public const API_VERSION = "3";

    // IP of database server
    public const DB_IP = "localhost"; // TODO: CHANGE ME
    // Database name to use in server
    public const DB_NAME = "jpi"; // TODO: CHANGE ME
    // Username to database
    public const DB_USERNAME = "root"; // TODO: CHANGE ME
    // Password for the user above
    public const DB_PASSWORD = ""; // TODO: CHANGE ME

    // The secret key to use in Firebase's JWT
    public const PORTFOLIO_ADMIN_SECRET_KEY = "changeme"; // TODO: CHANGE ME

    // Username for portfolio admin
    public const PORTFOLIO_ADMIN_USERNAME = "root"; // TODO: CHANGE ME
    // Hashed password for portfolio admin
    public const PORTFOLIO_ADMIN_PASSWORD = "root"; // TODO: CHANGE ME

    // A list of other domains that can call this API
    public const ALLOWED_DOMAINS = [
        "jahidulpabelislam.com",
        "cms.jahidulpabelislam.com",
    ];

    private static $instance;

    public $debug = false;

    public function __construct() {
        $environment = getenv("APPLICATION_ENV") ?? "production";

        // Only want debugging on development site
        $this->debug = false;
        if ($environment === "development") {
            $this->debug = true;
        }
    }

    /**
     * Singleton getter
     *
     * @return Config
     */
    public static function get(): Config {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}
