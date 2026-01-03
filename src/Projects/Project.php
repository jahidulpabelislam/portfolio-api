<?php

declare(strict_types=1);

namespace App\Projects;

use App\AbstractEntity as AbstractAPIEntity;
use App\Core;
use App\Entity\Timestamped;
use JPI\CRUD\API\AbstractEntity;
use JPI\CRUD\API\Entity\Filterable;
use JPI\CRUD\API\Entity\FilterableInterface;
use JPI\CRUD\API\Entity\Searchable;
use JPI\CRUD\API\Entity\SearchableInterface;
use JPI\Utils\URL;

/**
 * The Project Entity object class.
 */
final class Project extends AbstractAPIEntity implements FilterableInterface, SearchableInterface {

    use Filterable;
    use Searchable;
    use Timestamped;

    public const PUBLIC_STATUS = "published";

    protected static string $table = "projects";

    protected static array $dataMapping = [
        "name" => [
            "type" => "string",
        ],
        "date" => [
            "type" => "date",
        ],
        "url" => [
            "type" => "string",
        ],
        "github_url" => [
            "type" => "string",
        ],
        "download_url" => [
            "type" => "string",
        ],
        "short_description" => [
            "type" => "string",
        ],
        "long_description" => [
            "type" => "string",
        ],
        "colour" => [
            "type" => "string",
        ],
        "tags" => [
            "type" => "array",
        ],
        "status" => [
            "type" => "string",
            "default_value" => "draft",
        ],
        "type" => [
            "type" => "belongs_to",
            "entity" => Type::class,
        ],
        "images" => [
            "type" => "has_many",
            "entity" => Image::class,
            "column" => "project",
            "cascade_delete" => true,
        ],
    ];

    protected static array $searchableColumns = [
        "name",
        "tags",
        "long_description",
        "short_description",
        "status",
    ];

    public static string $defaultOrderByColumn = "date";
    public static bool $defaultOrderByASC = false;

    protected static string $crudService = ProjectCrudService::class;

    public function reloadImages(): void {
        unset($this->data["images"]["value"]);
        $this->images;
    }

    public function getAPIURL(): URL {
        return Core::get()->getRouter()->getURLForRoute("project", ["id" => $this->getId()]);
    }

    public function getAPILinks(): array {
        $links = parent::getAPILinks();
        $links["images"] = Core::get()->getRouter()->getURLForRoute("projectImages", ["projectId" => $this->getId()]);
        return $links;
    }

    public function getAPIResponse(int $depth = 1, ?AbstractEntity $parentEntity = null): array {
        $response = parent::getAPIResponse($depth, $parentEntity);

        if (!empty($response["type"])) {
            $response["type"] = $response["type"]["name"];
        }

        return $response;
    }
}
