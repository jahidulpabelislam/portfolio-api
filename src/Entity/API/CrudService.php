<?php

namespace App\Entity\API;

use App\Entity\FilterableInterface;
use App\Entity\SearchableInterface;
use App\HTTP\Request;
use App\Utils\Str;
use DateTime;
use Exception;
use JPI\ORM\Entity\Collection as EntityCollection;

class CrudService {

    protected $entityClass;

    protected $paginated = true;
    protected $perPage = 10;

    protected static $requiredColumns = [];

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

    protected function checkAndSetValues(AbstractEntity $entity, Request $request): void {
        $errors = [];

        $intColumns = $entity::getIntColumns();
        $arrayColumns = $entity::getArrayColumns();
        $dateTimeColumns = $entity::getDateTimeColumns();
        $dateColumns = $entity::getDateColumns();

        $data = $request->data->toArray();

        // Make sure data submitted are all valid.
        foreach ($data as $column => $value) {
            $label = Str::machineToDisplay($column);

            if (in_array($column, $intColumns)) {
                if (is_numeric($value) && $value == (int)$value) {
                    $value = (int)$value;
                }
                else {
                    $errors[$column] = "$label must be a integer.";
                }
            }
            else if (in_array($column, $arrayColumns)) {
                if (!is_array($value)) {
                    $errors[$column] = "$label must be an array.";
                }
            }
            else if (in_array($column, $dateColumns) || in_array($column, $dateTimeColumns)) {
                if (!empty($value) && (is_string($value) || is_numeric($value))) {
                    try {
                        $value = new DateTime($value);
                    }
                    catch (Exception $exception) {
                        $errors[$column] = "$label is a invalid date" . (in_array($column, $dateTimeColumns) ? " time" : "") . " format.";
                    }
                }
                else {
                    $errors[$column] = "$label must be a date" . (in_array($column, $dateTimeColumns) ? " time" : "") . ".";
                }
            }

            if (!array_key_exists($column, $errors)) {
                $entity->$column = $value;
            }
        }

        // Make sure all required columns were submitted.
        $missingColumns = array_diff(static::$requiredColumns, array_keys($data));
        foreach ($missingColumns as $column) {
            $errors[$column] = Str::machineToDisplay($column) . " is required.";
        }

        if ($errors) {
            throw new InvalidDataException('', 0, null, $errors);
        }
    }

    public function create(Request $request): AbstractEntity {
        $entity = $this->getEntityInstance();
        $this->checkAndSetValues($entity, $request);
        $entity->reload();

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

        $this->checkAndSetValues($entity, $request);

        $entity->save();
        $entity->reload();

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
