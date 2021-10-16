<?php

namespace App\HTTP\Controller;

use App\HTTP\Controller;
use App\HTTP\Response;

class Crud extends Controller implements AuthGuarded {

    protected $publicFunctions = [];

    protected $entityClass = null;

    public function getPublicFunctions(): array {
        return $this->publicFunctions;
    }

    /**
     * Gets all entities but paginated (also might include search & filters)
     *
     * @return Response
     */
    public function index(): Response {
        $entities = $this->entityClass::getCrudService()->index($this->request);
        return $this->getPaginatedItemsResponse($this->entityClass, $entities);
    }

    /**
     * Try and create/add a new entity.
     *
     * @return Response
     */
    public function create(): Response {
        $entity = $this->entityClass::getCrudService()->create($this->request);
        if ($entity->hasErrors()) {
            return $this->getInvalidInputResponse($entity->getErrors());
        }
        return self::getInsertResponse($this->entityClass, $entity);
    }

    /**
     * Get a particular entity.
     *
     * @param $id int|string The Id of the entity to get
     * @return Response
     */
    public function read($id): Response {
        $entity = $this->entityClass::getCrudService()->read($this->request);
        return self::getItemResponse($this->entityClass, $entity, $id);
    }

    /**
     * Try to update the entity.
     *
     * @param $id int|string The Id of the entity to update
     * @return Response
     */
    public function update($id): Response {
        $entity = $this->entityClass::getCrudService()->update($this->request);

        if ($entity && $entity->hasErrors()) {
            return $this->getInvalidInputResponse($entity->getErrors());
        }

        return self::getUpdateResponse($this->entityClass, $entity, $id);
    }

    /**
     * Try to delete the entity.
     *
     * @param $id int|string The Id of the entity to delete
     * @return Response
     */
    public function delete($id): Response {
        $entity = $this->entityClass::getCrudService()->delete($this->request);
        return self::getItemDeletedResponse($this->entityClass, $entity, $id);
    }
}