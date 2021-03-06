<?php
/**
 * The base Entity object class for Project & Projects Images.
 * Holds all ORM style functions.
 *
 * PHP version 7.1+
 *
 * @author Jahidul Pabel Islam <me@jahidulpabelislam.com>
 * @since Class available since Release: v3.0.0
 * @copyright 2010-2020 JPI
 */

namespace App;

if (!defined("ROOT")) {
    die();
}

use App\Database\Connection;
use App\Database\Query;
use DateTime;

abstract class Entity {

    protected static $db;

    public static $displayName = "";

    protected static $tableName = "";

    protected $columns = [];

    protected static $requiredColumns = [];

    protected static $intColumns = [];
    protected static $dateTimeColumns = [];

    protected static $searchableColumns = [];

    protected static $dateTimeFormat = "Y-m-d H:i:s";

    protected static $hasCreatedAt = true;
    protected static $hasUpdatedAt = true;

    protected static $orderByColumn = "id";
    protected static $orderByASC = true;

    protected static $defaultLimit = 10;

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

    protected static function getDB(): Connection {
        if (!static::$db) {
            $config = Config::get();
            static::$db = new Connection([
                "host" => $config->db_host,
                "database" => $config->db_name,
                "username" => $config->db_username,
                "password" => $config->db_password,
            ]);
        }

        return static::$db;
    }

    public static function getQuery(): Query {
        return new Query(static::getDB(), static::$tableName);
    }

