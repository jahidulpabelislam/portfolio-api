<?php
/*
 * Sets Constants for Connection to the Database & API's Auth
 * as well as other settings.
 *
 * PHP version 7
 *
 * @author Jahidul Pabel Islam <me@jahidulpabelislam.com>
 * @version 1
 * @link https://github.com/jahidulpabelislam/portfolio-api/
 * @since Class available since Release: v3
 * @copyright 2010-2018 JPI
*/

namespace JPI\API;

if (!defined("ROOT")) {
    die();
}

class Config {

    const API_VERSION = "3";

    // IP of database server
    const DB_IP = "localhost"; // TODO CHANGE ME
    // Database name to use in server
    const DB_NAME = "jpi"; // TODO CHANGE ME
    // Username to database
    const DB_USERNAME = "root"; // TODO CHANGE ME
    // Password for the user above
    const DB_PASSWORD = ""; // TODO CHANGE ME

    // The secret key to use in Firebase's JWT
    const PORTFOLIO_ADMIN_SECRET_KEY = "changeme"; // TODO CHANGE ME

    // Username for portfolio admin
    const PORTFOLIO_ADMIN_USERNAME = "root"; // TODO CHANGE ME
    // Hashed password for portfolio admin
    const PORTFOLIO_ADMIN_PASSWORD = "root"; // TODO CHANGE ME

    private static $instance = null;

    public $debug = false;

    public function __construct() {
        $environment = !empty(getenv("APPLICATION_ENV")) ? getenv("APPLICATION_ENV") : "development";
        // Don't want debugging on live/production site
        if ($environment === "production") {
            $this->debug = false;
            ini_set("display_errors", 0);
        }
        else {
            $this->debug = true;
            error_reporting(E_ALL);
            ini_set("display_errors", 1);
        }
    }

    /**
     * Singleton getter
     *
     * @return Config
     */
    public static function get() {

        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}

Config::get();