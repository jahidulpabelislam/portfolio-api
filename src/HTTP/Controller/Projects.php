<?php

/**
 * All the custom functions for the Projects part of the API that allow to perform all user requests.
 */

namespace App\HTTP\Controller;

use App\Auth\Manager as AuthManager;
use App\Core;
use App\Entity\Collection as EntityCollection;
use App\Entity\Project;
use App\Entity\ProjectImage;
use App\HTTP\Controller;
use App\HTTP\Response;
use App\Utils\Collection;
use Exception;

class Projects extends Controller implements AuthGuarded {

    public $publicFunctions = [
        "getProjects",
        "getProject",
        "getProjectImages",
        "getProjectImage",
    ];

    /**
     * @param $projectId int|string
     * @param $includeLinkedData bool
     * @return Project|null
     */
    private function getProjectEntity($projectId, bool $includeLinkedData = false): ?Project {
        $where = ["id = :id"];
        $params = ["id" => $projectId];
        if (!AuthManager::isLoggedIn($this->request)) {
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
        $params = clone $this->request->params;

        if (!isset($params["filters"])) {
            $params["filters"] = new Collection();
        }

        // As the user isn't logged in, filter by status = public
        if (!AuthManager::isLoggedIn($this->request)) {
            $params["filters"]["status"] = Project::PUBLIC_STATUS;
        }

        $query = Project::buildQueryFromFilters($params["filters"]->toArray());

        $where = $query["where"];
        $queryParams = $query["params"];

        $search = $this->request->getParam("search");
        if ($search) {
            $searchQuery = Project::buildSearchQuery($search);

            $where = array_merge($where, $searchQuery["where"]);
            $queryParams = array_merge($queryParams, $searchQuery["params"]);
        }

        $limit = $this->request->getParam("limit");
        $page = $this->request->getParam("page");

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
     * Try and add a Project a user has attempted to add
     *
     * @return Response
     */
    public function addProject(): Response {
        $project = Project::insert($this->request->data->toArray());
        if ($project->hasErrors()) {
            return $this->getInvalidInputResponse($project->getErrors());
        }
        $project->reload();
        return self::getInsertResponse(Project::class, $project);
    }

    /**
     * Try to edit a Project a user has added before
     *
     * @param $projectId int|string The Id of the Project to update
     * @return Response
     */
    public function updateProject($projectId): Response {
        $project = $this->getProjectEntity($projectId, true);
        if ($project) {
            $data = $this->request->data;
            $project->setValues($data->toArray());
            $project->save();

            if ($project->hasErrors()) {
                return $this->getInvalidInputResponse($project->getErrors());
            }

            // If images were passed update the sort order
            if (!empty($data["images"])) {
                $orders = [];
                foreach ($data["images"] as $i => $image) {
                    $orders[$image["id"]] = $i + 1;
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

    /**
     * Try to delete a Project a user has added before
     *
     * @param $projectId int|string The Id of the Project to delete
     * @return Response
     */
    public function deleteProject($projectId): Response {
        $isDeleted = false;

        $project = $this->getProjectEntity($projectId);
        if ($project) {
            $isDeleted = $project->delete();
        }

        return self::getItemDeletedResponse(Project::class, $project, $projectId, $isDeleted);
    }

    /**
     * Get a particular Project defined by $projectId
     *
     * @param $projectId int|string The Id of the Project to get
     * @return Response
     */
    public function getProject($projectId): Response {
        $project = $this->getProjectEntity($projectId, true);

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
        $project = $this->getProjectEntity($projectId, true);
        if ($project) {
            return $this->getItemsResponse(ProjectImage::class, $project->images);
        }

        return self::getItemNotFoundResponse(Project::class, $projectId)
            ->withCacheHeaders(Core::getDefaultCacheHeaders())
        ;
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
        // Get the file ext
        $imageFileExt = pathinfo(basename($image["name"]), PATHINFO_EXTENSION);

        // The directory to upload file
        $directory = "/project-images/";

        // The full path for new file on the server
        $newFilename = preg_replace("/[^a-z0-9]+/", "-", strtolower($project->name));
        $newFilename .= "-" . date("Ymd-His");
        $newFilename .= "-" . random_int(0, 99);
        $newFilename .= ".$imageFileExt";

        $newFileLocation = $directory . $newFilename;

        $newImageFullPath = APP_ROOT . $newFileLocation;

        // Check if file is a actual image
        $fileType = mime_content_type($image["tmp_name"]);
        if (strpos($fileType, "image/") === 0) {
            // Try to upload file
            if (move_uploaded_file($image["tmp_name"], $newImageFullPath)) {
                // Add new image with location into the database
                $imageData = [
                    "file" => $newFileLocation,
                    "project_id" => $project->getId(),
                    "sort_order_number" => 999, // High enough number
                ];
                $projectImage = ProjectImage::insert($imageData);
                $projectImage->reload();
                return self::getInsertResponse(ProjectImage::class, $projectImage);
            }

            // Else there was a problem uploading file to server
            return new Response(500, [
                "error" => "Sorry, there was an error uploading your image.",
            ]);
        }

        // Else bad request as file uploaded is not a image
        return new Response(400, [
            "error" => "File is not an image.",
        ]);
    }

    /**
     * Try to upload a Image user has tried to add as a Project Image
     *
     * @param $projectId int|string The Project Id to add this Image for
     * @return Response
     * @throws Exception
     */
    public function addProjectImage($projectId): Response {
        $files = $this->request->files;
        if (isset($files["image"])) {
            // Check the Project trying to add a Image for exists
            $project = $this->getProjectEntity($projectId);
            if ($project) {
                return self::uploadProjectImage($project, $files["image"]);
            }

            return self::getItemNotFoundResponse(Project::class, $projectId);
        }

        $errors = [
            "image" => "Image is a required field."
        ];
        return $this->getInvalidInputResponse($errors);
    }

    /**
     * Get a Project Image for a Project by Id
     *
     * @param $projectId int|string The Id of the Project trying to get Image for
     * @param $imageId int|string The Id of the Project Image to get
     * @return Response
     */
    public function getProjectImage($projectId, $imageId): Response {
        // Check the Project trying to get Image for exists
        $project = $this->getProjectEntity($projectId);
        if ($project) {
            $projectImage = ProjectImage::getById($imageId);

            // Even though a Project Image may have been found with $imageId, this may not be for project $projectId
            $projectId = (int)$projectId;
            if ($projectImage && !empty($projectImage->project_id) && $projectImage->project_id !== $projectId) {
                $response = new Response(404, [
                    "error" =>  "No {$projectImage::getDisplayName()} found identified by '$imageId' for Project: '$projectId'.",
                ]);
            }
            else {
                return self::getItemResponse(ProjectImage::class, $projectImage, $imageId);
            }
        }
        else {
            $response = self::getItemNotFoundResponse(Project::class, $projectId);
        }

        return $response->withCacheHeaders(Core::getDefaultCacheHeaders());
    }

    /**
     * Try to delete a Image linked to a Project
     *
     * @param $projectId int|string The Id of the Project trying to delete Image for
     * @param $imageId int|string The Id of the Project Image to delete
     * @return Response
     */
    public function deleteProjectImage($projectId, $imageId): Response {
        // Check the Project of the Image trying to edit actually exists
        $project = $this->getProjectEntity($projectId);
        if ($project) {
            $isDeleted = false;

            $projectImage = ProjectImage::getById($imageId);
            if ($projectImage) {
                $isDeleted = $projectImage->delete();
            }

            return self::getItemDeletedResponse(ProjectImage::class, $projectImage, $imageId, $isDeleted);
        }

        return self::getItemNotFoundResponse(Project::class, $projectId);
    }

}
