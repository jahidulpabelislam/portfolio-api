<?php
/**
 * Connects to a database and set up to send and receive data
 * using application specific constants defined in the Config.php file.
 *
 * A reusable file for other projects.
 *
 * MySQL & PDO specific.
 *
 * PHP version 7.1+
 *
 * @version 3.1.1
 * @author Jahidul Pabel Islam <me@jahidulpabelislam.com>
 * @copyright 2010-2019 JPI
 */

namespace JPI\API;

use PDO;
use PDOException;

if (!defined("ROOT")) {
    die();
}

class Database {

    private static $instance;

    private $db;

    /**
     * Connects to a MySQL engine
     * using application constants DB_IP, DB_USERNAME, and DB_PASSWORD
     * defined in Config.php
     */
    public function __construct() {
        $this->connectToDB();
    }

    private function connectToDB() {
        try {
            $dsn = "mysql:host=" . Config::DB_IP . ";dbname=" . Config::DB_NAME . ";charset-UTF-8";
            $options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];

            $this->db = new PDO($dsn, Config::DB_USERNAME, Config::DB_PASSWORD, $options);
        }
        catch (PDOException $error) {
            $errorMessage = $error->getMessage();
            error_log("Error creating a connection to database: {$errorMessage}, full error: {$error}");
        }
    }

    /**
     * Singleton getter
     */
    public static function get(): Database {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Executes a SQL query
     *
     * @param $query string The SQL query to run
     * @param $bindings array Array of any bindings to use with the SQL query
     * @return array Array of rows found and/or count of total affected rows
     */
    public function query(string $query, array $bindings = null): array {
        $response = [
            "affected_rows" => 0,
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
                if (stripos($query, "SELECT") !== false) {
                    $response["rows"] = $executedQuery->fetchAll(PDO::FETCH_ASSOC);
                }

                // Add the count of how many rows were effected
                $response["affected_rows"] = $executedQuery->rowCount();
            }
            catch (PDOException $error) {
                $errorMessage = $error->getMessage();
                error_log("Error executing query on database: {$errorMessage} using query: {$query} and bindings: " . print_r($bindings, true) . ", full error: {$error}");
            }
        }

        return $response;
    }

    public function getLastInsertedId(): ?int {
        if ($this->db) {
            return $this->db->lastInsertId();
        }

        return null;
    }
}

Database::get();
