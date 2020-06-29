<?php
/**
 * All the custom functions for the Projects part of the API that allow to perform all user requests.
 *
 * PHP version 7.1+
 *
 * @author Jahidul Pabel Islam <me@jahidulpabelislam.com>
 * @copyright 2010-2020 JPI
 */

namespace App\Controller;

if (!defined("ROOT")) {
    die();
}

use App\Controller;
use App\Core;
use App\Entity\Project;
use App\Entity\ProjectImage;
use App\Entity\User;
use Exception;

class Projects extends Controller {

    /**
     * @param $projectId int|string
     * @param $includeLinkedData bool
     * @return Project|null
     */
    private static function getProjectEntity($projectId, bool $includeLinkedData = false): ?Project {
        $project = Project::getById($projectId);
        if ($project && $includeLinkedData) {
            $project->loadProjectImages();
        }

        return $project;
    }

    /**
     * Gets all Projects but paginated, also might include search
     *
     * @return array The request response to send back
     */
    public function getProjects(): array {
        $params = $this->core->params;

        $limit = $params["limit"] ?? null;
        $page = $params["page"] ?? null;
        $projects = Project::getByParams($params, $limit, $page);

        if ($projects instanceof Project) {
            $projects = [$projects];
        }
        else if (!is_array($projects)) {
            $projects = [];
        }

        array_walk($projects, static function(Project $project) {
            $project->loadProjectImages();
        });

        return $this->getItemsSearchResponse(Project::class, $projects, $params);
    }

    /**
     * Try to either insert or update a Project
     *
     * @param $data array The values to save
     * @param $projectId int|string|null The Id of the Project to update (Only if a update request)
     * @return array The request response to send back
     */
    private function saveProject(array $data, $projectId = null): array {
        if (User::isLoggedIn()) {

            $isNew = $projectId === null;

            // Only validate on creation
            if ($isNew && !Core::hasRequiredFields(Project::class, $data)) {
                return $this->getInvalidFieldsResponse(Project::class, $data);
            }

            // Transform the incoming data into the necessary data for the database
            if (isset($data["date"])) {
                $data["date"] = date("Y-m-d", strtotime($data["date"]));
            }
            if (isset($data["skills"]) && is_array($data["skills"])) {
                $data["skills"] = implode(",", $data["skills"]);
            }

            if ($isNew) {
                $project = Project::insert($data);
                $response = self::getInsertResponse(Project::class, $project);
            }
            else {
                $project = self::getProjectEntity($projectId, true);
                if ($project) {
                    $project->setValues($data);
                    $project->save();

                    // If images were passed update the sort order
                    if (!empty($data["images"])) {
                        $orders = [];
                        foreach ($data["images"] as $i => $image) {
                            $imageData = json_decode($image, true);
                            $orders[$imageData["id"]] = $i + 1;
                        }

                        foreach ($project->images as $projectImage) {
                            $projectImage->sort_order_number = $orders[$projectImage->getId()];
                            $projectImage->save();
                        }
                    }
                }

                $response = self::getUpdateResponse(Project::class, $project, $projectId);
            }
        }
        else {
            $response = self::getNotAuthorisedResponse();
        }

        return $response;
    }

    /**
     * Try and add a Project a user has attempted to add
     *
     * @return array The request response to send back
     */
    public function addProject(): array {
        return $this->saveProject($this->core->data);
    }

    /**
     * Try to edit a Project a user has added before
     *
     * @param $projectId int|string The Id of the Project to update
     * @return array The request response to send back
     */
    public function updateProject($projectId): array {
        return $this->saveProject($this->core->params, $projectId);
    }

    /**
     * Try to delete a Project a user has added before
     *
     * @param $projectId int|string The Id of the Project to delete
     * @return array The request response to send back
     */
    public static function deleteProject($projectId): array {
        if (User::isLoggedIn()) {
            $isDeleted = false;

            $project = self::getProjectEntity($projectId);
            if ($project) {
                $isDeleted = $project->delete();
            }

            $response = self::getItemDeletedResponse(Project::class, $project, $projectId, $isDeleted);
        }
        else {
            $response = self::getNotAuthorisedResponse();
        }

        return $response;
    }

