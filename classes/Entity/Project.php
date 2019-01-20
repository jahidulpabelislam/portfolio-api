<?php
/*
 * The Project Entity object class (extends the base Entity class, where most of the ORM functionality lies).
 * Within this holds and methods where it overwrites or add extra custom functionality from the base Entity class.
 * Also holds any method only custom to Project entities.
 *
 * PHP version 7
 *
 * @author Jahidul Pabel Islam <me@jahidulpabelislam.com>
 * @version 1
 * @link https://github.com/jahidulpabelislam/portfolio-api/
 * @since Class available since Release: v3
 * @copyright 2012-2018 JPI
*/

namespace JPI\API\Entity;

if (!defined("ROOT")) {
    die();
}

class Project extends Entity {

    public $tableName = "PortfolioProject";

    public $displayName = "Project";

    protected $defaultOrderingByColumn = "Date";

    public $columns = [
        "ID",
        "Name",
        "Skills",
        "LongDescription",
        "ShortDescription",
        "Link",
        "GitHub",
        "Download",
        "Colour",
        "Date",
    ];

    protected $searchableColumns = [
        "Name",
        "Skills",
        "LongDescription",
        "ShortDescription",
    ];

    /**
     * Load a single Entity from the Database where a ID column = a value ($id)
     * Either return Entity with success meta data, or failed meta data
     * Uses helper function getByColumn();
     *
     * As extra functionality on top of default function
     * As Project is linked to Multiple Project Images
     * Add these to the response unless specified
     *
     * @param $id int The ID of the Entity to get
     * @param bool $images bool Whether of not to also get and output the Project Images linked to this Project
     * @return array The response from the SQL query
     */
    public function getById($id, $images = true): array {
        $response = parent::getById($id);

        // Check if database provided any meta data if so no problem with executing query but no project found
        if (!empty($response["row"])) {
            if ($images) {
                $images = $this->getProjectImages($id);
                $response["row"]["Images"] = $images;
            }
        }

        return $response;
    }

    /**
     * Save values to the Entity Table in the Database
     * Will either be a new insert or a update to an existing Entity
     *
     * Add extra functionality on top of default save
     * If the save was a update, update the Order 'SortOrderNumber' on its Project Images
     * The SortOrderNumber is based on to order the items are in
     *
     * @param $values array The values as an array to use for the Entity
     * @return array Either an array with successful meta data or an array of error feedback meta
     */
    public function save(array $values): array {

        $values["Date"] = date("Y-m-d", strtotime($values["Date"]));

        $response = parent::save($values);

        // Checks if the save was a update
        if (!empty($values["ID"])) {
            // Checks if update was ok
            if (!empty($response["row"])) {
                $images = json_decode($values["Images"]);

                if (count($images) > 0) {
                    foreach ($images as $sortOrder => $image) {
                        $imageUpdateData = ["ID" => $image->ID, "SortOrderNumber" => $sortOrder,];
                        $projectImage = new ProjectImage();
                        $projectImage->save($imageUpdateData);
                    }

                    $response = $this->getById($values["ID"]);
                }
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
     * @param $id int The ID of the Entity to delete
     * @return array Either an array with successful meta data or a array of error feedback meta
     */
    public function delete($id): array {
        $response = parent::delete($id);

        // Delete the images linked to the Project
        $projectImage = new ProjectImage();
        $images = $this->getProjectImages($id);
        foreach ($images as $image) {

            // Delete the image from the database & from file
            $projectImage->delete($image["ID"], $image["File"]);
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

        $response = parent::doSearch($params);

        // Loop through each project and get the Projects Images
        for ($i = 0; $i < $response["meta"]["count"]; $i++) {

            $images = $this->getProjectImages($response["rows"][$i]["ID"]);
            $response["rows"][$i]["Images"] = $images;
        }

        return $response;
    }

    /**
     * Helper function to get all Project Image Entities linked to this project
     *
     * @param $id int The Project Image to find Images for
     * @return array An array of ProjectImage's (if any found)
     */
    public function getProjectImages($id): array {
        // Get all the images linked to the Project
        $projectImage = new ProjectImage();
        $imagesResponse = $projectImage->getByColumn("ProjectID", $id);
        $images = $imagesResponse["rows"];

        return $images;
    }
}