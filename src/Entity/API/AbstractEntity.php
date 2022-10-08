<?php

namespace App\Entity\API;

use App\Entity\AbstractEntity as BaseEntity;
use DateTime;

abstract class AbstractEntity extends BaseEntity {

    protected static $crudService = CrudService::class;

    public static function getCrudService(): CrudService {
        return new static::$crudService(static::class);
    }

    public function getAPIResponse(): array {
        $response = [
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

            $response[$column] = $value;
        }

        return $response;
    }

    public function getAPILinks(): array {
        return [
            "self" => (string)$this->getAPIURL(),
        ];
    }
}