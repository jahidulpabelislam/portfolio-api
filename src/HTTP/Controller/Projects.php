<?php

/**
 * All the custom functions for the Projects part of the API that allow to perform all user requests.
 */

namespace App\HTTP\Controller;

use App\Auth\Manager as AuthManager;
use App\Core;
use App\Entity\Project;
use App\Entity\ProjectImage;
use App\HTTP\Controller;
use App\HTTP\Response;
use App\Utils\Collection;
use Exception;
use JPI\ORM\Entity\Collection as EntityCollection;

class Projects extends Controller implements AuthGuarded {

    protected $publicFunctions = [
        "getProjects",
        "getProject",
        "getProjectImages",
        "getProjectImage",
    ];

    public function getPublicFunctions(): array {
        return $this->publicFunctions;
    }

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
            $ids = [];
            foreach ($projects as $project) {
                $ids[] = $project->getId();
            }

            $images = ProjectImage::getByColumn("project_id", $ids);

            $imagesGrouped = [];
            foreach ($images as $image) {
                $imagesGrouped[$image->project_id][] = $image;
            }

            foreach ($projects as $project) {
                $project->images = new EntityCollection($imagesGrouped[$project->getId()] ?? []);
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
                    $projectImage->position = $orders[$projectImage->getId()];
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
        $project = $this->getProjectEntity($projectId);
        if ($project) {
            $project->delete();
        }

        return self::getItemDeletedResponse(Project::class, $project, $projectId);
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
        if (strpos(mime_content_type($image["tmp_name"]), "image/") !== 0) {
            return new Response(400, [
                "error" => "File is not an image.",
            ]);
        }

        $fileExt = pathinfo(basename($image["name"]), PATHINFO_EXTENSION);

        $parts = [
            preg_replace("/[^a-z0-9]+/", "-", strtolower($project->name)),
            date("Ymd-His"),
            random_int(0, 99)
        ];
        $newFilename = implode("-", $parts) . ".$fileExt";

        $newPath = "/project-images/$newFilename";

        $newPathFull = APP_ROOT . $newPath;

        if (move_uploaded_file($image["tmp_name"], $newPathFull)) {
            $projectImage = ProjectImage::insert([
                "file" => $newPath,
                "project_id" => $project->getId(),
                "position" => 999, // High enough number
            ]);
            $projectImage->reload();
            return self::getInsertResponse(ProjectImage::class, $projectImage);
        }

        return new Response(500, [
            "message" => "Sorry, there was an error uploading your image.",
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

        return $this->getInvalidInputResponse([
            "image" => "Image is a required field."
        ]);
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
            if (!$projectImage || $projectImage->project_id === $projectId) {
                return self::getItemResponse(ProjectImage::class, $projectImage, $imageId);
            }

            $response = new Response(404, [
                "message" => "No {$projectImage::getDisplayName()} found identified by '$imageId' for Project: '$projectId'.",
            ]);
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
            $projectImage = ProjectImage::getById($imageId);
            if ($projectImage) {
                $projectImage->delete();
            }

            return self::getItemDeletedResponse(ProjectImage::class, $projectImage, $imageId);
        }

        return self::getItemNotFoundResponse(Project::class, $projectId);
    }
}
