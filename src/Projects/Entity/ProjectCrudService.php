<?php

namespace App\Projects\Entity;

use App\Auth\Manager as AuthManager;
use App\Entity\CrudService as BaseService;
use App\HTTP\Request;
use App\Utils\Collection;
use JPI\ORM\Entity\Collection as EntityCollection;

class ProjectCrudService extends BaseService {

    protected function getEntityFromRequest(Request $request): ?Project {
        $where = ["id = :id"];

        $id = $request->getIdentifier('projectId') ?: $request->getIdentifier("id");
        $params = ["id" => $id];
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

        // If images were passed update the sort order
        if ($project && !$project->hasErrors() && !empty($request->data["images"])) {
            $project->loadImages();

            $positions = [];
            foreach ($request->data["images"] as $i => $image) {
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
