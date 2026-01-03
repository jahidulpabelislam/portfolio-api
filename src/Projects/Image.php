<?php

declare(strict_types=1);

namespace App\Projects;

use App\AbstractEntity as AbstractAPIEntity;
use App\Core;
use App\Entity\Timestamped;
use JPI\CRUD\API\AbstractEntity;
use JPI\Utils\URL;

/**
 * The Project Image Entity object class.
 */
final class Image extends AbstractAPIEntity {

    use Timestamped;

    public static string $displayName = "Project Image";

    protected static string $table = "project_images";

    protected static array $dataMapping = [
        "project" => [
            "type" => "belongs_to",
            "entity" => Project::class,
        ],
        "position" => [
            "type" => "int",
        ],
        "file" => [
            "type" => "string",
        ],
    ];

    public static string $defaultOrderByColumn = "position";

    /**
     * Add extra functionality on top of default delete function
     * As these Entities are linked to a file on the server
     * Here actually delete the file from the server
     */
    public function delete(): bool {
        $isDeleted = parent::delete();

        // Check if the deletion was ok
        if ($isDeleted && !empty($this->file)) {
            // Makes sure there is a leading slash
            $filePath = PUBLIC_ROOT . URL::addLeadingSlash($this->file);
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        return $isDeleted;
    }

    public function getAPIURL(): URL {
        return Core::get()->getRouter()->getURLForRoute(
            "projectImage",
            [
                "id" => $this->getId(),
                "projectId" => $this->project->getId(),
            ]
        );
    }

    public function getAPIResponse(int $depth = 1, ?AbstractEntity $parentEntity = null): array {
        $response = parent::getAPIResponse($depth, $parentEntity);

        if ($depth === 1) {
            unset($response["project"]);
        }

        $response["url"] = (string)Core::get()->getRequest()->makeURL($response["file"]);
        unset($response["file"]);

        return $response;
    }
}
