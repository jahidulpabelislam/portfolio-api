<?php
/**
 * Connects to a database and set up to send and receive data
 *
 * MySQL & PDO specific.
 *
 * PHP version 7.1+
 *
 * @author Jahidul Pabel Islam <me@jahidulpabelislam.com>
 * @copyright 2010-2020 JPI
 */

namespace App\Database;

if (!defined("ROOT")) {
    die();
}

use PDO;
use PDOException;
use PDOStatement;

class Connection {

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
     * @param $params array Array of any params/bindings to use with the SQL query
     * @return PDOStatement|null
     */
    private function run(string $query, ?array $params): ?PDOStatement {
        if ($this->pdo) {
            try {
                // Check if any params/bindings to execute
                if (isset($params)) {
                    $bindings = [];
                    foreach ($params as $key => $value) {
                        $bindings[":{$key}"] = $value;
                    }
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

    public function execute(string $query, ?array $params = null): int {
        $stmt = $this->run($query, $params);

        if ($stmt) {
            return $stmt->rowCount();
        }

        return 0;
    }

    public function getOne(string $query, ?array $params = null): ?array {
        $stmt = $this->run($query, $params);

        if ($stmt) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!empty($row)) {
                return $row;
            }
        }

        return null;
    }

    public function getAll(string $query, ?array $params = null): array {
        $stmt = $this->run($query, $params);

        if ($stmt) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return [];
    }

    public function getLastInsertedId(): ?int {
        return $this->pdo->lastInsertId();
    }
}
