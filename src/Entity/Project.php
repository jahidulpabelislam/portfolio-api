<?php
/**
 * The Project Entity object class (extends the base Entity class, where most of the ORM functionality lies).
 * Within this holds and methods where it overwrites or add extra custom functionality from the base Entity class.
 * Also holds any method only custom to Project entities.
 *
 * PHP version 7.1+
 *
 * @version 3.0.0
 * @since Class available since Release: v3.0.0
 * @author Jahidul Pabel Islam <me@jahidulpabelislam.com>
 * @copyright 2010-2019 JPI
 */

namespace App\Entity;

if (!defined("ROOT")) {
    die();
}

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

    private $images = null;

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

        // Delete all the images linked to this Project from the database & from disk
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
    private static function addStatusWhere($where, ?array $bindings, $limit = null): array {
        // As the user isn't logged in, filter by status = public
        if (!User::isLoggedIn()) {
            if (is_numeric($where)) {
                $where = [
                    "id = :id",
                ];
                $limit = 1;
            }
            elseif (is_string($where)) {
                $where = [$where];
            }
            else {
                $where = [];
            }

            if ($bindings === null) {
                $bindings = [];
            }

            $where[] = "status = :status";
            $bindings[":status"] = self::PUBLIC_STATUS;
        }
        return [$where, $bindings, $limit];
    }

    public static function get($select = "*", $where = null, ?array $bindings = null, $limit = null, $page = null) {
        [$where, $bindings, $limit] = static::addStatusWhere($where, $bindings, $limit);
        return parent::get($select, $where, $bindings, $limit, $page);
    }

    public static function getCount($where = null, ?array $bindings = null): int {
        [$where, $bindings] = static::addStatusWhere($where, $bindings);
        return parent::getCount($where, $bindings);
    }
}
