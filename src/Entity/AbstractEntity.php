<?php

namespace App\Entity;

use App\Core;
use App\Utils\Arrayable;
use App\Utils\Str;
use DateTime;
use JPI\Database\Connection;
use JPI\ORM\Entity as BaseEntity;
use JPI\ORM\Entity\Collection;

abstract class AbstractEntity extends BaseEntity implements Arrayable {

    protected static $dbConnection = null;

    protected static $displayName = "";

    protected static $requiredColumns = [];

    protected $errors = [];

    public static function getDisplayName(): string {
        return static::$displayName;
    }

    public static function getPluralDisplayName(): string {
        return static::$displayName . "s";
    }

    public static function getRequiredColumns(): array {
        return static::$requiredColumns;
    }

    public static function getDatabaseConnection(): Connection {
        if (!static::$dbConnection) {
            $config = Core::get()->getConfig();
            static::$dbConnection = new Connection([
                "host" => $config->db_host,
                "database" => $config->db_name,
                "username" => $config->db_username,
                "password" => $config->db_password,
            ]);
        }

        return static::$dbConnection;
    }

    public static function get($where = null, ?array $params = null, $limit = null, $page = null) {
        $result = parent::get($where, $params, $limit, $page);

        if (
            $result instanceof Collection ||
            $result === null ||
            $limit == 1 ||
            ($where && is_numeric($where))
        ) {
            return $result;
        }

        return new Collection($result);
    }

    public function getErrors(): array {
        return $this->errors;
    }

    public function hasErrors(): bool {
        return count($this->getErrors());
    }

    protected function addError(string $column, string $error): void {
        $this->errors[$column] = $error;
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

    public function save(): bool {
        if ($this->hasErrors()) {
            return false;
        }

        return parent::save();
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
