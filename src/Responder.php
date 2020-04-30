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

namespace App;

use App\Entity\Collection as EntityCollection;

trait Responder {

    protected $core;

    public function __construct(Core $core) {
        $this->core = $core;
    }

    /**
     * Generate meta data to send back when the method provided is not allowed on the URI
     */
    public function getMethodNotAllowedResponse(): array {
        return [
            "meta" => [
                "status" => 405,
                "message" => "Method Not Allowed.",
                "feedback" => "Method {$this->core->method} not allowed on " . $this->core->getAPIURL() . ".",
            ],
        ];
    }

    /**
     * Send necessary meta data back when user isn't logged in correctly
     */
    public static function getNotAuthorisedResponse(): array {
        return [
            "meta" => [
                "status" => 401,
                "message" => "Unauthorized",
                "feedback" => "You need to be logged in!",
            ],
        ];
    }

    public static function getLoggedOutResponse(): array {
        return [
            "meta" => [
                "ok" => true,
                "feedback" => "Successfully logged out.",
            ],
        ];
    }

    public static function getUnsuccessfulLogoutResponse(): array {
        return [
            "meta" => [
                "feedback" => "Couldn't successfully process your logout request!",
            ],
        ];
    }

    /**
     * Generate response data to send back when the URI provided is not recognised
     */
    public function getUnrecognisedURIResponse(): array {
        return [
            "meta" => [
                "status" => 404,
                "message" => "Not Found",
                "feedback" => "Unrecognised URI (" . $this->core->getAPIURL() . ").",
            ],
        ];
    }

    /**
     * Generate response data to send back when the requested API version is not recognised
     */
    public function getUnrecognisedAPIVersionResponse(): array {
        $shouldBeVersion = Config::get()->api_version;

        $shouldBeURI = $this->core->uriParts;
        $shouldBeURI[0] = "v" . $shouldBeVersion;
        $shouldBeURL = $this->core->getAPIURL($shouldBeURI);

        return [
            "meta" => [
                "status" => 404,
                "message" => "Not Found",
                "feedback" => "Unrecognised API version. Current version is {$shouldBeVersion}, so please update requested URL to {$shouldBeURL}.",
            ],
        ];
    }

    /**
     * Send necessary meta data back when required data/fields is not provided/valid
     */
    public function getInvalidFieldsResponse(string $entityClass, array $data, array $extraRequiredFields = []): array {
        $requiredFields = $entityClass::getRequiredFields();
        $requiredFields = array_merge($requiredFields, $extraRequiredFields);
        $invalidFields = Core::getInvalidFields($data, $requiredFields);

        return [
            "meta" => [
                "status" => 400,
                "message" => "Bad Request",
                "required_fields" => $requiredFields,
                "invalid_fields" => $invalidFields,
                "feedback" => "The necessary data was not provided, missing/invalid fields: " . implode(", ", $invalidFields) . ".",
            ],
        ];
    }

    /**
     * Return a response when items were requested,
     * so check if some found return the items (with necessary meta)
     * else if not found return necessary meta
     *
     * @param $entityClass string
     * @param $entities EntityCollection
     * @return array
     */
    public static function getItemsResponse(string $entityClass, EntityCollection $entities): array {
        $count = count($entities);

        $response = [
            "meta" => [
                "ok" => true,
                "count" => $count,
            ],
            "rows" => $entities->toArray()
        ];

        if (!$count) {
            $response["meta"]["feedback"] = "No {$entityClass::$displayName}s found.";
        }

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
     * @return array
     */
    public function getPaginatedItemsResponse(string $entityClass, EntityCollection $collection): array {
        $params = $this->core->params;

        // The items response is the base response, and the extra meta is added below
        $response = static::getItemsResponse($entityClass, $collection);

        $totalCount = $collection->getTotalCount();
        $response["meta"]["total_count"] = $totalCount;

        $limit = $collection->getLimit();
        $page = $collection->getPage();

        $lastPage = ceil($totalCount / $limit);
        $response["meta"]["total_pages"] = $lastPage;

        $pageURL = $this->core->getAPIURL();
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

            $response["meta"]["previous_page"] = Core::makeUrl($pageURL, $params);
        }

        $hasNextPage = $page < $lastPage;
        if ($hasNextPage) {
            $params["page"] = $page + 1;
            $response["meta"]["next_page"] = Core::makeUrl($pageURL, $params);
        }

        return $response;
    }

    private static function getItemFoundResponse(Entity $entity): array {
        return [
            "meta" => [
                "ok" => true,
            ],
            "row" => $entity->toArray(),
        ];
    }

    /**
     * @param $entityClass string
     * @param $id int|string|null
     * @return array
     */
    public static function getItemNotFoundResponse(string $entityClass, $id): array {
        return [
            "meta" => [
                "status" => 404,
                "message" => "Not Found",
                "feedback" => "No {$entityClass::$displayName} identified by {$id} found.",
            ],
        ];
    }

    /**
     * Return a response when a item was requested,
     * so check if found return the item (with necessary meta)
     * else if not found return necessary meta
     *
     * @param $entityClass string
     * @param $entity Entity|null
     * @param $id int|string|null
     * @return array
     */
    public static function getItemResponse(string $entityClass, ?Entity $entity, $id): array {
        if ($id && $entity && $entity->isLoaded() && $entity->getId() == $id) {
            return static::getItemFoundResponse($entity);
        }

        return static::getItemNotFoundResponse($entityClass, $id);
    }

    public static function getInsertResponse(string $entityClass, ?Entity $entity): array {
        if ($entity && $entity->isLoaded()) {
            $response = static::getItemFoundResponse($entity);

            $response["meta"]["status"] = 201;
            $response["meta"]["message"] = "Created";

            return $response;
        }

        return [
            "meta" => [
                "feedback" => "Failed to insert the new {$entityClass::$displayName}.",
            ],
        ];
    }

    /**
     * @param $entityClass string
     * @param $entity Entity|null
     * @param $id int|string|null
     * @return array
     */
    public static function getUpdateResponse(string $entityClass, ?Entity $entity, $id): array {
        if ($id && $entity && $entity->isLoaded() && $entity->getId() == $id) {
            return static::getItemFoundResponse($entity);
        }

        return [
            "meta" => [
                "feedback" => "Failed to update the {$entityClass::$displayName} identified by {$id}.",
            ],
        ];
    }

    /**
     * Return the response when a item was attempted to be deleted
     *
     * @param $entityClass string
     * @param $entity Entity|null
     * @param $id int|string|null
     * @param $isDeleted bool
     * @return array
     */
    public static function getItemDeletedResponse(string $entityClass, ?Entity $entity, $id, bool $isDeleted = false): array {
        if (!$id || !$entity || !$entity->isLoaded() || $entity->getId() != $id) {
            return static::getItemNotFoundResponse($entityClass, $id);
        }

        if ($isDeleted) {
            return [
                "meta" => [
                    "ok" => true,
                ],
                "row" => [
                    "id" => (int)$id,
                ],
            ];
        }

        return [
            "meta" => [
                "feedback" => "Failed to delete the {$entityClass::$displayName} identified by {$id}.",
            ],
        ];
    }

}
