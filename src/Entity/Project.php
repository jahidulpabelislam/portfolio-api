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
        "short_description"
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

    private $images = [];

    public function toArray(): array{
        $projectArray = parent::toArray();

        if (isset($projectArray["skills"])) {
            $skills = explode(",", $projectArray["skills"]);
            $skills = array_map("trim", $skills);
            $projectArray["skills"] = $skills;
        }

        $projectArray["images"] = array_map(static function(ProjectImage $image) {
            return $image->toArray();
        }, $this->images);

        return $projectArray;
    }

    /**
     * Helper function to get all Project Image Entities linked to this Project
     */
    public function loadProjectImages() {
        // Get all the images linked to the Project
        $this->images = ProjectImage::getByColumn("project_id", $this->id);
    }

    /**
     * Load a single Entity from the Database where a Id column = a value ($id)
     * Either return Entity with success meta data, or failed meta data
     * Uses helper function getByColumn();
     *
     * As extra functionality on top of default function
     * As Project is linked to Multiple Project Images
     * Add these to the response unless specified
     *
     * @param $id int The Id of the Entity to get
     * @param $includeLinkedData bool Whether to also get and include linked entity/data (images)
     */
    public static function getById($id, bool $includeLinkedData = true): Entity {
        $project = parent::getById($id);

        // If Project was found
        if ($project->id) {

            // If Project isn't public and user isn't logged in, don't return Project
            if ($project->status !== self::PUBLIC_STATUS && !User::isLoggedIn()) {
                $project->id = null;
                return $project;
            }

            // If Project's Images was requested, get and add these
            if ($includeLinkedData) {
                $project->loadProjectImages();
            }
        }

        return $project;
    }

    /**
     * Delete an Entity from the Database
     *
     * Add extra functionality on top of default delete function
     * As these Entities are linked to many Project Images, so delete these also
     *
     * @return bool Whether or not deletion was successful
     */
    public function delete(): bool {
        $isDeleted = parent::delete();

        // Delete all the images linked to this Project from the database & from disk
        if ($isDeleted) {
            foreach ($this->images as $image) {
                $image->delete();
            }
        }

        return $isDeleted;
    }

    /**
     * Gets all Entities but paginated, also might include search
     *
     * Adds extra functionality to:
     * - filter by public projects if (admin) user isn't currently logged in
     * - include any Images linked to all Projects found in search
     *
     * @param $params array Any data to aid in the search query
     * @return array The request response to send back
     */
    public function doSearch(array $params): array {
        // As the user isn't logged in, filter by status = public
        if (!User::isLoggedIn()) {
            $params["status"] = self::PUBLIC_STATUS;
        }

        $projects = parent::doSearch($params);

        // Loop through each Project and get the Projects Images
        $projects = array_map(static function(Project $project) {
            $project->loadProjectImages();

            return $project;
        }, $projects);

        return $projects;
    }
}
