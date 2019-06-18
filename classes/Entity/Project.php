<?php
/**
 * The Project Entity object class (extends the base Entity class, where most of the ORM functionality lies).
 * Within this holds and methods where it overwrites or add extra custom functionality from the base Entity class.
 * Also holds any method only custom to Project entities.
 *
 * PHP version 7.1+
 *
 * @version 1.2.1
 * @since Class available since Release: v3.0.0
 * @author Jahidul Pabel Islam <me@jahidulpabelislam.com>
 * @copyright 2010-2019 JPI
 */

namespace JPI\API\Entity;

use JPI\API\Auth;

if (!defined("ROOT")) {
    die();
}

class Project extends Entity {

    private const PUBLIC_STATUS = "published";

    public static $displayName = "Project";

    protected static $tableName = "portfolio_project";

    protected $columns = [
        "id" => null,
        "name" => "",
        "date" => "",
        "link" => "",
        "github" => "",
        "download" => "",
        "short_description" => "",
        "long_description" => "",
        "colour" => "",
        "skills" => "",
        "status" => "draft",
        "created_at" => "",
        "updated_at" => "",
    ];

    protected static $searchableColumns = [
        "name",
        "skills",
        "long_description",
        "short_description",
        "status",
    ];

    protected static $orderByColumn = "date";

    private $images = [];

    public function toArray(): array{
        $projectArray = parent::toArray();

        if (isset($projectArray["skills"])) {
            $skills = explode(",", $projectArray["skills"]);
            $skills = array_map("trim", $skills);
            $projectArray["skills"] = $skills;
        }

        if (count($this->images)) {
            $projectArray["images"] = array_map(function(ProjectImage $image) {
                return $image->toArray();
            }, $this->images);
        }

        return $projectArray;
    }

    /**
     * Helper function to get all Project Image Entities linked to this Project
     * @return array An array of ProjectImage's (if any found)
     */
    public function getProjectImages(): array {
        // Get all the images linked to the Project
        $projectImage = new ProjectImage();
        $images = $projectImage->getByColumn("project_id", $this->id);

        $this->images = $images;

        return $images;
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
     * @param $shouldGetImages bool Whether of not to also get and output the Project Images linked to this Project
     */
    public function getById($id, bool $shouldGetImages = true) {
        parent::getById($id);

        // If Project was found
        if (!empty($this->id)) {

            // If Project isn't public and user isn't logged in, don't return Project
            if ($this->status !== self::PUBLIC_STATUS && !Auth::isLoggedIn()) {
                $this->id = 0;
                return;
            }

            // If Project's Images was requested, get and add these
            if ($shouldGetImages) {
                $this->getProjectImages();
            }
        }
    }

    /**
     * Delete an Entity from the Database
     *
     * Add extra functionality on top of default delete function
     * As these Entities are linked to many Project Images, so delete these also
     *
     * @param $id int The Id of the Entity to delete
     * @return bool Whether or not deletion was successful
     */
    public function delete($id): bool {
        $isDeleted = parent::delete($id);

        // Delete all the images linked to this Project from the database & from disk
        if ($isDeleted) {
            foreach ($this->images as $image) {
                $image->delete($image->id);
            }
        }

        return $isDeleted;
    }

    /**
     * Gets all Entities but paginated, also might include search
     *
     * Adds extra functionality to include any Images linked to all Projects found in search
     *
     * @param $params array Any data to aid in the search query
     * @return array The request response to send back
     */
    public function doSearch(array $params): array {
        // As the user isn't logged in, filter by status = public
        if (!Auth::isLoggedIn()) {
            $params["status"] = self::PUBLIC_STATUS;
        }

        $projects = parent::doSearch($params);

        // Loop through each Project and get the Projects Images
        $projects = array_map(function(Project $project) {
            $project->getProjectImages();

            return $project;
        }, $projects);

        return $projects;
    }
}
