<?php
/*
 * All the custom functions for the Projects part of the API that allow to perform all user requests.
 *
 * PHP version 7
 *
 * @author Jahidul Pabel Islam <me@jahidulpabelislam.com>
 * @version 3.1.0
 * @link https://github.com/jahidulpabelislam/portfolio-api/
 * @copyright 2010-2019 JPI
*/

namespace JPI\API;

if (!defined("ROOT")) {
    die();
}

use JPI\API\Entity\Project;
use JPI\API\Entity\ProjectImage;

class Projects {

    private $api;

    /**
     * Projects constructor.
     */
    public function __construct() {
        $this->api = Core::get();
    }

    /*
     * Gets all Projects but paginated, also might include search
     *
     * @param $data array Any data to aid in the search query
     * @return array The request response to send back
     */
    public function getProjects(array $data): array {

        $project = new Project();
        $projects = $project->doSearch($data);

        return Responder::get()->getItemsSearchResponse($project, $projects, $data);
    }

    /**
     * Try to either insert or update a Project
     *
     * @param $data array The data to insert/update into the database for the Project
     * @return array The request response to send back
     */
    private function saveProject(array $data): array {
        if (Auth::isLoggedIn()) {

            $requiredFields = ["name", "date", "skills", "long_description", "short_description"];
            if ($this->api->hasRequiredFields($requiredFields)) {

                // Transform the incoming data into the necessary data for the database
                if (isset($data["date"])) {
                    $data["date"] = date("Y-m-d", strtotime($data["date"]));
                }

                if (isset($data["skills"]) && is_array($data["skills"])) {
                    $data["skills"] = implode(",", $data["skills"]);
                }

                $project = new Project();

                if (isset($data["id"])) {
                    $project->getById($data["id"], false);
                }

                $project->setValues($data);
                $project->save();

                // Checks if the save was a update & update was okay
                if (!empty($project->id) && !empty($data["images"])) {

                    $images = $data["images"];

                    if (count($images) > 0) {
                        foreach ($images as $sortOrder => $image) {
                            $imageUpdateData = json_decode($image, true);
                            $imageUpdateData["sort_order_number"] = $sortOrder + 1;

                            $projectImage = new ProjectImage();
                            $projectImage->setValues($imageUpdateData);
                            $projectImage->save();
                        }

                        $project->getById($project->id);
                    }
                }

                $response = Responder::getItemResponse($project, $project->id);
            }
            else {
                $response = Responder::get()->getInvalidFieldsResponse($requiredFields);
            }
        }
        else {
            $response = Responder::getNotAuthorisedResponse();
        }

        return $response;
    }

    /**
     * Try and add a Project a user has attempted to add
     *
     * @param $data array The data to insert into the database for this new Project
     * @return array The request response to send back
     */
    public function addProject(array $data): array {
        $response = $this->saveProject($data);

        if (!empty($response["row"])) {
            $response["meta"]["status"] = 201;
            $response["meta"]["message"] = "Created";
        }

        return $response;
    }

    /**
     * Try to edit a Project a user has added before
     *
     * @param $data array The new data entered to use to update the Project with
     * @return array The request response to send back
     */
    public function editProject(array $data): array {
        return $this->saveProject($data);
    }

    /**
     * Try to delete a Project a user has added before
     *
     * @param $data array The data sent to aid in the deletion of the Project
     * @return array The request response to send back
     */
    public function deleteProject(array $data): array {
        if (Auth::isLoggedIn()) {
            $project = new Project();
            $isDeleted = $project->delete($data["id"]);

            $response = Responder::getItemDeletedResponse($project, $data["id"], $isDeleted);
        }
        else {
            $response = Responder::getNotAuthorisedResponse();
        }

        return $response;
    }

    /**
     * Get a particular Project defined by $projectId
     *
     * @param $projectId int The Id of the Project to get
     * @param $getImages bool Whether the images for the Project should should be added
     * @return array The request response to send back
     */
    public function getProject($projectId, bool $getImages = false): array {

        $project = new Project();
        $project->getById($projectId, $getImages);

        return Responder::getItemResponse($project, $projectId);
    }

    /**
     * Get the Images attached to a Project
     *
     * @param $projectId int The Id of the Project
     * @return array The request response to send back
     */
    public function getProjectImages($projectId): array {

        // Check the Project trying to get Images for exists
        $projectRes = $this->getProject($projectId);
        if (!empty($projectRes["row"])) {

            $projectImage = new ProjectImage();
            $projectImages = $projectImage->getByColumn("project_id", $projectId);

            return Responder::getItemsResponse($projectImage, $projectImages);
        }

        return $projectRes;
    }

    /**
     * Try and upload the added image
     *
     * @param $project array The Project trying to upload image for
     * @return array The request response to send back
     */
    private function uploadProjectImage(array $project): array {
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
        $newFilename .= "-" . random_int(0, 99);
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
                $projectImage->setValues($values);
                $projectImage->save();

                $response = Responder::getItemResponse($projectImage, $projectImage->id);

                if (!empty($response["row"])) {
                    $response["meta"]["status"] = 201;
                    $response["meta"]["message"] = "Created";
                }
            }
            // Else there was a problem uploading file to server
            else {
                $response["meta"]["feedback"] = "Sorry, there was an error uploading your image.";
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
    public function addProjectImage(array $data): array {
        if (Auth::isLoggedIn()) {
            if (isset($_FILES["image"])) {

                // Check the Project trying to add a Image for exists
                $response = $this->getProject($data["project_id"]);
                if (!empty($response["row"])) {
                    $response = $this->uploadProjectImage($response["row"]);
                }
            }
            else {
                $requiredFields = ["image"];
                $response = Responder::get()->getInvalidFieldsResponse($requiredFields);
            }
        }
        else {
            $response = Responder::getNotAuthorisedResponse();
        }

        $response["meta"]["files"] = $_FILES;

        return $response;
    }

    /**
     * Get a Project Image for a Project by Id
     *
     * @param $projectId int The Id of the Project trying to get Images for
     * @param $imageId int The Id of the Project Image to get
     * @return array The request response to send back
     */
    public function getProjectImage($projectId, $imageId): array {

        // Check the Project trying to get Images for exists
        $response = $this->getProject($projectId);
        if (!empty($response["row"])) {
            $projectImage = new ProjectImage();
            $projectImage->getById($imageId);

            $response = Responder::getItemResponse($projectImage, $imageId);

            $projectId = (int)$projectId;
            if (!empty($projectImage->project_id) && $projectImage->project_id !== $projectId) {
                $response["row"] = [];
                $response["meta"]["feedback"] = "No {$projectImage::$displayName} found with {$imageId} as ID for Project: {$projectId}.";
            }
        }

        return $response;
    }

    /**
     * Try to delete a Image linked to a Project
     *
     * @param $data array The data sent to delete the Project Image
     * @return array The request response to send back
     */
    public function deleteProjectImage(array $data): array {
        if (Auth::isLoggedIn()) {

            $projectId = $data["project_id"];
            $imageId = $data["id"];

            // Check the Project trying to edit actually exists
            $response = $this->getProject($projectId);
            if (!empty($response["row"])) {

                // Update database to delete row
                $projectImage = new ProjectImage();
                $isDeleted = $projectImage->delete($imageId);

                $response = Responder::getItemDeletedResponse($projectImage, $imageId, $isDeleted);
            }
        }
        else {
            $response = Responder::getNotAuthorisedResponse();
        }

        return $response;
    }
}