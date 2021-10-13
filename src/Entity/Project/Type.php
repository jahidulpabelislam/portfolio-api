<?php

namespace App\Entity\Project;

use App\Entity;
use App\Entity\Timestamped;

class Type extends Entity {

    use Timestamped;

    public static $displayName = "Project Type";

    protected static $tableName = "portfolio_project_type";

    protected static $defaultColumns = [
        "project_id" => null,
        "name" => "",
    ];

    protected static $intColumns = ["project_id"];

    protected static $defaultLimit = null;
}
