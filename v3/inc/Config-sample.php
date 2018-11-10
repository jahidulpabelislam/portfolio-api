<?php
/*
 * Sets Constants for Connection to the Database
 * @author Jahidul Pabel Islam
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
		date_default_timezone_set("Europe/London");
		
		$environment = !empty(getenv('APPLICATION_ENV')) ? getenv('APPLICATION_ENV') : 'development';
		// Only want Google Analytic for live site
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