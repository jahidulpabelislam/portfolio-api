<?php

namespace App\Entity\Project;

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
}
