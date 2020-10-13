<?php

namespace App\Entity;

use DateTime;

trait Timestamped {

    public static function getDateTimeColumns(): array {
        $columns = parent::getDateTimeColumns();

        if (!isset(static::$hasCreatedAt) || static::$hasCreatedAt) {
            $columns[] = "created_at";
        }

        if (!isset(static::$hasUpdatedAt) || static::$hasUpdatedAt) {
            $columns[] = "updated_at";
        }

        return $columns;
    }

    public function __construct() {
        parent::__construct();

        if (!isset(static::$hasCreatedAt) || static::$hasCreatedAt) {
            $this->setValue("created_at", null);
        }

        if (!isset(static::$hasUpdatedAt) || static::$hasUpdatedAt) {
            $this->setValue("updated_at", null);
        }
    }

    public function save(): bool {
        $isNew = !$this->isLoaded();

        if ($isNew && (!isset(static::$hasCreatedAt) || static::$hasCreatedAt)) {
            $this->setValue("created_at", new DateTime());
        }

        if (!isset(static::$hasUpdatedAt) || static::$hasUpdatedAt) {
            $this->setValue("updated_at", new DateTime());
        }

        return parent::save();
    }

}
