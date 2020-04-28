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

use PDO;
use PDOException;
use PDOStatement;

class Connection {

    private $pdo;

    /**
     * Connects to a MySQL engine using PDO
     *
     * @throws Exception
     */
    public function __construct(array $config = []) {
        $defaults = [
            "host" => "127.0.0.1",
            "database" => "",
            "username" => "root",
            "password" => "root",
        ];

        $config = array_merge($defaults, $config);

        $host = $config["host"];
        $database = $config["database"];
        $username = $config["username"];
        $password = $config["password"];

        $dsn = "mysql:host={$host};dbname={$database};charset-UTF-8";
        $options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];

        try {
            $this->pdo = new PDO($dsn, $username, $password, $options);
        }
        catch (PDOException $error) {
            throw new Exception(
                "Error creating a connection to database: {$error->getMessage()}",
                $error->getCode(),
                $error->getPrevious()
            );
        }
    }

    /**
     * Executes a SQL query
     *
     * @param $query string The SQL query to run
     * @param $params array|null Array of any params/bindings to use with the SQL query
     * @return PDOStatement
     * @throws Exception
     */
    private function run(string $query, ?array $params): PDOStatement {
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
            throw new Exception(
                "Error executing query on database: {$error->getMessage()}, using query: {$query} and params: " . print_r($params, true),
                $error->getCode(),
                $error->getPrevious()
            );
        }
    }

    /**
     * @param $query string
     * @param $params array|null
     * @return int
     * @throws Exception
     */
    public function execute(string $query, ?array $params = null): int {
        $stmt = $this->run($query, $params);
        return $stmt->rowCount();
    }

    /**
     * @param $query string
     * @param $params array|null
     * @return array|null
     * @throws Exception
     */
    public function getOne(string $query, ?array $params = null): ?array {
        $stmt = $this->run($query, $params);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row !== false) {
            return $row;
        }

        return null;
    }

    /**
     * @param $query string
     * @param $params array|null
     * @return array[]
     * @throws Exception
     */
    public function getAll(string $query, ?array $params = null): array {
        $stmt = $this->run($query, $params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getLastInsertedId(): ?int {
        return $this->pdo->lastInsertId();
    }

}
