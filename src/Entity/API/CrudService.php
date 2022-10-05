<?php

namespace App\Entity\API;

use App\Entity\FilterableInterface;
use App\Entity\SearchableInterface;
use App\HTTP\Request;
use JPI\ORM\Entity\Collection as EntityCollection;

class CrudService {

    protected $entityClass;

    protected $paginated = true;
    protected $perPage = 10;

    public function __construct(string $entityClass) {
        $this->entityClass = $entityClass;
    }

    public function getEntityInstance(): AbstractEntity {
        return new $this->entityClass();
    }

    protected function getEntityFromRequest(Request $request): ?AbstractEntity  {
        return $this->getEntityInstance()::getById($request->getIdentifier("id"));
    }

    public function index(Request $request): EntityCollection {
        $where = [];
        $queryParams = [];

        $entity = $this->getEntityInstance();

        if ($entity instanceof FilterableInterface) {
            $filters = $request->getParam("filters");
            if ($filters) {
                $query = $entity::buildQueryFromFilters($filters->toArray());

                $where = $query["where"];
                $queryParams = $query["params"];
            }
        }

        if ($entity instanceof SearchableInterface) {
            $search = $request->getParam("search");
            if ($search) {
                $searchQuery = $entity::buildSearchQuery($search);

                $where = array_merge($where, $searchQuery["where"]);
                $queryParams = array_merge($queryParams, $searchQuery["params"]);
            }
        }

        if (!$this->paginated) {
            return $entity::get($where, $queryParams);
        }

        $limit = (int)$request->getParam("limit");
        if (!$limit) {
            $limit = $this->perPage;
        }

        $page = $request->hasParam("page") ? $request->getParam("page") : 1;

        if (is_numeric($page)) {
            $page = (int)$page;
        }

        // If invalid use page 1
        if (!$page || $page < 1) {
            $page = 1;
        }

        $entities = $entity::get($where, $queryParams, $limit, $page);

        // Handle where limit is 1
        if ($entities instanceof $this->entityClass) {
            $totalCount = $entity::getCount($where, $queryParams);
            $entities = new EntityCollection([$entities], $totalCount, $limit, $page);
        }

        return $entities;
    }

    public function create(Request $request): AbstractEntity {
        $entity = $this->getEntityInstance()::insert($request->data->toArray());

        if (!$entity->hasErrors()) {
            $entity->reload();
        }

        return $entity;
    }

    public function read(Request $request): ?AbstractEntity {
        return $this->getEntityFromRequest($request);
    }

    public function update(Request $request): ?AbstractEntity {
        $entity = $this->getEntityFromRequest($request);

        if (!$entity) {
            return null;
        }

        $entity->setValues($request->data->toArray());
        $entity->save();

        if (!$entity->hasErrors()) {
            $entity->reload();
        }

        return $entity;
    }

    public function delete(Request $request): ?AbstractEntity {
        $entity = $this->getEntityFromRequest($request);

        if ($entity) {
            $entity->delete();
        }

        return $entity;
    }
}
