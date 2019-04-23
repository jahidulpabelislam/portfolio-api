<?php
/*
 * The Project Entity object class (extends the base Entity class, where most of the ORM functionality lies).
 * Within this holds and methods where it overwrites or add extra custom functionality from the base Entity class.
 * Also holds any method only custom to Project entities.
 *
 * PHP version 7
 *
 * @author Jahidul Pabel Islam <me@jahidulpabelislam.com>
 * @version 1.1.0
 * @link https://github.com/jahidulpabelislam/portfolio-api/
 * @since Class available since Release: v3.0.0
 * @copyright 2010-2019 JPI
*/

namespace JPI\API\Entity;

use JPI\API\Auth;
use JPI\API\Helper;

if (!defined("ROOT")) {
    die();
}

class Project extends Entity {

    const PUBLIC_STATUS = "published";

    protected $tableName = "portfolio_project";

    protected $columns = [
        "id",
        "name",
        "date",
        "skills",
        "link",
        "github",
        "download",
        "colour",
        "short_description",
        "long_description",
        "status",
        "created_at",
        "updated_at",
    ];

    protected $searchableColumns = [
        "name",
        "skills",
        "long_description",
        "short_description",
        "status",
    ];

    protected $defaultOrderByColumn = "date";

    public $displayName = "Project";

    /**
     * Helper function to get all Project Image Entities linked to this Project
     *
     * @param $id int The Project Image to find Images for
     * @return array An array of ProjectImage's (if any found)
     */
    public function getProjectImages($id): array {
        // Get all the images linked to the Project
        $projectImage = new ProjectImage();
        $imagesResponse = $projectImage->getByColumn("project_id", $id);
        $images = $imagesResponse["rows"];

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
     * @param bool $getImages bool Whether of not to also get and output the Project Images linked to this Project
     * @return array The response from the SQL query
     */
    public function getById($id, bool $getImages = true): array {
        $response = parent::getById($id);

        // If Project was found
        if (!empty($response["row"])) {
            if (!Auth::isLoggedIn() && $response["row"]["status"] !== self::PUBLIC_STATUS) {
                return Helper::getNotAuthorisedResponse();
            }

            // If Project's Images was requested, get and add these
            if ($getImages) {
                $getImages = $this->getProjectImages($id);
                $response["row"]["images"] = $getImages;
            }
        }

        return $response;
    }

    /**
     * Save values to the Entity Table in the Database
     * Will either be a new insert or a update to an existing Entity
     *
     * Add extra functionality on top of default save
     * If the save was a update, update the Order 'sort_order_number' on its Project Images
     * The sort_order_number is based on to order the items are in
     *
     * @param $values array The values as an array to use for the Entity
     * @return array Either an array with successful meta data or an array of error feedback meta
     */
    public function save(array $values): array {

        $values["date"] = date("Y-m-d", strtotime($values["date"]));

        $response = parent::save($values);

        // Checks if the save was a update & update was okay
        if (!empty($values["id"]) && !empty($response["row"]) && !empty($values["images"])) {

            $images = json_decode($values["images"], true);

            if (count($images) > 0) {
                foreach ($images as $sortOrder => $image) {
                    $imageUpdateData = [
                        "id" => $image["id"],
                        "sort_order_number" => $sortOrder,
                    ];
                    $projectImage = new ProjectImage();
                    $projectImage->save($imageUpdateData);
                }

                $response = $this->getById($values["id"]);
            }
        }

        return $response;
    }

    /**
     * Delete an Entity from the Database
     *
     * Add extra functionality on top of default delete function
     * As these Entities are linked to many Project Images, so delete these also
     *
     * @param $id int The Id of the Entity to delete
     * @return array Either an array with successful meta data or a array of error feedback meta
     */
    public function delete($id): array {
        $response = parent::delete($id);

        // Delete all the images linked to this Project from the database & from disk
        $projectImage = new ProjectImage();
        $images = $this->getProjectImages($id);
        foreach ($images as $image) {
            $projectImage->delete($image["id"], $image["file"]);
        }

        return $response;
    }

    /**
     * Gets all Entities but paginated, also might include search
     *
     * Adds extra functionality to include any Images linked to all Projects found in search
     *
     * @param array $params array Any data to aid in the search query
     * @return array The request response to send back
     */
    public function doSearch(array $params): array {

        if (!Auth::isLoggedIn()) {
            $params["status"] = self::PUBLIC_STATUS;
        }

        $response = parent::doSearch($params);

        // Loop through each Project and get the Projects Images
        $response["rows"] = array_map(function($project) {
            $project["images"] = $this->getProjectImages($project["id"]);

            return $project;
        }, $response["rows"]);

        return $response;
    }
}
