<?php
/**
 * Connects to a database and set up to send and receive data
 *
 * MySQL & PDO specific.
 *
 * PHP version 7.1+
 *
 * @version 5.0.0
 * @author Jahidul Pabel Islam <me@jahidulpabelislam.com>
 * @copyright 2010-2019 JPI
 */

namespace JPI\API;

use PDO;
use PDOException;
use PDOStatement;

if (!defined("ROOT")) {
    die();
}

class Database {

    private $pdo;

    /**
     * Connects to a MySQL engine using PDO
     */
    public function __construct(string $databaseName, string $username, string $password, string $host = "127.0.0.1") {
        try {
            $dsn = "mysql:host={$host};dbname={$databaseName};charset-UTF-8";
            $options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];

            $this->pdo = new PDO($dsn, $username, $password, $options);
        }
        catch (PDOException $error) {
            $errorMessage = $error->getMessage();
            error_log("Error creating a connection to database: {$errorMessage}, full error: {$error}");
        }
    }

    /**
     * Executes a SQL query
     *
     * @param $query string The SQL query to run
     * @param $bindings array Array of any bindings to use with the SQL query
     * @return PDOStatement|null
     */
    private function _execute(string $query, ?array $bindings): ?PDOStatement {
        if ($this->pdo) {
            try {
                // Check if any bindings to execute
                if (isset($bindings)) {
                    $stmt = $this->pdo->prepare($query);
                    $stmt->execute($bindings);
                }
                else {
                    $stmt = $this->pdo->query($query);
                }

                return $stmt;
            }
            catch (PDOException $error) {
                $errorMessage = $error->getMessage();
                error_log("Error executing query on database: {$errorMessage} using query: {$query} and bindings: " . print_r($bindings, true) . ", full error: {$error}");
            }
        }

        return null;
    }

    public function execute(string $query, array $bindings = null): int {
        $stmt = $this->_execute($query, $bindings);

        if ($stmt) {
            return $stmt->rowCount();
        }

        return 0;
    }

    public function getOne(string $query, array $bindings = null): array {
        $stmt = $this->_execute($query, $bindings);

        if ($stmt) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }

        return [];
    }

    public function getAll(string $query, array $bindings = null): array {
        $stmt = $this->_execute($query, $bindings);

        if ($stmt) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return [];
    }

    public function getLastInsertedId(): ?int {
        return $this->pdo->lastInsertId();
    }
}
