<?php

namespace App\Projects\Entity;

use App\Entity;
use App\Entity\Timestamped;

class Type extends Entity {

    use Timestamped;

    public static $displayName = "Project Type";

    protected static $tableName = "portfolio_project_type";

    protected static $defaultColumns = [
        "name" => "",
    ];

    protected static $defaultLimit = null;

    public static function getByNameOrCreate(string $name): Type {
        $type = static::getByColumn("name", $name, 1);

        if ($type) {
            return $type;
        }

        return static::insert(["name" => $name]);
    }
}
