<?php

namespace App\Entity;

use App\APIEntity;
use App\HTTP\Request;
use JPI\ORM\Entity\Collection as EntityCollection;

class CrudService {

    protected $entityClass;
    protected $paginated = true;
    protected $perPage = 10;

    public function __construct(string $entityClass) {
        $this->entityClass = $entityClass;
    }

    protected function getEntityFromRequest(Request $request) {
        return $this->entityClass::getById($request->getIdentifier("id"));
    }

    public function index(Request $request): EntityCollection {
        $where = [];
        $queryParams = [];

        $entity = new $this->entityClass();
        if ($entity instanceof FilterableInterface) {
            $filters = $request->getParam("filters");
            if ($filters) {
                $query = $this->entityClass::buildQueryFromFilters($filters->toArray());

                $where = $query["where"];
                $queryParams = $query["params"];
            }
        }

        if ($entity instanceof SearchableInterface) {
            $search = $request->getParam("search");
            if ($search) {
                $searchQuery = $this->entityClass::buildSearchQuery($search);

                $where = array_merge($where, $searchQuery["where"]);
                $queryParams = array_merge($queryParams, $searchQuery["params"]);
            }
        }

        $limit = null;
        $page = null;
        if ($this->paginated) {
            $limit = (int) $request->getParam("limit");
            if (!$limit) {
                $limit = $this->perPage;
            }
            $page = $request->getParam("page");
        }

        $entities = $this->entityClass::get($where, $queryParams, $limit, $page);

        if ($entities instanceof $this->entityClass) {
            $totalCount = $this->entityClass::getCount($where, $queryParams);
            $entities = new PaginatedCollection([$entities], $totalCount, $limit, $page);
        }

        return $entities;
    }

    public function create(Request $request): ?APIEntity {
        $entity = $this->entityClass::insert($request->data->toArray());

        if (!$entity->hasErrors()) {
            $entity->reload();
        }

        return $entity;
    }

    public function read(Request $request): ?APIEntity {
        return $this->getEntityFromRequest($request);
    }

    public function update(Request $request): ?APIEntity {
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

    public function delete(Request $request): ?APIEntity {
        $entity = $this->getEntityFromRequest($request);

        if ($entity) {
            $entity->delete();
        }

        return $entity;
    }
}
