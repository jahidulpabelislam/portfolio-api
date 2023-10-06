<?php

declare(strict_types=1);

namespace App\Projects\Entity;

use App\Entity\API\AbstractEntity;
use App\Entity\API\CrudService as BaseService;
use App\Entity\API\InvalidDataException;
use JPI\HTTP\Request;
use JPI\ORM\Entity\Collection as EntityCollection;
use JPI\Utils\Collection;

class ProjectCrudService extends BaseService {

    protected static array $requiredColumns = [
        "name",
        "date",
        "tags",
        "long_description",
        "short_description",
    ];

    protected function getEntityFromRequest(Request $request): ?Project {
        $routeParams = $request->getAttribute("route_params");

        $query = $this->getEntityInstance()::newQuery()
            ->where("id", "=", $routeParams["projectId"] ?? $routeParams["id"])
            ->limit(1)
        ;

        if (!$request->getAttribute("is_authenticated")) {
            $query->where("status", "=", Project::PUBLIC_STATUS);
        }

        return $query->select();
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
            $typeIds = [];
            foreach ($projects as $project) {
                $ids[] = $project->getId();
                $typeIds[$project->type_id] = "";
            }

            $images = Image::newQuery()
                ->where("project_id", "IN", $ids)
                ->select()
            ;

            $imagesGrouped = [];
            foreach ($images as $image) {
                $imagesGrouped[$image->project_id][] = $image;
            }

            $types = Type::newQuery()
                ->where("id", "IN", array_keys($typeIds))
                ->select()
            ;

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

    protected function setValuesFromRequest(AbstractEntity $entity, Request $request): void {
        $errors = [];

        try {
            parent::setValuesFromRequest($entity, $request);
        } catch (InvalidDataException $exception) {
            $errors = $exception->getErrors();
        }

        $data = $request->data->toArray();

        if (empty($data["type_id"]) && empty($data["type"])) {
            $errors["type_id"] = "Type is required.";
        } elseif (!array_key_exists("type_id", $data) && !empty($data["type"])) {
            $type = Type::getByNameOrCreate($data["type"]);
            $entity->type_id = $type->getId();
        }

        if ($errors) {
            throw new InvalidDataException($errors);
        }
    }

    public function create(Request $request): Project {
        $project = parent::create($request);

        $project->images = new EntityCollection([]);

        $project->loadType();

        return $project;
    }

    public function read(Request $request): ?Project {
        $project = parent::read($request);

        if ($project) {
            $project->loadType();
            $project->loadImages();
        }

        return $project;
    }

    public function update(Request $request): ?Project {
        $project = parent::update($request);

        $input = $request->getArrayFromBody();

        if ($project) {
            $project->loadType();

            // If images were passed update the sort order
            if (!empty($input["images"])) {
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
        }

        return $project;
    }
}
