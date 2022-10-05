<?php

namespace App\Entity;

use App\APIEntity;
use App\Core;
use App\HTTP\Response;
use JPI\ORM\Entity\Collection as EntityCollection;

trait Responder {

    /**
     * Return a response when items were requested,
     * so check if some found return the items (with necessary meta)
     * else if not found return necessary meta
     *
     * @param $entityClass string
     * @param $entities EntityCollection
     * @return Response
     */
    public function getItemsResponse(string $entityClass, EntityCollection $entities): Response {
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
                "self" => (string)$this->request->getURL(),
            ],
        ];

        if (!$count) {
            $content["message"] = "No {$entityClass::getPluralDisplayName()} found.";
        }

        return (new Response(200, $content))
            ->withCacheHeaders(Core::getDefaultCacheHeaders())
        ;
    }

    /**
     * Return a response when items request was a search request,
     * so check if some found return the items (with necessary meta)
     * else if not found return necessary meta
     *
     * Use getItemsResponse function as the base response, then just adds additional meta data
     *
     * @param $entityClass string
     * @param $collection EntityCollection
     * @return Response
     */
    public function getPaginatedItemsResponse(string $entityClass, EntityCollection $collection): Response {
        $params = (clone $this->request->params)->toArray();

        // The items response is the base response, and the extra meta is added below
        $response = $this->getItemsResponse($entityClass, $collection);

        $content = $response->getContent();

        unset($content["_links"]);

        $totalCount = $collection->getTotalCount();
        $content["_total_count"] = $totalCount;

        $limit = $collection->getLimit();
        $page = $collection->getPage();

        $lastPage = ceil($totalCount / $limit);
        $content["_total_pages"] = $lastPage;

        $url = $this->request->getURL();

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

        return $response->withContent($content)
            ->withCacheHeaders(Core::getDefaultCacheHeaders())
        ;
    }

    private static function getItemFoundResponse(APIEntity $entity): Response {
        return new Response(200, [
            "data" => $entity->getAPIResponse(),
            "_links" => $entity->getAPILinks(),
        ]);
    }

    /**
     * @param $entityClass string
     * @param $id int|string|null
     * @return Response
     */
    public static function getItemNotFoundResponse(string $entityClass, $id): Response {
        return new Response(404, [
            "message" => "No {$entityClass::getDisplayName()} identified by '$id' found.",
        ]);
    }

    /**
     * Return a response when a item was requested,
     * so check if found return the item (with necessary meta)
     * else if not found return necessary meta
     *
     * @param $entityClass string
     * @param $entity APIEntity|null
     * @param $id int|string|null
     * @return Response
     */
    public static function getItemResponse(string $entityClass, ?APIEntity $entity, $id): Response {
        if ($id && $entity && $entity->isLoaded() && $entity->getId() == $id) {
            $response = static::getItemFoundResponse($entity);
        }
        else {
            $response = static::getItemNotFoundResponse($entityClass, $id);
        }

        return $response->withCacheHeaders(Core::getDefaultCacheHeaders());
    }

    public static function getInsertResponse(string $entityClass, ?APIEntity $entity): Response {
        if ($entity && $entity->isLoaded()) {
            return static::getItemFoundResponse($entity)
                ->withStatus(201)
                ->withHeader("Location", (string)$entity->getAPIURL())
            ;
        }

        return new Response(500, [
            "message" => "Failed to insert the new {$entityClass::getDisplayName()}.",
        ]);
    }

    /**
     * @param $entityClass string
     * @param $entity APIEntity|null
     * @param $id int|string|null
     * @return Response
     */
    public static function getUpdateResponse(string $entityClass, ?APIEntity $entity, $id): Response {
        if ($id && $entity && $entity->isLoaded() && $entity->getId() == $id) {
            return static::getItemFoundResponse($entity);
        }

        return new Response(500, [
            "message" => "Failed to update the {$entityClass::getDisplayName()} identified by '$id'.",
        ]);
    }

    /**
     * Return the response when a item was attempted to be deleted
     *
     * @param $entityClass string
     * @param $entity APIEntity|null
     * @param $id int|string|null
     * @return Response
     */
    public static function getItemDeletedResponse(string $entityClass, ?APIEntity $entity, $id): Response {
        if (!$id || !$entity || !$entity->isLoaded() || $entity->getId() != $id) {
            return static::getItemNotFoundResponse($entityClass, $id);
        }

        if ($entity->isDeleted()) {
            return new Response(204);
        }

        return new Response(500, [
            "message" => "Failed to delete the {$entityClass::getDisplayName()} identified by '$id'.",
        ]);
    }
}
