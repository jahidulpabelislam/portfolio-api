<?php

namespace App\Projects\Entity;

use App\Entity\API\CrudService as BaseService;
use JPI\HTTP\Request;
use JPI\ORM\Entity\Collection as EntityCollection;
use JPI\Utils\Collection;

class ProjectCrudService extends BaseService {

    protected static $requiredColumns = [
        "name",
        "date",
        "type",
        "tags",
        "long_description",
        "short_description",
    ];

    protected function getEntityFromRequest(Request $request): ?Project {
        $where = ["id = :id"];

        $routeParams = $request->getAttribute("route_params");
        $id = $routeParams["projectId"] ?? $routeParams["id"];
        $params = ["id" => $id];
        if (!$request->getAttribute("is_authenticated")) {
            $where[] = "status = :status";
            $params["status"] = Project::PUBLIC_STATUS;
        }

        return Project::get($where, $params, 1);
    }

    public function index(Request $request): EntityCollection {
        $request = clone $request;

        // As the user isn't logged in, filter by status = public
        if (!$request->getAttribute("is_authenticated")) {
            $params = $request->getQueryParams();
            if (!isset($params["filters"])) {
                $params["filters"] = new Collection();
            }
            $params["filters"]["status"] = Project::PUBLIC_STATUS;
            $request->setQueryParams($params);
        }

        $projects = parent::index($request);

        if (count($projects)) {
            $ids = [];
            foreach ($projects as $project) {
                $ids[] = $project->getId();
            }

            $images = Image::getByColumn("project_id", $ids);

            $imagesGrouped = [];
            foreach ($images as $image) {
                $imagesGrouped[$image->project_id][] = $image;
            }

            foreach ($projects as $project) {
                $project->images = new EntityCollection($imagesGrouped[$project->getId()] ?? []);
            }
        }

        return $projects;
    }

    public function read(Request $request): ?Project {
        $project = parent::read($request);

        if ($project) {
            $project->loadImages();
        }

        return $project;
    }

    public function update(Request $request): ?Project {
        $project = parent::update($request);

        $input = $request->getArrayFromBody();

        // If images were passed update the sort order
        if ($project && !empty($input["images"])) {
            $project->loadImages();

            $positions = [];
            foreach ($input["images"] as $i => $image) {
                $positions[$image["id"]] = $i + 1;
            }

            foreach ($project->images as $image) {
                $newPosition = $positions[$image->getId()];
                if ($image->position !== $newPosition) {
                    $image->position = $newPosition;
                    $image->save();
                }
            }

            $project->loadImages(true); // Reload
        }

        return $project;
    }
}
