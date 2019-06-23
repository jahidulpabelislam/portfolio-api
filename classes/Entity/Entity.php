<?php
/**
 * The base Entity object class for Project & Projects Images.
 * Holds all ORM style functions.
 *
 * PHP version 7.1+
 *
 * @version 2.0.0
 * @since Class available since Release: v3.0.0
 * @author Jahidul Pabel Islam <me@jahidulpabelislam.com>
 * @copyright 2010-2019 JPI
 */

namespace JPI\API\Entity;

if (!defined("ROOT")) {
    die();
}

use DateTime;
use JPI\API\Database;

abstract class Entity {

    public static $displayName = "";

    protected static $tableName = "";

    protected $columns = [];

    protected static $intColumns = ["id"];

    protected static $dateTimeColumns = ["created_at", "updated_at"];

    protected static $searchableColumns = [];

    protected static $orderByColumn = "id";
    protected static $orderByDirection = "DESC";

    protected static $defaultLimitBy = 10;

    public $limitBy = 10;
    public $page = 1;

    private $db;

    public function __construct() {
        $this->db = Database::get();
    }

    public function __isset($name) {
        return isset($this->columns[$name]);
    }

    public function __get($name) {
        if (array_key_exists($name, $this->columns)) {
            return $this->columns[$name];
        }
    }

    public function __set($name, $value) {
        if (array_key_exists($name, $this->columns)) {
            if (in_array($name, static::$intColumns)) {
                $value = (int)$value;
            }

            $this->columns[$name] = $value;
        }
    }

    public function setValues($values) {
        $columns = array_keys($this->columns);
        foreach ($columns as $column) {
            if (isset($values[$column])) {
                $this->{$column} = $values[$column];
            }
        }
    }

    public function toArray(): array {
        $array = $this->columns;

        foreach ($array as $column => $value) {
            if (in_array($column, static::$dateTimeColumns)) {
                $datetime = DateTime::createFromFormat("Y-m-d G:i:s", $value);
                if ($datetime) {
                    $array[$column] = $datetime->format("Y-m-d G:i:s e");
                }
            }
        }

        return $array;
    }

    /**
     * Get Entities from the Database where a column ($column) = a value ($value)
     */
    public function getByColumn(string $column, $value): array {
        $query = "SELECT * FROM " . static::$tableName .
                    " WHERE {$column} = :value
                    ORDER BY " . static::$orderByColumn . " " . static::$orderByDirection . ";";
        $bindings = [":value" => $value];
        $rows = $this->db->getAll($query, $bindings);

        $entities = array_map(function($row) {
            $entity = new static();
            $entity->setValues($row);

            return $entity;
        }, $rows);

        return $entities;
    }

    /**
     * Load a single Entity from the Database where a Id column = a value ($id)
     * Uses helper function getByColumn
     */
    public function getById($id) {
        if (is_numeric($id)) {
            $entities = $this->getByColumn("id", (int)$id);

            // Check everything was okay, so as this /Should/ return only one, set values from first item
            if (count($entities) > 0) {
                $this->setValues($entities[0]->columns);
            }
        }
    }

    /**
     * Helper function to generate a INSERT SQL query using the Entity's columns and provided data
     *
     * @return array [string, array] Return the raw SQL query and an array of bindings to use with query
     */
    private function generateInsertQuery(): array {
        $columnsQuery = $valuesQuery = "";
        $bindings = [];

        foreach ($this->columns as $column => $value) {
            if ($column !== "id") {
                $columnsQuery .= "{$column}, ";
                $valuesQuery .= ":{$column}, ";
                $bindings[":{$column}"] = $value;
            }
        }
        $columnsQuery = rtrim($columnsQuery, " ,");
        $valuesQuery = rtrim($valuesQuery, " ,");

        $query = "INSERT INTO " . static::$tableName . " ({$columnsQuery}) VALUES ({$valuesQuery});";

        return [$query, $bindings];
    }

    /**
     * Helper function to generate a UPDATE SQL query using the Entity's columns and provided data
     *
     * @return array [string, array] Return the raw SQL query and an array of bindings to use with query
     */
    private function generateUpdateQuery(): array {
        $valuesQuery = "";
        $bindings = [];

        foreach ($this->columns as $column => $value) {
            $bindings[":{$column}"] = $value;

            if ($column !== "id") {
                $valuesQuery .= "{$column} = :{$column}, ";
            }
        }
        $valuesQuery = rtrim($valuesQuery, " ,");

        $query = "UPDATE " . static::$tableName . " SET {$valuesQuery} WHERE id = :id;";

        return [$query, $bindings];
    }

    /**
     * Save values to the Entity Table in the Database
     * Will either be a new insert or a update to an existing Entity
     */
    public function save() {

        $id = $this->id ?? null;

        $isNew = empty($id);

        if (array_key_exists("updated_at", $this->columns)) {
            $this->updated_at = date("Y-m-d H:i:s");
        }

        if ($isNew) {
            if (array_key_exists("created_at", $this->columns)) {
                $this->created_at = date("Y-m-d H:i:s");
            }

            [$query, $bindings] = $this->generateInsertQuery();
        }
        else {
            // Check the Entity trying to edit actually exists
            $existingEntity = new static();
            $existingEntity->getById($id);
            if (empty($existingEntity->id)) {
                $this->id = null;
                return;
            }

            if (array_key_exists("created_at", $this->columns)) {
                $createdAt = new DateTime($this->created_at);
                $this->created_at = $createdAt->format("Y-m-d H:i:s");
            }

            [$query, $bindings] = $this->generateUpdateQuery();
        }

        $affectedRows = $this->db->doQuery($query, $bindings);

        // If insert was ok, load the new values into entity state
        if ($affectedRows) {
            $id = $id ?? $this->db->getLastInsertedId();
            $this->getById($id);
        }
    }

