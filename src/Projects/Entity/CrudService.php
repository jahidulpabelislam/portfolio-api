<?php

namespace App\Projects\Entity;

use App\Auth\Manager as AuthManager;
use App\APIEntity;
use App\Entity\Collection as EntityCollection;
use App\Entity\CrudService as BaseService;
use App\HTTP\Request;
use App\Utils\Collection;

class CrudService extends BaseService {

    protected function getEntityFromRequest(Request $request) {
        $where = ["id = :id"];
        $params = ["id" => $request->getIdentifier("id")];
        if (!AuthManager::isLoggedIn($request)) {
            $where[] = "status = :status";
            $params["status"] = Project::PUBLIC_STATUS;
        }

        return Project::get($where, $params, 1);
    }

    public function index(Request $request): EntityCollection {
        $request = clone $request;

        // As the user isn't logged in, filter by status = public
        if (!AuthManager::isLoggedIn($request)) {
            if (!isset($request->params["filters"])) {
                $request->params["filters"] = new Collection();
            }
            $request->params["filters"]["status"] = Project::PUBLIC_STATUS;
        }

        $projects = parent::index($request);

        if (count($projects)) {
            $images = Image::getByColumn("project_id", $projects->pluck("id")->toArray());
            $imagesGrouped = $images->groupBy("project_id");

            foreach ($projects as $project) {
                $project->images = $imagesGrouped[$project->getId()] ?? new EntityCollection();
            }

            $types = Type::getById($projects->pluck("type_id")->toArray());
            $typesGrouped = $types->groupBy("id");

            foreach ($projects as $project) {
                $project->type = $typesGrouped[$project->type_id][0] ?? null;
            }
        }

        return $projects;
    }

    public function create(Request $request): ?APIEntity {
        $request = clone $request;

        $data = $request->data;
        if (!empty($data["type"])) {
            $type = Type::getByNameOrCreate($data["type"]);
            $data["type_id"] = $type->getId();
        }

        $project = parent::create($request);

        if ($project) {
            $project->loadType();
        }

        return $project;
    }

    public function read(Request $request): ?APIEntity {
        $project = parent::read($request);

        if ($project) {
            $project->loadType();
            $project->loadImages();
        }

        return $project;
    }

    public function update(Request $request): ?APIEntity {
        $request = clone $request;

        $data = $request->data;
        if (!empty($data["type"])) {
            $type = Type::getByNameOrCreate($data["type"]);
            $data["type_id"] = $type->getId();
        }

        $project = parent::update($request);

        if ($project && !$project->hasErrors()) {
            $project->loadType();

            // If images were passed update the sort order
            if (!empty($data["images"])) {
                $project->loadImages();

                $orders = [];
                foreach ($data["images"] as $i => $image) {
                    $orders[$image["id"]] = $i + 1;
                }

                foreach ($project->images as $projectImage) {
                    $projectImage->position = $orders[$projectImage->getId()];
                    $projectImage->save();
                }

                $project->loadImages(true);
            }
        }

        return $project;
    }
}
