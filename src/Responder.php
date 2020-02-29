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

if (!defined("ROOT")) {
    die();
}

class Responder {

    private $api;

    private static $instance;

    public function __construct() {
        $this->api = Core::get();
    }

    /**
     * Singleton getter
     */
    public static function get(): Responder {
        if (!self::$instance) {
            self::$instance = new Responder();
        }

        return self::$instance;
    }

    /**
     * Generate meta data to send back when the method provided is not allowed on the URI
     */
    public function getMethodNotAllowedResponse(): array {
        return [
            "meta" => [
                "status" => 405,
                "message" => "Method Not Allowed.",
                "feedback" => "Method {$this->api->method} not allowed on " . $this->api->getAPIURL() . ".",
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
                "status" => 500,
                "message" => "Internal Server Error",
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
                "feedback" => "Unrecognised URI (" . $this->api->getAPIURL() . ").",
            ],
        ];
    }

    /**
     * Generate response data to send back when the requested API version is not recognised
     */
    public function getUnrecognisedAPIVersionResponse(): array {
        $shouldBeVersion = Config::get()->api_version;

        $shouldBeURI = $this->api->uriArray;
        $shouldBeURI[0] = "v" . $shouldBeVersion;
        $shouldBeURL = $this->api->getAPIURL($shouldBeURI);

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
    public function getInvalidFieldsResponse(string $entity, array $extraRequiredFields = []): array {
        $requiredFields = $entity::getRequiredFields();
        $requiredFields = array_merge($requiredFields, $extraRequiredFields);
        $invalidFields = $this->api->getInvalidFields($requiredFields);

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
     */
    public static function getItemsResponse(string $entityClass, ?array $entities = []): array {
        $count = $entities ? count($entities) : 0;
        if ($count) {

            $rows = array_map(static function(Entity $entity) {
                return $entity->toArray();
            }, $entities);

            return [
                "meta" => [
                    "ok" => true,
                    "count" => $count,
                ],
                "rows" => $rows,
            ];
        }

        return [
            "meta" => [
                "count" => 0,
                "status" => 404,
                "message" => "Not Found",
                "feedback" => "No {$entityClass::$displayName}s found.",
            ],
            "rows" => [],
        ];
    }

    /**
     * Return a response when items request was a search request,
     * so check if some found return the items (with necessary meta)
     * else if not found return necessary meta
     *
     * Use getItemsResponse function as the base response, then just adds additional meta data
     */
    public function getItemsSearchResponse(string $entityClass, ?array $entities = [], array $params = []): array {
        // The items response is the base response, and the extra meta is added below
        $response = self::getItemsResponse($entityClass, $entities);

        $resultFromGeneration = $entityClass::generateWhereClausesFromParams($params);
        $totalCount = $entityClass::getCount($resultFromGeneration["where"] ?? null, $resultFromGeneration["params"] ?? null);
        $response["meta"]["total_count"] = $totalCount;

        $limit = $entityClass::getLimit($params["limit"] ?? null);
        $page = $entityClass::getPage($params["page"] ?? null);

        $lastPage = ceil($totalCount / $limit);
        $response["meta"]["total_pages"] = $lastPage;

        $pageURL = $this->api->getAPIURL();
        if (isset($params["limit"])) {
            $params["limit"] = $limit;
        }

        $hasPreviousPage = ($page > 1) && ($lastPage >= ($page - 1));
        $response["meta"]["has_previous_page"] = $hasPreviousPage;
        if ($hasPreviousPage) {
            $params["page"] = $page - 1;
            $response["meta"]["previous_page_url"] = $pageURL;
            $response["meta"]["previous_page_params"] = $params;
        }

        $hasNextPage = $page < $lastPage;
        $response["meta"]["has_next_page"] = $hasNextPage;
        if ($hasNextPage) {
            $params["page"] = $page + 1;
            $response["meta"]["next_page_url"] = $pageURL;
            $response["meta"]["next_page_params"] = $params;
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

    public static function getItemNotFoundResponse(string $entityClass, $id): array {
        return [
            "meta" => [
                "status" => 404,
                "message" => "Not Found",
                "feedback" => "No {$entityClass::$displayName} found with {$id} as ID.",
            ],
            "row" => [],
        ];
    }

    /**
     * Return a response when a item was requested,
     * so check if found return the item (with necessary meta)
     * else if not found return necessary meta
     */
    public static function getItemResponse(string $entityClass, ?Entity $entity, $id): array {
        if ($id && $entity && $entity->id && $entity->id == $id) {
            return self::getItemFoundResponse($entity);
        }

        return self::getItemNotFoundResponse($entityClass, $id);
    }

    public static function getInsertResponse(string $entityClass, ?Entity $entity): array {
        if ($entity && $entity->id) {
            $response = self::getItemFoundResponse($entity);

            $response["meta"]["status"] = 201;
            $response["meta"]["message"] = "Created";

            return $response;
        }

        return [
            "meta" => [
                "status" => 500,
                "message" => "Internal Server Error",
                "feedback" => "Failed to insert the new {$entityClass::$displayName}.",
            ],
            "row" => [],
        ];
    }

    public static function getUpdateResponse(string $entityClass, ?Entity $entity, $id): array {
        if ($id && $entity && $entity->id && $entity->id == $id) {
            return self::getItemFoundResponse($entity);
        }

        return [
            "meta" => [
                "status" => 500,
                "message" => "Internal Server Error",
                "feedback" => "Failed to update the {$entityClass::$displayName} identified by {$id}.",
            ],
            "row" => [],
        ];
    }

    /**
     * Return the response when a item was attempted to be deleted
     */
    public static function getItemDeletedResponse(string $entityClass, ?Entity $entity, $id, bool $isDeleted = false): array {
        if (!$entity) {
            return self::getItemNotFoundResponse($entityClass, $id);
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
                "status" => 500,
                "message" => "Internal Server Error",
                "feedback" => "Couldn't delete {$entityClass::$displayName} with {$id} as ID.",
            ],
            "row" => [],
        ];
    }

}
