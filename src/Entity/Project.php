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

use App\Entity;

class Project extends Entity {

    use Searchable;

    private const PUBLIC_STATUS = "published";

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
     * Adds filter by public projects if (admin) user isn't currently logged in
     *
     * @param $where string[]|string|int|null
     * @param $params array|null
     * @param $limit int|string|null
     * @return array
     */
    private static function addStatusWhere($where, ?array $params, $limit = null): array {
        // As the user isn't logged in, filter by status = public
        if (!User::isLoggedIn()) {

            if ($params === null) {
                $params = [];
            }

            if (is_numeric($where)) {
                $params["id"] = (int)$where;
                $where = ["id = :id"];
                $limit = 1;
            }
            else if (is_string($where)) {
                $where = [$where];
            }
            else if (!is_array($where)) {
                $where = [];
            }

            $statusWhere = "status = :status";
            if (!in_array($statusWhere, $where)) {
                $where[] = $statusWhere;
            }

            $params["status"] = self::PUBLIC_STATUS;
        }
        return [$where, $params, $limit];
    }

    public static function getCount($where = null, ?array $params = null): int {
        [$where, $params] = static::addStatusWhere($where, $params);
        return parent::getCount($where, $params);
    }

    public static function get($where = null, ?array $params = null, $limit = null, $page = null) {
        [$where, $params, $limit] = static::addStatusWhere($where, $params, $limit);
        return parent::get($where, $params, $limit, $page);
    }

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

}
