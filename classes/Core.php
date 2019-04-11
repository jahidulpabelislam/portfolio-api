<?php
/*
 * All the custom functions for this API that allow to perform all user requests.
 *
 * PHP version 7
 *
 * @author Jahidul Pabel Islam <me@jahidulpabelislam.com>
 * @version 3
 * @link https://github.com/jahidulpabelislam/portfolio-api/
 * @copyright 2010-2018 JPI
*/

namespace JPI\API;

if (!defined("ROOT")) {
    die();
}

use JPI\API\Entity\Project;
use JPI\API\Entity\ProjectImage;

class Core {

    private $db = null;

    /**
     * API constructor.
     */
    public function __construct() {
        $this->db = Database::get();
    }

    /**
     * Gets all Projects but paginated, also might include search
     *
     * @param $data array Any data to aid in the search query
     * @return array The request response to send back
     */
    public function getProjects($data) {

        $projects = new Project();
        $response = $projects->doSearch($data);

        return $response;
    }

    /**
     * Try and add a Project a user has attempted to add
     *
     * @param $data array The data to insert into the database for this new Project
     * @return array The request response to send back
     */
    public function addProject($data) {

        // Checks if user is authored
        if (Auth::isLoggedIn()) {

            // Checks if data needed is present and not empty
            $requiredData = ["name", "skills", "long_description", "short_description", "github", "date"];
            if (Helper::hasRequiredData($data, $requiredData)) {

                $project = new Project();
                $response = $project->save($data);

            }
            // Else the data needed was not provided
            else {
                $response = Helper::getDataNotProvidedResponse($requiredData);
            }
        }
        else {
            $response = Helper::getNotAuthorisedResponse();
        }

        return $response;
    }

    /**
     * Try to edit a Project a user has added before
     *
     * @param $data array The new data entered to use to update the Project with
     * @return array The request response to send back
     */
    public function editProject($data) {

        // Checks if user is authored
        if (Auth::isLoggedIn()) {

            // Checks if data needed is present and not empty
            $requiredData = ["id", "name", "skills", "long_description", "short_description", "github", "date"];
            if (Helper::hasRequiredData($data, $requiredData)) {

                $project = new Project();
                $response = $project->save($data);

            }
            // Else the data was not provided
            else {
                $response = Helper::getDataNotProvidedResponse($requiredData);
            }
        }
        else {
            $response = Helper::getNotAuthorisedResponse();
        }

        return $response;
    }

    /**
     * Try to delete a Project a user has added before
     *
     * @param $data array The data sent to aid in the deletion of the Project
     * @return array The request response to send back
     */
    public function deleteProject($data) {

        // Checks if user is authored
        if (Auth::isLoggedIn()) {

            // Checks if the data needed is present and not empty
            $requiredData = ["id"];
            if (Helper::hasRequiredData($data, $requiredData)) {

                $project = new Project();
                $response = $project->delete($data["id"]);

            }
            // Else the data needed was not provided
            else {
                $response = Helper::getDataNotProvidedResponse($requiredData);
            }
        }
        else {
            $response = Helper::getNotAuthorisedResponse();
        }

        return $response;
    }

    /**
     * Get a particular Project defined by $projectId
     *
     * @param $projectId int The id of the Project to get
     * @param bool $images bool Whether the images for the Project should should be added
     * @return array The request response to send back
     */
    public function getProject($projectId, $images = false) {

        $project = new Project();
        $response = $project->getById($projectId, $images);

        return $response;
    }

    /**
     * Get the Images attached to a Project
     *
     * @param $projectId int The Id of the Project
     * @return array The request response to send back
     */
    public function getProjectImages($projectId) {

        // Check the Project trying to get Images for
        $response = $this->getProject($projectId);
        if (!empty($response["row"])) {

            $projectImage = new ProjectImage();
            $response = $projectImage->getByColumn("project_id", $projectId);
        }

        return $response;
    }

