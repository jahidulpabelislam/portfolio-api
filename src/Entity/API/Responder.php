<?php

declare(strict_types=1);

namespace App\Entity\API;

use App\Core;
use JPI\HTTP\Request;
use JPI\HTTP\Response;
use JPI\ORM\Entity\Collection as EntityCollection;
use JPI\ORM\Entity\PaginatedCollection as PaginatedEntityCollection;

trait Responder {

    abstract public function getEntityInstance(): AbstractEntity;

    /**
     * Return a response when items were requested,
     * so check if some found return the items (with necessary meta)
     * else if not found return necessary meta
     */
    public function getItemsResponse(
        Request $request,
        EntityCollection $entities,
        AbstractEntity $entityInstance = null
    ): Response {
        $entityInstance = $entityInstance ?? $this->getEntityInstance();

        $count = count($entities);
        $data = [];

        foreach ($entities as $entity) {
            $response = $entity->getAPIResponse();
            $response["_links"] = $entity->getAPILinks();
            $data[] = $response;
        }

        $content = [
            "data" => $data,
            "_links" => [
                "self" => (string)$request->getURL(),
            ],
        ];

        if (!$count) {
            $content["message"] = "No {$entityInstance::getPluralDisplayName()} found.";
        }

        return Response::json(200, $content)
            ->withCacheHeaders(Core::getDefaultCacheHeaders())
        ;
    }

    /**
     * Return a response when items request was a search request,
     * so check if some found return the items (with necessary meta)
     * else if not found return necessary meta
     *
     * Use getItemsResponse function as the base response, then just adds additional meta data
     */
    public function getPaginatedItemsResponse(
        Request $request,
        PaginatedEntityCollection $collection,
        AbstractEntity $entityInstance = null
    ): Response {
        $params = $request->getQueryParams()->toArray();

        // The items response is the base response, and the extra meta is added below
        $response = $this->getItemsResponse($request, $collection, $entityInstance);

        $content = $response->getBody();

        $content = json_decode($content, true);

        unset($content["_links"]);

        $totalCount = $collection->getTotalCount();
        $content["_total_count"] = $totalCount;

        $limit = $collection->getLimit();
        $page = $collection->getPage();

        $lastPage = ceil($totalCount / $limit);
        $content["_total_pages"] = $lastPage;

        $url = $request->getURL();

        // Always update to the used value if param was passed (incase it was different)
        if (isset($params["limit"])) {
            $params["limit"] = $limit;
        }
        if (isset($params["page"])) {
            $params["page"] = $page;
        }

        $url->setParams($params);

        $content["_links"] = [
            "self" => (string)$url,
        ];

        $hasPreviousPage = ($page > 1) && ($lastPage >= ($page - 1));
        if ($hasPreviousPage) {
            if ($page > 2) {
                $url->setParam("page", $page - 1);
            }
            else {
                $url->removeParam("page");
            }

            $content["_links"]["previous_page"] = (string)$url;
        }

        $hasNextPage = $page < $lastPage;
        if ($hasNextPage) {
            $url->setParam("page", $page + 1);
            $content["_links"]["next_page"] = (string)$url;
        }

        return $response->withJSON($content)
            ->withCacheHeaders(Core::getDefaultCacheHeaders())
        ;
    }

    private function getItemFoundResponse(Request $request, AbstractEntity $entity): Response {
        return Response::json(200, [
            "data" => $entity->getAPIResponse(),
            "_links" => $entity->getAPILinks(),
        ]);
    }

    public function getItemNotFoundResponse(
        Request $request,
        string|int $id = null,
        AbstractEntity $entityInstance = null
    ): Response {
        $entityInstance = $entityInstance ?? $this->getEntityInstance();

        $id = $id ?? $request->getAttribute("route_params")["id"];

        return Response::json(404, [
            "message" => "No {$entityInstance::getDisplayName()} identified by '$id' found.",
        ]);
    }

    /**
     * Return a response when a item was requested,
     * so check if found return the item (with necessary meta)
     * else if not found return necessary meta
     */
    public function getItemResponse(
        Request $request,
        ?AbstractEntity $entity,
        string|int $id = null,
        AbstractEntity $entityInstance = null
    ): Response {
        $entityInstance = $entityInstance ?? $this->getEntityInstance();

        $id = $id ?? $request->getAttribute("route_params")["id"];

        if ($id && $entity && $entity->isLoaded() && $entity->getId() == $id) {
            $response = $this->getItemFoundResponse($request, $entity);
        }
        else {
            $response = $this->getItemNotFoundResponse($request, $id, $entityInstance);
        }

        return $response->withCacheHeaders(Core::getDefaultCacheHeaders());
    }

    public function getInsertResponse(
        Request $request,
        ?AbstractEntity $entity,
        AbstractEntity $entityInstance = null
    ): Response {
        $entityInstance = $entityInstance ?? $this->getEntityInstance();

        if ($entity && $entity->isLoaded()) {
            return $this->getItemFoundResponse($request, $entity)
                ->withStatus(201)
                ->withHeader("Location", (string)$entity->getAPIURL())
            ;
        }

        return Response::json(500, [
            "message" => "Failed to insert the new {$entityInstance::getDisplayName()}.",
        ]);
    }

    public function getUpdateResponse(
        Request $request,
        ?AbstractEntity $entity,
        string|int $id = null,
        AbstractEntity $entityInstance = null
    ): Response {
        $entityInstance = $entityInstance ?? $this->getEntityInstance();

        $id = $id ?? $request->getAttribute("route_params")["id"];

        if ($id && $entity && $entity->isLoaded() && $entity->getId() == $id) {
            return $this->getItemFoundResponse($request, $entity);
        }

        return Response::json(500, [
            "message" => "Failed to update the {$entityInstance::getDisplayName()} identified by '$id'.",
        ]);
    }

    /**
     * Return the response when a item was attempted to be deleted
     */
    public function getItemDeletedResponse(
        Request $request,
        ?AbstractEntity $entity,
        string|int $id = null,
        AbstractEntity $entityInstance = null
    ): Response {
        $entityInstance = $entityInstance ?? $this->getEntityInstance();

        $id = $id ?? $request->getAttribute("route_params")["id"];

        if (!$id || !$entity || !$entity->isLoaded() || $entity->getId() != $id) {
            return $this->getItemNotFoundResponse($request, $id, $entityInstance);
        }

        if ($entity->isDeleted()) {
            return Response::json(204);
        }

        return Response::json(500, [
            "message" => "Failed to delete the {$entityInstance::getDisplayName()} identified by '$id'.",
        ]);
    }
}
