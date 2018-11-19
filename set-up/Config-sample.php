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
 * @copyright 2014-2018 JPI
*/

namespace JPI\API;

class Config {

	public $debug = false;

	private static $instance = null;

	// IP of database server
	const DB_IP = 'localhost';
	// Database name to use in server
	const DB_NAME = 'jpi';
	// Username to database
	const DB_USERNAME = 'root';
	// Password for the user above
	const DB_PASSWORD = '';

	// Username for portfolio admin
	const PORTFOLIO_ADMIN_USERNAME = 'root';
	// Hashed password for portfolio admin
	const PORTFOLIO_ADMIN_PASSWORD = 'root';

	public function __construct() {
		$environment = !empty(getenv('APPLICATION_ENV')) ? getenv('APPLICATION_ENV') : 'development';
		// Don't want debugging on live/production site
		if ($environment === 'production') {
			$this->debug = false;
			ini_set('display_errors', 0);
		}
		else {
			$this->debug = true;
			error_reporting(E_ALL);
			ini_set('display_errors', 1);
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