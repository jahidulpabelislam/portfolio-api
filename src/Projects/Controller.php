<?php

/**
 * All the custom functions for the Projects part of the API that allow to perform all user requests.
 */

namespace App\Projects;

use App\Core;
use App\HTTP\AbstractCrudController;
use App\Projects\Entity\Image;
use App\Projects\Entity\Project;
use Exception;
use JPI\HTTP\Response;
use JPI\HTTP\UploadedFile;

class Controller extends AbstractCrudController {

    protected $entityClass = Project::class;

    protected $publicActions = [
        "index",
        "read",
        "getImages",
        "getImage",
    ];

    /**
     * Get the Images attached to a Project
     *
     * @param $projectId int|string The Id of the Project
     * @return Response
     */
    public function getImages($projectId): Response {
        // Check the Project trying to get Images for exists
        $project = $this->getEntityInstance()::getCrudService()->read($this->getRequest());
        if ($project) {
            $project->loadImages();
            return $this->getItemsResponse($project->images, new Image());
        }

        return $this->getItemNotFoundResponse($projectId)
            ->withCacheHeaders(Core::getDefaultCacheHeaders())
        ;
    }

    /**
     * Try and upload the added image
     *
     * @param $project Project The Project trying to upload image for
     * @param $file UploadedFile The uploaded file
     * @return Response
     * @throws Exception
     */
    private function uploadImage(Project $project, UploadedFile $file): Response {
        if (strpos(mime_content_type($file->getTempName()), "image/") !== 0) {
            return Response::json(400, [
                "error" => "File is not an image.",
            ]);
        }

        $fileExt = pathinfo(basename($file->getFilename()), PATHINFO_EXTENSION);

        $parts = [
            preg_replace("/[^a-z0-9]+/", "-", strtolower($project->name)),
            date("Ymd-His"),
            random_int(0, 99)
        ];
        $newFilename = implode("-", $parts) . ".$fileExt";

        $newPath = "/project-images/$newFilename";

        $newPathFull = APP_ROOT . $newPath;

        if ($file->saveTo($newPathFull)) {
            $projectImage = Image::insert([
                "file" => $newPath,
                "project_id" => $project->getId(),
                "position" => 999, // High enough number
            ]);
            $projectImage->reload();
            return $this->getInsertResponse($projectImage, new Image());
        }

        return Response::json(500, [
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
    public function addImage($projectId): Response {
        $request = $this->getRequest();

        if (!$request->getAttribute("is_authenticated")) {
            return static::getNotAuthorisedResponse();
        }

        $files = $request->getFiles();
        if (isset($files["image"])) {
            // Check the Project trying to add a Image for exists
            $project = $this->getEntityInstance()::getCrudService()->read($request);
            if ($project) {
                return $this->uploadImage($project, $files["image"]);
            }

            return $this->getItemNotFoundResponse($projectId);
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
    public function getImage($projectId, $imageId): Response {
        $request = $this->getRequest();

        // Check the Project trying to get Image for exists
        $project = $this->getEntityInstance()::getCrudService()->read($request);
        if ($project) {
            $image = Image::getCrudService()->read($request);

            // Even though a Project Image may have been found with $imageId, this may not be for project $projectId
            $projectId = (int)$projectId;
            if (!$image || $image->project_id === $projectId) {
                return $this->getItemResponse($image, $imageId, new Image());
            }

            $response = Response::json(404, [
                "message" => "No {$image::getDisplayName()} found identified by '$imageId' for Project: '$projectId'.",
            ]);
        }
        else {
            $response = $this->getItemNotFoundResponse($projectId);
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
    public function deleteImage($projectId, $imageId): Response {
        $request = $this->getRequest();

        if (!$request->getAttribute("is_authenticated")) {
            return static::getNotAuthorisedResponse();
        }

        // Check the Project of the Image trying to edit actually exists
        $project = $this->getEntityInstance()::getCrudService()->read($request);
        if (!$project) {
            return $this->getItemNotFoundResponse($projectId);
        }

        $image = Image::getCrudService()->delete($request);
        return $this->getItemDeletedResponse($image, $imageId, new Image());
    }
}
