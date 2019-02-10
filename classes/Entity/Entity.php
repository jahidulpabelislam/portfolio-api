<?php
/*
 * The base Entity object class for Project & Projects Images.
 * Holds all ORM style functions.
 *
 * PHP version 7
 *
 * @author Jahidul Pabel Islam <me@jahidulpabelislam.com>
 * @version 1
 * @link https://github.com/jahidulpabelislam/portfolio-api/
 * @since Class available since Release: v3
 * @copyright 2010-2018 JPI
*/

namespace JPI\API\Entity;

if (!defined("ROOT")) {
    die();
}

use JPI\API\Database;

abstract class Entity {

    public $response = [];

    public $tableName = null;

    public $displayName = null;

    public $columns = [];

    protected $searchableColumns = [];

    protected $defaultOrderingByColumn = "id";

    protected $defaultOrderingByDirection = "DESC";

    protected $defaultLimit = 10;

    private $db = null;

    /**
     * Entity constructor.
     *
     * If $id is passed, load up the Entity from Database where id = $id
     *
     * @param null $id int The id of a Entity in the Database to load
     */
    public function __construct($id = null) {
        $this->db = Database::get();

        if ($id) {
            $this->response = $this->getById($id);
        }
    }

    /**
     * Load Entities from the Database where a column ($column) = a value ($value)
     * Either return Entities with success meta data, or failed meta data
     *
     * @param $column string
     * @param $value string
     * @return array The response from the SQL query
     */
    public function getByColumn($column, $value): array {

        $query = "SELECT * FROM {$this->tableName} WHERE {$column} = :value ORDER BY {$this->defaultOrderingByColumn} {$this->defaultOrderingByDirection};";
        $bindings = [":value" => $value,];
        $response = $this->db->query($query, $bindings);

        // Check everything was okay
        if ($response["meta"]["affected_rows"] > 0) {
            $response["meta"]["ok"] = true;
        }
        // Check if database provided any meta data if not no problem with executing query but no item found
        else if ($response["meta"]["affected_rows"] <= 0 && !isset($response["meta"]["feedback"])) {
            $response["meta"]["status"] = 404;
            $response["meta"]["feedback"] = "No {$this->displayName}s found with {$value} as {$column}.";
            $response["meta"]["message"] = "Not Found";
        }

        $response["meta"]["count"] = $response["meta"]["affected_rows"];

        return $response;
    }

