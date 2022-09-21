<?php

namespace App\Entity;

use DateTime;

trait Timestamped {

    public function __construct() {
        parent::__construct();

        $this->addTimestampColumns();
    }

    protected function addTimestampColumns(): void {
        if (!isset(static::$hasCreatedAt) || static::$hasCreatedAt) {
            $this->setValue("created_at", null);
        }

        if (!isset(static::$hasUpdatedAt) || static::$hasUpdatedAt) {
            $this->setValue("updated_at", null);
        }
    }

    protected static function getTimestampColumns(): array {
        $columns = [];

        if (!isset(static::$hasCreatedAt) || static::$hasCreatedAt) {
            $columns[] = "created_at";
        }

        if (!isset(static::$hasUpdatedAt) || static::$hasUpdatedAt) {
            $columns[] = "updated_at";
        }

        return $columns;
    }

    public static function getDateTimeColumns(): array {
        return array_merge(parent::getDateTimeColumns(), static::getTimestampColumns());
    }

    protected function setTimestamps(): void {
        $isNew = !$this->isLoaded();

        if ($isNew && (!isset(static::$hasCreatedAt) || static::$hasCreatedAt)) {
            $this->setValue("created_at", new DateTime());
        }

        if (!isset(static::$hasUpdatedAt) || static::$hasUpdatedAt) {
            $this->setValue("updated_at", new DateTime());
        }
    }

    public function save(): bool {
        $this->setTimestamps();

        return parent::save();
    }
}
