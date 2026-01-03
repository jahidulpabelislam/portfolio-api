<?php

declare(strict_types=1);

namespace App\HTTP;

use App\Core;
use JPI\CRUD\API\AbstractController;
use JPI\CRUD\API\AbstractEntity;
use JPI\HTTP\Request;
use JPI\HTTP\Response;
use JPI\ORM\Entity\Collection as EntityCollection;
use JPI\ORM\Entity\PaginatedCollection as PaginatedEntityCollection;

/**
 * Adds caching to the GET requests.
 */
abstract class AbstractCRUDController extends AbstractController {

    public function getEntitiesResponse(
        Request $request,
        EntityCollection $entities,
        ?AbstractEntity $entityInstance = null
    ): Response {
        return parent::getEntitiesResponse($request, $entities, $entityInstance)
            ->withCacheHeaders(Core::getDefaultCacheHeaders())
        ;
    }

    public function getPaginatedEntitiesResponse(
        Request $request,
        PaginatedEntityCollection $collection,
        ?AbstractEntity $entityInstance = null
    ): Response {
        return parent::getPaginatedEntitiesResponse($request, $collection, $entityInstance)
            ->withCacheHeaders(Core::getDefaultCacheHeaders())
        ;
    }

    public function getEntityResponse(
        Request $request,
        ?AbstractEntity $entity,
        string|int|null $id = null,
        ?AbstractEntity $entityInstance = null
    ): Response {
        return parent::getEntityResponse($request, $entity, $id, $entityInstance)
            ->withCacheHeaders(Core::getDefaultCacheHeaders())
        ;
    }
}
