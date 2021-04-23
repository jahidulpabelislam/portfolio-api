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

use App\Controller;
use App\Core;
use App\Entity\Collection as EntityCollection;
use App\Entity\Project;
use App\Entity\ProjectImage;
use App\Entity\User;
use App\HTTP\Response;
use Exception;

class Projects extends Controller {

    /**
     * @param $projectId int|string
     * @param $includeLinkedData bool
     * @return Project|null
     */
    private static function getProjectEntity($projectId, bool $includeLinkedData = false): ?Project {
        $where = ["id = :id"];
        $params = ["id" => $projectId];
        if (!User::isLoggedIn()) {
            $where[] = "status = :status";
            $params["status"] = Project::PUBLIC_STATUS;
        }

        $project = Project::get($where, $params, 1);
        if ($project && $includeLinkedData) {
            $project->loadProjectImages();
        }

        return $project;
    }

    /**
     * Gets all Projects but paginated, also might include search
     *
     * @return Response
     */
    public function getProjects(): Response {
        $params = $this->core->params;

        $limit = $params["limit"] ?? null;
        $page = $params["page"] ?? null;

        // As the user isn't logged in, filter by status = public
        if (!User::isLoggedIn()) {
            $params["status"] = Project::PUBLIC_STATUS;
        }

        $query = Project::buildQueryFromFilters($params);

        $where = $query["where"];
        $queryParams = $query["params"];

        $search = $params["search"] ?? null;
        if ($search) {
            $searchQuery = Project::buildSearchQuery($search);

            $where = array_merge($where, $searchQuery["where"]);
            $queryParams = array_merge($queryParams, $searchQuery["params"]);
        }

        $projects = Project::get($where, $queryParams, $limit, $page);

        if ($projects instanceof Project) {
            $totalCount = Project::getCount($where, $queryParams);
            $projects = new EntityCollection([$projects], $totalCount, $limit, $page);
        }

        if (count($projects)) {
            $images = ProjectImage::getByColumn("project_id", $projects->pluck("id")->toArray());
            $imagesGrouped = $images->groupBy("project_id");

            foreach ($projects as $project) {
                $project->images = $imagesGrouped[$project->getId()] ?? new EntityCollection();
            }
        }

        return $this->getPaginatedItemsResponse(Project::class, $projects);
    }

    /**
     * Try to either insert or update a Project
     *
     * @param $data array The values to save
     * @param $projectId int|string|null The Id of the Project to update (Only if a update request)
     * @return Response
     */
    private function saveProject(array $data, $projectId = null): Response {
        if (User::isLoggedIn()) {
            $isNew = $projectId === null;

            // Only validate on creation
            if ($isNew && !Core::hasRequiredFields(Project::class, $data)) {
                return $this->getInvalidFieldsResponse(Project::class, $data);
            }

            if ($isNew) {
                $project = Project::insert($data);
                $project->reload();
                return self::getInsertResponse(Project::class, $project);
            }

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

                $project->reload();
            }

            return self::getUpdateResponse(Project::class, $project, $projectId);
        }

