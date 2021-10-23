<?php

use App\Projects\Entity\Project;
use App\Projects\Entity\Type;

require_once __DIR__ . "/../bootstrap.php";

$totalCount = Project::getCount("type != ''");

$totalPages = ceil($totalCount / 10);

for ($page = 1; $page <= $totalPages; $page++) {
    $projects = Project::get("type != ''", null,  null, $page);

    foreach ($projects as $project) {
        $type = Type::getByNameOrCreate(trim($project->type));
        $project->type_id = $type->getId();
        $project->save();
    }
}
