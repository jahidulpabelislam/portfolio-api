<?php
/**
 * All the custom functions for the Projects part of the API that allow to perform all user requests.
 *
 * PHP version 7.1+
 *
 * @version 3.3.0
 * @author Jahidul Pabel Islam <me@jahidulpabelislam.com>
 * @copyright 2010-2019 JPI
*/

namespace JPI\API\Controller;

if (!defined("ROOT")) {
    die();
}

use JPI\API\Core as API;
use JPI\API\Responder;
use JPI\API\Entity\User;
use JPI\API\Entity\Project;
use JPI\API\Entity\ProjectImage;

class Projects {

    /**
     * Gets all Projects but paginated, also might include search
     *
     * @return array The request response to send back
     */
    public static function getProjects(): array {
        $data = API::get()->data;

        $project = new Project();
        $projects = $project->doSearch($data);

        return Responder::get()->getItemsSearchResponse($project, $projects, $data);
    }

    /**
     * Try to either insert or update a Project
     *
     * @param null $projectId int The Id of the Project to update (Only if a update request)
     * @return array The request response to send back
     */
    private static function _saveProject($projectId = null): array {
        if (User::isLoggedIn()) {

            // Only validate on creation
            if (empty($projectId) && !API::get()->hasRequiredFields(Project::class)) {
                return Responder::get()->getInvalidFieldsResponse(Project::class);
            }

            $data = API::get()->data;

            // Transform the incoming data into the necessary data for the database
            if (isset($data["date"])) {
                $data["date"] = date("Y-m-d", strtotime($data["date"]));
            }
            if (isset($data["skills"]) && is_array($data["skills"])) {
                $data["skills"] = implode(",", $data["skills"]);
            }

            // Checks if the save was okay, and images were passed, update the sort order on the images
            if (!empty($data["images"])) {
                foreach ($data["images"] as $i => $image) {
                    $imageData = json_decode($image, true);

                    $projectImage = ProjectImage::getById($imageData["id"]);
                    $projectImage->update(["sort_order_number" => $i + 1]);
                }
            }

            if (empty($projectId)) {
                $project = Project::insert($data);
                $response = Responder::getInsertResponse($project);
            }
            else {
                $project = Project::getById($projectId);
                $project->update($data);
                $response = Responder::getUpdateResponse($project, $projectId);
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
     * @return array The request response to send back
     */
    public static function addProject(): array {
        $response = self::_saveProject();

        // If successful, as this is a new Project creation override the meta
        if (!empty($response["row"])) {
            $response["meta"]["status"] = 201;
            $response["meta"]["message"] = "Created";
        }

        return $response;
    }

    /**
     * Try to edit a Project a user has added before
     *
     * @param $projectId int The Id of the Project to update
     * @return array The request response to send back
     */
    public static function updateProject($projectId): array {
        return self::_saveProject($projectId);
    }

    /**
     * Try to delete a Project a user has added before
     *
     * @param $projectId int The Id of the Project to delete
     * @return array The request response to send back
     */
    public static function deleteProject($projectId): array {
        if (User::isLoggedIn()) {
            $project = Project::getById($projectId);
            $isDeleted = $project->delete();

            $response = Responder::getItemDeletedResponse($project, $projectId, $isDeleted);
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
     * @param $shouldGetImages bool Whether the images for the Project should should be added
     * @return array The request response to send back
     */
    public static function getProject($projectId, bool $shouldGetImages = false): array {
        $project = Project::getById($projectId, $shouldGetImages);

        return Responder::getItemResponse($project, $projectId);
    }

    /**
     * Get the Images attached to a Project
     *
     * @param $projectId int The Id of the Project
     * @return array The request response to send back
     */
    public static function getProjectImages($projectId): array {
        // Check the Project trying to get Images for exists
        $projectRes = self::getProject($projectId);
        if (!empty($projectRes["row"])) {

            $projectImage = new ProjectImage();
            $projectImages = ProjectImage::getByColumn("project_id", (int)$projectId);

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
    private static function _uploadProjectImage(array $project, array $image): array {
        $response = [];

        $projectId = $project["id"];
        $projectName = $project["name"];

        $projectNameFormatted = strtolower($projectName);
        $projectNameFormatted = preg_replace("/[^a-z0-9]+/", "-", $projectNameFormatted);

        // Get the file ext
        $imageFileExt = pathinfo(basename($image["name"]), PATHINFO_EXTENSION);

        // The directory to upload file
        $directory = "/project-images/";

        // The full path for new file on the server
        $newFilename = $projectNameFormatted;
        $newFilename .= "-" . date("Ymd-His");
        $newFilename .= "-" . random_int(0, 99);
        $newFilename .= ".{$imageFileExt}";

        $newFileLocation = $directory . $newFilename;

        $newImageFullPath = ROOT . $newFileLocation;

        // Check if file is a actual image
        $fileType = mime_content_type($image["tmp_name"]);
        if (strpos($fileType, "image/") === 0) {

            // Try to uploaded file
            if (move_uploaded_file($image["tmp_name"], $newImageFullPath)) {

                // Add new image with location into the database
                $imageData = [
                    "file" => $newFileLocation,
                    "project_id" => $projectId,
                    "sort_order_number" => 999, // High enough number
                ];
                $projectImage = ProjectImage::insert($imageData);

                $response = Responder::getInsertResponse($projectImage);
            }
            else {
                // Else there was a problem uploading file to server
                $response["meta"]["feedback"] = "Sorry, there was an error uploading your image.";
            }
        }
        else {
            // Else bad request as file uploaded is not a image
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
     * @param $projectId int The Project Id to add this Image for
     * @return array The request response to send back
     */
    public static function addProjectImage($projectId): array {
        if (User::isLoggedIn()) {
            $files = API::get()->files;
            if (isset($files["image"])) {

                // Check the Project trying to add a Image for exists
                $response = self::getProject($projectId);
                if (!empty($response["row"])) {
                    $response = self::_uploadProjectImage($response["row"], $files["image"]);
                }
            }
            else {
                $requiredFields = ["image"];
                $response = Responder::get()->getInvalidFieldsResponse(User::class, $requiredFields);
            }
        }
        else {
            $response = Responder::getNotAuthorisedResponse();
        }

        return $response;
    }

    /**
     * Get a Project Image for a Project by Id
     *
     * @param $projectId int The Id of the Project trying to get Image for
     * @param $imageId int The Id of the Project Image to get
     * @return array The request response to send back
     */
    public static function getProjectImage($projectId, $imageId): array {
        // Check the Project trying to get Images for exists
        $response = self::getProject($projectId);
        if (!empty($response["row"])) {
            $projectImage = ProjectImage::getById($imageId);

            $response = Responder::getItemResponse($projectImage, $imageId);

            // Even though a Project Image may have been found with $imageId, this may not be for project $projectId
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
     * @param $projectId int The Id of the Project trying to delete Image for
     * @param $imageId int The Id of the Project Image to delete
     * @return array The request response to send back
     */
    public static function deleteProjectImage($projectId, $imageId): array {
        if (User::isLoggedIn()) {
            // Check the Project of the Image trying to edit actually exists
            $response = self::getProject($projectId);
            if (!empty($response["row"])) {

                // Delete row from database
                $projectImage = ProjectImage::getById($imageId);
                $isDeleted = $projectImage->delete();

                $response = Responder::getItemDeletedResponse($projectImage, $imageId, $isDeleted);
            }
        }
        else {
            $response = Responder::getNotAuthorisedResponse();
        }

        return $response;
    }
}
