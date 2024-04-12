<?php

declare(strict_types=1);

namespace App\Entity;

use DateTime;

trait Timestamped {

    protected static function addTimestampToDataMapping(array $mapping): array {
        if (!isset(static::$hasCreatedAt) || static::$hasCreatedAt) {
            $mapping["created_at"] = [
                "type" => "date_time",
            ];
        }

        if (!isset(static::$hasUpdatedAt) || static::$hasUpdatedAt) {
            $mapping["updated_at"] = [
                "type" => "date_time",
            ];
        }

        return $mapping;
    }

    public static function getDataMapping(): array {
        $mapping = parent::getDataMapping();
        return static::addTimestampToDataMapping($mapping);
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
