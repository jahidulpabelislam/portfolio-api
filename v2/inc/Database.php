<?php
/*
 * Connects to a database and set up to send and receive data
 * using application constants defined in config.php. file
 * a reusable file for other projects
 * MySQL specific
 * @author Jahidul Pabel Islam
 */

class Database {

	private $db = null;
	private static $instance = null;

	/**
	 * Connects to a MySQL engine
	 * using application constants DB_IP, DB_USERNAME, and DB_PASSWORD
	 * defined in config.php
	 */
	public function __construct() {

		$dsn = "mysql:host=" . DB_IP . ";dbname=" . DB_NAME . ";charset-UTF-8";
		$option = array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION);

		try {
			$this->db = new PDO($dsn, DB_USERNAME, DB_PASSWORD, $option);
		}
		catch (PDOException $failure) {
			if (defined("DEBUG") && DEBUG) {
				echo $failure;
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
	 * Executes a sql query
	 * @param $query string the sql query to run
	 * @param null $bindings array array of any bindings to do with sql query
	 * @return array array of data
	 */
	public function query($query, $bindings = null) {

		if ($this->db) {

			try {

				//check if any bindings to execute
				if (isset($bindings)) {
					$result = $this->db->prepare($query);
					$result->execute($bindings);
				}
				else {
					$result = $this->db->query($query);
				}

				//if query was a select, return array of data
				if (strpos($query, "SELECT") !== false) {
					$results["rows"] = $result->fetchAll(PDO::FETCH_ASSOC);
				}

				$results["count"] = $result->rowCount();
			}
			catch (PDOException $failure) {

				if (defined("DEBUG") && DEBUG) {
					$results["meta"]["error"] = $failure;
				}

				$results["meta"]["ok"] = false;
				$results["meta"]["feedback"] = "Problem with Server.";
			}
		}
		else {
			$results["meta"]["ok"] = false;
			$results["meta"]["feedback"] = "Problem with Server.";
		}
		return $results;
	}

	/**
	 * @return int id of last inserted row of data
	 */
	public function lastInsertId() {
		return $this->db->lastInsertId();
	}
}

Database::get();