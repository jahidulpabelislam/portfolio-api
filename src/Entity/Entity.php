<?php
/**
 * The base Entity object class for Project & Projects Images.
 * Holds all ORM style functions.
 *
 * PHP version 7.1+
 *
 * @version 3.0.0
 * @since Class available since Release: v3.0.0
 * @author Jahidul Pabel Islam <me@jahidulpabelislam.com>
 * @copyright 2010-2019 JPI
 */

namespace JPI\API\Entity;

if (!defined("ROOT")) {
    die();
}

use DateTime;
use JPI\API\Config;
use JPI\API\Database;

abstract class Entity {

    protected static $db;

    public static $displayName = "";

    protected static $tableName = "";

    protected $columns = [];

    protected static $intColumns = ["id"];

    protected static $dateTimeColumns = ["created_at", "updated_at"];
    protected static $dateTimeFormat = "Y-m-d H:i:s";

    protected static $searchableColumns = [];

    protected static $orderByColumn = "id";
    protected static $orderByDirection = "DESC";

    protected static $defaultLimitBy = 10;

    public $limitBy = 10;
    public $page = 1;

    protected static function getDB(): Database {
        if (!self::$db) {
            self::$db = new Database(Config::DB_NAME, Config::DB_USERNAME, Config::DB_PASSWORD);
        }

        return self::$db;
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

    public function setValues(array $values) {
        $columns = array_keys($this->columns);
        foreach ($columns as $column) {
            if (array_key_exists($column, $values)) {
                $this->{$column} = $values[$column];
            }
        }
    }

    public function toArray(): array {
        $array = $this->columns;

        foreach ($array as $column => $value) {
            if (in_array($column, static::$dateTimeColumns)) {
                $datetime = DateTime::createFromFormat(self::$dateTimeFormat, $value);
                if ($datetime) {
                    $array[$column] = $datetime->format("Y-m-d H:i:s e");
                }
            }
        }

        return $array;
    }

    public static function createEntity(array $row): Entity {
        $entity = new static();
        $entity->setValues($row);

        return $entity;
    }

    public static function createEntities(array $rows): array {
        return array_map(["self", "createEntity"], $rows);
    }

    /**
     * Get Entities from the Database where a column ($column) = a value ($value)
     */
    public static function getByColumn(string $column, $value): array {
        $query = "SELECT * FROM " . static::$tableName . " 
                           WHERE {$column} = :value
                           ORDER BY " . static::$orderByColumn . " " . static::$orderByDirection . ";";
        $bindings = [":value" => $value];
        $rows = self::getDB()->getAll($query, $bindings);

        return self::createEntities($rows);
    }

    /**
     * Load a single Entity from the Database where a Id column = a value ($id)
     * Uses helper function getByColumn
     */
    public static function getById($id): Entity {
        if (is_numeric($id)) {
            $entities = self::getByColumn("id", (int)$id);

            // Check everything was okay, so as this /Should/ return only one, set values from first item
            if (count($entities)) {
                return $entities[0];
            }
        }

        return new static();
    }

    /**
     * Helper function to generate a UPDATE SQL query using the Entity's columns and provided data
     *
     * @return array [string, array] Return the raw SQL query and an array of bindings to use with query
     */
    private function generateSaveQuery(): array {
        $isNew = empty($this->id);

        $valuesQueries = $bindings= [];

        foreach ($this->columns as $column => $value) {
            $placeholder = ":{$column}";

            if ($column !== "id" || !$isNew) {
                $bindings[$placeholder] = $value;
            }

            if ($column !== "id") {
                $valuesQueries[] = "{$column} = {$placeholder}";
            }
        }
        $valuesQuery = implode(", ", $valuesQueries);

        $query = $isNew ? "INSERT INTO" : "UPDATE";
        $query .= " " . static::$tableName . " SET {$valuesQuery} ";
        $query .= $isNew ? ";" : "WHERE id = :id;";

        return [$query, $bindings];
    }

    /**
     * Save values to the Entity Table in the Database
     * Will either be a new insert or a update to an existing Entity
     */
    private function save(array $data): Entity {
        if (array_key_exists("updated_at", $this->columns)) {
            $data["updated_at"] = date(self::$dateTimeFormat);
        }

        $this->setValues($data);

        [$query, $bindings] = $this->generateSaveQuery();

        $db = self::getDB();
        $affectedRows = $db->execute($query, $bindings);

        // If insert/update was ok, load the new values into entity state
        if ($affectedRows) {
            $id = $this->id ?? $db->getLastInsertedId();
            return self::getById($id);
        }

        // Saving failed so reset id
        $this->id = null;
        return $this;
    }

    public static function insert(array $data): Entity {
        $entity = new static();

        if (array_key_exists("created_at", $entity->columns)) {
            $data["created_at"] = date(self::$dateTimeFormat);
        }

        return $entity->save($data);
    }

    public static function update(array $data): Entity {
        $entity = static::getById($data["id"]);
        if (!$entity->id) {
            return $entity;
        }

        if (array_key_exists("created_at", $entity->columns)) {
            $createdAtVal = null;
            if (!empty($entity->created_at)) {
                $createdAt = new DateTime($entity->created_at);
                $createdAtVal = $createdAt->format(self::$dateTimeFormat);
            }

            $data["created_at"] = $createdAtVal;
        }

        return $entity->save($data);
    }

    /**
     * Delete an Entity from the Database
     *
     * @return bool Whether or not deletion was successful
     */
    public function delete(): bool {
        $query = "DELETE FROM " . static::$tableName . " WHERE id = :id;";
        $bindings = [":id" => $this->id];
        $affectedRows = self::getDB()->execute($query, $bindings);

        // Whether the deletion was ok
        $isDeleted = $affectedRows > 0;

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
    private static function generateSearchWhereQuery(array $params): array {
        if (!static::$searchableColumns) {
            return ["", []];
        }

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

    /**
     * Used to get a total count of Entities using a where clause
     * Used together with Entity::doSearch, as this return a limited Entities
     * but we want to get a number of total items without limit
     *
     * @param $params array Any data to aid in the search query
     * @return int
     */
    public static function getTotalCountForSearch(array $params): int {
        [$whereClause, $bindings] = self::generateSearchWhereQuery($params);

        $query = "SELECT COUNT(*) AS total_count
                         FROM " . static::$tableName . " {$whereClause};";
        $row = self::getDB()->getOne($query, $bindings);

        return $row["total_count"] ?? 0;
    }

    /**
     * Gets all Entities but paginated, also might include search
     *
     * @param $params array Any data to aid in the search query
     * @return array The request response to send back
     */
    public function doSearch(array $params): array {
        // If user added a limit param, use this if valid, unless its bigger than default
        if (!empty($params["limit"])) {
            $limit = (int)$params["limit"];
            $this->limitBy = min($limit, static::$defaultLimitBy);
        }

        // If limit is invalid use default
        if ($this->limitBy < 1) {
            $this->limitBy = static::$defaultLimitBy;
        }

        // Generate a offset to the query, if a page was specified using page & limit values
        $offset = 0;
        if (!empty($params["page"])) {
            $page = (int)$params["page"];
            if ($page > 1) {
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
            [$whereQuery, $bindings] = self::generateSearchWhereQuery($params);
        }

        $query = "SELECT * FROM " . static::$tableName . " {$whereQuery}
                           ORDER BY " . static::$orderByColumn . " " . static::$orderByDirection . "
                           LIMIT {$this->limitBy} OFFSET {$offset};";
        $rows = self::getDB()->getAll($query, $bindings);

        return self::createEntities($rows);
    }
}