    /**
     * Load a single Entity from the Database where a id column = a value ($id)
     * Either return Entity with success meta data, or failed meta data
     * Uses helper function getByColumn();
     *
     * @param $id int The id of the Entity to get
     * @return array The response from the SQL query
     */
    public function getById($id): array {

        if (is_numeric($id)) {
            $response = $this->getByColumn("id", (int)$id);

            $response["row"] = [];

            // Check everything was okay, so as this /Should/ return only one, use 'Row' as index
            if ($response["meta"]["affected_rows"] > 0) {
                $response["row"] = $response["rows"][0];
            }
            // Check if database provided any meta data if so no problem with executing query but no item found
            else if (isset($response["meta"]["status"]) && $response["meta"]["status"] === 404) {
                $response["meta"]["feedback"] = "No {$this->displayName} found with {$id} as ID.";
            }

            unset($response["rows"]);
            unset($response["meta"]["count"]);
        }
        else {
            $response = [
                "row" => [],
                "meta" => [
                    "status" => 404,
                    "feedback" => "No {$this->displayName} found with {$id} as ID (Please note ID must be a numeric value).",
                    "message" => "Not Found",
                ],
            ];
        }

        return $response;
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
            if ($column !== "id" && !empty($values[$column])) {
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

        if (empty($id)) {
            list($query, $bindings) = $this->generateInsertQuery($values);
        }
        else {
            // Check the Entity trying to edit actually exists
            $response = $this->getById($id);
            if (empty($response["row"])) {
                return $response;
            }
            list($query, $bindings) = $this->generateUpdateQuery($values);
        }

        $response = $this->db->query($query, $bindings);

        // Checks if insert was ok
        if ($response["meta"]["affected_rows"] > 0) {

            $id = (empty($id)) ? $this->db->getLastInsertedId() : $id;

            $response = $this->getById($id);

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
     * @param $id int The id of the Entity to delete
     * @return array Either an array with successful meta data or a array of error feedback meta
     */
    public function delete($id): array {

        // Check the Entity trying to delete actually exists
        $response = $this->getById($id);
        if (!empty($response["row"])) {

            $query = "DELETE FROM {$this->tableName} WHERE id = :id;";
            $bindings = [":id" => $id,];
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
     * @param $search string The string to search for within searchable columns (if any)
     * @return array An array consisting of the generated where clause and an associative array containing any bindings to aid the Database querying
     */
    private function generateSearchWhereQuery($search): array {

        if ($this->searchableColumns) {
            // Split each word in search
            $searchWords = explode(" ", $search);

            $searchString = $searchStringReversed = "%";

            // Loop through each search word
            foreach ($searchWords as $word) {
                $searchString .= "{$word}%";
            }

            $searchesReversed = array_reverse($searchWords);

            // Loop through each search word
            foreach ($searchesReversed as $word) {
                $searchStringReversed .= "{$word}%";
            }

            $whereClause = "WHERE";

            // Loop through each search word
            foreach ($this->searchableColumns as $column) {
                $whereClause .= " {$column} LIKE :searchString OR {$column} LIKE :searchStringReversed OR";
            }

            $whereClause = rtrim($whereClause, "OR");

            $bindings = [
                "searchString" => $searchString,
                "searchStringReversed" => $searchStringReversed,
            ];

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
     * @param $whereClause string
     * @param array $bindings array Any data to aid in the database querying
     * @return int
     */
    public function getTotalCountByWhereClause($whereClause, array $bindings): int {
        $query = "SELECT COUNT(*) AS total_count FROM {$this->tableName} {$whereClause};";
        $totalCount = $this->db->query($query, $bindings);

        if ($totalCount && count($totalCount["rows"]) > 0) {
            return $totalCount["rows"][0]["total_count"];
        }

        return 0;
    }

    /**
     * Gets all Entities but paginated, also might include search
     *
     * @param array $params array Any data to aid in the search query
     * @return array The request response to send back
     */
    public function doSearch(array $params): array {

        // If user added a limit param, use this if valid, unless its bigger than 10
        if (isset($params["limit"])) {
            $limit = min(abs(intval($params["limit"])), $this->defaultLimit);
        }

        // Default limit to 10 if not specified or invalid
        if (!isset($limit) || !is_int($limit) || $limit < 1) {
            $limit = $this->defaultLimit;
        }

        $offset = 0;

        // Add a offset to the query, if specified
        if (isset($params["offset"])) {
            $offset = abs(intval($params["offset"]));
        }

        // Generate a offset to the query, if a page was specified using, page number and limit number
        if (isset($params["page"])) {
            $page = abs(intval($params["page"]));
            if (is_int($page) && $page > 1) {
                $offset = $limit * ($page - 1);
            }
        }

        $bindings = [];

        $whereClause = "";

        // Add a filter if a search was entered
        if (!empty($params["search"])) {

            list($whereClause, $bindings) = $this->generateSearchWhereQuery($params["search"]);
        }

        $query = "SELECT * FROM  {$this->tableName}  {$whereClause} ORDER BY {$this->defaultOrderingByColumn} {$this->defaultOrderingByDirection} LIMIT {$limit} OFFSET {$offset};";
        $response = $this->db->query($query, $bindings);

        $response["meta"]["count"] = $response["meta"]["affected_rows"];

        // Check if database provided any meta data if not all ok
        if ($response["meta"]["affected_rows"] > 0 && !isset($response["meta"]["feedback"])) {

            $response["meta"]["total_count"] = $this->getTotalCountByWhereClause($whereClause, $bindings);
            $response["meta"]["ok"] = true;
        }
        else if ($response["meta"]["affected_rows"] === 0 && !isset($response["meta"]["feedback"])) {
            $response["meta"]["status"] = 404;
            $response["meta"]["feedback"] = "No {$this->displayName}s found.";
            $response["meta"]["message"] = "Not Found";
            $response["meta"]["total_count"] = 0;
        }

        return $response;
    }
}