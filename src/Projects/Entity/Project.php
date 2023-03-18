<?php

/**
 * The Project Entity object class (extends the base Entity class, where most of the ORM functionality lies).
 * Within this holds and methods where it overwrites or add extra custom functionality from the base Entity class.
 * Also holds any method only custom to Project entities.
 */

namespace App\Projects\Entity;

use App\Core;
use App\Entity\API\AbstractEntity as AbstractAPIEntity;
use App\Entity\Filterable;
use App\Entity\FilterableInterface;
use App\Entity\Searchable;
use App\Entity\SearchableInterface;
use App\Entity\Timestamped;
use JPI\ORM\Entity\Collection as EntityCollection;
use JPI\Utils\URL;

class Project extends AbstractAPIEntity implements FilterableInterface, SearchableInterface {

    use Filterable;
    use Searchable;
    use Timestamped;

    public const PUBLIC_STATUS = "published";

    protected static string $table = "projects";

    protected static array $defaultColumns = [
        "name" => "",
        "date" => null,
        "type" => "",
        "url" => "",
        "github_url" => "",
        "download_url" => "",
        "short_description" => "",
        "long_description" => "",
        "colour" => "",
        "tags" => [],
        "status" => "draft",
    ];

    protected static array $dateColumns = ["date"];
    protected static array $arrayColumns = ["tags"];

    protected static array $searchableColumns = [
        "name",
        "type",
        "tags",
        "long_description",
        "short_description",
        "status",
    ];

    public static string $defaultOrderByColumn = "date";
    public static bool $defaultOrderByASC = false;

    protected static string $crudService = ProjectCrudService::class;

    public ?EntityCollection $images = null;

    /**
     * Helper function to get all Project Image Entities linked to this Project
     */
    public function loadImages(bool $reload = false): void {
        if ($this->isLoaded() && !$this->isDeleted() && ($reload || is_null($this->images))) {
            $this->images = Image::newQuery()->where("project_id", "=", $this->getId())->select();
        }
    }

    public function reload(): void {
        parent::reload();

        if (!is_null($this->images)) {
            $this->loadImages(true);
        }
    }

    /**
     * Add extra functionality as a Project is linked to many Project Images, so delete these also
     */
    public function delete(): bool {
        $this->loadImages(); // Make sure images are loaded first so we can delete later

        $isDeleted = parent::delete();

        if ($isDeleted) {
            foreach ($this->images as $image) {
                $image->delete();
            }
        }

        return $isDeleted;
    }

    public function toArray(): array {
        $array = parent::toArray();

        if ($this->images instanceof EntityCollection) {
            $array["images"] = [];
            foreach ($this->images as $image) {
                $array["images"][] = $image->toArray();
            }
        }

        return $array;
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

        if ($this->images instanceof EntityCollection) {
            $response["images"] = [];
            foreach ($this->images as $image) {
                $imageResponse = $image->getAPIResponse();
                $imageResponse["_links"] = $image->getAPILinks();
                $response["images"][] = $imageResponse;
            }
        }

        return $response;
    }
}
