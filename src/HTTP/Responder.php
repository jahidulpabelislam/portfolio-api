<?php

/**
 * Class for storing any helper functions which constructs responses
 *
 * PHP version 7.1+
 *
 * @author Jahidul Pabel Islam <me@jahidulpabelislam.com>
 * @since Class available since Release: v3.3.0
 * @copyright 2010-2020 JPI
 */

namespace App\HTTP;

use App\APIEntity;
use App\Config;
use App\Core;
use App\Entity\Collection as EntityCollection;

trait Responder {

    protected $core;

    public function __construct(Core $core) {
        $this->core = $core;
    }

    /**
     * Generate meta data to send back when the method provided is not allowed on the URI
     */
    public function getMethodNotAllowedResponse(): Response {
        return new Response(405, [
            "error" => "Method {$this->core->method} not allowed on " . $this->core->getRequestedURL() . ".",
        ]);
    }

    /**
     * Send necessary meta data back when user isn't logged in correctly
     */
    public static function getNotAuthorisedResponse(): Response {
        return new Response(401, [
            "error" => "You need to be logged in!",
        ]);
    }

    public static function getLoggedOutResponse(): Response {
        return new Response(200, [
            "meta" => [
                "message" => "Successfully logged out.",
            ],
        ]);
    }

    public static function getUnsuccessfulLogoutResponse(): Response {
        return new Response(500, [
            "error" => "Couldn't successfully process your logout request!",
        ]);
    }

    /**
     * Generate response data to send back when the URI provided is not recognised
     */
    public function getUnrecognisedURIResponse(): Response {
        return new Response(404, [
            "error" => "Unrecognised URI (" . $this->core->getRequestedURL() . ").",
        ]);
    }

    /**
     * Generate response data to send back when the requested API version is not recognised
     */
    public function getUnrecognisedAPIVersionResponse(): Response {
        $shouldBeVersion = Config::get()->api_version;

        $shouldBeURI = $this->core->uriParts;
        $shouldBeURI[0] = "v{$shouldBeVersion}";
        $shouldBeURL = Core::makeFullURL($shouldBeURI);

        return new Response(404, [
            "error" => "Unrecognised API version. Current version is {$shouldBeVersion}, so please update requested URL to {$shouldBeURL}.",
        ]);
    }

    /**
     * Send necessary meta data back when required data/fields is not provided/valid
     */
    public function getInvalidFieldsResponse(string $entityClass, array $data, array $extraRequiredFields = []): Response {
        $requiredFields = $entityClass::getRequiredFields();
        $requiredFields = array_merge($requiredFields, $extraRequiredFields);
        $invalidFields = Core::getInvalidFields($data, $requiredFields);

        return new Response(400, [
            "meta" => [
                "required_fields" => $requiredFields,
                "invalid_fields" => $invalidFields,
            ],
            "error" => "The necessary data was not provided, missing/invalid fields: " . implode(", ", $invalidFields) . ".",
        ]);
    }

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
            "meta" => [
                "count" => $count,
                "links" => [
                    "self" => $this->core->getRequestedURL(),
                ],
            ],
            "data" => $data,
        ];

        if (!$count) {
            $content["meta"]["message"] = "No {$entityClass::$displayName}s found.";
        }

        $response = new Response(200, $content);
        $response->setCacheHeaders(Core::getDefaultCacheHeaders());
        return $response;
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
        $params = $this->core->params;

        // The items response is the base response, and the extra meta is added below
        $response = $this->getItemsResponse($entityClass, $collection);

        $content = $response->getContent();

        unset($content["meta"]["links"]);

        $totalCount = $collection->getTotalCount();
        $content["meta"]["total_count"] = $totalCount;

        $limit = $collection->getLimit();
        $page = $collection->getPage();

        $lastPage = ceil($totalCount / $limit);
        $content["meta"]["total_pages"] = $lastPage;

        $pageURL = $this->core->getRequestedURL();

        // Always update to the used value if param was passed (incase it was different)
        if (isset($params["limit"])) {
            $params["limit"] = $limit;
        }
        if (isset($params["page"])) {
            $params["page"] = $page;
        }

        $content["meta"]["links"] = [
            "self" => Core::makeUrl($pageURL, $params),
        ];

        $hasPreviousPage = ($page > 1) && ($lastPage >= ($page - 1));
        if ($hasPreviousPage) {
            if ($page > 2) {
                $params["page"] = $page - 1;
            }
            else {
                unset($params["page"]);
            }

            $content["meta"]["links"]["previous_page"] = Core::makeUrl($pageURL, $params);
        }

        $hasNextPage = $page < $lastPage;
        if ($hasNextPage) {
            $params["page"] = $page + 1;
            $content["meta"]["links"]["next_page"] = Core::makeUrl($pageURL, $params);
        }

        $response->setContent($content);

        return $response;
    }

    private static function getItemFoundResponse(APIEntity $entity): Response {
        return new Response(200, [
            "meta" => [
                "links" => $entity->getAPILinks(),
            ],
            "data" => $entity->getAPIResponse(),
        ]);
    }

    /**
     * @param $entityClass string
     * @param $id int|string|null
     * @return Response
     */
    public static function getItemNotFoundResponse(string $entityClass, $id): Response {
        return new Response(404, [
            "error" => "No {$entityClass::$displayName} identified by {$id} found.",
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
            $response->setCacheHeaders(Core::getDefaultCacheHeaders());
            return $response;
        }

        return static::getItemNotFoundResponse($entityClass, $id);
    }

    public static function getInsertResponse(string $entityClass, ?APIEntity $entity): Response {
        if ($entity && $entity->isLoaded()) {
            $response = static::getItemFoundResponse($entity);
            $response->setStatus(201);
            $response->addHeader("Location", $entity->getAPIURL());
            return $response;
        }

        return new Response(500, [
            "error" => "Failed to insert the new {$entityClass::$displayName}.",
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
            "error" => "Failed to update the {$entityClass::$displayName} identified by {$id}.",
        ]);
    }

    /**
     * Return the response when a item was attempted to be deleted
     *
     * @param $entityClass string
     * @param $entity APIEntity|null
     * @param $id int|string|null
     * @param $isDeleted bool
     * @return Response
     */
    public static function getItemDeletedResponse(
        string $entityClass,
        ?APIEntity $entity,
        $id,
        bool $isDeleted = false
    ): Response {
        if (!$id || !$entity || !$entity->isLoaded() || $entity->getId() != $id) {
            return static::getItemNotFoundResponse($entityClass, $id);
        }

        if ($isDeleted) {
            return new Response(200, [
                "data" => [
                    "id" => (int)$id,
                ],
            ]);
        }

        return new Response(500, [
            "error" => "Failed to delete the {$entityClass::$displayName} identified by {$id}.",
        ]);
    }

}
