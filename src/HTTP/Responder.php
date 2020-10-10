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
use App\Core;
use App\Entity\Collection as EntityCollection;

trait Responder {

    protected $core;

    public function __construct(Core $core) {
        $this->core = $core;
    }

    public static function newResponse(array $body = null): Response {
        $response = new Response();

        if ($body) {
            $response->setBody($body);
        }

        return $response;
    }

    /**
     * Generate meta data to send back when the method provided is not allowed on the URI
     */
    public function getMethodNotAllowedResponse(): Response {
        return static::newResponse([
            "meta" => [
                "status" => 405,
                "message" => "Method Not Allowed.",
                "feedback" => "Method {$this->core->method} not allowed on " . $this->core->getRequestedURL() . ".",
            ],
        ]);
    }

    /**
     * Send necessary meta data back when user isn't logged in correctly
     */
    public static function getNotAuthorisedResponse(): Response {
        return static::newResponse([
            "meta" => [
                "status" => 401,
                "message" => "Unauthorized",
                "feedback" => "You need to be logged in!",
            ],
        ]);
    }

    public static function getLoggedOutResponse(): Response {
        return static::newResponse([
            "ok" => true,
            "meta" => [
                "feedback" => "Successfully logged out.",
            ],
        ]);
    }

    public static function getUnsuccessfulLogoutResponse(): Response {
        return static::newResponse([
            "meta" => [
                "feedback" => "Couldn't successfully process your logout request!",
            ],
        ]);
    }

    /**
     * Generate response data to send back when the URI provided is not recognised
     */
    public function getUnrecognisedURIResponse(): Response {
        return static::newResponse([
            "meta" => [
                "status" => 404,
                "message" => "Not Found",
                "feedback" => "Unrecognised URI (" . $this->core->getRequestedURL() . ").",
            ],
        ]);
    }

    /**
     * Generate response data to send back when the requested API version is not recognised
     */
    public function getUnrecognisedAPIVersionResponse(): Response {
        $shouldBeVersion = Config::get()->api_version;

        $shouldBeURI = $this->core->uriParts;
        $shouldBeURI[0] = "v" . $shouldBeVersion;
        $shouldBeURL = Core::makeFullURL($shouldBeURI);

        return static::newResponse([
            "meta" => [
                "status" => 404,
                "message" => "Not Found",
                "feedback" => "Unrecognised API version. Current version is {$shouldBeVersion}, so please update requested URL to {$shouldBeURL}.",
            ],
        ]);
    }

    /**
     * Send necessary meta data back when required data/fields is not provided/valid
     */
    public function getInvalidFieldsResponse(string $entityClass, array $data, array $extraRequiredFields = []): Response {
        $requiredFields = $entityClass::getRequiredFields();
        $requiredFields = array_merge($requiredFields, $extraRequiredFields);
        $invalidFields = Core::getInvalidFields($data, $requiredFields);

        return static::newResponse([
            "meta" => [
                "status" => 400,
                "message" => "Bad Request",
                "required_fields" => $requiredFields,
                "invalid_fields" => $invalidFields,
                "feedback" => "The necessary data was not provided, missing/invalid fields: " . implode(", ", $invalidFields) . ".",
            ],
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
    public static function getItemsResponse(string $entityClass, EntityCollection $entities): Response {
        $count = count($entities);
        $data = [];

        foreach ($entities as $entity) {
            $data[] = $entity->getAPIResponse();
        }

        $body = [
            "ok" => true,
            "meta" => [
                "count" => $count,
            ],
            "data" => $data,
        ];

        if (!$count) {
            $body["meta"]["feedback"] = "No {$entityClass::$displayName}s found.";
        }

        return static::newResponse($body);
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
        $response = static::getItemsResponse($entityClass, $collection);

        $body = $response->getBody();

        $totalCount = $collection->getTotalCount();
        $body["meta"]["total_count"] = $totalCount;

        $limit = $collection->getLimit();
        $page = $collection->getPage();

        $lastPage = ceil($totalCount / $limit);
        $body["meta"]["total_pages"] = $lastPage;

        $pageURL = $this->core->getRequestedURL();
        if (isset($params["limit"])) {
            $params["limit"] = $limit;
        }

        $hasPreviousPage = ($page > 1) && ($lastPage >= ($page - 1));
        if ($hasPreviousPage) {
            if ($page > 2) {
                $params["page"] = $page - 1;
            }
            else {
                unset($params["page"]);
            }

            $body["meta"]["previous_page"] = Core::makeUrl($pageURL, $params);
        }

        $hasNextPage = $page < $lastPage;
        if ($hasNextPage) {
            $params["page"] = $page + 1;
            $body["meta"]["next_page"] = Core::makeUrl($pageURL, $params);
        }

        $response->setBody($body);

        return $response;
    }

    private static function getItemFoundResponse(APIEntity $entity): Response {
        return static::newResponse([
            "ok" => true,
            "data" => $entity->getAPIResponse(),
        ]);
    }

    /**
     * @param $entityClass string
     * @param $id int|string|null
     * @return Response
     */
    public static function getItemNotFoundResponse(string $entityClass, $id): Response {
        return static::newResponse([
            "meta" => [
                "status" => 404,
                "message" => "Not Found",
                "feedback" => "No {$entityClass::$displayName} identified by {$id} found.",
            ],
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

            $lastModifiedDate = $entity->getLastModifiedDate();
            if ($lastModifiedDate) {
                $response->addHeader("Last-Modified", $lastModifiedDate);
            }

            return $response;
        }

        return static::getItemNotFoundResponse($entityClass, $id);
    }

    public static function getInsertResponse(string $entityClass, ?APIEntity $entity): Response {
        if ($entity && $entity->isLoaded()) {
            $response = static::getItemFoundResponse($entity);

            $body = $response->getBody();
            $body["meta"]["status"] = 201;
            $body["meta"]["message"] = "Created";
            $response->setBody($body);

            $response->addHeader("Location", $entity->getAPIURL());

            return $response;
        }

        return static::newResponse([
            "meta" => [
                "feedback" => "Failed to insert the new {$entityClass::$displayName}.",
            ],
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

        return static::newResponse([
            "meta" => [
                "feedback" => "Failed to update the {$entityClass::$displayName} identified by {$id}.",
            ],
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
            return static::newResponse([
                "ok" => true,
                "data" => [
                    "id" => (int)$id,
                ],
            ]);
        }

        return static::newResponse([
            "meta" => [
                "feedback" => "Failed to delete the {$entityClass::$displayName} identified by {$id}.",
            ],
        ]);
    }

}
