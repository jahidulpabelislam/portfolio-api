<?php

declare(strict_types=1);

namespace App\Projects;

use JPI\CRUD\API\AbstractEntity;
use JPI\CRUD\API\CrudService as BaseService;
use JPI\CRUD\API\Entity\InvalidDataException;
use JPI\HTTP\Request;
use JPI\ORM\Entity\Collection as EntityCollection;
use JPI\Utils\Collection;

final class ProjectCrudService extends BaseService {

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
                $project->setValues(
                    [
                        "images" => new EntityCollection($imagesGrouped[$project->getId()] ?? []),
                        "type_id" => $typesGrouped[$project->type_id] ?? new Type(),
                    ],
                    true
                );
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

        $data = $request->getArrayFromBody()->toArray();

        if (array_key_exists("type", $data) && array_key_exists("type_id", $data)) {
            $errors["type"] = "Only one of `type`, `type_id` can be submitted.";
        }
        else if (array_key_exists("type_id", $data)) {
            if (empty($data["type_id"])) {
                $errors["type_id"] = "`type_id` cannot be empty.";
            }
        }
        else if (array_key_exists("type", $data)) {
            if (empty($data["type"])) {
                $errors["type"] = "`type` cannot be empty.";
            }
            else {
                $type = Type::getByNameOrCreate($data["type"]);
                $entity->type = $type;
            }
        }
        else if (!$entity->isLoaded()) {
            $errors["type"] = "`type` is required.";
        }

        if ($errors) {
            throw new InvalidDataException($errors);
        }
    }

    public function create(Request $request): Project {
        $project = parent::create($request);

        $project->images = new EntityCollection([]);

        return $project;
    }

    public function read(Request $request): ?Project {
        $project = parent::read($request);

        if ($project) {
            // Just load
            $project->type;
            $project->images;
        }

        return $project;
    }

    public function update(Request $request): ?Project {
        $project = parent::update($request);

        $input = $request->getArrayFromBody();

        if ($project) {
            $project->type; // load

            // If images were passed update the sort order
            if (!empty($input["images"])) {
                $imagesData = $input["images"]->toArray();

                foreach ($project->images as $image) {
                    $newPosition = array_search($image->getId(), $imagesData) + 1;
                    if ($image->position !== $newPosition) {
                        $image->position = $newPosition;
                        $image->save();
                    }
                }

                $project->reloadImages();
            }
        }

        return $project;
    }
}
