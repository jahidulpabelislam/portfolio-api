<?php

namespace App\Entity\API;

use App\Entity\FilterableInterface;
use App\Entity\SearchableInterface;
use App\Utils\Str;
use DateTime;
use Exception;
use JPI\HTTP\Request;
use JPI\ORM\Entity\Collection as EntityCollection;
use JPI\ORM\Entity\PaginatedCollection as PaginatedEntityCollection;

class CrudService {

    protected bool $paginated = true;
    protected int $perPage = 10;

    protected static array $requiredColumns = [];

    public function __construct(protected string $entityClass) {
    }

    public function getEntityInstance(): AbstractEntity {
        return new $this->entityClass();
    }

    protected function getEntityFromRequest(Request $request): ?AbstractEntity {
        $id = $request->getAttribute("route_params")["id"];
        if (!is_numeric($id)) {
            return null;
        }

        return $this->getEntityInstance()
            ->getById($request->getAttribute("route_params")["id"])
        ;
    }

    public function index(Request $request): EntityCollection {
        $entity = $this->getEntityInstance();

        $query = $this->getEntityInstance()::newQuery();

        if ($entity instanceof FilterableInterface) {
            $filters = $request->getQueryParam("filters");
            if ($filters) {
                $entity::addFiltersToQuery($query, $filters->toArray());
            }
        }

        if ($entity instanceof SearchableInterface) {
            $search = $request->getQueryParam("search");
            if ($search) {
                $entity::addSearchToQuery($query, $search);
            }
        }

        if (!$this->paginated) {
            return $query->select();
        }

        $limit = (int)$request->getQueryParam("limit");
        if (!$limit) {
            $limit = $this->perPage;
        }

        $page = $request->hasQueryParam("page") ? $request->getQueryParam("page") : 1;

        if (is_numeric($page)) {
            $page = (int)$page;
        }

        // If invalid use page 1
        if (!$page || $page < 1) {
            $page = 1;
        }

        $query->limit($limit, $page);

        $entities = $query->select();

        // Handle where limit is 1
        if ($entities instanceof $this->entityClass) {
            $entities = new PaginatedEntityCollection([$entities], $query->count(), $limit, $page);
        }

        return $entities;
    }

    /**
     * Checks the data in the request + sets entity values from valid data.
     */
    protected function setValuesFromRequest(AbstractEntity $entity, Request $request): void {
        $errors = [];

        $intColumns = $entity::getIntColumns();
        $arrayColumns = $entity::getArrayColumns();
        $dateTimeColumns = $entity::getDateTimeColumns();
        $dateColumns = $entity::getDateColumns();

        $requiredColumns = static::$requiredColumns;

        $data = $request->getArrayFromBody()->toArray();

        // Make sure data submitted is all valid.
        foreach ($entity::getColumns() as $column) {
            $label = Str::machineToDisplay($column);

            if (empty($data[$column])) {
                if (in_array($column, $requiredColumns)) {
                    $errors[$column] = "$label is required.";
                }

                continue;
            }

            $value = $data[$column];

            if (in_array($column, $intColumns)) {
                if (is_numeric($value) && $value == (int)$value) {
                    $value = (int)$value;
                }
                else {
                    $errors[$column] = "$label must be a integer.";
                }
            }
            else if (in_array($column, $dateColumns) || in_array($column, $dateTimeColumns)) {
                try {
                    $value = new DateTime($value);
                }
                catch (Exception $exception) {
                    $errors[$column] = "$label is a invalid date" . (in_array($column, $dateTimeColumns) ? " time" : "") . " format.";
                }
            }
            else if (in_array($column, $arrayColumns) && !is_array($value)) {
                $errors[$column] = "$label must be an array.";
            }

            if (!array_key_exists($column, $errors)) {
                $entity->$column = $value;
            }
        }

        if ($errors) {
            throw new InvalidDataException($errors);
        }
    }

    public function create(Request $request): AbstractEntity {
        $entity = $this->getEntityInstance();
        $this->setValuesFromRequest($entity, $request);
        $entity->save();
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

        $this->setValuesFromRequest($entity, $request);

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
