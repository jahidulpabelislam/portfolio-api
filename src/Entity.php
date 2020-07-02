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

use App\Database\Collection as DbCollection;
use App\Database\Connection;
use App\Database\Query;
use App\Entity\Collection as EntityCollection;
use DateTime;

abstract class Entity {

    protected static $db;

    public static $displayName = "";

    protected static $tableName = "";

    protected $identifier = null;
    protected $columns = [];

    protected static $requiredColumns = [];

    protected static $intColumns = [];

    protected static $dateTimeColumns = [];
    protected static $dateTimeFormat = "Y-m-d H:i:s";

    protected static $dateColumns = [];
    protected static $dateFormat = "Y-m-d";

    protected static $arrayColumns = [];
    protected static $arrayColumnSeparator = ",";

    protected static $searchableColumns = [];

    protected static $hasCreatedAt = true;
    protected static $hasUpdatedAt = true;

    protected static $orderByColumn = "id";
    protected static $orderByASC = true;

    protected static $defaultLimit = 10;

    public static function getIntColumns(): array {
        return static::$intColumns;
    }

    public static function getDateTimeColumns(): array {
        $columns = static::$dateTimeColumns;

        if (static::$hasCreatedAt) {
            $columns[] = "created_at";
        }

        if (static::$hasUpdatedAt) {
            $columns[] = "updated_at";
        }

        return $columns;
    }

    public static function getDateColumns(): array {
        return static::$dateColumns;
    }

    public static function getArrayColumns(): array {
        return static::$arrayColumns;
    }

    public static function getRequiredFields(): array {
        return static::$requiredColumns;
    }

