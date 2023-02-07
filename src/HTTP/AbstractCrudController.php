<?php

namespace App\HTTP;

use App\Auth\GuardedControllerInterface;
use App\Entity\API\AbstractEntity as AbstractAPIEntity;
use App\Entity\API\InvalidDataException;
use App\Entity\API\Responder as EntityResponder;
use JPI\ORM\Entity\PaginatedCollection;

abstract class AbstractCrudController extends AbstractController implements GuardedControllerInterface {

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

        if ($entities instanceof PaginatedCollection) {
            return $this->getPaginatedItemsResponse($entities);
        }

        return $this->getItemsResponse($entities);
    }

    /**
     * Try and create/add a new entity.
     *
     * @return Response
     */
    public function create(): Response {
        try {
            $entity = $this->getEntityInstance()::getCrudService()->create($this->request);
        } catch (InvalidDataException $exception) {
            return $this->getInvalidInputResponse($exception->getErrors());
        }

        return $this->getInsertResponse($entity);
    }

    /**
     * Get a particular entity.
     *
     * @param $id int|string The Id of the entity to get
     * @return Response
     */
    public function read($id): Response {
        $entity = $this->getEntityInstance()::getCrudService()->read($this->request);
        return $this->getItemResponse($entity, $id);
    }

    /**
     * Try to update the entity.
     *
     * @param $id int|string The Id of the entity to update
     * @return Response
     */
    public function update($id): Response {
        try {
            $entity = $this->getEntityInstance()::getCrudService()->update($this->request);
        } catch (InvalidDataException $exception) {
            return $this->getInvalidInputResponse($exception->getErrors());
        }

        return $this->getUpdateResponse($entity, $id);
    }

    /**
     * Try to delete the entity.
     *
     * @param $id int|string The Id of the entity to delete
     * @return Response
     */
    public function delete($id): Response {
        $entity = $this->getEntityInstance()::getCrudService()->delete($this->request);
        return $this->getItemDeletedResponse($entity, $id);
    }
}