    /**
     * Delete an Entity from the Database
     *
     * @param $id int The Id of the Entity to delete
     * @return bool Whether or not deletion was successful
     */
    public function delete($id): bool {
        $isDeleted = false;

        // Check the Entity trying to delete actually exists
        $this->getById($id);
        if (!empty($this->id) && $this->id == $id) {

            $query = "DELETE FROM " . static::$tableName . " WHERE id = :id;";
            $bindings = [":id" => (int)$id];
            $affectedRows = $this->db->doQuery($query, $bindings);

            // Whether the deletion was ok
            $isDeleted = $affectedRows > 0;
        }

        return $isDeleted;
    }

    /**
     * Helper function
     * Used to generate a where clause for a search on a entity along with any binding needed
     * Used with Entity::doSearch();
     *
     * @param $params array The fields to search for within searchable columns (if any)
     * @return array An array consisting of the generated where clause and an associative array containing any bindings to aid the Database querying
     */
    private function generateSearchWhereQuery(array $params): array {
        if (static::$searchableColumns) {

            $searchValue = $params["search"] ?? "";

            // Split each word in search
            $searchWords = explode(" ", $searchValue);
            $searchString = "%" . implode("%", $searchWords) . "%";

            $searchesReversed = array_reverse($searchWords);
            $searchStringReversed = "%" . implode("%", $searchesReversed) . "%";

            $bindings = [
                ":searchString" => $searchString,
                ":searchStringReversed" => $searchStringReversed,
            ];

            $globalWhereClauses = [];
            $searchWhereClause = "";

            // Loop through each searchable column
            foreach (static::$searchableColumns as $column) {
                $searchWhereClause .= " {$column} LIKE :searchString OR {$column} LIKE :searchStringReversed OR";

                if (!empty($params[$column])) {
                    $binding = ":{$column}";
                    $globalWhereClauses[] = " {$column} = {$binding}";
                    $bindings[$binding] = $params[$column];
                }
            }
            if (!empty($searchWhereClause)) {
                $lastTwoChars = substr($searchWhereClause, -2);
                if ($lastTwoChars === "OR") {
                    $searchWhereClause = substr($searchWhereClause, 0, -2);
                }
            }

            $globalWhereClause = "";
            if (!empty($globalWhereClauses)) {
                $globalWhereClause = " AND " . implode(" AND ", $globalWhereClauses);
            }

            $whereClause = "WHERE ({$searchWhereClause}) {$globalWhereClause}";

            return [$whereClause, $bindings];
        }
        else {
            return ["", []];
        }
    }

    /**
     * Used to get a total count of Entities using a where clause
     * Used together with Entity::doSearch, as this return a limited Entities
     * but we want to get a number of total items without limit
     *
     * @param $params array Any data to aid in the search query
     * @return int
     */
    public function getTotalCountForSearch(array $params): int {
        [$whereClause, $bindings] = $this->generateSearchWhereQuery($params);

        $query = "SELECT COUNT(*) AS total_count FROM " . static::$tableName . " {$whereClause};";
        $row = $this->db->getOne($query, $bindings);

        return $row["total_count"] ?? 0;
    }

    /**
     * Gets all Entities but paginated, also might include search
     *
     * @param $params array Any data to aid in the search query
     * @return array The request response to send back
     */
    public function doSearch(array $params): array {

        $this->limitBy = static::$defaultLimitBy;

        // If user added a limit param, use this if valid, unless its bigger than default
        if (!empty($params["limit"])) {
            $limit = (int)$params["limit"];
            $this->limitBy = min($limit, $this->limitBy);
        }

        // If limit is invalid use default
        if ($this->limitBy < 1) {
            $this->limitBy = static::$defaultLimitBy;
        }

        // Generate a offset to the query, if a page was specified using page & limit values
        $offset = 0;
        if (!empty($params["page"])) {
            $page = (int)$params["page"];
            if (is_int($page) && $page > 1) {
                $offset = $this->limitBy * ($page - 1);
            }
            else {
                $page = 1;
            }

            $this->page = $page;
        }

        $bindings = [];
        $whereQuery = "";

        // Add a filter if a search was entered
        if (!empty($params)) {
            [$whereQuery, $bindings] = $this->generateSearchWhereQuery($params);
        }

        $query = "SELECT * FROM " . static::$tableName . " {$whereQuery}
                    ORDER BY " . static::$orderByColumn . " " . static::$orderByDirection .
                    " LIMIT {$this->limitBy} OFFSET {$offset};";
        $rows = $this->db->getAll($query, $bindings);

        $entities = array_map(function($row) {
            $entity = new static();
            $entity->setValues($row);

            return $entity;
        }, $rows);

        return $entities;
    }
}
