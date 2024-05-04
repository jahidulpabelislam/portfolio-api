<?php

declare(strict_types=1);

/**
 * The Project Entity object class (extends the base Entity class, where most of the ORM functionality lies).
 * Within this holds and methods where it overwrites or add extra custom functionality from the base Entity class.
 * Also holds any method only custom to Project entities.
 */

namespace App\Projects;

use App\AbstractEntity as AbstractAPIEntity;
use App\Core;
use App\Entity\Timestamped;
use JPI\CRUD\API\Entity\Filterable;
use JPI\CRUD\API\Entity\FilterableInterface;
use JPI\CRUD\API\Entity\Searchable;
use JPI\CRUD\API\Entity\SearchableInterface;
use JPI\Utils\URL;

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
            "column" => "project_id",
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

    public function reload(): void {
        parent::reload();

        if (!is_null($this->data["type"])) {
            unset($this->data["type"]["value"]);
            $this->type;
        }
        if (!is_null($this->data["images"])) {
            $this->reloadImages();
        }
    }

    /**
     * Add extra functionality as a Project is linked to many Project Images, so delete these also
     */
    public function delete(): bool {
        $isDeleted = parent::delete();

        if ($isDeleted) {
            foreach ($this->images as $image) {
                $image->delete();
            }
        }

        return $isDeleted;
    }

    public function getAPIURL(): URL {
        return Core::get()->getRouter()->getURLForRoute("project", ["id" => $this->getId()]);
    }

    public function getAPILinks(): array {
        $links = parent::getAPILinks();
        $links["images"] = (string)Core::get()->getRouter()->getURLForRoute("projectImages", ["projectId" => $this->getId()]);
        return $links;
    }

    public function getAPIResponse(): array {
        $response = parent::getAPIResponse();

        if (!empty($response["type"])) {
            $response["type"] = $response["type"]["name"];
        }

        return $response;
    }
}