    /**
     * Get a particular Project defined by $projectId
     *
     * @param $projectId int|string The Id of the Project to get
     * @param $includeLinkedData bool Whether to also get and include linked entity/data (images)
     * @return array The request response to send back
     */
    public static function getProject($projectId, bool $includeLinkedData = true): array {
        $project = self::getProjectEntity($projectId, $includeLinkedData);

        return self::getItemResponse(Project::class, $project, $projectId);
    }

    /**
     * Get the Images attached to a Project
     *
     * @param $projectId int|string The Id of the Project
     * @return array The request response to send back
     */
    public static function getProjectImages($projectId): array {
        // Check the Project trying to get Images for exists
        $project = self::getProjectEntity($projectId, true);
        if ($project) {
            return self::getItemsResponse(ProjectImage::class, $project->images);
        }

        return self::getItemNotFoundResponse(Project::class, $projectId);
    }

    /**
     * Try and upload the added image
     *
     * @param $project Project The Project trying to upload image for
     * @param $image array The uploaded image
     * @return array The request response to send back
     * @throws Exception
     */
    private static function uploadProjectImage(Project $project, array $image): array {
        $response = [];

        $projectId = $project->getId();
        $projectName = $project->name;

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

                $response = self::getInsertResponse(ProjectImage::class, $projectImage);
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
     * @param $projectId int|string The Project Id to add this Image for
     * @return array The request response to send back
     * @throws Exception
     */
    public function addProjectImage($projectId): array {
        if (User::isLoggedIn()) {
            $files = $this->core->files;
            if (isset($files["image"])) {

                // Check the Project trying to add a Image for exists
                $project = self::getProjectEntity($projectId);
                if ($project) {
                    $response = self::uploadProjectImage($project, $files["image"]);
                }
                else {
                    $response = self::getItemNotFoundResponse(Project::class, $projectId);
                }
            }
            else {
                $requiredFields = ["image"];
                $response = $this->getInvalidFieldsResponse(ProjectImage::class, [], $requiredFields);
            }
        }
        else {
            $response = self::getNotAuthorisedResponse();
        }

        return $response;
    }

    /**
     * Get a Project Image for a Project by Id
     *
     * @param $projectId int|string The Id of the Project trying to get Image for
     * @param $imageId int|string The Id of the Project Image to get
     * @return array The request response to send back
     */
    public static function getProjectImage($projectId, $imageId): array {
        // Check the Project trying to get Images for exists
        $project = self::getProjectEntity($projectId);
        if ($project) {
            $projectImage = ProjectImage::getById($imageId);

            $response = self::getItemResponse(ProjectImage::class, $projectImage, $imageId);

            // Even though a Project Image may have been found with $imageId, this may not be for project $projectId
            $projectId = (int)$projectId;
            if ($projectImage && !empty($projectImage->project_id) && $projectImage->project_id !== $projectId) {
                $response["row"] = [];
                $response["meta"]["feedback"] = "No {$projectImage::$displayName} found with {$imageId} as ID for Project: {$projectId}.";
            }

            return $response;
        }

        return self::getItemResponse(Project::class, $project, $projectId);
    }

    /**
     * Try to delete a Image linked to a Project
     *
     * @param $projectId int|string The Id of the Project trying to delete Image for
     * @param $imageId int|string The Id of the Project Image to delete
     * @return array The request response to send back
     */
    public static function deleteProjectImage($projectId, $imageId): array {
        if (User::isLoggedIn()) {
            // Check the Project of the Image trying to edit actually exists
            $project = self::getProjectEntity($projectId);
            if ($project) {
                $isDeleted = false;

                // Delete row from database
                $projectImage = ProjectImage::getById($imageId);
                if ($projectImage) {
                    $isDeleted = $projectImage->delete();
                }

                $response = self::getItemDeletedResponse(ProjectImage::class, $projectImage, $imageId, $isDeleted);
            }
            else {
                $response = self::getItemNotFoundResponse(Project::class, $projectId);
            }
        }
        else {
            $response = self::getNotAuthorisedResponse();
        }

        return $response;
    }

}
