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

namespace App\Entity;

if (!defined("ROOT")) {
    die();
}

use DateTime;
use App\Config;
use App\Database;

abstract class Entity {

    protected static $db;

    public static $displayName = "";

    protected static $tableName = "";

    protected $columns = [];

    protected static $requiredColumns = [];

    protected static $intColumns = [];

    protected static $dateTimeColumns = [];
    protected static $dateTimeFormat = "Y-m-d H:i:s";

    protected static $hasCreatedAt = true;

    protected static $hasUpdatedAt = true;

    protected static $searchableColumns = [];

    protected static $orderByColumn = "id";
    protected static $orderByASC = true;

    protected static $defaultLimitBy = 10;

    public $totalCount = 0;
    public $limitBy = 10;
    public $page = 1;

    protected static function getDB(): Database {
        if (!static::$db) {
            $config = Config::get();
            static::$db = new Database(
                $config->db_name,
                $config->db_username,
                $config->db_password,
                $config->db_host
            );
        }

        return static::$db;
    }

    public function __construct() {
        // Slight hack so id is the first item...
        $columns = ["id" => null];
        $this->columns = array_merge($columns, $this->columns);

        if (static::$hasCreatedAt) {
            $this->setValue("created_at", null);
        }

        if (static::$hasUpdatedAt) {
            $this->setValue("updated_at", null);
        }
    }

    /**
     * Convenient function to get the single value from an array if it's the only value
     *
     * @param $value
     * @return array|mixed
     */
    private static function singleArrayValue($value) {
        if ($value && is_array($value) && count($value) === 1) {
            return array_shift($value);
        }

        return $value;
    }

    private static function getIntColumns(): array {
        $intColumns = static::$intColumns;
        $intColumns[] = "id";

        return $intColumns;
    }

    private static function getDataTimeColumns(): array {
        $columns = static::$dateTimeColumns;

        if (static::$hasCreatedAt) {
            $columns[] = "created_at";
        }

        if (static::$hasUpdatedAt) {
            $columns[] = "updated_at";
        }

        return $columns;
    }

    public static function getRequiredFields(): array {
        return static::$requiredColumns;
    }

    public function __isset($name) {
        return isset($this->columns[$name]);
    }

    public function __get($name) {
        if (array_key_exists($name, $this->columns)) {
            return $this->columns[$name];
        }
    }

    private function setValue($column, $value) {
        if (in_array($column, static::getIntColumns())) {
            $value = (int)$value;
        }

        $this->columns[$column] = $value;
    }

    public function __set($name, $value) {
        if (array_key_exists($name, $this->columns)) {
            $this->setValue($name, $value);
        }
    }

    public function setValues(array $values) {
        $columns = array_keys($this->columns);
        foreach ($columns as $column) {
            if (array_key_exists($column, $values)) {
                $this->setValue($column, $values[$column]);
            }
        }
    }