        return self::getNotAuthorisedResponse();
    }

    /**
     * Try and add a Project a user has attempted to add
     *
     * @return Response
     */
    public function addProject(): Response {
        return $this->saveProject($this->core->data);
    }

    /**
     * Try to edit a Project a user has added before
     *
     * @param $projectId int|string The Id of the Project to update
     * @return Response
     */
    public function updateProject($projectId): Response {
        return $this->saveProject($this->core->params, $projectId);
    }

    /**
     * Try to delete a Project a user has added before
     *
     * @param $projectId int|string The Id of the Project to delete
     * @return Response
     */
    public static function deleteProject($projectId): Response {
        if (User::isLoggedIn()) {
            $isDeleted = false;

            $project = self::getProjectEntity($projectId);
            if ($project) {
                $isDeleted = $project->delete();
            }

            return self::getItemDeletedResponse(Project::class, $project, $projectId, $isDeleted);
        }

        return self::getNotAuthorisedResponse();
    }

    /**
     * Get a particular Project defined by $projectId
     *
     * @param $projectId int|string The Id of the Project to get
     * @param $includeLinkedData bool Whether to also get and include linked entity/data (images)
     * @return Response
     */
    public static function getProject($projectId, bool $includeLinkedData = true): Response {
        $project = self::getProjectEntity($projectId, $includeLinkedData);

        return self::getItemResponse(Project::class, $project, $projectId);
    }

    /**
     * Get the Images attached to a Project
     *
     * @param $projectId int|string The Id of the Project
     * @return Response
     */
    public function getProjectImages($projectId): Response {
        // Check the Project trying to get Images for exists
        $project = self::getProjectEntity($projectId, true);
        if ($project) {
            return $this->getItemsResponse(ProjectImage::class, $project->images);
        }

        return self::getItemNotFoundResponse(Project::class, $projectId);
    }

    /**
     * Try and upload the added image
     *
     * @param $project Project The Project trying to upload image for
     * @param $image array The uploaded image
     * @return Response
     * @throws Exception
     */
    private static function uploadProjectImage(Project $project, array $image): Response {
        $response = [];
        $statusCode = 500;

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
            // Try to upload file
            if (move_uploaded_file($image["tmp_name"], $newImageFullPath)) {
                // Add new image with location into the database
                $imageData = [
                    "file" => $newFileLocation,
                    "project_id" => $projectId,
                    "sort_order_number" => 999, // High enough number
                ];
                $projectImage = ProjectImage::insert($imageData);
                $projectImage->reload();
                return self::getInsertResponse(ProjectImage::class, $projectImage);
            }

            // Else there was a problem uploading file to server
            $response["error"] = "Sorry, there was an error uploading your image.";
        }
        else {
            // Else bad request as file uploaded is not a image
            $statusCode = 400;

            $response["error"] = "File is not an image.";
        }

        return new Response($statusCode, $response);
    }

    /**
     * Try to upload a Image user has tried to add as a Project Image
     *
     * @param $projectId int|string The Project Id to add this Image for
     * @return Response
     * @throws Exception
     */
    public function addProjectImage($projectId): Response {
        if (User::isLoggedIn()) {
            $files = $this->core->files;
            if (isset($files["image"])) {
                // Check the Project trying to add a Image for exists
                $project = self::getProjectEntity($projectId);
                if ($project) {
                    return self::uploadProjectImage($project, $files["image"]);
                }

                return self::getItemNotFoundResponse(Project::class, $projectId);
            }

            $requiredFields = ["image"];
            return $this->getInvalidFieldsResponse(ProjectImage::class, [], $requiredFields);
        }

        return self::getNotAuthorisedResponse();
    }

    /**
     * Get a Project Image for a Project by Id
     *
     * @param $projectId int|string The Id of the Project trying to get Image for
     * @param $imageId int|string The Id of the Project Image to get
     * @return Response
     */
    public static function getProjectImage($projectId, $imageId): Response {
        // Check the Project trying to get Images for exists
        $project = self::getProjectEntity($projectId);
        if ($project) {
            $projectImage = ProjectImage::getById($imageId);

            $response = self::getItemResponse(ProjectImage::class, $projectImage, $imageId);

            // Even though a Project Image may have been found with $imageId, this may not be for project $projectId
            $projectId = (int)$projectId;
            if ($projectImage && !empty($projectImage->project_id) && $projectImage->project_id !== $projectId) {
                $responseContent = $response->getContent();
                $responseContent["data"] = [];
                $responseContent["error"] = "No {$projectImage::$displayName} found identified by {$imageId} for Project: {$projectId}.";
                $response->setContent($responseContent);
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
     * @return Response
     */
    public static function deleteProjectImage($projectId, $imageId): Response {
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

                return self::getItemDeletedResponse(ProjectImage::class, $projectImage, $imageId, $isDeleted);
            }

            return self::getItemNotFoundResponse(Project::class, $projectId);
        }

        return self::getNotAuthorisedResponse();
    }

}
