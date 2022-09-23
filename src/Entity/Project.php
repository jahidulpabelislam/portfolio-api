<?php

/**
 * The Project Entity object class (extends the base Entity class, where most of the ORM functionality lies).
 * Within this holds and methods where it overwrites or add extra custom functionality from the base Entity class.
 * Also holds any method only custom to Project entities.
 */

namespace App\Entity;

use App\APIEntity;
use App\Core;
use JPI\ORM\Entity\Collection as EntityCollection;

class Project extends APIEntity {

    use Filterable;
    use Searchable;
    use Timestamped;

    public const PUBLIC_STATUS = "published";

    public static $displayName = "Project";

    protected static $table = "portfolio_project";

    protected static $defaultColumns = [
        "name" => "",
        "date" => null,
        "type" => "",
        "url" => "",
        "github_url" => "",
        "download_url" => "",
        "short_description" => "",
        "long_description" => "",
        "colour" => "",
        "skills" => [],
        "status" => "draft",
    ];

    protected static $requiredColumns = [
        "name",
        "date",
        "type",
        "skills",
        "long_description",
        "short_description",
    ];

    protected static $dateColumns = ["date"];

    protected static $arrayColumns = ["skills"];

    protected static $searchableColumns = [
        "name",
        "type",
        "skills",
        "long_description",
        "short_description",
        "status",
    ];

    protected static $orderByColumn = "date";
    protected static $orderByASC = false;

    /**
     * @var EntityCollection|null
     */
    public $images = null;

    /**
     * Helper function to get all Project Image Entities linked to this Project
     */
    public function loadProjectImages(bool $reload = false): void {
        if ($this->isLoaded() && !$this->isDeleted() && ($reload || is_null($this->images))) {
            $this->images = ProjectImage::getByColumn("project_id", $this->getId());
        }
    }

    /**
     * @inheritDoc
     */
    public function reload(): void {
        parent::reload();

        if (!is_null($this->images)) {
            $this->loadProjectImages(true);
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
        $this->loadProjectImages(); // Make sure images are loaded first so we can delete later

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
