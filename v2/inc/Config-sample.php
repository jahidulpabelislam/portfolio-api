<?php
/*
 * Sets Constants for Connection to the Database
 * @author Jahidul Pabel Islam
*/

namespace JPI\API;

date_default_timezone_set("Europe/London");
ini_set('display_errors', 0);

class Config {
	
	const DEBUG = false;
	
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
}