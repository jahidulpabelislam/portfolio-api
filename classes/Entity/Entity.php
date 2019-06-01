<?php
/*
 * The base Entity object class for Project & Projects Images.
 * Holds all ORM style functions.
 *
 * PHP version 7
 *
 * @author Jahidul Pabel Islam <me@jahidulpabelislam.com>
 * @version 1.1.0
 * @link https://github.com/jahidulpabelislam/portfolio-api/
 * @since Class available since Release: v3.0.0
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

    protected $tableName = "";

    protected $columns = [];

    protected $intColumns = ["id"];

    protected $dateTimeColumns = ["created_at", "updated_at"];

    protected $searchableColumns = [];

    protected $orderByColumn = "id";

    protected $orderByDirection = "DESC";

    protected $defaultLimitBy = 10;
    public $limitBy = 10;

    public $page = 1;

    private $db;

    /**
     * Entity constructor
     */
    public function __construct() {
        $this->db = Database::get();
    }

    public function __isset($name) {
        return isset($this->columns[$name]);
    }

    public function __get($name) {
        if (isset($this->columns[$name])) {
            return $this->columns[$name];
        }
    }

    public function __set($name, $value) {
        if (isset($this->columns[$name])) {
            if (in_array($name, $this->intColumns)) {
                $value = (int)$value;
            }
            else if (in_array($name, $this->dateTimeColumns)) {
                $datetime = DateTime::createFromFormat("Y-m-d G:i:s", $value);
                if ($datetime) {
                    $value = $datetime->format("Y-m-d G:i:s e");
                }
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
        return $this->columns;
    }

    /**
     * Load Entities from the Database where a column ($column) = a value ($value)
     * Either return Entities with success meta data, or failed meta data
     */
    public function getByColumn(string $column, $value): array {
        $query = "SELECT * FROM {$this->tableName}
                    WHERE {$column} = :value
                    ORDER BY {$this->orderByColumn} {$this->orderByDirection};";
        $bindings = [":value" => $value];
        $response = $this->db->query($query, $bindings);

        $rows = array_map(function($row) {
            $entity = new static();
            $entity->setValues($row);

            return $entity;
        }, $response["rows"]);

        return $rows;
    }

    /**
     * Load a single Entity from the Database where a Id column = a value ($id)
     * Uses helper function getByColumn
     */
    public function getById($id) {
        if (is_numeric($id)) {
            $rows = $this->getByColumn("id", (int)$id);

            // Check everything was okay, so as this /Should/ return only one, set values from first item
            if (count($rows) > 0) {
                $this->setValues($rows[0]->columns);
            }
        }
    }

    /**
     * Helper function to generate a INSERT SQL query using the Entity's columns and provided data
     *
     * @param $values array The values as an array to use for the new Entity
     * @return array [string, array] Return the raw SQL query and an array of bindings to use with query
     */
    private function generateInsertQuery(array $values): array {
        $columnsQuery = "(";
        $valuesQuery = "(";
        $bindings = [];

        foreach ($this->columns as $column) {
            if ($column !== "id" && isset($values[$column])) {
                $columnsQuery .= "{$column}, ";
                $valuesQuery .= ":{$column}, ";
                $bindings[":{$column}"] = $values[$column];
            }
        }
        $columnsQuery = rtrim($columnsQuery, ", ");
        $columnsQuery .= ")";

        $valuesQuery = rtrim($valuesQuery, ", ");
        $valuesQuery .= ")";

        $query = "INSERT INTO {$this->tableName} {$columnsQuery} VALUES {$valuesQuery};";

        return [$query, $bindings];
    }

    /**
     * Helper function to generate a UPDATE SQL query using the Entity's columns and provided data
     *
     * @param $values array The new values as an array to use for the Entity's update
     * @return array [string, array] Return the raw SQL query and an array of bindings to use with query
     */
    private function generateUpdateQuery(array $values): array {
        $valuesQuery = "SET ";
        $bindings = [];

        foreach ($this->columns as $column) {
            if (isset($values[$column])) {
                $bindings[":{$column}"] = $values[$column];

                if ($column !== "id") {
                    $valuesQuery .= "{$column} = :{$column}, ";
                }
            }
        }

        $valuesQuery = rtrim($valuesQuery, ", ");

        $query = "UPDATE {$this->tableName} {$valuesQuery} WHERE id = :id;";

        return [$query, $bindings];
    }

    /**
     * Save values to the Entity Table in the Database
     * Will either be a new insert or a update to an existing Entity
     *
     * @param $values array The values as an array to use for the Entity
     * @return array Either an array with successful meta data or an array of error feedback meta
     */
    public function save(array $values): array {

        $id = $values["id"] ?? null;

        if (in_array("updated_at", $this->columns)) {
            $values["updated_at"] = date("Y-m-d H:i:s");
        }

        if (empty($id)) {
            if (in_array("created_at", $this->columns)) {
                $values["created_at"] = date("Y-m-d H:i:s");
            }

            list($query, $bindings) = $this->generateInsertQuery($values);
        }
        else {
            // Check the Entity trying to edit actually exists
            $this->getById($id);
            if (empty($this->id)) {
                return [];
            }
            list($query, $bindings) = $this->generateUpdateQuery($values);
        }

        $response = $this->db->query($query, $bindings);

        // Checks if insert was ok
        if ($response["meta"]["affected_rows"] > 0) {

            $id = $id ?? $this->db->getLastInsertedId();

            $this->getById($id);

            if (empty($values["id"])) {
                $response["meta"]["status"] = 201;
                $response["meta"]["message"] = "Created";
            }
        }

        return $response;
    }

    /**
     * Delete an Entity from the Database
     *
     * @param $id int The Id of the Entity to delete
     * @return array Either an array with successful meta data or a array of error feedback meta
     */
    public function delete($id): array {
        $response = [];

        // Check the Entity trying to delete actually exists
        $this->getById($id);
        if (!empty($this->id) && $this->id == $id) {
            $id = (int)$id;

            $query = "DELETE FROM {$this->tableName} WHERE id = :id;";
            $bindings = [":id" => $id];
            $response = $this->db->query($query, $bindings);

            // Check if the deletion was ok
            if ($response["meta"]["affected_rows"] > 0) {

                $response["meta"]["ok"] = true;
                $response["row"]["id"] = $id;
            }

            unset($response["rows"]);
        }

        return $response;
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

        if ($this->searchableColumns) {
            $bindings = [];

            $searchString = $params["search"] ?? "";

            // Split each word in search
            $searchWords = explode(" ", $searchString);

            $searchString = "%" . implode("%", $searchWords) . "%";

            $searchesReversed = array_reverse($searchWords);

            $searchStringReversed = "%" . implode("%", $searchesReversed) . "%";

            $searchWhereClause = "WHERE (";

            $globalWhereClauses = [];

            // Loop through each searchable column
            foreach ($this->searchableColumns as $column) {
                $searchWhereClause .= " {$column} LIKE :searchString OR {$column} LIKE :searchStringReversed OR";

                if (!empty($params[$column])) {
                    $binding = ":{$column}";
                    $globalWhereClauses[] = " {$column} = {$binding}";
                    $bindings[$binding] = $params[$column];
                }
            }

            $searchWhereClause = rtrim($searchWhereClause, "OR");
            $searchWhereClause .= ")";

            $bindings[":searchString"] = $searchString;
            $bindings[":searchStringReversed"] = $searchStringReversed;

            $globalWhereClause = "";
            if (!empty($globalWhereClauses)) {
                $globalWhereClause = "AND " . implode(" AND ", $globalWhereClauses);
            }

            $whereClause = $searchWhereClause . " " . $globalWhereClause;

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

        $query = "SELECT COUNT(*) AS total_count FROM {$this->tableName} {$whereClause};";
        $totalCountRes = $this->db->query($query, $bindings);

        if ($totalCountRes && count($totalCountRes["rows"]) > 0) {
            return $totalCountRes["rows"][0]["total_count"];
        }

        return 0;
    }

    /**
     * Gets all Entities but paginated, also might include search
     *
     * @param $params array Any data to aid in the search query
     * @return array The request response to send back
     */
    public function doSearch(array $params): array {

        $this->limitBy = $this->defaultLimitBy;

        // If user added a limit param, use this if valid, unless its bigger than 10
        if (!empty($params["limit"])) {
            $limit = (int)$params["limit"];
            $this->limitBy = min($limit, $this->limitBy);
        }

        // If limit is invalid use default
        if ($this->limitBy < 1) {
            $this->limitBy = $this->defaultLimitBy;
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
        $whereClause = "";

        // Add a filter if a search was entered
        if (!empty($params)) {
            [$whereClause, $bindings] = $this->generateSearchWhereQuery($params);
        }

        $query = "SELECT * FROM {$this->tableName} {$whereClause}
                    ORDER BY {$this->orderByColumn} {$this->orderByDirection}
                    LIMIT {$this->limitBy} OFFSET {$offset};";
        $response = $this->db->query($query, $bindings);

        $rows = array_map(function($row) {
            $entity = new static();
            $entity->setValues($row);

            return $entity;
        }, $response["rows"]);

        return $rows ?? [];
    }
}
