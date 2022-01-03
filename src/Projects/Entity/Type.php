<?php

namespace App\Projects\Entity;

use App\APIEntity;
use App\Core;
use App\Entity\Timestamped;

class Type extends APIEntity {

    use Timestamped;

    public static $displayName = "Project Type";

    protected static $table = "portfolio_project_type";

    protected static $defaultColumns = [
        "name" => "",
    ];

    protected static $crudService = TypeCrudService::class;

    public static function getByNameOrCreate(string $name): Type {
        $type = static::getByColumn("name", $name, 1);

        if ($type) {
            return $type;
        }

        return static::insert(["name" => $name]);
    }

    public function getAPIURL(): string {
        return Core::get()->getRouter()->makeUrl(
            "projectType",
            [
                "id" => $this->getId(),
            ]
        );
    }
}
