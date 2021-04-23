<?php

/**
 * The Project Entity object class (extends the base Entity class, where most of the ORM functionality lies).
 * Within this holds and methods where it overwrites or add extra custom functionality from the base Entity class.
 * Also holds any method only custom to Project entities.
 *
 * PHP version 7.1+
 *
 * @author Jahidul Pabel Islam <me@jahidulpabelislam.com>
 * @since Class available since Release: v3.0.0
 * @copyright 2010-2020 JPI
 */

namespace App\Entity;

use App\APIEntity;
use App\Core;
use DateTime;

class Project extends APIEntity {

    use Filterable;
    use Searchable;
    use Timestamped;

    public const PUBLIC_STATUS = "published";

    public static $displayName = "Project";

    protected static $tableName = "portfolio_project";

    protected static $defaultColumns = [
        "name" => "",
        "date" => null,
        "type" => "",
        "link" => "",
        "github" => "",
        "download" => "",
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
     * @var Collection|null
     */
    public $images = null;

    /**
     * Helper function to get all Project Image Entities linked to this Project
     */
    public function loadProjectImages(bool $reload = false) {
        if ($this->isLoaded() && ($reload || $this->images === null)) {
            $this->images = ProjectImage::getByColumn("project_id", $this->getId());
        }
    }

    /**
     * @inheritDoc
     */
    public function reload() {
        parent::reload();
        if ($this->isLoaded() && $this->images !== null) {
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
        $isDeleted = parent::delete();

        if ($isDeleted) {
            $this->loadProjectImages();
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
        return Core::get()->getRouter()->makeUrl("project", ["id" => $this->id]);
    }

    public function getAPILinks(): array {
        $links = parent::getAPILinks();
        $links["images"] = Core::get()->getRouter()->makeUrl("projectImages", ["projectId" => $this->getId()]);
        return $links;
    }

    public function getAPIResponse(): array {
        $response = parent::getAPIResponse();

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
