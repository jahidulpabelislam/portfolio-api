<?php

namespace App\Projects\Entity;

use App\Core;
use App\Entity\API\AbstractEntity as AbstractAPIEntity;
use App\Entity\Timestamped;

class Type extends AbstractAPIEntity {

    use Timestamped;

    public static $displayName = "Project Type";

    protected static string $table = "project_types";

    protected static array $dataMapping = [
        "name" => [
            "type" => "string",
        ],
    ];

    protected static string $crudService = TypeCrudService::class;

    public static function getByNameOrCreate(string $name): Type {
        $type = static::newQuery()
            ->where("name", "=", $name)
            ->limit(1)
            ->select()
        ;

        if ($type) {
            return $type;
        }

        return static::insert(["name" => $name]);
    }

    public function getAPIURL(): string {
        return Core::get()->getRouter()->getURLForRoute(
            "projectType",
            [
                "id" => $this->getId(),
            ]
        );
    }
}
