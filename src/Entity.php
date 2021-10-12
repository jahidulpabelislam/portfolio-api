<?php

/**
 * The base Entity object class for database tables.
 *
 * Holds all ORM style functions.
 */

namespace App;

use App\Database\AwareTrait as DatabaseAware;
use JPI\Database\Collection as DBCollection;
use App\Entity\Collection as EntityCollection;
use App\Entity\Validated;
use App\Utils\Arrayable;
use App\Utils\Str;
use DateTime;
use Exception;

abstract class Entity implements Arrayable {

    use DatabaseAware;
    use Validated;

    protected static $displayName = "";

    protected static $tableName = "";

    protected $identifier = null;

    protected $columns;

    protected static $defaultColumns = [];

    protected static $intColumns = [];
    protected static $dateTimeColumns = [];
    protected static $dateColumns = [];
    protected static $arrayColumns = [];
    protected static $arrayColumnSeparator = ",";

    protected static $orderByColumn = "id";
    protected static $orderByASC = true;

    protected static $defaultLimit = 10;

    public static function getDisplayName(): string {
        return static::$displayName;
    }

    public static function getPluralDisplayName(): string {
        return static::$displayName . "s";
    }

    public static function getIntColumns(): array {
        return static::$intColumns;
    }

    public static function getDateTimeColumns(): array {
        return static::$dateTimeColumns;
    }

    public static function getDateColumns(): array {
        return static::$dateColumns;
    }

    public static function getArrayColumns(): array {
        return static::$arrayColumns;
    }

    /**
     * @param $columns string[]|string|null
     * @param $where string[]|string|int|null
     * @param $params array|null
     * @param $orderBy string[]|string|null
     * @param $limit int|null
     * @param $page int|string|null
     * @return DBCollection|array|null
     */
    public static function select(
        $columns = "*",
        $where = null,
        ?array $params = null,
        $orderBy = null,
        ?int $limit = null,
        $page = null
    ) {
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

    private function setId(?int $id): void {
        $this->identifier = $id;
    }

    public function getId(): ?int {
        return $this->identifier;
    }

    protected function setValue(string $column, $value, bool $fromDB = false): void {
        $label = Str::machineToDisplay($column);

        unset($this->errors[$column]);

        if (in_array($column, static::getIntColumns())) {
            if (is_numeric($value) && $value == (int)$value) {
                $value = (int)$value;
            }
            else if (!is_null($value)) {
                $this->addError($column, "$label must be a integer.");
            }
        }
        else if (in_array($column, static::getArrayColumns())) {
            if ($fromDB && is_string($value)) {
                $value = explode(static::$arrayColumnSeparator, $value);
            }
            else if (!is_array($value) && !is_null($value)) {
                $this->addError($column, "$label must be an array.");
            }
        }
        else if (in_array($column, static::getDateColumns()) || in_array($column, static::getDateTimeColumns())) {
            if (!empty($value) && (is_string($value) || is_numeric($value))) {
                try {
                    $value = new DateTime($value);
                }
                catch (Exception $exception) {
                    error_log("Error creating DateTime instance: " . $exception->getMessage());
                    $this->addError(
                        $column,
                        "$label is a invalid date" . (in_array($column, static::getDateTimeColumns()) ? " time" : "") . " format."
                    );
                }
            }
            else if (!($value instanceof DateTime) && !is_null($value)) {
                $this->addError(
                    $column,
                    "$label must be a date" . (in_array($column, static::getDateTimeColumns()) ? " time" : "") . "."
                );
            }
        }

        // Unexpected value, set to null
        if (isset($this->errors[$column])) {
            $value = null;
        }
        else if (!$value && in_array($column, static::getRequiredColumns())) {
            $this->addError($column, "$label is required.");
        }

        $this->columns[$column] = $value;
    }

    public function setValues(array $values, bool $fromDB = false): void {
        $columns = array_keys($this->columns);
        foreach ($columns as $column) {
            if (array_key_exists($column, $values)) {
                $this->setValue($column, $values[$column], $fromDB);
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

        return $this->columns[$name] ?? null;
    }

    public function __isset(string $name): bool {
        if ($name === "id") {
            return isset($this->identifier);
        }

        return isset($this->columns[$name]);
    }

    public function __construct() {
        $this->columns = static::$defaultColumns;
    }

    public function isLoaded(): bool {
        return !is_null($this->getId());
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
        $entity = new static();
        $entity->setValues($row, true);
        $entity->setId($row["id"]);
        return $entity;
    }

    /**
     * @param $rows DBCollection|array
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
        if (is_numeric($limit)) {
            $limit = (int)$limit;
        }

        // If invalid use default
        if (!$limit || $limit < 1 || (static::$defaultLimit && static::$defaultLimit < $limit)) {
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
     * @param $where string[]|string|int|null
     * @param $params array|null
     * @param $limit int|string|null
     * @param $page int|string|null
     * @return EntityCollection|static|null
     */
    public static function get($where = null, ?array $params = null, $limit = null, $page = null) {
        $orderBy = static::getOrderBy();
        $limit = static::getLimit($limit);

        $rows = static::select("*", $where, $params, $orderBy, $limit, $page);

        if (is_null($rows)) {
            return null;
        }

        if (($where && is_numeric($where)) || $limit === 1) {
            return static::populateFromDB($rows);
        }

        $entities = static::populateEntitiesFromDB($rows);

        $total = null;
        $limit = null;
        $page = null;
        if ($rows instanceof DBCollection) {
            $total = $rows->getTotalCount();
            $limit = $rows->getLimit();
            $page = $rows->getPage();
        }

        return new EntityCollection($entities, $total, $limit, $page);
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
                $ins[] = ":$key";
                $params[$key] = $value;
            }

            $where = "$column in (" . implode(", ", $ins) . ")";
        } else {
            $where = "$column = :$column";
            $params = [$column => $value];
        }

        return static::get($where, $params, $limit, $page);
    }

    /**
     * Load Entity(ies) from the Database where Id column equals/in $id.
     *
     * @param $id int|string|array
     * @return EntityCollection|static|null
     */
    public static function getById($id) {
        if (is_numeric($id) || is_array($id)) {
            return static::getByColumn("id", $id, is_numeric($id) ? 1 : null);
        }

        return null;
    }

    public function reload(): void {
        if ($this->isLoaded()) {
            $row = static::select("*", $this->getId());
            if ($row) {
                $this->setValues($row, true);
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
                    $value = $value->format("Y-m-d");
                }
                else if (in_array($column, $dateTimeColumns)) {
                    $value = $value->format("Y-m-d H:i:s");
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
        if ($this->hasErrors()) {
            return false;
        }

        if ($this->isLoaded()) {
            $rowsAffected = static::getQuery()->update($this->getValuesToSave(), $this->getId());
            if ($rowsAffected === 0) {
                // Updating failed so reset id
                $this->setId(null);
            }
        } else {
            $newId = static::getQuery()->insert($this->getValuesToSave());
            $this->setId($newId);
        }

        return $this->isLoaded();
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

        foreach ($this->columns as $column => $value) {
            $array[$column] = $value;
        }

        return $array;
    }
}
