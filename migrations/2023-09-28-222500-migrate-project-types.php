<?php

use App\Projects\Project;
use App\Projects\Type;
use JPI\Database\Query;

require_once __DIR__ . "/../bootstrap.php";

$totalCount = Project::newQuery()->where("type != ''")->count();

$totalPages = ceil($totalCount / 10);

$query = new Query\Builder(Project::getDatabase());
$query->table(Project::getTable());

for ($page = 1; $page <= $totalPages; $page++) {
    $projects = Project::newQuery()
        ->where("type != ''")
        ->limit(10, $page)
        ->select()
    ;

    foreach ($projects as $project) {
        // Need to manually get the type column as not in the entity no more.
        $typeName = (clone $query)
            ->column("type")
            ->where("id = {$project->getId()}")
            ->limit(1)
            ->select()
            ["type"]
        ;

        $type = Type::getByNameOrCreate(trim($typeName));

        $project->type_id = $type->getId();
        $project->save();
    }
}
