<?php

/**
 * The Project Image Entity object class (extends the base Entity class, where most of the ORM functionality lies).
 * Within this holds and methods where it overwrites or add extra custom functionality from the base Entity class.
 * Also holds any method only custom to Project Image entities.
 */

namespace App\Projects\Entity;

use App\APIEntity;
use App\Entity\Timestamped;
use App\Core;
use App\Utils\Str;

class Image extends APIEntity {

    use Timestamped;

    public static $displayName = "Project Image";

    protected static $tableName = "portfolio_project_image";

    protected static $defaultColumns = [
        "project_id" => null,
        "position" => 0,
        "file" => "",
    ];

    protected static $intColumns = ["project_id", "position"];

    protected static $orderByColumn = "position";

    protected static $defaultLimit = null;

    /**
     * @inheritDoc
     *
     * Add extra functionality on top of default delete function
     * As these Entities are linked to a file on the server
     * Here actually delete the file from the server
     *
     * @return bool Whether or not deletion was successful
     */
    public function delete(): bool {
        $isDeleted = parent::delete();

        // Check if the deletion was ok
        if ($isDeleted && !empty($this->file)) {
            // Makes sure there is a leading slash
            $filePath = APP_ROOT . Str::addLeadingSlash($this->file);
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        return $isDeleted;
    }

    public function getAPIURL(): string {
        return Core::get()->getRouter()->makeUrl(
            "projectImage",
            [
                "id" => $this->getId(),
                "projectId" => $this->project_id,
            ]
        );
    }

    public function getAPIResponse(): array {
        $response = parent::getAPIResponse();

        $response["url"] = Core::get()->makeFullURL($response["file"]);
        unset($response["file"]);

        return $response;
    }
}
