<?php

/**
 * All the custom functions for the Projects part of the API that allow to perform all user requests.
 */

namespace App\Projects;

use App\Core;
use App\Projects\Entity\Project;
use App\Projects\Entity\Image;
use App\HTTP\Response;
use App\HTTP\Controller\Crud;
use Exception;

class Controller extends Crud {

    protected $entityClass = Project::class;

    protected $publicFunctions = [
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
        $project = $this->getEntityInstance()::getCrudService()->read($this->request);
        if ($project) {
            $project->loadImages();
            return $this->getItemsResponse(Image::class, $project->images);
        }

        return self::getItemNotFoundResponse($this->entityClass, $projectId)
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
    private static function uploadImage(Project $project, array $image): Response {
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
            $projectImage = Image::insert([
                "file" => $newPath,
                "project_id" => $project->getId(),
                "position" => 999, // High enough number
            ]);
            $projectImage->reload();
            return self::getInsertResponse(Image::class, $projectImage);
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
    public function addImage($projectId): Response {
        $files = $this->request->files;
        if (isset($files["image"])) {
            // Check the Project trying to add a Image for exists
            $project = $this->getEntityInstance()::getCrudService()->read($this->request);
            if ($project) {
                return self::uploadImage($project, $files["image"]);
            }

            return self::getItemNotFoundResponse($this->entityClass, $projectId);
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
        // Check the Project trying to get Image for exists
        $project = $this->getEntityInstance()::getCrudService()->read($this->request);
        if ($project) {
            $image = Image::getCrudService()->read($this->request);

            // Even though a Project Image may have been found with $imageId, this may not be for project $projectId
            $projectId = (int)$projectId;
            if (!$image || $image->project_id === $projectId) {
                return self::getItemResponse(Image::class, $image, $imageId);
            }

            $response = new Response(404, [
                "message" => "No {$image::getDisplayName()} found identified by '$imageId' for Project: '$projectId'.",
            ]);
        }
        else {
            $response = self::getItemNotFoundResponse($this->entityClass, $projectId);
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
        // Check the Project of the Image trying to edit actually exists
        $project = $this->getEntityInstance()::getCrudService()->read($this->request);
        if (!$project) {
            return self::getItemNotFoundResponse($this->entityClass, $projectId);
        }

        $image = Image::getCrudService()->delete($this->request);
        return self::getItemDeletedResponse(Image::class, $image, $imageId);
    }
}
