<?php

declare(strict_types=1);

namespace App\Projects;

use App\Core;
use App\HTTP\AbstractCRUDController;
use JPI\HTTP\Response;
use JPI\HTTP\UploadedFile;

/**
 * All the custom functions for the Projects part of the API that allow to perform all user requests.
 */
final class Controller extends AbstractCRUDController {

    protected string $entityClass = Project::class;

    protected array $publicActions = [
        "index",
        "read",
        "getImages",
        "getImage",
    ];

    /**
     * Get the Images attached to a Project
     */
    public function getImages(string|int $projectId): Response {
        $request = $this->getRequest();
        // Check the Project trying to get Images for exists
        $project = $this->getEntityInstance()::getCrudService()->getEntityFromRequest($request);
        if ($project) {
            return $this->getEntitiesResponse($request, $project->images, new Image());
        }

        return $this->getEntityNotFoundResponse($request, $projectId)
            ->withCacheHeaders(Core::getDefaultCacheHeaders())
        ;
    }

    /**
     * Try and upload the added image
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
            random_int(0, 99),
        ];
        $newFilename = implode("-", $parts) . ".$fileExt";

        $newPath = "/project-images/$newFilename";

        $newPathFull = PUBLIC_ROOT . $newPath;

        if ($file->saveTo($newPathFull)) {
            $projectImage = Image::insert([
                "file" => $newPath,
                "project" => $project,
                "position" => 999, // High enough number
            ]);
            $projectImage->reload();
            return $this->getEntityCreateResponse($this->getRequest(), $projectImage, new Image());
        }

        return Response::json(500, [
            "message" => "Sorry, there was an error uploading your image.",
        ]);
    }

    /**
     * Try to upload a Image user has tried to add as a Project Image
     */
    public function addImage(string|int $projectId): Response {
        $request = $this->getRequest();

        if (!$request->getAttribute("is_authenticated")) {
            return static::getNotAuthorisedResponse();
        }

        $files = $request->getFiles();
        if (isset($files["image"])) {
            // Check the Project trying to add a Image for exists
            $project = $this->getEntityInstance()::getCrudService()->getEntityFromRequest($request);
            if ($project) {
                return $this->uploadImage($project, $files["image"]);
            }

            return $this->getEntityNotFoundResponse($request, $projectId);
        }

        return $this->getInvalidInputResponse([
            "image" => "Image is a required field.",
        ]);
    }

    /**
     * Get a Project Image for a Project by Id
     */
    public function getImage(string|int $projectId, string|int $imageId): Response {
        $request = $this->getRequest();

        // Check the Project trying to get Image for exists
        $project = $this->getEntityInstance()::getCrudService()->getEntityFromRequest($request);
        if ($project) {
            $image = Image::getCrudService()->read($request);

            // Even though a Project Image may have been found with $imageId, this may not be for project $projectId
            $projectId = (int)$projectId;
            if (!$image || $image->project_id === $projectId) {
                return $this->getEntityResponse($request, $image, $imageId, new Image());
            }

            $response = Response::json(404, [
                "message" => "No {$image::getDisplayName()} found identified by '$imageId' for Project: '$projectId'.",
            ]);
        }
        else {
            $response = $this->getEntityNotFoundResponse($request, $projectId);
        }

        return $response->withCacheHeaders(Core::getDefaultCacheHeaders());
    }

    /**
     * Try to delete a Image linked to a Project
     */
    public function deleteImage(string|int $projectId, string|int $imageId): Response {
        $request = $this->getRequest();

        if (!$request->getAttribute("is_authenticated")) {
            return static::getNotAuthorisedResponse();
        }

        // Check the Project of the Image trying to edit actually exists
        $project = $this->getEntityInstance()::getCrudService()->getEntityFromRequest($request);
        if (!$project) {
            return $this->getItemNotFoundResponse($request, $projectId);
        }

        $image = Image::getCrudService()->delete($request);
        return $this->getItemDeletedResponse($request, $image, $imageId, new Image());
    }
}
