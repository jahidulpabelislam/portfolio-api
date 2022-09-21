<?php

namespace App\Projects\Entity;

use App\Auth\Manager as AuthManager;
use App\APIEntity;
use App\Entity\CrudService as BaseService;
use App\HTTP\Request;
use App\Utils\Collection;
use JPI\ORM\Entity\Collection as EntityCollection;

class CrudService extends BaseService {

    protected function getEntityFromRequest(Request $request): ?APIEntity {
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

    public function read(Request $request): ?APIEntity {
        $project = parent::read($request);

        if ($project) {
            $project->loadImages();
        }

        return $project;
    }

    public function update(Request $request): ?APIEntity {
        $data = $request->data;

        $project = parent::update($request);

        if ($project && !$project->hasErrors()) {
            // If images were passed update the sort order
            if (!empty($data["images"])) {
                $project->loadImages();

                $orders = [];
                foreach ($data["images"] as $i => $image) {
                    $orders[$image["id"]] = $i + 1;
                }

                foreach ($project->images as $image) {
                    $newPosition = $orders[$image->getId()];
                    if ($image->position != $newPosition) {
                        $image->position = $newPosition;
                        $image->save();
                    }
                }

                $project->loadImages(true);
            }
        }

        return $project;
    }
}
