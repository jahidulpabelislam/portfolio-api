<?php

declare(strict_types=1);

namespace App\Projects;

use App\AbstractEntity as AbstractAPIEntity;
use App\Core;
use App\Entity\Timestamped;
use JPI\Utils\URL;

final class Type extends AbstractAPIEntity {

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

    public function getAPIURL(): URL {
        return Core::get()->getRouter()->getURLForRoute(
            "projectType",
            [
                "id" => $this->getId(),
            ]
        );
    }
}
