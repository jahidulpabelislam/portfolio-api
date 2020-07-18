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

abstract class Entity implements Arrayable {

    protected static $db;

    public static $displayName = "";

    protected static $tableName = "";

    protected $identifier = null;
    protected $columns = null;

    protected static $defaultColumns = [];

    protected static $requiredColumns = [];

    protected static $intColumns = [];

    protected static $dateTimeColumns = [];
    protected static $dateTimeFormat = "Y-m-d H:i:s";

    protected static $dateColumns = [];
    protected static $dateFormat = "Y-m-d";

    protected static $arrayColumns = [];
    protected static $arrayColumnSeparator = ",";

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
     * @param $page int|string|null
     * @return DbCollection|array|null
     */
    public static function select($columns = "*", $where = null, ?array $params = null, $orderBy = null, ?int $limit = null, $page = null) {
        return static::getQuery()->select($columns, $where, $params, $orderBy, $limit, $page);
    }

    /**
     * Used to get a total count of Entities using a where clause
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
            if (!empty($value) && (is_string($value) || is_numeric($value))) {
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
        $this->columns = static::$defaultColumns;

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

    protected static function getOrderBy(): array {
        $orderBys = [];
        if (static::$orderByColumn) {
            $orderBys[] = static::$orderByColumn . " " . (static::$orderByASC ? "ASC" : "DESC");
        }

        // Sort by id if not already to stop any randomness on rows with same value on above
        if (static::$orderByColumn !== "id") {
            $orderBys[] = "id ASC";
        }

        return $orderBys;
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
     * @param $value string|int|array
     * @param $limit int|string|null
     * @param $page int|string|null
     * @return EntityCollection|static|null
     */
    public static function getByColumn(string $column, $value, $limit = null, $page = null) {
        if (is_array($value)) {
            $values = $value;
            $params = [];
            $ins = [];
            foreach ($values as $i => $value) {
                $key = "{$column}_" . ($i + 1);
                $ins[] = ":{$key}";
                $params[$key] = $value;
            }

            $where = "{$column} in (" . implode(", ", $ins) . ")";
        } else {
            $where = "{$column} = :{$column}";
            $params = [$column => $value];
        }

        return static::get($where, $params, $limit, $page);
    }

    /**
     * Load Entity(ies) from the Database where Id column equals/in $id.
     *
     * @param $id int|string|array
     * @return static|static[]|null
     */
    public static function getById($id) {
        if (is_numeric($id)) {
            return static::get((int)$id);
        }

        if (is_array($id)) {
            return static::getByColumn("id", $id);
        }

        return null;
    }

    public function reload() {
        if ($this->isLoaded()) {
            $row = static::select("*", $this->getId());
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
