<?php

namespace App\Projects\Entity;

use App\Auth\Manager as AuthManager;
use App\APIEntity;
use App\Entity\CrudService as BaseService;
use App\HTTP\Request;
use App\Utils\Collection;
use JPI\ORM\Entity\Collection as EntityCollection;

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
            $params = clone $request->params;
            if (!isset($params["filters"])) {
                $params["filters"] = new Collection();
            }
            $params["filters"]["status"] = Project::PUBLIC_STATUS;
            $request->params = $params;
        }

        $projects = parent::index($request);

        if (count($projects)) {
            $ids = [];
            $typeIds = [];
            foreach ($projects as $project) {
                $ids[] = $project->getId();
                $typeIds[$project->type_id] = "";
            }

            $images = Image::getByColumn("project_id", $ids);

            $imagesGrouped = [];
            foreach ($images as $image) {
                $imagesGrouped[$image->project_id][] = $image;
            }

            $types = Type::getById(array_keys($typeIds));
            $typesGrouped = [];
            foreach ($types as $type) {
                $typesGrouped[$type->getId()] = $type;
            }

            foreach ($projects as $project) {
                $project->images = new EntityCollection($imagesGrouped[$project->getId()] ?? []);
                $project->type = $typesGrouped[$project->type_id] ?? null;
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
