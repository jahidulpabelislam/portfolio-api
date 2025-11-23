<?php

declare(strict_types=1);

namespace App\HTTP;

use JPI\CRUD\API\AbstractEntity;
use App\Core;
use JPI\CRUD\API\AbstractController;
use JPI\HTTP\Request;
use JPI\HTTP\Response;
use JPI\ORM\Entity\Collection as EntityCollection;
use JPI\ORM\Entity\PaginatedCollection as PaginatedEntityCollection;

/**
 * Adds caching to the GET requests.
 */
abstract class AbstractCRUDController extends AbstractController {

    public function getItemsResponse(
        Request $request,
        EntityCollection $entities,
        ?AbstractEntity $entityInstance = null
    ): Response {
        return parent::getItemsResponse($request, $entities, $entityInstance)
            ->withCacheHeaders(Core::getDefaultCacheHeaders())
        ;
    }

    public function getPaginatedItemsResponse(
        Request $request,
        PaginatedEntityCollection $collection,
        ?AbstractEntity $entityInstance = null
    ): Response {
        return parent::getPaginatedItemsResponse($request, $collection, $entityInstance)
            ->withCacheHeaders(Core::getDefaultCacheHeaders())
        ;
    }

    public function getItemResponse(
        Request $request,
        ?AbstractEntity $entity,
        string|int|null $id = null,
        ?AbstractEntity $entityInstance = null
    ): Response {
        return parent::getItemResponse($request, $entity, $id, $entityInstance)
            ->withCacheHeaders(Core::getDefaultCacheHeaders())
        ;
    }
}
