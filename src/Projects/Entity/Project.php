<?php

/**
 * The Project Entity object class (extends the base Entity class, where most of the ORM functionality lies).
 * Within this holds and methods where it overwrites or add extra custom functionality from the base Entity class.
 * Also holds any method only custom to Project entities.
 */

namespace App\Projects\Entity;

use App\APIEntity;
use App\Core;
use App\Entity\Collection;
use App\Entity\FilterableInterface;
use App\Entity\Filterable;
use App\Entity\SearchableInterface;
use App\Entity\Searchable;
use App\Entity\Timestamped;

class Project extends APIEntity implements FilterableInterface, SearchableInterface {

    use Filterable;
    use Searchable;
    use Timestamped;

    public const PUBLIC_STATUS = "published";

    public static $displayName = "Project";

    protected static $tableName = "portfolio_project";

    protected static $defaultColumns = [
        "name" => "",
        "date" => null,
        "url" => "",
        "github_url" => "",
        "download_url" => "",
        "short_description" => "",
        "long_description" => "",
        "colour" => "",
        "skills" => [],
        "status" => "draft",
        "type_id" => null,
    ];

    protected static $requiredColumns = [
        "name",
        "date",
        "type",
        "skills",
        "long_description",
        "short_description",
    ];

    protected static $intColumns = ["type_id"];
    protected static $dateColumns = ["date"];
    protected static $arrayColumns = ["skills"];

    protected static $searchableColumns = [
        "name",
        "skills",
        "long_description",
        "short_description",
        "status",
    ];

    protected static $orderByColumn = "date";
    protected static $orderByASC = false;

    protected static $crudService = CrudService::class;

    /**
     * @var Collection|null
     */
    public $images = null;

    public $type = null;

    /**
     * Helper function to get all Project Image Entities linked to this Project
     */
    public function loadImages(bool $reload = false): void {
        if ($this->isLoaded() && ($reload || is_null($this->images))) {
            $this->images = Image::getByColumn("project_id", $this->getId());
        }
    }

    /**
     * Helper function to get the type entity
     */
    public function loadType(bool $reload = false): void {
        if ($this->isLoaded() && ($reload || is_null($this->type))) {
            $this->type = Type::getById($this->type_id);
        }
    }

    /**
     * @inheritDoc
     */
    public function reload(): void {
        parent::reload();
        if ($this->isLoaded()) {
            if (!is_null($this->type)) {
                $this->loadType(true);
            }
            if (!is_null($this->images)) {
                $this->loadImages(true);
            }
        }
    }

    /**
     * @inheritDoc
     *
     * Add extra functionality as a Project is linked to many Project Images, so delete these also
     *
     * @return bool Whether or not deletion was successful
     */
    public function delete(): bool {
        $isDeleted = parent::delete();

        if ($isDeleted) {
            $this->loadImages();
            foreach ($this->images as $image) {
                $image->delete();
            }
        }

        return $isDeleted;
    }

    public function toArray(): array {
        $array = parent::toArray();

        if ($this->images instanceof Collection) {
            $array["images"] = $this->images->toArray();
        }

        return $array;
    }

    public function getAPIURL(): string {
        return Core::get()->getRouter()->makeUrl("project", ["id" => $this->getId()]);
    }

    public function getAPILinks(): array {
        $links = parent::getAPILinks();
        $links["images"] = Core::get()->getRouter()->makeUrl("projectImages", ["projectId" => $this->getId()]);
        return $links;
    }

    public function getAPIResponse(): array {
        $response = parent::getAPIResponse();

        unset($response["type_id"]);
        $response["type"] = $this->type ? $this->type->name : "";

        if ($this->images instanceof Collection) {
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