    public function toArray(): array {
        $array = $this->columns;

        $dateTimeColumns = static::getDataTimeColumns();

        foreach ($array as $column => $value) {
            if (in_array($column, $dateTimeColumns)) {
                $datetime = DateTime::createFromFormat(static::$dateTimeFormat, $value);
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
        return array_map(["static", "createEntity"], $rows);
    }

    protected static function getOrderByQuery(): string {
        if (static::$orderByColumn) {
            $orderBys = [
                static::$orderByColumn . " " . (static::$orderByASC ? "ASC" : "DESC")
            ];

            // Sort by id if not already to stop any randomness on rows with same value on above
            if (static::$orderByColumn !== "id") {
                $orderBys[] = "id ASC";
            }

            $orderBys = self::singleArrayValue($orderBys);

            if (is_array($orderBys)) {
                return "ORDER BY \n\t" . implode(",\n\t", $orderBys);
            }

            return "ORDER BY {$orderBys}";
        }

        return "";
    }

    /**
     * @param array|string $select
     * @param array|string|int $where
     * @param int|null $limit
     * @param int|null $offset
     * @return string
     */
    protected static function generateSelectQuery($select = "*", $where = null, int $limit = null, int $offset = null): string {
        $select = self::singleArrayValue($select);
        $_select = $select ?: "*";
        if ($select && is_array($select)) {
            $_select = "\n\t" . implode(",\n\t", $select);
        }

        $query = "SELECT {$_select} \n"
               . "FROM " . static::$tableName . " \n";

        if ($where) {
            if (is_numeric($where)) {
                $query .= "WHERE id = :id \n"
                        . "LIMIT 1;";
                return $query;
            }

            $where = self::singleArrayValue($where);
            if (is_array($where)) {
                $query .= "WHERE \n\t" . implode("\n\tAND ", $where) . "\n";
            }
            else if (is_string($where)) {
                $query .= "WHERE {$where} \n";
            }
        }

        $orderBy = static::getOrderByQuery();
        if ($orderBy) {
            $query .= "{$orderBy} \n";
        }

        if ($limit) {
            $query .= "LIMIT {$limit}";
            if ($offset) {
                $query .= " OFFSET {$offset}";
            }
        }

        return trim($query) . ";";
    }

    /**
     * @param array|string $select
     * @param array|string|int $where
     * @param null $bindings
     * @param int|null $limit
     * @param int|null $offset
     * @return Entity|array[Entity]
     */
    public static function get($select = "*", $where = null, $bindings = null, int $limit = null, int $offset = null) {
        $query = static::generateSelectQuery($select, $where, $limit, $offset);

        if (($where && is_numeric($where)) || $limit == 1) {
            if (is_numeric($where)) {
                $bindings =[":id" => $where];
            }
            $row = static::getDB()->getOne($query, $bindings);
            if (!empty($row)) {
                return static::createEntity($row);
            }

            return new static();
        }

        $rows = static::getDB()->getAll($query, $bindings);
        return static::createEntities($rows);
    }

    /**
     * Get Entities from the Database where a column ($column) = a value ($value)
     */
    public static function getByColumn(string $column, $value): array {
        $bindings = [":{$column}" => $value];
        return static::get("*", "{$column} = :{$column}", $bindings);
    }

    /**
     * Load a single Entity from the Database where a Id column = a value ($id).
     * Uses helper function generateGetByColumnQuery to generate the necessary SQL query and bindings
     */
    public static function getById($id): Entity {
        if (is_numeric($id)) {
            return static::get("*", (int)$id);
        }

        return new static();
    }

    /**
     * Helper function to generate a UPDATE SQL query using the Entity's columns and provided data
     *
     * @return array [string, array] Return the raw SQL query and an array of bindings to use with query
     */
    protected function generateSaveQuery(): array {
        $isNew = empty($this->id);

        $valuesQueries = $bindings= [];

        foreach ($this->columns as $column => $value) {
            $placeholder = ":{$column}";

            if ($column !== "id" || !$isNew) {
                $bindings[$placeholder] = $value;
            }

            if ($column !== "id") {
                $valuesQueries[] = "\n\t{$column} = {$placeholder}";
            }
        }
        $valuesQuery = implode(", ", $valuesQueries);

        $query = $isNew ? "INSERT INTO" : "UPDATE";
        $query .= " " . static::$tableName . "\n";
        $query .= "SET {$valuesQuery}";
        $query .= $isNew ? ";" : "\nWHERE id = :id;";

        return [$query, $bindings];
    }

    /**
     * Save values to the Entity Table in the Database
     * Will either be a new insert or a update to an existing Entity
     */
    public function save(): bool {
        if (empty($this->id) && static::$hasCreatedAt) {
            $this->setValue("created_at", date(static::$dateTimeFormat));
        }
        if (static::$hasUpdatedAt) {
            $this->setValue("updated_at", date(static::$dateTimeFormat));
        }

        [$query, $bindings] = $this->generateSaveQuery();

        $db = static::getDB();
        $affectedRows = $db->execute($query, $bindings);

        // If insert/update was ok, load the new values into entity state
        if ($affectedRows) {
            $id = $this->id ?? $db->getLastInsertedId();
            $updatedEntity = static::getById($id);
            $this->columns = $updatedEntity->columns;
            return true;
        }

        // Saving failed so reset id
        $this->id = null;
        return false;
    }

    public static function insert(array $data = []): Entity {
        $entity = new static();

        $entity->setValues($data);
        $entity->save();

        return $entity;
    }

    /**
     * Delete an Entity from the Database
     *
     * @return bool Whether or not deletion was successful
     */
    public function delete(): bool {
        $query = "DELETE FROM " . static::$tableName . " WHERE id = :id;";
        $bindings = [":id" => $this->id];
        $affectedRows = static::getDB()->execute($query, $bindings);

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
    protected static function generateSearchWhereClauses(array $params): array {
        if (!static::$searchableColumns) {
            return ["", []];
        }

        $searchValue = $params["search"] ?? null;

        $bindings = [];

        if ($searchValue) {
            // Split each word in search
            $searchWords = explode(" ", $searchValue);
            $searchString = "%" . implode("%", $searchWords) . "%";

            $searchesReversed = array_reverse($searchWords);
            $searchStringReversed = "%" . implode("%", $searchesReversed) . "%";

            $bindings = [
                ":searchString" => $searchString,
                ":searchStringReversed" => $searchStringReversed,
            ];
        }

        $whereClauses = [];
        $searchWhereClauses = [];

        // Loop through each searchable column
        foreach (static::$searchableColumns as $column) {
            if ($searchValue) {
                $searchWhereClauses[] = "{$column} LIKE :searchString";
                $searchWhereClauses[] = "{$column} LIKE :searchStringReversed";
            }

            if (!empty($params[$column])) {
                $binding = ":{$column}";
                $whereClauses[] = "{$column} = {$binding}";
                $bindings[$binding] = $params[$column];
            }
        }

        if (!empty($searchWhereClauses)) {
            array_unshift($whereClauses, "(\n\t\t" . implode("\n\t\tOR ", $searchWhereClauses) . "\n\t)");
        }

        return [$whereClauses, $bindings];
    }

    /**
     * Used to get a total count of Entities using a where clause
     * Used together with Entity::doSearch, as this return a limited Entities
     * but we want to get a number of total items without limit
     *
     * @return int
     */
    public static function getCount($where, $bindings): int {
        $query = static::generateSelectQuery("COUNT(*)", $where);
        return static::getDB()->getColumn($query, $bindings) ?? 0;
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
        $where = "";

        // Add a filter if a search was entered
        if (!empty($params)) {
            [$where, $bindings] = static::generateSearchWhereClauses($params);
        }

        $this->totalCount = static::getCount($where, $bindings);

        return static::get("*", $where, $bindings, $this->limitBy, $offset);
    }
}
