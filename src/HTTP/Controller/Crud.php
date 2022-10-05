<?php

namespace App\HTTP\Controller;

use App\Entity\API\AbstractEntity as AbstractAPIEntity;
use App\Entity\API\Responder as EntityResponder;
use App\HTTP\Controller;
use App\HTTP\Response;

abstract class Crud extends Controller implements AuthGuarded {

    use EntityResponder;

    protected $publicFunctions = [];

    protected $entityClass = null;

    public function getPublicFunctions(): array {
        return $this->publicFunctions;
    }

    public function getEntityInstance(): AbstractAPIEntity {
        return new $this->entityClass();
    }

    /**
     * Gets all entities but paginated (also might include search & filters)
     *
     * @return Response
     */
    public function index(): Response {
        $entities = $this->getEntityInstance()::getCrudService()->index($this->request);

        if ($entities->getTotalCount()) {
            return $this->getPaginatedItemsResponse($this->entityClass, $entities);
        }

        return $this->getItemsResponse($this->entityClass, $entities);
    }

    /**
     * Try and create/add a new entity.
     *
     * @return Response
     */
    public function create(): Response {
        $entity = $this->getEntityInstance()::getCrudService()->create($this->request);
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
        $entity = $this->getEntityInstance()::getCrudService()->read($this->request);
        return self::getItemResponse($this->entityClass, $entity, $id);
    }

    /**
     * Try to update the entity.
     *
     * @param $id int|string The Id of the entity to update
     * @return Response
     */
    public function update($id): Response {
        $entity = $this->getEntityInstance()::getCrudService()->update($this->request);

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
        $entity = $this->getEntityInstance()::getCrudService()->delete($this->request);
        return self::getItemDeletedResponse($this->entityClass, $entity, $id);
    }
}