    public static function getDB(): Connection {
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

    /**
     * @param $columns string[]|string|null
     * @param $where string[]|string|int|null
     * @param $params array|null
     * @param $orderBy string[]|string|null
     * @param $limit int|null
     * @param $page int|null
     * @return DbCollection|array|null
     */
    public static function select($columns = "*", $where = null, ?array $params = null, $orderBy = null, ?int $limit = null, ?int $page = null) {
        return static::getQuery()->select($columns, $where, $params, $orderBy, $limit, $page);
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

    private function setId(?int $id) {
        $this->identifier = $id;
    }

    public function getId(): ?int {
        return $this->identifier;
    }

    private function setValue(string $column, $value) {
        if (in_array($column, static::getIntColumns())) {
            $value = (int)$value;
        }
        else if (in_array($column, static::getArrayColumns())) {
            if (is_string($value)) {
                $value = explode(static::$arrayColumnSeparator, $value);
            }
            else if (!is_array($value)) {
                $value = []; // Unexpected value, set to empty array
            }
        }
        else if (in_array($column, static::getDateColumns()) || in_array($column, static::getDateTimeColumns())) {
            if ($value && is_string($value)) {
                $value = new DateTime($value);
            }
            else if (!($value instanceof DateTime)) {
                $value = null; // Unexpected value, set to null
            }
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

    public function __set(string $name, $value) {
        if (array_key_exists($name, $this->columns)) {
            $this->setValue($name, $value);
        }
    }

    public function __get(string $name) {
        if ($name === "id") {
            return $this->getId();
        }

        if (array_key_exists($name, $this->columns)) {
            return $this->columns[$name];
        }

        return null;
    }

    public function __isset(string $name): bool {
        if ($name === "id") {
            return isset($this->identifier);
        }

        return isset($this->columns[$name]);
    }

    public function __construct() {
        if (static::$hasCreatedAt) {
            $this->setValue("created_at", null);
        }

        if (static::$hasUpdatedAt) {
            $this->setValue("updated_at", null);
        }
    }

    public function isLoaded(): bool {
        return $this->getId() !== null;
    }

    public static function factory(?array $data = null): Entity {
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
        $entity = static::factory($row);
        $entity->setId($row["id"]);
        return $entity;
    }

    /**
     * @param $rows array[]
     * @return static[]
     */
    private static function populateEntitiesFromDB($rows): array {
        $entities = [];

        foreach ($rows as $row) {
            $entities[] = static::populateFromDB($row);
        }

        return $entities;
    }

    /**
     * Get the limit to use for a SQL query
     * Can specify a limit and it will make sure it is not above the max/default
     *
     * @param $limit int|string|null
     * @return int|null
     */
    protected static function getLimit($limit = null): ?int {
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
    protected static function getPage($page = null): ?int {
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
     * @param $where string[]|string|int
     * @param $params array|null
     * @param $limit int|string|null
     * @param $page int|string|null
     * @return EntityCollection|static|null
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

        $entities = static::populateEntitiesFromDB($rows);

        return new EntityCollection(
            $entities,
            $rows->getTotalCount(),
            $rows->getLimit(),
            $rows->getPage()
        );
    }

    /**
     * Get Entities from the Database where a column ($column) = a value ($value)
     *
     * @param $column string
     * @param $value string|int
     * @param $limit int|string|null
     * @param $page int|string|null
     * @return EntityCollection|static|null
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

    /**
     * Helper function
     * Used to generate a where clause for a search on a entity along with any params needed
     * Used with Entity::getByParams();
     *
     * @param $params array The fields to search for within searchable columns (if any)
     * @return array|null [string, array] Generated SQL where clause(s) and an associative array containing any params for query
     */
    protected static function generateWhereClausesFromParams(array $params): ?array {
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
     * Gets all Entities but paginated, also might include search
     *
     * @param $params array Any data to aid in the search query
     * @param $limit int|string|null
     * @param $page int|string|null
     * @return EntityCollection|static|null
     */
    public static function getByParams(array $params, $limit = null, $page = null) {
        // Add filters/wheres if a search was entered
        $resultFromGeneration = static::generateWhereClausesFromParams($params);

        return static::get($resultFromGeneration["where"] ?? null, $resultFromGeneration["params"] ?? null, $limit, $page);
    }

    public function reload() {
        if ($this->isLoaded()) {
            $row = static::select("*", $this->getId(), null, null, 1);
            if ($row) {
                $this->setValues($row);
                return;
            }

            $this->setId(null);
        }
    }

    protected function getValuesToSave(): array {
        $values = [];

        $arrayColumns = static::getArrayColumns();
        $dateColumns = static::getDateColumns();
        $dateTimeColumns = static::getDateTimeColumns();

        foreach ($this->columns as $column => $value) {
            if (in_array($column, $arrayColumns)) {
                $value = implode(static::$arrayColumnSeparator, $value);
            }
            else if ($value instanceof DateTime) {
                if (in_array($column, $dateColumns)) {
                    $value = $value->format(static::$dateFormat);
                }
                else if (in_array($column, $dateTimeColumns)) {
                    $value = $value->format(static::$dateTimeFormat);
                }
            }
            $values[$column] = $value;
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
            $this->setValue("created_at", new DateTime());
        }
        if (static::$hasUpdatedAt) {
            $this->setValue("updated_at", new DateTime());
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
            $rowsAffected = $query->update($values, $this->getId());
            $wasSuccessful = $rowsAffected > 0;
        }

        if ($wasSuccessful) {
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
        $entity = static::factory($data);
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
            $rowsAffected = static::getQuery()->delete($this->getId());
            return $rowsAffected > 0;
        }

        return false;
    }

    public function toArray(): array {
        $array = [
            "id" => $this->getId(),
        ];

        $dateColumns = static::getDateColumns();
        $dateTimeColumns = static::getDateTimeColumns();

        foreach ($this->columns as $column => $value) {
            if ($value instanceof DateTime) {
                if (in_array($column, $dateColumns)) {
                    $value = $value->format("Y-m-d");
                }
                else if (in_array($column, $dateTimeColumns)) {
                    $value = $value->format("Y-m-d H:i:s e");
                }
            }

            $array[$column] = $value;
        }

        return $array;
    }

}
