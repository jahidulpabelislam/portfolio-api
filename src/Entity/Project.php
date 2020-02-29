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

if (!defined("ROOT")) {
    die();
}

use App\Entity;

class Project extends Entity {

    private const PUBLIC_STATUS = "published";

    public static $displayName = "Project";

    protected static $tableName = "portfolio_project";

    protected $columns = [
        "name" => "",
        "date" => "",
        "type" => "",
        "link" => "",
        "github" => "",
        "download" => "",
        "short_description" => "",
        "long_description" => "",
        "colour" => "",
        "skills" => "",
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

    public $images = null;

    public function toArray(): array {
        $projectArray = parent::toArray();

        if (isset($projectArray["skills"])) {
            $skills = explode(",", $projectArray["skills"]);
            array_walk($skills, "trim");
            $projectArray["skills"] = $skills;
        }

        if ($this->images !== null) {
            $projectArray["images"] = array_map(static function(ProjectImage $image) {
                return $image->toArray();
            }, $this->images);
        }

        return $projectArray;
    }

    /**
     * Helper function to get all Project Image Entities linked to this Project
     */
    public function loadProjectImages() {
        if ($this->id) {
            $this->images = ProjectImage::getByColumn("project_id", $this->id);
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

    /**
     * Adds filter by public projects if (admin) user isn't currently logged in
     */
    private static function addStatusWhere($where, ?array $params, $limit = null): array {
        // As the user isn't logged in, filter by status = public
        if (!User::isLoggedIn()) {

            if ($params === null) {
                $params = [];
            }

            if (is_numeric($where)) {
                $params["id"] = $where;
                $where = ["id = :id"];
                $limit = 1;
            }
            else if (is_string($where)) {
                $where = [$where];
            }
            else if (!is_array($where)) {
                $where = [];
            }

            $where[] = "status = :status";
            $params["status"] = self::PUBLIC_STATUS;
        }
        return [$where, $params, $limit];
    }

    public static function get($where = null, ?array $params = null, $limit = null, $page = null) {
        [$where, $params, $limit] = static::addStatusWhere($where, $params, $limit);
        return parent::get($where, $params, $limit, $page);
    }

    public static function getCount($where = null, ?array $params = null): int {
        [$where, $params] = static::addStatusWhere($where, $params);
        return parent::getCount($where, $params);
    }
}
