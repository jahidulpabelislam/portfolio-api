<?php
/*
 * Connects to a database and set up to send and receive data
 * using application specific constants defined in the Config.php file.
 *
 * A reusable file for other projects.
 *
 * MySQL & PDO specific.
 *
 * PHP version 7
 *
 * @author Jahidul Pabel Islam <me@jahidulpabelislam.com>
 * @version 3
 * @link https://github.com/jahidulpabelislam/portfolio-api/
 * @copyright 2014-2018 JPI
*/

namespace JPI\API;

if (!defined("ROOT")) {
	die();
}

class Database {

	private $db = null;

	private $config = null;

	private $error = null;

	private static $instance = null;

	/**
	 * Connects to a MySQL engine
	 * using application constants DB_IP, DB_USERNAME, and DB_PASSWORD
	 * defined in Config.php
	 */
	public function __construct() {

		$this->config = Config::get();

		$dsn = "mysql:host=" . Config::DB_IP . ";dbname=" . Config::DB_NAME . ";charset-UTF-8";
		$option = [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,];

		try {
			$this->db = new \PDO($dsn, Config::DB_USERNAME, Config::DB_PASSWORD, $option);
		}
		catch (\PDOException $error) {
			error_log("Error creating a connection to database: " . $error->getMessage(). ", full error: " . $error);
			if ($this->config->debug) {
				$this->error = $error->getMessage();
			}
		}
	}

	/**
	 * Singleton getter
	 *
	 * @return Database
	 */
	public static function get() {

		if (self::$instance === null) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Executes a SQL query
	 *
	 * @param $query string The SQL query to run
	 * @param null $bindings array Array of any bindings to use with the SQL query
	 * @return array Array of data or meta feedback
	 */
	public function query($query, $bindings = null) {

		$response = [
			"meta" => [
				"affected_rows" => 0,
			],
			"rows" => [],
		];

		if ($this->db) {

			try {

				// Check if any bindings to execute
				if (isset($bindings)) {
					$executedQuery = $this->db->prepare($query);
					$executedQuery->execute($bindings);
				}
				else {
					$executedQuery = $this->db->query($query);
				}

				// If query was a select, return array of data
				if (strpos($query, "SELECT") !== false) {
					$response["rows"] = $executedQuery->fetchAll(\PDO::FETCH_ASSOC);
				}

				// Add the count of how many rows were effected
				$response["meta"]["affected_rows"] = $executedQuery->rowCount();
			}
			catch (\PDOException $error) {
				error_log("Error executing query on database: " . $error->getMessage() . " using query: $query and bindings: " . print_r($bindings, true) . ", full error: " . $error);

				$response["meta"]["ok"] = false;
				$response["meta"]["feedback"] = "Problem with Server.";

				if ($this->config->debug) {
					$response["meta"]["feedback"] = $error->getMessage();
				}
			}
		}
		else {
			$response["meta"]["ok"] = false;
			$response["meta"]["feedback"] = "Problem with Server.";

			if ($this->config->debug) {
				$response["meta"]["feedback"] = $this->error;
			}
		}

		return $response;
	}

	/**
	 * @return int The ID of last inserted row of data
	 */
	public function getLastInsertedId() {
		if ($this->db) {
			return $this->db->lastInsertId();
		}

		return null;
	}
}

Database::get();