    /**
     * Try and upload the added image
     *
     * @param $project array The Project trying to upload image for
     * @return array The request response to send back
     */
    private function uploadProjectImage($project): array {

        $response = [];

        $projectId = $project["id"];

        $projectName = $project["name"];

        $projectNameFormatted = strtolower($projectName);
        $projectNameFormatted = preg_replace("/[^a-z0-9]+/", "-", $projectNameFormatted);

        $image = $_FILES["image"];

        // Get the file ext
        $imageFileExt = pathinfo(basename($image["name"]), PATHINFO_EXTENSION);

        // The directory to upload file
        $directory = "/project-images/";

        // The full path for new file on the server
        $newFilename = $projectNameFormatted;
        $newFilename .= "-" . date("Ymd-His");
        $newFilename .= "-" . mt_rand(0, 99);
        $newFilename .= "." . $imageFileExt;

        $newFileLocation = $directory . $newFilename;

        $newImageFullPath = ROOT . $newFileLocation;

        // Check if file is a actual image
        $fileType = mime_content_type($image["tmp_name"]);
        if (stripos($fileType, "image/") !== false) {

            // Try to uploaded file
            if (move_uploaded_file($image["tmp_name"], $newImageFullPath)) {

                // Update database with location of new Image
                $values = [
                    "file" => $newFileLocation,
                    "project_id" => $projectId,
                    "sort_order_number" => 999, // High enough number
                ];
                $projectImage = new ProjectImage();
                $response = $projectImage->save($values);
            }
            // Else there was a problem uploading file to server
            else {
                $response["meta"]["feedback"] = "Sorry, there was an error uploading your Image.";
            }
        }
        // Else bad request as file uploaded is not a image
        else {
            $response["meta"] = [
                "status" => 400,
                "message" => "Bad Request",
                "feedback" => "File is not an image.",
            ];
        }

        return $response;
    }

    /**
     * Try to upload a Image user has tried to add as a Project Image
     *
     * @param $data array The data sent to aid in Inserting Project Image
     * @return array The request response to send back
     */
    public function addProjectImage($data) {

        // Checks if user is authored
        if (Auth::isLoggedIn()) {

            // Checks if the data needed is present and not empty
            $requiredData = ["project_id"];
            if (Helper::hasRequiredData($data, $requiredData) && isset($_FILES["image"])) {

                // Check the Project trying to add a a Image for exists
                $response = $this->getProject($data["project_id"]);
                if (!empty($response["row"])) {

                    $response = $this->uploadProjectImage($response["row"]);
                }
            }
            // Else data needed was not provided
            else {
                $requiredData[] = "image";
                $response = Helper::getDataNotProvidedResponse($requiredData);
            }
        }
        else {
            $response = Helper::getNotAuthorisedResponse();
        }

        $response["meta"]["files"] = $_FILES;

        return $response;
    }

    /**
     * Get a Project Image for a Project by id
     *
     * @param $projectId int The id of the Project trying to get Images for
     * @param $imageId int The id of the Project Image to get
     * @return array The request response to send back
     */
    public function getProjectImage($projectId, $imageId) {

        // Check the Project trying to get Images for
        $response = $this->getProject($projectId);
        if (!empty($response["row"])) {
            $projectImage = new ProjectImage($imageId);

            $projectImage->checkProjectImageIsChildOfProject($projectId);

            $response = $projectImage->response;
        }

        return $response;
    }

    /**
     * Try to delete a Image linked to a Project
     *
     * @param $data array The data sent to delete the Project Image
     * @return array The request response to send back
     */
    public function deleteImage($data) {

        // Checks if user is authored
        if (Auth::isLoggedIn()) {

            // Checks if data needed is present and not empty
            $requiredData = ["project_id", "id"];
            if (Helper::hasRequiredData($data, $requiredData)) {

                $projectId = $data["project_id"];
                $imageId = $data["id"];

                // Check the Project trying to edit actually exists
                $response = $this->getProject($projectId);
                if (!empty($response["row"])) {

                    $response = $this->getProjectImage($projectId, $imageId);

                    if (!empty($response["row"])) {

                        $fileName = $response["row"]["file"];

                        // Update database to delete row
                        $projectImage = new ProjectImage();
                        $response = $projectImage->delete($imageId, $fileName);
                    }
                }
            } // Else data was not provided
            else {
                $response = Helper::getDataNotProvidedResponse($requiredData);
            }
        }
        else {
            $response = Helper::getNotAuthorisedResponse();
        }

        return $response;
    }
}
