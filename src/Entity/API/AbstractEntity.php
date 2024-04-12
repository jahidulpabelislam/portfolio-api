<?php

declare(strict_types=1);

namespace App\Entity\API;

use App\Entity\AbstractEntity as BaseEntity;
use DateTime;
use JPI\ORM\Entity;
use JPI\ORM\Entity\Collection as EntityCollection;
use ReflectionClass;

abstract class AbstractEntity extends BaseEntity {

    protected static string $crudService = CrudService::class;

    public static function getDisplayName(): string {
        if (isset(static::$displayName)) {
            return static::$displayName;
        }

        return (new ReflectionClass(static::class))->getShortName();
    }

    public static function getPluralDisplayName(): string {
        return static::getDisplayName() . "s";
    }

    public static function getCrudService(): CrudService {
        return new static::$crudService(static::class);
    }

    public function getAPIResponse(): array {
        $response = [
            "id" => $this->getId(),
        ];

        $mapping = static::getDataMapping();

        foreach ($this->data as $column => $value) {
            if (!array_key_exists("value", $value)) {
                continue;
            }

            $value = $value["value"];

            if ($value instanceof Entity) {
                $value = $value->getAPIResponse();
            }
            else if ($value instanceof EntityCollection) {
                $items = $value;
                $value = [];
                foreach ($items as $item) {
                    $itemResponse = $item->getAPIResponse();
                    $itemResponse["_links"] = $item->getAPILinks();
                    $value[] = $itemResponse;
                }
            }
            else if ($value instanceof DateTime) {
                if ($mapping[$column]["type"] === "date_time") {
                    $value = $value->format("Y-m-d H:i:s e");
                }
                else {
                    $value = $value->format("Y-m-d");
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
