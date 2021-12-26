<?php

namespace App;

use App\Utils\Arrayable;
use App\Utils\Str;
use DateTime;
use JPI\Database\Connection;
use JPI\ORM\Entity as BaseEntity;

abstract class Entity extends BaseEntity implements Arrayable {

    protected static $displayName = "";

    protected static $defaultLimit = 10;

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

    protected static function getLimit($limit = null): ?int {
        $limit = parent::getLimit($limit);

        // If invalid use default
        if (!$limit || $limit < 1 || (static::$defaultLimit && static::$defaultLimit < $limit)) {
            $limit = static::$defaultLimit;
        }

        return $limit;
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