    private static function getIntColumns(): array {
        return static::$intColumns;
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

    private function setValue(string $column, $value) {
        if ($column === "id") {
            return;
        }

        if (in_array($column, static::getIntColumns())) {
            $value = (int)$value;
        }

        $this->columns[$column] = $value;
    }

    public function setValues(array $values) {
        $columns = array_keys($this->columns);
        foreach ($columns as $column) {
            if (array_key_exists($column, $values)) {
                $this->setValue($column, $values[$column]);
            }
        }
    }

    private function setId(?int $id) {
        $this->columns["id"] = $id;
    }

    public function __isset(string $name): bool {
        return isset($this->columns[$name]);
    }

    public function __get(string $name) {
        if (array_key_exists($name, $this->columns)) {
            return $this->columns[$name];
        }

        return null;
    }

    public function __set(string $name, $value) {
        if (array_key_exists($name, $this->columns)) {
            $this->setValue($name, $value);
        }
    }

    public function isLoaded(): bool {
        return !empty($this->id);
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

    public static function create(?array $data = null): Entity {
        $entity = new static();

        if (!empty($data)) {
            $entity->setValues($data);
        }

        return $entity;
    }

    /**
     * @param $row array
     * @return static
     */
    private static function populateFromDB(array $row): Entity {
        $entity = static::create($row);
        $entity->setId($row["id"]);
        return $entity;
    }

    /**
     * @param $rows array[]
     * @return static[]
     */
    private static function populateEntitiesFromDB(array $rows): array {
        return array_map(["static", "populateFromDB"], $rows);
    }

    /**
     * Get the limit to use for a SQL query
     * Can specify a limit and it will make sure it is not above the max/default
     *
     * @param $limit int|string|null
     * @return int|null
     */
    public static function getLimit($limit = null): ?int {
        // If limit specified use unless it's bigger than default
        if (is_numeric($limit)) {
            $limit = (int)$limit;
            if (static::$defaultLimit && static::$defaultLimit < $limit) {
                $limit = static::$defaultLimit;
            }
        }

        // If invalid use default
        if (!$limit || $limit < 1) {
            $limit = static::$defaultLimit;
        }

        return $limit;
    }

    /**
     * Get the page to use for a SQL query
     * Can specify the page and it will make sure it is valid
     *
     * @param $page int|string|null
     * @return int|null
     */
    public static function getPage($page = null): ?int {
        if (is_numeric($page)) {
            $page = (int)$page;
        }

        // If invalid use page 1
        if (!$page || $page < 1) {
            $page = 1;
        }

        return $page;
    }

    protected static function getOrderBy(): ?array {
        if (static::$orderByColumn) {
            $orderBys = [
                static::$orderByColumn . " " . (static::$orderByASC ? "ASC" : "DESC"),
            ];

            // Sort by id if not already to stop any randomness on rows with same value on above
            if (static::$orderByColumn !== "id") {
                $orderBys[] = "id ASC";
            }

            return $orderBys;
        }

        return null;
    }

    /**
     * @param $columns string[]|string|null
     * @param $where string[]|string|int|null
     * @param $params array|null
     * @param $orderBy string[]|string|null
     * @param $limit int|null
     * @param $page int|null
     * @return array[]|array|null
     */
    public static function select($columns = "*", $where = null, ?array $params = null, $orderBy = null, ?int $limit = null, ?int $page = null): ?array {
        return static::getQuery()->select($columns, $where, $params, $orderBy, $limit, $page);
    }

    /**
     * @param $where string[]|string|int
     * @param $params array|null
     * @param $limit int|string|null
     * @param $page int|string|null
     * @return static[]|static|null
     */
    public static function get($where = null, ?array $params = null, $limit = null, $page = null) {
        $orderBy = static::getOrderBy();
        $limit = static::getLimit($limit);
        $page = static::getPage($page);

        $rows = static::select("*", $where, $params, $orderBy, $limit, $page);

        if ($rows === null) {
            return null;
        }

        if (($where && is_numeric($where)) || $limit === 1) {
            return static::populateFromDB($rows);
        }

        return static::populateEntitiesFromDB($rows);
    }

    /**
     * Get Entities from the Database where a column ($column) = a value ($value)
     *
     * @param $column string
     * @param $value string|int
     * @param $limit int|string|null
     * @param $page int|string|null
     * @return static[]|static|null
     */
    public static function getByColumn(string $column, $value, $limit = null, $page = null) {
        $params = [$column => $value];
        return static::get("{$column} = :{$column}", $params, $limit, $page);
    }

    /**
     * Load a single Entity from the Database where a Id column = a value ($id).
     *
     * @param $id int|string
     * @return static|null
     */
    public static function getById($id): ?Entity {
        if (is_numeric($id)) {
            return static::get((int)$id);
        }

        return null;
    }

    public function refresh() {
        if ($this->isLoaded()) {
            $row = static::select("*", $this->id, null, null, 1);
            if ($row) {
                $this->setValues($row);
                return;
            }

            $this->setId(null);
        }
    }

    /**
     * Helper function
     * Used to generate a where clause for a search on a entity along with any params needed
     * Used with Entity::getByParams();
     *
     * @param $params array The fields to search for within searchable columns (if any)
     * @return array|null [string, array] Generated SQL where clause(s) and an associative array containing any params for query
     */
    public static function generateWhereClausesFromParams(array $params): ?array {
        if (!static::$searchableColumns) {
            return null;
        }

        $searchValue = $params["search"] ?? null;

        $queryParams = [];

        if ($searchValue) {
            // Split each word in search
            $searchWords = explode(" ", $searchValue);
            $searchString = "%" . implode("%", $searchWords) . "%";

            $searchesReversed = array_reverse($searchWords);
            $searchStringReversed = "%" . implode("%", $searchesReversed) . "%";

            $queryParams["searchString"] = $searchString;
            $queryParams["searchStringReversed"] = $searchStringReversed;
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
                $whereClauses[] = "{$column} = :{$column}";
                $queryParams[$column] = $params[$column];
            }
        }

        if (!empty($searchWhereClauses)) {
            array_unshift($whereClauses, "(\n\t\t" . implode("\n\t\tOR ", $searchWhereClauses) . "\n\t)");
        }

        return [
            "where" => $whereClauses,
            "params" => $queryParams,
        ];
    }

    /**
     * Used to get a total count of Entities using a where clause
     * Used together with Entity::getByParams, as this return a limited Entities
     * but we want to get a number of total items without limit
     *
     * @param $where string[]|string|null
     * @param $params array|null
     * @return int
     */
    public static function getCount($where = null, ?array $params = null): int {
        return static::getQuery()->count($where, $params);
    }

    /**
     * Gets all Entities but paginated, also might include search
     *
     * @param $params array Any data to aid in the search query
     * @param $limit int|string|null
     * @param $page int|string|null
     * @return static[]|static|null
     */
    public static function getByParams(array $params, $limit = null, $page = null) {
        // Add filters/wheres if a search was entered
        $resultFromGeneration = static::generateWhereClausesFromParams($params);

        return static::get($resultFromGeneration["where"] ?? null, $resultFromGeneration["params"] ?? null, $limit, $page);
    }

    protected function getValuesToSave(): array {
        $values = [];

        foreach ($this->columns as $column => $value) {
            if ($column !== "id") {
                $values[$column] = $value;
            }
        }

        return $values;
    }

    /**
     * Save values to the Entity Table in the Database
     * Will either be a new insert or a update to an existing Entity
     */
    public function save(): bool {
        $isNew = !$this->isLoaded();
        if ($isNew && static::$hasCreatedAt) {
            $this->setValue("created_at", date(static::$dateTimeFormat));
        }
        if (static::$hasUpdatedAt) {
            $this->setValue("updated_at", date(static::$dateTimeFormat));
        }

        $wasSuccessful = false;
        $query = static::getQuery();
        $values = $this->getValuesToSave();
        if ($isNew) {
            $newId = $query->insert($values);
            if ($newId) {
                $this->setId($newId);
                $wasSuccessful = true;
            }
        }
        else {
            $rowsAffected = $query->update($values, $this->id);
            $wasSuccessful = $rowsAffected > 0;
        }

        // If insert/update was ok, load the new values into entity state
        if ($wasSuccessful) {
            $this->refresh();
            return true;
        }

        // Saving failed so reset id
        $this->setId(null);
        return false;
    }

    /**
     * @param $data array|null
     * @return static
     */
    public static function insert(array $data = null): Entity {
        $entity = static::create($data);
        $entity->save();

        return $entity;
    }

    /**
     * Delete an Entity from the Database
     *
     * @return bool Whether or not deletion was successful
     */
    public function delete(): bool {
        if ($this->isLoaded()) {
            $rowsAffected = static::getQuery()->delete($this->id);
            return $rowsAffected > 0;
        }

        return false;
    }

}
