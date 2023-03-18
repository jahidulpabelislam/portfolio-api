<?php

namespace App\HTTP;

use App\Entity\API\AbstractEntity as AbstractAPIEntity;
use App\Entity\API\InvalidDataException;
use App\Entity\API\Responder as EntityResponder;
use JPI\HTTP\Response;
use JPI\ORM\Entity\PaginatedCollection;

abstract class AbstractCrudController extends AbstractController {

    use EntityResponder;

    protected array $publicActions = [];

    protected string $entityClass;

    public function getPublicActions(): array {
        return $this->publicActions;
    }

    public function getEntityInstance(): AbstractAPIEntity {
        return new $this->entityClass();
    }

    /**
     * Gets all entities but paginated (also might include search & filters)
     */
    public function index(): Response {
        $request = $this->getRequest();

        if (
            !in_array("index", $this->getPublicActions())
            && !$request->getAttribute("is_authenticated")
        ) {
            return static::getNotAuthorisedResponse();
        }

        $entities = $this->getEntityInstance()::getCrudService()->index($request);

        if ($entities instanceof PaginatedCollection) {
            return $this->getPaginatedItemsResponse($request, $entities);
        }

        return $this->getItemsResponse($request, $entities);
    }

    public function create(): Response {
        $request = $this->getRequest();

        if (
            !in_array("create", $this->getPublicActions())
            && !$request->getAttribute("is_authenticated")
        ) {
            return static::getNotAuthorisedResponse();
        }

        try {
            $entity = $this->getEntityInstance()::getCrudService()->create($request);
        } catch (InvalidDataException $exception) {
            return $this->getInvalidInputResponse($exception->getErrors());
        }

        return $this->getInsertResponse($request, $entity);
    }

    public function read($id): Response {
        $request = $this->getRequest();

        if (
            !in_array("read", $this->getPublicActions())
            && !$request->getAttribute("is_authenticated")
        ) {
            return static::getNotAuthorisedResponse();
        }

        $entity = $this->getEntityInstance()::getCrudService()->read($request);
        return $this->getItemResponse($request, $entity, $id);
    }

    public function update($id): Response {
        $request = $this->getRequest();

        if (
            !in_array("update", $this->getPublicActions())
            && !$request->getAttribute("is_authenticated")
        ) {
            return static::getNotAuthorisedResponse();
        }

        try {
            $entity = $this->getEntityInstance()::getCrudService()->update($request);
        } catch (InvalidDataException $exception) {
            return $this->getInvalidInputResponse($exception->getErrors());
        }

        return $this->getUpdateResponse($request, $entity, $id);
    }

    public function delete($id): Response {
        $request = $this->getRequest();

        if (
            !in_array("delete", $this->getPublicActions())
            && !$request->getAttribute("is_authenticated")
        ) {
            return static::getNotAuthorisedResponse();
        }

        $entity = $this->getEntityInstance()::getCrudService()->delete($request);
        return $this->getItemDeletedResponse($request, $entity, $id);
    }
}
